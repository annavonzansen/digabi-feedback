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

/**
 * This file contains Digabi_Feedback class.
 *
 * @package Digabi_Feedback.php
 * @author Matti Lattu <matti.lattu@ylioppilastutkinto.fi>
 */

/**
 * This is a Digabi_Feedback class which processes feedback files from
 * the Digabi OS clients. The client script can be found at
 * https://github.com/digabi/digabi-feedback/blob/master/usr/lib/digabi/scripts/collect-feedback
 * 
 * A short example of using the code:
 * 
 * $data_file = 'full/path/to/data/file/digabi-feedback.log';
 *
 * $d = new Digabi_Feedback($data_file);
 *
 * 	foreach ($d->enum_fields() as $this_field) {
 *			if ($d->field_has_multi($this_field)) {
 *				// Field may have multiple values
 * 			echo($this_field.": ".join(', ', $d->get_field($this_field))."\n");
 * 		}
 *			else {
 *				// Field has only one value
 *				echo($this_field.": ".$d->get_field($this_field)."\n");
 *			}
 *		}
 *
 * @package Digabi_Feedback
 * @author Matti Lattu <matti.lattu@ylioppilastutkinto.fi>
 */
 
class Digabi_Feedback {
	protected $filename = NULL;
	
	/**
	 * @internal
	 * @var array Hash array of defined data fields. Each key points to an array
	 *		with three elements:
	 *    1) Feedback section key (e.g. "meminfo")
	 *		2) One of following:
	 *			Regular expression (string) which extracts the desired value
	 *			Function name which extracts the desired value
	 *		3) TRUE if multiple values are accepted, FALSE if only single value is possible.
	 */
	protected $FIELDS = Array(
      // Commented out to avoid this to be saved to WP
      //'boot_id' => Array('id','/^Boot-ID: (.*)\n/i', FALSE),
      'digabi_version' => Array('version', '/^Digabi-Version: (.*)\n/i', FALSE),
		'manufacturer' => Array('dmidecode', '/^System Information\nManufacturer: (.+)$/m', FALSE),
      'product_name' => Array('dmidecode', '/System Information\n.*?\nProduct Name: (.+)\n/m', FALSE),
      'product_sku' => Array('dmidecode', '/System Information\n.*?\n.*?\n.*?\n.*?\n.*?\nSKU Number: (.+)\n/m', FALSE),
		'memtotal' => Array('meminfo', '/^MemTotal: (\d+)/m', FALSE),
		'cpu_model_name' => Array('cpuinfo', '/^model name: (.+)$/m', FALSE),
		'cpu_freq' => Array('cpuinfo', '/^cpu MHz: ([\d\.]+)$/m', FALSE),
		'network_hw' => Array('lshw', 'parse_value_network_hw', TRUE),
		'multimedia_product' => Array('lshw', '/\*-multimedia[^\*]*product: (.*?)\n/s', TRUE),
		'multimedia_driver' => Array('lshw', '/\*-multimedia[^\*]*configuration: .*?driver\=(.+?) /s', TRUE),
		'firmware_description' => Array('lshw', '/\*-firmware[^\*]*description: (.*?)\n/s', FALSE),
		'firmware_vendor' => Array('lshw', '/\*-firmware[^\*]*vendor: (.*?)\n/s', FALSE),
		'firmware_version' => Array('lshw', '/\*-firmware[^\*]*version: (.*?)\n/s', FALSE),
		'firmware_capabilities' => Array('lshw', '/\*-firmware[^\*]*capabilities: (.*?)\n/s', FALSE),
      'screen_resolution_current' => Array('xrandr','/Screen \d+:.+?current (.+?),/s', TRUE),
      'screen_resolution_all' => Array('xrandr', '/(Screen \d+: .*?)\n/s', TRUE),
	);

	/**
	 * @var array Hash array of parsed data file. Use parse_file() to set this.
	 */	
	public $data = NULL;

	/**
	 * @var string Error message, NULL if not errors
	 */
	public $error = NULL;
		
	public function __construct ($file) {
		if (is_file($file) and is_readable($file)) {
			$this->filename = $file;
			if (!$this->parse_file()) {
				// Parsing file failed
				$this->error = "Parsing file failed";
				return NULL;
			}
		}
		else {
			// Given file is not regular readable file
			$this->error = "Given file $file is not readable";
			return NULL;
		}
	}
	
	private function parse_file () {
		// Read data file to a string
		$f = fopen($this->filename, "r");
		
		$this->data = Array();
		$current_section = NULL;

		while ($line = fgets($f)) {
			// Line cleanup
			$line = preg_replace('/[^a-zA-Z0-9\#\:@\=\-,\.\(\)_\/\* ]/', '', $line);
			$line = preg_replace('/ +/', ' ', $line);
			$line = preg_replace('/^\s+/', '', $line);
			$line = preg_replace('/\s+$/', '', $line);
			
			if (preg_match('/^# BEGIN\: +\-\-\d*\-*(.+?)\.?s?h?\-\- #/', $line, $matches)) {
				// Starts a new section (current format)
				$current_section = strtolower($matches[1]);
				continue;
			}
			elseif (preg_match('/^# \-\-(.+)\-\- #/', $line, $matches)) {
				// Starts a new section (old format)
				$current_section = strtolower($matches[1]);
				continue;
			}
			elseif (preg_match('/^# END\: +\-\-(.+)\-\- #/', $line, $matches)) {
				// Ends a new section
				$current_section = NULL;
				continue;
			}
			
			// Define section
			if (!isset($this->data[$current_section])) {
				$this->data[$current_section] = Array();
			}
			
			if (preg_match('/^I\: Executing command \((.+)\)/', $line, $matches)) {
				// Defines a section command
				$this->data[$current_section]['command'] = $matches[1];
				continue;
			}
			elseif (preg_match('/^I\: Reading input file \((.+)\)/', $line, $matches)) {
				// Defines a section command
				$this->data[$current_section]['input_file'] = $matches[1];
				continue;
			}
			
			if (!is_null($current_section)) {
				if (@$this->data[$current_section]['string'] != '') {
					$this->data[$current_section]['string'] .= $line."\n";
				}
				else {
					$this->data[$current_section]['string'] = $line."\n";
				}
			}
		}
		
		fclose($f);
		$data_str = file_get_contents($this->filename);
		
		return TRUE;
	}
	
	function find_str ($section, $regexp, $multiple=FALSE) {
		if (is_array(@$this->data[$section]) and isset($this->data[$section]['string'])) {
			// Section exists
			
			if (is_callable(Array($this, $regexp))) {
				// The regexp is actually a callable function (usually parse_value_*())
				return $this->$regexp($this->data[$section]['string']);
			}
			elseif ($multiple) {
				// Search for multiple matches (array of strings)
				
				if (preg_match_all($regexp, $this->data[$section]['string'], $matches, PREG_PATTERN_ORDER)) {
					return $matches[1];
				}
				// Nothing found
				return Array();
			}
			else {
				// Search for single match (one string)
				
				if (preg_match($regexp, $this->data[$section]['string'], $matches)) {
					// Regexp found something, return content of $1 - 1st (...)
					return $matches[1];
				}
				// Nothing found
				return '';
			}
		}
		else {
			// Section missing
			return NULL;
		}
	}
	
	function enum_fields () {
		return array_keys($this->FIELDS);
	}
	
	function field_has_multi ($field_name) {
		return $this->FIELDS[$field_name][2];
	}
	
	function get_field($field_name) {
		return $this->find_str($this->FIELDS[$field_name][0], $this->FIELDS[$field_name][1], $this->FIELDS[$field_name][2]);
	}
	
	function get_field_str($field_name, $separator='; ') {
		$r = NULL;

		if ($this->field_has_multi($field_name)) {
			// Field may have multiple values
			$r = join($separator, $this->get_field($field_name));
		}
		else {
			// Field has only one value
			$r = $this->get_field($field_name);
		}

		return $r;		
	}
	
	/**
	 * Extracts network strings. Typically called by find_str() as defined in $FIELDS.
	 *
	 * @param array $data_str Desired data section as a string.
	 * @return array Array of network cards in a user-readable format.
	 */
	function parse_value_network_hw ($data_str) {
		$return_data = Array();
		
		if (preg_match_all('/^(\*\-[^\*]+)/m', $data_str, $matches)) {
			foreach ($matches[1] as $this_part) {
				if (preg_match('/^\*\-network/', $this_part)) {
					// This is a network part
					$net_dev = Array();
					if (preg_match('/\nlogical name: (.*?)\n/s', $this_part, $net_matches)) {
						array_push($net_dev, $net_matches[1]);
					}
					if (preg_match('/\nconfiguration: .*driver\=(.+?) /s', $this_part, $net_matches)) {
						array_push($net_dev, $net_matches[1]);
					}
					if (preg_match('/\nproduct: (.*?)\n/s', $this_part, $net_matches)) {
						array_push($net_dev, $net_matches[1]);
					}
					
					array_push($return_data, join(' : ',$net_dev));
				}
			}
		}

		return $return_data;
	}
	
}

?>
