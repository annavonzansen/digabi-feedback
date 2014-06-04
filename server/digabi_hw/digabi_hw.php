<?php
/*
Plugin Name: Digabi HW
Plugin URI: https://github.com/digabi/digabi-feedback
Description: Adds support for Digabi Hardware post type and metadata fields
Version: 2014-06-02
Author: Matti Lattu
License: GPL3
Text Domain: digabi_hw
*/

/*  Copyright 2014 Matti Lattu and Ville Korhonen
 
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

// Add hook for internationalisation, also loads global variables
// from settings.php
add_action('init', 'digabihw_activate_i18n');

include_once(ABSPATH.'wp-admin/includes/plugin.php' );
include_once(ABSPATH.'wp-admin/includes/taxonomy.php');
include_once(ABSPATH.'wp-content/plugins/digabi_hw/settings.php');
include_once(ABSPATH.'wp-content/plugins/digabi_hw/digabi_hw_functions.php');
include_once(ABSPATH.'wp-content/plugins/digabi_hw/digabi_hw_feedback.php');

register_activation_hook(__FILE__, "digabihw_activate");
register_deactivation_hook(__FILE__, "digabihw_deactivate");

if (is_plugin_active('digabi_hw/digabi_hw.php')) {
	// This plugin is active, add custom post type
	add_action('init', 'digabihw_register_post_type');
   
   // This plugin is active, add custom taxonomy
   add_action('init', 'digabihw_register_taxonomy');
}

// Add hook to show the custom fields in the digabihw post types
add_filter('the_content', 'digabihw_show_custom_fields');

/**
 * This function should be called whenever admin activates digabi_hw plugin
 * from administrator panel. It calls all necessary routines to start the operation.
 */
function digabihw_activate () {
	// Add custom post type
	digabihw_register_post_type();
   
   // Add custom taxonomy
   digabihw_register_taxonomy();
}

/**
 * This function should be called whenever admin activates digabi_hw plugin
 * from administrator panel. It calls all necessary routines to start the operation.
 */

function digabihw_deactivate () {
	// Remove data from custom metadata fields (FIXME?)
	
	// Remove custom metadata (FIXME?)
	
	// Remove custom post type (FIXME?)
}

/**
 * Initialises Wordpress I18N support as explained at
 * http://codex.wordpress.org/I18n_for_WordPress_Developers
 */
function digabihw_activate_i18n () {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain('digabi_hw', FALSE, $plugin_dir.'/lang/');
    digabihw_set_global_settings();
}

/**
 * Replace the post content by hardware data retrieved from custom fields.
 * This function is called by filter hook 'the_content'.
 * @global WP_Query $wp_query
 * @global WP_Post $post
 * @global array $DIGABIHW_SHOW_FIELDS_BASIC
 * @global array $DIGABIHW_SHOW_FIELDS_DETAILED
 * @global string $DIGABIHW_POST_STATUS
 * @global mixed $DIGABIHW_MINIMUM_VALUES
 * @param type $post_content Initial post content.
 * @return string Changed post content
 */
function digabihw_show_custom_fields ($post_content) {
	global $wp_query;
	global $post;
	global $DIGABIHW_SHOW_FIELDS_BASIC;
   global $DIGABIHW_SHOW_FIELDS_DETAILED;
   global $DIGABIHW_POST_STATUS;
   global $DIGABIHW_MINIMUM_VALUES;
   
	if ($post->post_type == 'digabihw_report') {
		// This post type is DigabiHW Report
		
      // Store new post content to this
      $new_post_content = '';
      
      // Get status
      $this_status_all = get_post_meta($post->ID, 'digabihw_status', TRUE);
      
      foreach (explode(':', $this_status_all) as $this_status) {
        if (@$DIGABIHW_POST_STATUS[$this_status] != '') {
            $new_post_content .= '<p>'.$DIGABIHW_POST_STATUS[$this_status].'</p>';
        }
        elseif ($this_status != '') {
            $new_post_content .= '<p>'.__("Unknown status code", 'digabi_hw').": ".$this_status.'</p>';
        }
      }

      // Get hit counter
      $hit_counter = get_post_meta($post->ID, 'digabihw_counter', TRUE);
      
      if ($hit_counter > 1) {
          $new_post_content .= '<p>'.__("Reports of this machine", 'digabi_hw').': '.$hit_counter.'</p>';
      }
      
		// Get stored fields
		$data_fields = explode(':', get_post_meta($post->ID, 'digabihw_fields', TRUE));
		
      // Array for passed and failed requirements
      $requirements_failed = Array();
      $requirements_passed = Array();
      
		$new_post_content .= "<table>\n";
		
      // Print header for basic data
      if (count($DIGABIHW_SHOW_FIELDS_BASIC) > 0) {
          $new_post_content .= "<tr><td colspan='2'><h2 id='digabihw_subject_basic_".$post->ID."'>".__("Basic Info", 'digabi_hw')."</h2></td></tr>\n";

          // Loop through all data basic fields 
        foreach ($DIGABIHW_SHOW_FIELDS_BASIC as $this_data_field => $this_data_legend) {
            if (in_array($this_data_field, $data_fields)) {
                // The data fields exists in the data
                
                // Read the value
                $this_value = get_post_meta($post->ID, 'digabihw_'.$this_data_field, TRUE);
                
                // Check the mimimum requirements
                $minimum_comment = '';
                
                if (!is_null(@$DIGABIHW_MINIMUM_VALUES[$this_data_field])) {
                    // This data even has a minimun value function
                    if (call_user_func($DIGABIHW_MINIMUM_VALUES[$this_data_field], $this_value)) {
                        $minimum_comment = ' ['.__("Meets the requirements", 'digabi_hw').']';
                        array_push($requirements_passed, $this_data_legend);
                    }
                    else {
                        $minimum_comment = ' ['.__("Does not meet the requirements", 'digabi_hw').']';
                        array_push($requirements_failed, $this_data_legend);
                    }
                }
                
                // For manufacturer field, add a search link to the value
                if ($this_data_field == 'manufacturer') {
                    $this_url = digabihw_get_category_url($this_value);
                    if ($this_url != '') {
                        // This manufacturer has a category with an URL
                        $this_value = '<a href="'.$this_url.'" title="'.__("Search", 'digabi_hw').' '.$this_value.'">'.$this_value.'</a>';
                    }
                }
                
                $new_post_content .= "<tr class='digabihw_fields_basic_".$post->ID."'>";
                $new_post_content .= "<td>".$this_data_legend."</td>";
                $new_post_content .= "<td>".$this_value.$minimum_comment."</td>";
                $new_post_content .= "</tr>\n";
            }
        }
      }
      
		
      // Print header for detailed data
      if (count($DIGABIHW_SHOW_FIELDS_DETAILED) > 0) {
          $new_post_content .= "<tr><td colspan='2'><h2 id='digabihw_subject_detailed_".$post->ID."'>".__("Detailed Info", 'digabi_hw')."</h2></td></tr>\n";
      
        // Loop through all data detailed fields 
        foreach ($DIGABIHW_SHOW_FIELDS_DETAILED as $this_data_field => $this_data_legend) {
            if (in_array($this_data_field, $data_fields)) {
                // The data fields exists in the data

                // Read the value
                $this_value = get_post_meta($post->ID, 'digabihw_'.$this_data_field, TRUE);
                
                // Check the mimimum requirements
                $minimum_comment = '';
                
                if (!is_null(@$DIGABIHW_MINIMUM_VALUES[$this_data_field])) {
                    // This data even has a minimun value function
                    if (call_user_func($DIGABIHW_MINIMUM_VALUES[$this_data_field], $this_value)) {
                        $minimum_comment = ' ['.__("Meets the requirements", 'digabi_hw').']';
                        array_push($requirements_passed, $this_data_legend);
                    }
                    else {
                        $minimum_comment = ' ['.__("Does not meet the requirements", 'digabi_hw').']';
                        array_push($requirements_failed, $this_data_legend);
                    }
                }
                
                $new_post_content .= "<tr class='digabihw_fields_detailed_".$post->ID."'>";
                $new_post_content .= "<td>".$this_data_legend."</td>";
                $new_post_content .= "<td>".$this_value.$minimum_comment."</td>";
                $new_post_content .= "</tr>\n";
            }
        }
      }
      
      // Show passed/failed requirements in the table
      if (count($requirements_failed) > 0 or count($requirements_passed) > 0) {
          $new_post_content .= "<tr><td colspan='2'><h2 id='digabihw_subject_requirements'>".__("Requirements", 'digabi_hw')."</h2></td></tr>\n";
          
          if (count($requirements_passed) > 0) {
              // We have passed requirements
              $new_post_content .= "<tr><td>".__("Passed requirements",'digabi_hw')."</td><td>";
              
              $new_post_content .= join(', ', $requirements_passed);
              
              $new_post_content .= "</td></tr>";
          }
          
          if (count($requirements_failed) > 0) {
              // We have failed requirements
              $new_post_content .= "<tr><td>".__("Failed requirements",'digabi_hw')."</td><td>";
              
              $new_post_content .= join(', ', $requirements_failed);
              
              $new_post_content .= "</td></tr>";
          }
      }

      $new_post_content .= "</table>\n";
      
      $all_categories = wp_list_categories('echo=0&orderby=name&taxonomy=digabihw_dev&title_li=&style=none');
      $all_categories = rtrim( trim( str_replace( '<br />',  " ", $all_categories ) ), " ");
      $new_post_content .= "<p>".__("Search devices",'digabi_hw').': '.$all_categories."<p>";
		
      // Print needed JavaScript
      $new_post_content .= "<script language='JavaScript'>\n";
      $new_post_content .= 'jQuery("#digabihw_subject_basic_'.$post->ID.'").click(function() { jQuery(".digabihw_fields_basic_'.$post->ID.'").toggle("slow"); } );'."\n";
      $new_post_content .= 'jQuery("#digabihw_subject_detailed_'.$post->ID.'").click(function() { jQuery(".digabihw_fields_detailed_'.$post->ID.'").toggle("slow"); } );'."\n";
      $new_post_content .= 'jQuery(".digabihw_fields_detailed_'.$post->ID.'").hide();'."\n";
      $new_post_content .= "</script>\n";
      
		return $new_post_content;
	}
   elseif ($post->post_type == 'page' or $post->post_type == 'post') {
       // Define HW data array here so you don't have to re-read it if
       // you have more than one menu in a single page
       $hw_post_data = NULL;
       
       if (preg_match('/\[digabihw_menu_full\]/i', $post_content)) {
           // The post/page contains our magic shortcode
           if (is_null($hw_post_data)) {
               // The $hw_post_data is uninitialised so let's fill it
               $hw_post_data = digabihw_enumerate_posts();
           }
           
           $shortcode_replacement = "<ul>";
           
           foreach ($hw_post_data as $this_manufacturer => $this_data) {
               $shortcode_replacement .= "<li>".$this_manufacturer;
               foreach ($this_data as $this_product => $this_url_array) {
                   foreach ($this_url_array as $this_url) {
                       $shortcode_replacement .= "<br/><a href='$this_url'>$this_product</a>";
                   }
               }
               $shortcode_replacement .= "</li>";
           }
           
           $shortcode_replacement .= "</ul>";
           
           $new_post_content = preg_replace('/\[digabihw_menu_full\]/i', $shortcode_replacement, $post_content);
           
           return $new_post_content;
       }
   }

	// This was not our post type, return unchanged content
	return $post_content;
}


// Options menu

add_action( 'admin_menu', 'digabihw_plugin_menu' );

// Add a new submenu under Settings:
function digabihw_plugin_menu () {
	$page = add_options_page(__('Digabi HW','digabi_hw'), __('Digabi HW','digabi_hw'), 'manage_options', 'digabihw-settings', 'digabihw_settings_page');
   add_action("load-$page", "digabihw_settings_export_csv");
}

// digabihw_settings_page() displays the page content for the Test settings submenu
function digabihw_settings_page() {
	global $DIGABIHW_SAVEPATH;

	echo("<h2>" . __( 'Digabi HW Settings menu', 'menu-test' ) . "</h2>");

   echo("<form method='post'><p class='submit'>");
   wp_nonce_field('digabi-hw');
   echo("<input type='hidden' name='noheader' value='true'><input type='submit' name='submit' value='Export CSV'></p></form>");
   
	$post_type_activated = 'No';
	
	foreach (get_post_types(Array(), 'names') as $this_post_type) {
		if ($this_post_type == 'digabihw_report') {
			$post_type_activated = 'Yes';
		}
	}
	
	echo('<p>DigabuHW Custom Post type is active: <b>'.$post_type_activated.'</b></p>');
	echo('<p>File prefix for submission dumps: <b>'.$DIGABIHW_SAVEPATH.'</b></p>');

}

/**
 * Creates CSV dump of custom posts. This is called by WP action hook set in
 * digabihw_plugin_menu(). The columns are read from
 * $DIGABIHW_SHOW_FIELDS_CSV, $DIGABIHW_SHOW_FIELDS_BASIC and
 * $DIGABIHW_SHOW_FIELDS_DETAILED (see settings.php). The output is sent
 * directly to stdout. After printing output the function calls exit(0).
 * 
 * @global array $DIGABIHW_SHOW_FIELDS_CSV
 * @global array $DIGABIHW_SHOW_FIELDS_BASIC
 * @global array $DIGABIHW_SHOW_FIELDS_DETAILED
 */
function digabihw_settings_export_csv () {
    global $DIGABIHW_SHOW_FIELDS_CSV;
    global $DIGABIHW_SHOW_FIELDS_BASIC;
    global $DIGABIHW_SHOW_FIELDS_DETAILED;
    
    if( isset( $_POST['submit'] ) && 'Export CSV' == $_POST['submit'] ) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=digabihw_export_'.date('Y-m-d').".csv");
        
        // Separator
        $sep = ',';
        
        $all_fields = array_merge(
                $DIGABIHW_SHOW_FIELDS_CSV,
                $DIGABIHW_SHOW_FIELDS_BASIC,
                $DIGABIHW_SHOW_FIELDS_DETAILED
        );
        
        $all_data = digabihw_enumerate_post_data();

        $op = fopen('php://output','w');
        
        // Print header data
        fputcsv($op, array_values($all_fields), $sep);
        
        // Print post data
        foreach ($all_data as $this_post) {
            $row_data = Array();
            foreach ($all_fields as $this_key => $this_legend) {
                if (@$this_post[$this_key] != '') {
                    array_push($row_data, $this_post[$this_key]);
                }
                else {
                    array_push($row_data, NULL);
                }
            }
            fputcsv($op, $row_data, $sep);
        }
        
        fclose($op);
        
        // Exit here
        exit(0);
    }
}


?>
