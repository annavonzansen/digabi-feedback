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


// Define your test files here
$try_us = Array(
	"test_data/digabi-feedback.acer.log",
);

function _log ($msg) {
	echo($msg."\n");
}

if(php_sapi_name() != "cli") {
	// The script is not executed as CLI
	exit(0);
}

include_once("Digabi_Feedback.php");



foreach ($try_us as $this_file) {
	$d = new Digabi_Feedback($this_file);
	
	if (!is_null($d->error)) {
		echo("Error: ".$d->error."\n");
		$d->error = NULL;
		continue;
	}

	echo("-----------------------------------\n");
	//print_r($d->data);

	foreach ($d->enum_fields() as $this_field) {
		/*
      // Handle single and multiple values differently
       
		if ($d->field_has_multi($this_field)) {
			// Field may have multiple values
			echo($this_field.": ".join('; ', $d->get_field($this_field))."\n");
		}
		else {
			// Field has only one value
			echo($this_field.": ".$d->get_field($this_field)."\n");
		}
		*/
		
      // Get single or multiple values using get_field_str()
		echo($this_field.":\t".$d->get_field_str($this_field)."\n");
	}
}

?>

