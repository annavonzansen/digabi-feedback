<?php

/*
    Digabi laitteistotietojen tallennus (v1, flat-file)

    Copyright 2014 Matti Lattu and Ville Korhonen

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


include_once("Digabi_Feedback.php");

/**
 * Process an incoming feedback file with Digabi_Feedback class.
 * @global type $DIGABIHW_POST_AUTHOR_ID
 * @global type $DIGABIHW_POST_CATEGORIES
 * @global type $DIGABIHW_POST_DEFAULT_TITLE
 * @global string $DIGABIHW_POST_STATUS_USER
 * @global string $DIGABIHW_POST_PARENT_CATEGORY_ID
 * @param string $filename File to process.
 * @return string Returns post URL on success, NULL on failure. Reports human-readable messages
 * directly to log with digabihw_warning().
 */
function digabihw_process_data_file ($filename) {
    global $DIGABIHW_POST_AUTHOR_ID;
    global $DIGABIHW_POST_CATEGORIES;
    global $DIGABIHW_POST_DEFAULT_TITLE;
    global $DIGABIHW_POST_STATUS_USER;
    global $DIGABIHW_POST_PARENT_CATEGORY_ID;

    // Store WP post URL here after successfull operation to be later
    // returned upstream.
    $wp_post_url = NULL;
    
	if (is_file($filename)) {
		// Yes, we got feedback which this script can process
		
		// Read and process feedback file to object $d 
		$d = new Digabi_Feedback($filename);
		
		if (!is_null($d->error)) {
			digabihw_warning("Incoming file $filename is not parseable");
         return NULL;
		}
		
		$extracted_fields = Array();
		
		foreach ($d->enum_fields() as $this_field) {
			if (!is_null($d->get_field_str($this_field))) {
				$extracted_fields[$this_field] = $d->get_field_str($this_field);
			}
		}
		
		if (count($extracted_fields) > 0) {
			// We have data to post
			
			// Gather post data (name, slug, date etc)
			$post_title = $DIGABIHW_POST_DEFAULT_TITLE;
			if ($extracted_fields['manufacturer'] and $extracted_fields['product_name']) {
				$post_title = $extracted_fields['manufacturer'].' '.$extracted_fields['product_name'];
			}
			elseif ($extracted_fields['manufacturer']) {
				$post_title = $extracted_fields['manufacturer'];
            $extracted_fields['product_name'] = '-';
			}
			elseif ($extracted_fields['product_name']) {
				$post_title = $extracted_fields['product_name'];
            $extracted_fields['manufacturer'] = '-';
			}
			
         // Get SHA1 hash for the extracted data
         $this_hash = digabihw_get_hash($extracted_fields);
         if (is_null($this_hash)) {
             // We got an error from the hash function
             digabihw_warning("Could not count unique hash value from an incoming data file '".$filename."'. This file will not be processed.");
             // Skip this file
             continue;
         }
         
         $url_already_exists = digabihw_machine_already_exists($this_hash);
         if (!is_null($url_already_exists)) {
             // We already have entry for this machine
             
             digabihw_machine_add_counter($extracted_fields['manufacturer'], $extracted_fields['product_name']);
             
             if (count($url_already_exists) > 0) {
                 $wp_post_url = $url_already_exists[0];
             }
         } else {
             // This is a new machine - create a fresh post
         
            $post_slug = strtolower($post_title);
            $post_slug = preg_replace('/ /', '-', $post_slug);
            $post_slug = preg_replace('/[^a-z0-9\-]/', '', $post_slug);

            $post_datetime = strftime('%Y-%m-%d %H:%i:%s');

            $wp_post_data = Array(
               'post_status' => 'publish',
               'post_type' => 'digabihw_report',
               'post_author' => $DIGABIHW_POST_AUTHOR_ID,
               'post_parent' => 0,
               'post_content' => '<p>Digabi Hardware report in custom fields</p>',
               'post_modified' => $post_datetime,
               'post_title' => $post_title,
               'post_name' => $post_slug,
               'post_category' => $DIGABIHW_POST_CATEGORIES,
            );

            // Post data
            $wp_error = NULL;
            $wp_post_id = wp_insert_post($wp_post_data, $wp_error);

            if ($wp_post_id == 0) {
               // We have an error

               digabihw_error_exit(4, "Could not insert WP post. ".join("; ", $wp_error->errors));
            }

            // Store custom fields to the post
            foreach ($extracted_fields as $this_field => $this_value) {
               add_post_meta($wp_post_id, 'digabihw_'.$this_field, $this_value, TRUE);
            }
            
            // Store hit counter
            add_post_meta($wp_post_id, 'digabihw_counter', 1, TRUE);
            
            // Store status code - may we should define a variable for this in the settings.php?
            add_post_meta($wp_post_id, 'digabihw_status', 'ok:user', TRUE);

            // Store custom field names to the post
            add_post_meta($wp_post_id, 'digabihw_fields', join(':', array_keys($extracted_fields)), TRUE);
            
            // Store hash value to the post
            add_post_meta($wp_post_id, 'digabihw_hash', $this_hash, TRUE);
                    
            // Add category for Manufacturer (if we have a manufacturer)
            if ($extracted_fields['manufacturer']) {
                // Using custom taxonomy 'digabihw_dev'
                
                $cat_id = digabihw_get_category_id($extracted_fields['manufacturer']);
                $retval = wp_set_object_terms($wp_post_id, $cat_id, 'digabihw_dev');
            }
            
            // We are done with adding a fresh post, get post URL
            $wp_post_url = get_permalink($wp_post_id);
         }
		}
		else {
			// No extracted fields - no post
			digabihw_warning("Feedback ".$outfile." did not contain any data fields");
         return NULL;
		}
      
      return $wp_post_url;
	}
  
   // File is not readable
   digabihw_warning("File ".$filename." is not readable");
   return NULL;
}

/**
 * Sends email with an instructions how to contribute to the post by submitting
 * BIOS details.
 * @param string $email Target email
 * @param string $url URL of the submitted post. This will be embedded in the email.
 * @return boolean TRUE on success, otherwise FALSE.
 * @global array $DIGABIHW_EMAIL_SETTINGS
 */
function digabihw_send_email ($email, $url) {
    global $DIGABIHW_EMAIL_SETTINGS;
    
    // Embed given URL to the email
    $message = preg_replace('/#URL#/', $url, $DIGABIHW_EMAIL_SETTINGS['message']);
    
    return mail($email, $DIGABIHW_EMAIL_SETTINGS['subject'], $message, $DIGABIHW_EMAIL_SETTINGS['additional_headers']);
}

/**
 * Returns a random string. Used to create an unique ID for captcha.
 * @param int $length Length of the random string
 * @return string Random string
 */
function digabihw_rand_string($length) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";	

	$size = strlen( $chars );
   $str = '';
	for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
	}

	return $str;
}

/**
 * Remove old entries from captcha registry
 * @param type $timeframe If the entry is older than this (seconds) the entry will be deleted.
 */
function digabihw_captcha_cleanup ($timeframe) {
    $all_options = wp_load_alloptions();
    
    foreach ($all_options as $this_option_name => $this_option_value) {
        if (preg_match('/^digabihw_captcha_time_(\w+)/', $this_option_name, $match)) {
            // We found a key which belongs us
            $this_id = $match[1];
            
            if ($this_option_value < time()-$timeframe) {
                // The option is outdated
                delete_option('digabihw_captcha_time_'.$this_id);
                delete_option('digabihw_captcha_code_'.$this_id);
            }
        }
    }
}

// Main program
// FIXME: Rewrite this according to https://willnorris.com/2009/06/wordpress-plugin-pet-peeve-2-direct-calls-to-plugin-files

// Initialise custom taxonomy
add_action('init', 'digabihw_register_post_type');
add_action('init', 'digabihw_register_taxonomy');

// Initialise feedback receive function
add_action('parse_request', 'digabihw_process_feedback');
add_filter('query_vars', 'digabihw_register_queryvars');

function digabihw_register_queryvars ($vars) {
    $vars[] = 'digabihw_version';
    $vars[] = 'digabihw_email';
    $vars[] = 'digabihw_data';
    $vars[] = 'digabihw_captcha_id';
    $vars[] = 'digabihw_captcha_code';
    $vars[] = 'digabihw_comment';
    return $vars;
}

function digabihw_process_feedback ($wp) {
    global $DIGABIHW_SAVEPATH;
    
    /**
     * This function echoes result code + message to the client. The code
     * and message are separated with colon:
     * 0:OK
     * 
     * Following messages are implemented:
     * 0:URL_TO_THE_POST
     * 1:No post URL (creating a post failed)
     * 2:Unknown Captcha ID (user supplied an unknown ID)
     * 3:Captcha check failed (user supplied a captcha code which differs from
     *        the code in the image)
     */
    
    /**
     * Captcha checking has three stages:
     * 1) Client asks for a Captcha ID (query: digabihw_captcha_id=0). This
     *    replaces the session variable as the clients don't support it.
     *    This option reservers a new ID and prints it to the client.
     * 2) Client asks for the captcha image (query: digabihw_captcha_id=$ID).
     *    This is created using Securimage class. This option creates a new
     *    captcha image, prints is to the user and stores the correct code.
     * 3) Client posts the full data including the captcha codes
     *    (query: digabihw_captcha_id=$ID & digabihw_captcha_code=$CODE). This
     *    option verifies the saved code with the provided code. If the codes
     *    differ returns a error code & message and exits. 
     */

    $ID_LENGTH = 32;
    
    if (array_key_exists('digabihw_captcha_id', $wp->query_vars) and $wp->query_vars['digabihw_captcha_id'] == '0') {
        // Captcha ID 0 is given - client requests a new captcha ID
        
        // Remove all old captcha settings (database cleanup)
        digabihw_captcha_cleanup(60);
    
        $id_is_unique = false;
        $new_id = null;
        
        while (!$id_is_unique) {
            // Create a new ID
            $new_id = digabihw_rand_string($ID_LENGTH);
            
            // Try if ID is unused - and save the current time
            $id_is_unique = add_option('digabihw_captcha_time_'.$new_id, time(), null, 'yes');
        }
        
        // Report Captcha ID
        header("Content-type: text/plain; charset=utf-8");
        echo($new_id);
        
        exit(0);
    }
    elseif (array_key_exists('digabihw_captcha_id', $wp->query_vars) and !array_key_exists('digabihw_captcha_code', $wp->query_vars)) {
        // Captcha ID is given - client requests captcha image

        $given_id = preg_replace('/\W/', '', $wp->query_vars['digabihw_captcha_id']);
        if (strlen($given_id) != $ID_LENGTH) {
            header("Content-type: text/plain; charset=utf-8");
            echo('2:Unknown Captcha ID'.chr(10).chr(10));
            exit(0);
        }
        
        $stored_time = get_option('digabihw_captcha_time_'.$given_id, null);
        
        if (is_null($stored_time)) {
            header("Content-type: text/plain; charset=utf-8");
            echo('2:Unknown Captcha ID'.chr(10).chr(10));
            exit(0);
        }

        // Create captcha image
        $securimage = new Securimage(Array('no_exit' => true));
        $securimage->createCode();
        
        // Write the Captcha code (the show() method takes care of the HTTP headers)
        $securimage->show();

        // Store the captcha code
        update_option('digabihw_captcha_code_'.$given_id, $securimage->getCode(false, false));

        exit(0);
    }
    elseif (array_key_exists('digabihw_captcha_id', $wp->query_vars) and array_key_exists('digabihw_captcha_code', $wp->query_vars)) {
        $given_id = preg_replace('/\W/', '', $wp->query_vars['digabihw_captcha_id']);
        if (strlen($given_id) != $ID_LENGTH) {
            header("Content-type: text/plain; charset=utf-8");
            echo('2:Unknown Captcha ID'.chr(10).chr(10));
            exit(0);
        }
        
        $stored_code = get_option('digabihw_captcha_code_'.$given_id, null);
        
        if (is_null($stored_code)) {
            header("Content-type: text/plain; charset=utf-8");
            echo('2:Unknown Captcha ID'.chr(10).chr(10));
            exit(0);
        }
        
        if (strtolower($stored_code) != strtolower($wp->query_vars['digabihw_captcha_code'])) {
            // Stored code and given code differ -> we are dealing with a robot!!!
            
            header("Content-type: text/plain; charset=utf-8");
            echo('3:Captcha check failed'.chr(10).chr(10));
            exit(0);
        }
    }

    // The user has passed the captcha verification
    
    if (array_key_exists('digabihw_version', $wp->query_vars)) {
        // We have incoming data

        $final_post_url = NULL;

        // Do we have an email address? If so, remove it from the data as we are
        // not allowed to save it along with the submission.
        $user_email = NULL;
        if (array_key_exists('digabihw_email', $wp->query_vars)) {
            $user_email = $wp->query_vars['digabihw_email'];
            
            // Remove this from $_POST to avoid storing it with the report
            unset($_POST['digabihw_email']);
        }

        // Store rest of the POST data to a JSON string
        $data = json_encode($_POST);

        // Write data to file
        $outfile = $DIGABIHW_SAVEPATH . date("Y-m-d_H-i-s") . '_' . rand(10000, 99999);
        if (!file_put_contents($outfile . '.json', $data)) {
           digabihw_error_exit(11, "Could not write JSON data file ".$outfile);
        }

        // Save other files included in the post
        foreach ($_FILES as $this_uploaded_file) {
            _log("Uploaded file: ".$this_uploaded_file['name']);
           $size = $this_uploaded_file['size'];
           if ($size > 200000) {
              // Skip too large files
              digabihw_warning("Uploaded file ".$this_uploaded_file['name']." is too large, size: ".$this_uploaded_file['size']);
              continue;
           }

           if (!mkdir($outfile)) {
              digabihw_error_exit(12, "Could not create data directory ".$outfile);
           }

           $tgt_file = $outfile . '/' . basename($this_uploaded_file['name']);

           // Move files from upload to final location
           if (!move_uploaded_file($this_uploaded_file['tmp_name'], $tgt_file)) {
              digabihw_error_exit(13, "Could not move uploaded file to ".$tgt_file);
           }
        }

        // If uploaded files contain digabi-feedback.XXXX.log process them

        if (is_dir($outfile) and $dir_handle = opendir($outfile)) {
            while (false !== ($entry = readdir($dir_handle))) {
                if (preg_match('/^digabi\-feedback\..+\.log$/i', $entry)) {
                    $final_post_url = digabihw_process_data_file($outfile.'/'.$entry);

                    if (is_null($final_post_url)) {
                        digabihw_warning("Processing $outfile/$entry failed");
                    } else {
                        _log("Processing $outfile/$entry succeeded");

                        // Write WP Post URL to a file
                        $post_url_file = $outfile.'/'.$entry.'.wppost';
                        if (is_null(file_put_contents($post_url_file, $final_post_url))) {
                            digabihw_warning("Could not store WP Post URL to $post_url_file");
                        }
                    }
                }
            }
        }
        else {
           _log("No incoming files");
        }

        // If we have email to the user, let's send the mail
        if ($user_email != '') {
            if (digabihw_is_valid_email($user_email)) {
                // The email appears to have correct syntax
                if (!digabihw_send_email($user_email, $final_post_url)) {
                    // We failed to send the email
                    digabihw_warning("Failed to send an email to \"$user_email\" (URL \"$final_post_url\")");
                }
            }
            else {
                digabihw_warning("User has submitted us an email \"$user_email\" but it appears to have an incorrect format (URL \"$final_post_url\")");
            }
        }

        // Report URL
        header("Content-type: text/plain; charset=utf-8");
        
        if (is_null($final_post_url)) {
            echo("1:No post URL".chr(10).chr(10));
        } else {
            echo("0:".$final_post_url.chr(10).chr(10));
        }
        
        // We are done, exit here
        exit(0);
    }
}




?>
