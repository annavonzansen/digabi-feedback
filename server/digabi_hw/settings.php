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
 * This file contains settings for Digabi HW plugin. Some settings contain
 * i18n translations and thus they are set after the i18n is loaded.
 */

$DIGABIHW_SAVEPATH = NULL;
$DIGABIHW_POST_DEFAULT_SLUG = NULL;
$DIGABIHW_POST_DEFAULT_TITLE = NULL;
$DIGABIHW_POST_AUTHOR_ID = NULL;
$DIGABIHW_SHOW_FIELDS_BASIC = NULL;
$DIGABIHW_SHOW_FIELDS_DETAILED = NULL;
$DIGABIHW_SHOW_FIELDS_CSV = NULL;
$DIGABIHW_MINIMUM_VALUES = NULL;
$DIGABIHW_POST_STATUS = NULL;
$DIGABIHW_EMAIL_SETTINGS = NULL;

/**
 * Sets Digabi HW global variables (settings).
 * @global string $DIGABIHW_SAVEPATH
 * @global string $DIGABIHW_POST_DEFAULT_SLUG
 * @global string $DIGABIHW_POST_DEFAULT_TITLE
 * @global int $DIGABIHW_POST_AUTHOR_ID
 * @global array $DIGABIHW_SHOW_FIELDS_BASIC
 * @global array $DIGABIHW_SHOW_FIELDS_DETAILED
 * @global array $DIGABIHW_SHOW_FIELDS_CSV
 * @global array $DIGABIHW_MINIMUM_VALUES
 * @global array $DIGABIHW_POST_STATUS
 * @global array $DIGABIHW_EMAIL_SETTINGS
 */
function digabihw_set_global_settings () {
    global $DIGABIHW_SAVEPATH;
    global $DIGABIHW_CSVPATH;
    global $DIGABIHW_POST_DEFAULT_SLUG;
    global $DIGABIHW_POST_DEFAULT_TITLE;
    global $DIGABIHW_POST_AUTHOR_ID;
    global $DIGABIHW_SHOW_FIELDS_BASIC;
    global $DIGABIHW_SHOW_FIELDS_DETAILED;
    global $DIGABIHW_SHOW_FIELDS_CSV;
    global $DIGABIHW_MINIMUM_VALUES;
    global $DIGABIHW_POST_STATUS;
    global $DIGABIHW_EMAIL_SETTINGS;

    /**
     * Save path for raw client uploads. The path will be appended by
     * datetime and random string. Path /var/digabihw/digabihw_post_'
     * may result '/var/digabihw/digabihw_post_2014-04-24_19-03-41_38585'
     * @global string $DIGABIHW_SAVEPATH
     */

    $DIGABIHW_SAVEPATH = '/tmp/digabihw_post_';

    /**
     * Default title to WP Custom Post. This subject will be used if no
     * subject can be composed from retrieved data.
     * @global string $DIGABIHW_POST_DEFAULT_TITLE
     */

    $DIGABIHW_POST_DEFAULT_TITLE = 'Unknown Device';

    /**
     * Default slug (short URL) to WP Custom Post. This slug will be used if
     * a slug derived from default title cannot be used.
     * @global string $DIGABIHW_POST_DEFAULT_SLUG
     */

    $DIGABIHW_POST_DEFAULT_SLUG = 'unknown-device';

    /**
     * Use this author ID to WP Custom Post. The value is a WP author ID
     * (int).
     * @global int $DIGABIHW_POST_AUTHOR_ID
     */

    $DIGABIHW_POST_AUTHOR_ID = 0;

    /**
     * Show these fields in WP Custom Post Basic View. The keys must correspond to field names
     * in Digabi_Feedback class. The values are legends shown to the user.
     * @global array $DIGABIHW_SHOW_FIELDS_BASIC
     */

    $DIGABIHW_SHOW_FIELDS_BASIC = Array(
        'manufacturer' => __('Manufacturer','digabi_hw'),
        'product_name' => __('Product Name','digabi_hw'),
        'memtotal' => __('System Memory (Mb)', 'digabi_hw'),
        'cpu_model_name' => __('CPU Model','digabi_hw'),
        'network_hw' => __('Network Hardware','digabi_hw'),
        'multimedia_product' => __('Multimedia Hardware','digabi_hw'),
        'screen_resolution_current' => __("Screen Resolution","digabi_hw"),
    );

    /**
     * Show these fields in WP Custom Post Detailed View. The keys must correspond to field names
     * in Digabi_Feedback class. The values are legends shown to the user.
     * @global array $DIGABIHW_SHOW_FIELDS_DETAILED
     */

    $DIGABIHW_SHOW_FIELDS_DETAILED = Array(
        'product_sku' => __('Product SKU','digabi_hw'),
        'firmware_description' => __('Firmware Description','digabi_hw'),
        'firmware_vendor' => __('Firmware Vendor','digabi_hw'),
        'firmware_version' => __('Firmware Version','digabi_hw'),
        'firmware_capabilities' => __('Firmware Capabilities','digabi_hw'),
        'multimedia_driver' => __('Multimedia Driver','digabi_hw'),
        'screen_resolution_all' => __("Possible Screen Resolutions","digabi_hw"),
        'digabi_version' => __("Digabi Version", 'digabi_hw'),
    );

    /**
     * Show these fields in CSV export (settings page). The keys must correspond to
     * the field names in the custom fields. The values are legends shown in the
     * column headers.
     * @global array $DIGABIHW_SHOW_FIELDS_CSV
     */

    $DIGABIHW_SHOW_FIELDS_CSV = Array(
        'counter' => __("Report Counter", 'digabi_hw'),
        'status' => __("Report Status", 'digabi_hw'),
    );

    /**
     * Contains rules for minimum values. The minimum values are taken from the MEB
     * criteria. The array contains user-defined function which gets the value as
     * an parameter and return either TRUE (the criteria is passed) or FALSE (not passed).
     * @global array $DIGABIHW_MINIMUM_VALUES
     */

    $DIGABIHW_MINIMUM_VALUES = Array(
        'memtotal' => create_function('$memory', 'if ($memory > 2048000) { return TRUE; } return FALSE;'),
        'screen_resolution_current' => create_function('$resolution', '$res_array = explode("x", $resolution); if ($res_array[0] >= 1024 and $res_array[1] >= 768) { return TRUE; } return FALSE;'),
        'multimedia_driver' => create_function('$driver_name', 'if ($driver_name != "") return TRUE; return FALSE;'),
    );

    /**
     * Possible value for WP Custom Post field "digabihw_status". This value
     * tells that the entry is user-supplied and not checked by the MEB.
     * @global string $DIGABIHW_POST_STATUS
     */
    $DIGABIHW_POST_STATUS = Array(
        'ok' => __("This device is <b>working</b>.", 'digabi_hw'),
        'failed' => __("This device is <b>not working</b>.", 'digabi_hw'),
        'user' => __("Note: This report is user-supplied and has not been reviewed by the MEB staff.",'digabi_hw'),
        'meb' => __("This report has been reviewed by the MEB staff.",'digabi_hw'),
    );

    // Temporary variable to hold message body as heredoc appears not to work
    // inside array definition.
    
    $email_message = <<<DIGABIHW_EMAIL_END
Hei! 
Huippua, että onnistuit käynnistämään DigabiOS:n muistitikulta! 
Laitteen käynnistäminen ulkoiselta medialta (tikulta) ei vielä ole kaikille arkipäivää, joten nyt voit olla avuksi! Voisitko käydä kertomassa, miten onnistuit eli raportoi koneesi BIOS-asetukset osoitteessa #URL#.
Kiitos jo etukäteen!

Hej!
Fint att du lyckades starta din dator från en DigabiOS-minnepinne!
Inte alla bootar sina datorer varje dag från ett externt medium (USB-minne) så nu är det dags att tipsa hur man gör det! Gå till adressen #URL# för att berätta hur du startade din dator - rapportera alltså om dina BIOS-inställningar!
Tack på förhand!

Hi!
We´re happy to hear that you booted your computer from an DigabiOS USB flash drive!
Not everyone of us knows how to boot from an external hard drive so now you can help other people! Please go to address #URL# and report your BIOS settings.
Thanks a lot!

DIGABIHW_EMAIL_END;
    
    /**
     * Parameters used in sending UTF8-encoded email. This constant is used in feedback.php / digabihw_send_email().
     * @global array $DIGABIHW_EMAIL_SETTINGS
     */
    $DIGABIHW_EMAIL_SETTINGS = Array(
        'message' => $email_message,
        'subject' => '=?UTF-8?B?'.base64_encode("Digabi OS - BIOS").'?=',
        'additional_headers' => "From: digabi@ylioppilastutkinto.fi\r\nMIME-Version: 1.0\r\nContent-type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n",
    );
}

?>
