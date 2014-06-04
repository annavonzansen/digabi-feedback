<?php

/*  Copyright 2014 Matti Lattu and Ville Korhonen

    This file is part of Digabi HW.
 
    Digabi HW is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (defined("ABSPATH")) {
	// This is executed through WordPress
	include_once(ABSPATH.'wp-admin/includes/plugin.php' );
   include_once(ABSPATH.'wp-admin/includes/taxonomy.php');
	include_once(ABSPATH.'wp-content/plugins/digabi_hw/settings.php');
}
else {
	// This is executed directly through feedback.php
	include_once("../../../wp-load.php");
	include_once("../../../wp-admin/includes/plugin.php");
   include_once("../../../wp-admin/includes/taxonomy.php");
	include_once("settings.php");
}

// log function - from http://fuelyourcoding.com/simple-debugging-with-wordpress/

if(!function_exists('_log')){
  function _log( $message ) {
   if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}

/**
 * FIXME: Documentation
 * @param int $exit_code
 * @param string $log_message
 */
function digabihw_error_exit ($exit_code, $log_message) {
	error_log("Error #".$exit_code." - ".$log_message);
	echo($exit_code.":error\n");
	exit($exit_code);
}

/**
 * Fixme: Documentation
 * @param string $log_message
 */
function digabihw_warning ($log_message) {
	error_log("Warning: ".$log_message);
}


/**
 * This function registers WP Custom Post type 'digabihw_report' with Custom Fields.
 * @return boolean TRUE on success, FALSE on failure (see WP log).
 */
function digabihw_register_post_type () {
	// Add custom post type
	
	$retobj = register_post_type('digabihw_report',
		Array(
			'labels' => Array('name' => __("HW Reports",'digabi_hw'),'singular' => __("HW Report",'digabi_hw')	),
			'public' => TRUE,
			'has_archive' => TRUE,
			'rewrite' => Array('slug' => 'hwreports'),
			'description' => "Digabi Hardware report",
         'supports' => Array('title', 'custom-fields', 'comments'),
         'taxonomies' => Array(),
		)
	);
	
	if (is_wp_error($retobj)) {
		digabihw_warning("Failed to register custom post type, errors: ".join('; ',$retobj->errors));
      return FALSE;
	}
   
   return TRUE;
}

/**
 * Registers Digabi HW custom taxonomy 'digabihw_dev' used for device manufacturers.
 * @return boolean Always TRUE.
 */

function digabihw_register_taxonomy () {
    register_taxonomy(
            'digabihw_dev',
            'digabihw_report',
            Array(
               'label' => __('Device Category','digabi_hw'),
                'rewrite' => Array('slug' => 'dev'),
            ));
    return TRUE;
}


/**
 * Builds an tree model (array of arrays) if hardware report posts.
 * The first level of arrays are manufacturers (custom field digabihw_manufacturer),
 * second are product names (custom field digabihw_product_name). The values are shortlinks
 * to the hardware report custom post.
 * Array(
 *  [Acer] => Array (
 *          [Aspire E1-470] => Array(
 *              'https://harkko.lattu.biz/wp/?p=23',
 *              'https://harkko.lattu.biz/wp/?p=13'
 *              ),
 *          [One] => Array(
 *              'https://harkko.lattu.biz/wp/?p=69'
 *              )
 *      )
 *  [Intel(R) Corporation] => Array (
 *          [Aspire E1-470] => Array(
 *              'https://harkko.lattu.biz/wp/?p=14'
 *              )
 *      )
 *  [innotek GmbH] => Array (
 *          [VirtualBox] => Array(
 *              'https://harkko.lattu.biz/wp/?p=12'
 *              )
 *      )
 * )
 * @return array Array of all hardware reports.
 */
function digabihw_enumerate_posts () {
    // Collect data to this array
    $data = Array();
    
    $search_array = Array(
        'post_type' => 'digabihw_report',
    );
    
    $this_query = new WP_Query($search_array);

    if ($this_query->have_posts()) {
        // We have matching posts
        
        foreach ($this_query->posts as $this_post) {
            // Get necessary fields
            $this_manufacturer = get_post_meta($this_post->ID, 'digabihw_manufacturer', TRUE);
            $this_product_name = get_post_meta($this_post->ID, 'digabihw_product_name', TRUE);
            
            if ($this_manufacturer != '' and $this_product_name != '') {
                // Store data to the array
                if (is_array($data[$this_manufacturer][$this_product_name])) {
                    array_push($data[$this_manufacturer][$this_product_name], wp_get_shortlink($this_post->ID));
                }
                else {
                    $data[$this_manufacturer][$this_product_name] = Array(wp_get_shortlink($this_post->ID));
                }
            }
        }
    }
    
    return $data;
}

/**
 * Builds an array of hash arrays containing all custom post data. Each object
 * contains a hash array. The keys are custom key names. If the name begins
 * with "digabihw_" it is removed.
 * @return array Array of hash arrays.
 */
function digabihw_enumerate_post_data () {
    // Collect data to this array
    $data = Array();
    
    $search_array = Array(
        'post_type' => 'digabihw_report',
    );
    
    $this_query = new WP_Query($search_array);

    if ($this_query->have_posts()) {
        // We have matching posts
        foreach ($this_query->posts as $this_post) {
            $custom_fields = get_post_custom_keys($this_post->ID);
            
            $this_data = Array();
            
            foreach ($custom_fields as $this_custom_field_raw) {
                $this_value = get_post_meta($this_post->ID, $this_custom_field_raw, TRUE);
                $this_custom_field = preg_replace('/^digabihw_/', '', $this_custom_field_raw);
                
                $this_data[$this_custom_field] = $this_value;
            }
            
            array_push($data, $this_data);
        }
    }
    
    return $data;
}

/**
 * Calculates hash to the values of a given data array. This function can be
 * used to calculate a hash value to be used as "digabihw_hash".
 * @param type $data
 * @return string Hash value, NULL on failure
 */
function digabihw_get_hash ($data) {
    $data_string = '';
    
    if (!ksort($data)) {
        // Failed to sort data
        return NULL;
    }
    
    // Add all values to data string
    foreach ($data as $this_key => $this_value) {
        $data_string .= $this_value;
    }
    
    if ($data_string == '') {
        // data string is empty -> fail
        return NULL;
    }
    
    // Return whatever sha1() returns
    return sha1($data_string);
}

/**
 * Checks whether HW report already exists for a manufacturer/product name combination.
 * @param string $hash Hash value to check
 * @return array If HW entries already exists returns array of permalinks, otherwise NULL..
 */
function digabihw_machine_already_exists ($hash) {
    $hash = strtolower($hash);
    
    $search_array = Array(
        'post_type' => 'digabihw_report',
        'meta_query' => Array(
            Array(
                'key' => 'digabihw_hash',
                'value' => $hash,
                'compare' => '='
            )
        )
    );
    
    $this_query = new WP_Query($search_array);
    
    if ($this_query->have_posts()) {
        // We have matching posts - machine already exists

        $permalinks = Array();
        
        foreach ($this_query->posts as $this_post) {
           $this_permalink = get_permalink($this_post->ID);
           if ($this_permalink) {
              array_push($permalinks, $this_permalink);
           }
        }
        
        // Return all found permalinks
        return $permalinks;
    }
    
    // No matching posts - machine does not exist
    return NULL;
}

/**
 * Add counter to a manufacturer/product name combination. Calling this
 * function adds hardware report counter by one.
 * @param type $manufacturer
 * @param type $product
 * @return int How many posts were updated.
 */
function digabihw_machine_add_counter($manufacturer, $product) {
    $search_array = Array(
        'post_type' => 'digabihw_report',
        'meta_query' => Array(
            Array(
                'key' => 'digabihw_manufacturer',
                'value' => $manufacturer,
                'compare' => '='
            ),
            Array(
                'key' => 'digabihw_product_name',
                'value' => $product,
                'compare' => '='
            )
        )
    );
    
    $this_query = new WP_Query($search_array);
    
    $update_counter = 0;
    
    if ($this_query->have_posts()) {
        // We have matching posts
        
        foreach ($this_query->posts as $this_post) {
            // Update counter for this post
            $this_counter = get_post_meta($this_post->ID, 'digabihw_counter', TRUE);
            $this_counter++;
            update_post_meta($this_post->ID, 'digabihw_counter', $this_counter);
            
            $update_counter++;
        }
    }
    
    return $update_counter;
}

/**
 * Finds the category ID for a given $name from custom category 'digabihw_dev'.
 * If the category does not exists, creates a new one.
 * @param string $name Category name
 * @param boolean $create_if_not_exist (optional) Setting this to TRUE creates
 * the given category if it already does not exist. Defaults to TRUE.
 * @return int Category ID, NULL in case no category was found/created.
 */
function digabihw_get_category_id ($name, $create_if_not_exist = TRUE) {
    // Try to find an existing category
    $result = get_term_by('name', $name, 'digabihw_dev', ARRAY_A);
    
    if ($result['term_id']) {
        // There is an existing category
        // We must cast the ID as explained in the get_term_by() documentation
        return (int) $result['term_id'];
    }
    
    if (!$create_if_not_exist) {
        // There was no existing category and the caller did not want
        // us to create one.
        return NULL;
    }
    
    // Let's create a new category
    $cat_id = wp_insert_category(
            Array(
                'cat_name'=>$extracted_fields['manufacturer'],
                'taxonomy'=>'digabihw_dev')
    );
    
    return $cat_id;
}

/**
 * Finds the category URL for given $name from custom category 'digabihw_dev'.
 * @param string $name Category name
 * @return string Category URL or empty string if no category was found.
 */
function digabihw_get_category_url ($name) {
    $category_id = digabihw_get_category_id($name, FALSE);
    
    if (is_null($category_id)) {
        // No category ID was found
        return '';
    }
    
    return get_term_link($category_id, 'digabihw_dev');
}

/**
 * Check whether parameter has a syntax of an email.
 * Note: The code was taken from http://www.linuxjournal.com/article/9585?page=0,0
 * @param string $email Email address to check
 * @return boolean Returns TRUE if given email has a valid format, otherwise FALSE.
 */
function digabihw_is_valid_email ($email) {
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

?>