# Digabi HW Wordpress Plugin

## Flow of Execution

Digabi HW is a Wordpress plugin to receive, store and display Digabi feedback
reports with WP. The basic flow of execution to receive a hardware report is:

1. Client transmits hardware report using HTTP POST (feedback.php).
   The flat file is stored to a desired path (`$DIGABIHW_SAVEPATH`).
2. Important data is read from the flat file (Digabi_Feedback.php) and stored
   to a Wordpress (feedback.php). For this a custom post type `digabihw_report`
   with custom fields are introduced (digabi_hw.php). The plugin also adds
   a custom taxonomy "digabihw_dev" for Device Manufacturers.
3. If an entry with equal Manufacturer and Product Name is found a new post
   will not be made but a report counter (a custom field "digabihw_counter")
   is added by one.
4. Now the report is received.

The display of the reports is introduced in digabi_hw.php. It hooks a function
`digabihw_show_custom_fields()` to the WP filter hook `the_content` so the
function is called whenever WP wishes to show some post content.

The function has two modes of operation:

* If the content belongs to the Digabi HW custom post type `digabihw_report`
  it creates new post content (a HTML table) based on the hardware data
  stored to the the custom fields.
* If the content belongs to the standard post types "page" or "post" it
  looks for a shortcode `[digabihw_menu_full]` and replaces it with a
  HTML-formatted list of manufacturers and product names. The data is
  collected on-the-fly from the Digabi HW custom post types.

## Custom Post Type "digabihw_report"

For the implementation see `digabihw_register_post_type()` in
`digabi_hw_functions.php`.

## Custom Fields

Wordpress custom fields are a data model with fields and values tied to a post.
The fields can be inserted and deleted on the fly and there is no pre-defined
data model.

The Digabi HW custom fields are written when the data is saved (feedback.php).
After this the data is only read. The field names have a prefix `digabihw_`.

There are some fields with special meaning:

* *digabihw_fields* contains a colon-separated (:) list of field names reported
  by the Digabi_Feedback. The field names are stored without the prefix
  ("digabihw_").
* *digabihw_hash* is a SHA1 hash of the values of `digabihw_fields`
  variables. This hash is used to detect whether a submitted hardware report
  is an existing (`digabihw_counter` is added by one) or a new one (a new
  custom post is generated).
* *digabihw_counter* is a counter for reports for an unique combination of
  a Manufacturer and a Product Name.
* *digabihw_status* contains one or more status codes for the post. The valid
  status codes are defined in the $DIGABIHW_POST_STATUS (settings.php). The
  string is a colon-separated (:).

Other fields are used to store whatever data is detected from the flat-file
report by the Digabi_Feedback class (Digabi_Feedback.php). The field names
equal to the field names used in the Digabi_Feedback (e.g. `manufacturer`,
`product_name`) but with the prefix (e.g. `digabihw_manufacturer`,
`digabihw_product_name`).

The custom fields are accessed both directly using WP functions
`add_post_meta()` and `get_post_meta()`. `digabi_hw_functions.php` implements
some functions that can be used.

## Custom Taxonomy "digabihw_dev"

Custom Taxonomy is used to create a tag-like browsing option. The taxonomy
contains only tags from the Manufacturer field.

For the implementation see `digabihw_register_taxonomy()` in
digabihw_functions.php.

The custom taxonomy is used since we wanted to keep the Digabi HW
taxonomy separated from the regular tags and categories used in the
normal WP site.

## Adding New Data Fields

Adding new data fields to WP page is quite straightforward.

1. Obviously, the data should be present in the flat-file hardware report.
   First you should execute the submit-feedback script using the digabi tool
   (i.e. `/usr/bin/digabi submit-feedback`). If the new data field does not
   exist in the file you should add it by writing a new hook into
   `/usr/lib/digabi/feedback-hooks/`.
2. Get a hardware report file from the desired target machine with the
   necessary data. The easiest way to access the data is to execute
   "digabi submit-feedback" and preview the collected data.
3. Add the field to `$FIELDS` array in the Digabi_Feedback class
   (Digabi_Feedback.php). Fields with single values can be extracted with
   a regular expression. For a more complex values you may have to write a
   small function. There are examples for both cases.
4. You may run try_parse.php against your test files. Define test files
   using the $try_us array.
5. When your new field exists in the Digabi_Feedback its values are added
   to WP Custom Posts as new custom fields. You can see (and edit) the
   values by editing the custom post.
6. To show the value for the users add it to `$DIGABIHW_SHOW_FIELDS_BASIC` or
   `$DIGABIHW_SHOW_FIELDS_DETAILED` (settings.php).
7. If you don't want to show the value in the WP post but only in the CSV
   export you can add the value to `$DIGABIHW_SHOW_FIELDS_CSV` (settings.php).
8. As you added new legends you have to add the translations for these strings
   (see Translating).

## Translating

Digabi HW implements Wordpress I18N support as documented in 
http://codex.wordpress.org/I18n_for_WordPress_Developers. The language
files are in `lang/` directory.

1. Get the makepot.php from http://develop.svn.wordpress.org/trunk/tools/i18n/.
   Download all the files as they are needed to execute the main script.
2. Execute makepot.php:
   `php makepot.php wp-plugin path/to/wp-content/plugins/digabi_hw/ digabi_hw.pot`

   You may want to define the `$pomo` values in the `not-gettexted.php`,
   `pot-ext-meta.php` and `extract.php` to avoid warnings. They should point
   to your Wordpress installation:

   `PHP Warning:  require_once(/home/src/wp-includes/pomo/po.php): failed to 
   open stream: No such file or directory in /home/matti/makepot/not-gettexted.php
   on line 16`

   Now you have the `digabi_hw.pot` file.
3. Translate the pot to a .po and .mo files.
4. Copy the files to lang/ directory following the existing naming convention.
