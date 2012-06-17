<?php

// page settings sections & fields as well as the contextual help text.
include_once('qtranslate-slug-settings-options.php');


/**
 * Prints upgrade form in Qtranslate Slug admin page
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return array
 */
function qts_upgrade() {
	global $qtranslate_slug;
	
	if ( !$qtranslate_slug->check_old_data() ) return;
	
	echo "<div id=\"qts-upgrade-box\" class=\"updated\" style=\"margin: 15px 0; margin-bottom:25px; padding: 10px\">" . PHP_EOL;
	
	echo "<h3>" . __('Upgrade your data', 'qts') . "</h3>" . PHP_EOL;
	echo "<p>" . __('There are slugs stored with old data system. Please upgrade your data pressing upgrade button:', 'qts') . "</p>" . PHP_EOL;
	
	echo "<div id=\"qts-upgrade-form\">" . PHP_EOL;
	echo "<input type=\"hidden\" id=\"qts-upgrade-action\" name=\"qts-upgrade-action\" value=\"qts-upgrade-check\" />" .PHP_EOL;
	echo "<input type=\"hidden\" id=\"qts-upgrade-nonce\" name=\"qts-upgrade-nonce\" value=\"". wp_create_nonce('qts-upgrade') . "\" />" .PHP_EOL;
	echo "<p><a id=\"qts-upgrade-button\" href=\"#\" class=\"button-primary\">" . __('upgrade now', 'qts') . "</a><img src=\"".admin_url('images/wpspin_light.gif')."\" class=\"qts-loading\" id=\"qts-loading\" alt=\"\"></p>" . PHP_EOL;
	echo "</div></div>" . PHP_EOL;
}


/**
 * AJAX: upgrade old database to new system
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return boolean
 */
function qts_process_old_data( $old_data = null, $type = 'post' ) {
	global $wpdb, $qtranslate_slug;
	
	if ( is_null($old_data) || empty($old_data) ) return 0;
	foreach ($old_data as $row) {
		switch ($row->qts_type) {
			case 1:
			case 2:
				update_post_meta( $row->qts_id, $qtranslate_slug->get_meta_key($row->qts_lang), $row->qts_slug);
				break;
			case 3:
			case 4:
				update_term_meta( $row->qts_id, $qtranslate_slug->get_meta_key($row->qts_lang), $row->qts_slug);
				break;
			case 5:
				if ($type == 'post')
					update_post_meta( $row->qts_id, $qtranslate_slug->get_meta_key($row->qts_lang), $row->qts_slug);
				
				if ($type == 'term')
					update_term_meta( $row->qts_id, $qtranslate_slug->get_meta_key($row->qts_lang), $row->qts_slug);
				break;
		};
	}
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}qtranslate_slug");
	
	return 1;
}

function qts_upgrade_response($type = 0, $slug = null) {
	switch ( $type ) {
		default:
		case 0:
			$response = array(
				'status' => 0,
				'message' => __('Sorry, some error happened. Refresh this page and try again please.', 'qts'),
			);
			break;
		   
		case 1:
			$response = array(
				'status' => 1,
				'message' => __('Congratulations, your data has been upgraded, you are up to date.', 'qts'),
			);
			break;
		case 2:
			$response = array(
				'status' => 2,
				'message' => sprintf(__('One step more, select what type of slug is this: <strong>%s</strong>', 'qts'), $slug),
				'options' => array( 'post' => __('Post, Page or Custom post type', 'qts'), 'term' => __('Category, tag or custom taxonomy') )
			);
			break;
	}
	
	return $response;
}



/**
 * AJAX: upgrade old database to new system
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return JSON object
 */
function qts_upgrade_check() {
	global $qtranslate_slug;
	
	$old_data = $qtranslate_slug->check_old_data();
	
	if ( wp_verify_nonce( $_POST['nonce'], 'qts-upgrade') ):
	
		// if data process to save...
		if ($old_data) {
			
			// if defined data type 5, process to upgrade
			if ( isset($_POST['type']) ):
				
				$res =  qts_process_old_data($old_data, $_POST['type']);
				
				$response = qts_upgrade_response($res);
				
			else :
				
				// define wich type of data is 5
				$array_of_5 = array();
				foreach ( $old_data as $row )
					if ( $row->qts_type == 5 ) $array_of_5[] = $row->qts_slug;
				
				if (!empty($array_of_5)) $response = qts_upgrade_response(2, implode(', ', $array_of_5));
				
				// if no data type 5, process to upgrade
				if (!isset($response)) {
					$res =  qts_process_old_data($old_data);
					$response = qts_upgrade_response($res);
				}
				
			endif;
			
		
		// if not continue	
		} else {
	
			$response = qts_upgrade_response();
		
		}
	
	else:
		
		$response = qts_upgrade_response();
		
	endif;
	
	// response output
	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;
}
add_action('wp_ajax_qts-upgrade-check',  'qts_upgrade_check');



/**
 * Helper function for defining variables for the current page
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return array
 */
function qts_get_settings() {
	
	$output = array();
	
	// put together the output array 
	$output['qts_option_name']		= QTS_OPTIONS_NAME; // the option name as used in the get_option() call.
	$output['qts_page_title'] 		= __('Qtranslate Slug options', 'qts'); // the settings page title
	$output['qts_page_sections'] 	= qts_options_page_sections(); // the setting section
	$output['qts_page_fields'] 		= qts_options_page_fields(); // the setting fields
	$output['qts_contextual_help'] 	= qts_options_page_contextual_help(); // the contextual help
	
return $output;
}



/**
 * Helper function for registering our form field settings
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * src: http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
 * @param (array) $args The array of arguments to be used in creating the field
 * @return function call
 */
function qts_create_settings_field( $args = array() ) {
	// default array to overwrite when calling the function
	$defaults = array(
		'id'      => 'default_field', 					// the ID of the setting in our options array, and the ID of the HTML form element
		'title'   => 'Default Field', 					// the label for the HTML form element
		'desc'    => 'This is a default description.', 	// the description displayed under the HTML form element
		'std'     => '', 								// the default value for this setting
		'type'    => 'text', 							// the HTML form element to use
		'section' => 'main_section', 					// the section this setting belongs to must match the array key of a section in qts_options_page_sections()
		'choices' => array(), 							// (optional): the values in radio buttons or a drop-down menu
		'class'   => '' 								// the HTML form element class. Is used for validation purposes and may be also use for styling if needed.
	);
	
	// "extract" to be able to use the array keys as variables in our function output below
	extract( wp_parse_args( $args, $defaults ) );
	
	// additional arguments for use in form field output in the function qts_show_form_field!
	$field_args = array(
		'type'      => $type,
		'id'        => $id,
		'desc'      => $desc,
		'std'       => $std,
		'choices'   => $choices,
		'label_for' => $id,
		'class'     => $class
	);

	add_settings_field( $id, $title, 'qts_show_form_field', __FILE__, $section, $field_args );

}



/**
 * Register our setting, settings sections and settings fields
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 */
function qts_register_settings(){
	
	// get the settings sections array
	$settings_output = qts_get_settings();
	$qts_option_name = $settings_output['qts_option_name'];
	
	//setting
	register_setting($qts_option_name, $qts_option_name, 'qts_validate_options' );
	
	//sections
	if(!empty($settings_output['qts_page_sections'])){
		// call the "add_settings_section" for each!
		foreach ( $settings_output['qts_page_sections'] as $id => $title ) {
			add_settings_section( $id, $title, 'qts_section_fn', __FILE__);
		}
	}
		
	//fields
	if(!empty($settings_output['qts_page_fields'])){
		// call the "add_settings_field" for each!
		foreach ($settings_output['qts_page_fields'] as $option) {
			qts_create_settings_field($option);
		}
	}
}
add_action( 'admin_init', 'qts_register_settings' );



/**
 * Group scripts (js & css)
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 */
function qts_settings_scripts() {
	global $qtranslate_slug;
	
	wp_enqueue_style('qts_theme_settings_css', plugins_url( 'assets/css/qts-settings.css' , __FILE__ ) );
	wp_enqueue_script( 'qts_theme_settings_js', plugins_url( 'assets/js/qts-settings.js' , __FILE__ ), array('jquery'));
	
	if ($qtranslate_slug->check_old_data())
		wp_enqueue_script('qts_theme_settings_upgrade_js', plugins_url( 'assets/js/qts-settings-upgrade.js' , __FILE__ ), array('jquery') );
}



/**
 * The Admin menu page
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 */
function qts_add_menu() {
	global $current_screen;

	$settings_output = qts_get_settings();
	// collect our contextual help text
	$qts_contextual_help = $settings_output['qts_contextual_help'];
	
	// Display Settings Page link under the "Appearance" Admin Menu
	$qts_settings_page = add_options_page(__('Qtranslate Slug options', 'qts'), __('Slug options', 'qts'), 'manage_options', QTS_PAGE_BASENAME, 'qts_show_settings_page');
		// contextual help
		/*
		if ($qts_settings_page) {
			$current_screen = get_current_screen();
			//$current_screen->add_help_tab( array( $qts_settings_page, $qts_contextual_help ));
		}
		*/
		// css & js
		add_action( 'load-'. $qts_settings_page, 'qts_settings_scripts' );	
}
add_action( 'admin_menu', 'qts_add_menu' );



////////////////////////////////////////////////////////////////////////////////////////
	
// Callback functions



/**
 * Section HTML, displayed before the first option
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return echoes output
 */
function qts_section_fn($page_section = false) {
	
	if ( !$page_section || !isset($page_section['id']) ) return;
	
	switch ($page_section['id']) {
		case 'post_types':	
		
			echo "<p>" . __('For example, the post_type <kbd>books</kbd>, in Spanish would be displayed as <code>http://example.org/es/libros/post-type-name/</code>. If you leave this blank will use the default option when you <a href="http://codex.wordpress.org/Function_Reference/register_post_type">registered</a> the post_type.', 'qts') . "</p>";
			break;
		
		case 'taxonomies':
			
			echo "<p>" . __('For example, the taxonomy <kbd>category</kbd>, in Spanish would be displayed as <code>http://example.org/es/categoria/taxonomy-name/</code>. If you leave this blank will use the default option when you <a href="http://codex.wordpress.org/Function_Reference/register_taxonomy">registered</a> the taxonomy (if you previously setup a base permastruct for <u>categories</u> or <u>tags</u> in <a href="options-permalink.php">permalinks</a> page, these bases will be overwritten by the translated ones).', 'qts') . "</p>";
			break;
	}
}



/**
 * Form Fields HTML: all form field types share the same function
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return echoes output
 */
function qts_show_form_field($args = array()) {
	global $qtranslate_slug;
	
	extract( $args );
	
	// get the settings sections array
	$settings_output = qts_get_settings();
	$options = $qtranslate_slug->get_options();
	
	// pass the standard value if the option is not yet set in the database
	if ( !isset( $options[$id] ) && 'type' != 'checkbox' ) {
		$options[$id] = $std;
	}
	
	// additional field class. output only if the class is defined in the create_setting arguments
	$field_class = ($class != '') ? ' ' . $class : '';
	
	// switch html display based on the setting type.	
	switch ( $type ) {
		case 'text':
			$options[$id] = stripslashes($options[$id]);
			$options[$id] = esc_attr( $options[$id]);
			echo "<input class='regular-text$field_class' type='text' id='$id' name='" . QTS_OPTIONS_NAME . "[$id]' value='$options[$id]' />";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
		break;
		
		case "multi-text":
			foreach($choices as $item) {
				
				$item = explode("|",$item); // cat_name|cat_slug
				$item[0] = esc_html__($item[0], 'qts');
				
				if (!empty($options[$id])) {
					foreach ($options[$id] as $option_key => $option_val){
						if ($item[1] == $option_key) {
							$value = $option_val;
						}
					}
				} else {
					$value = '';
				}
				
				echo "<span>$item[0]:</span> <input class='$field_class' type='text' id='$id|$item[1]' name='" . QTS_OPTIONS_NAME . "[$id|$item[1]]' value='$value' /><br/>";
			}
			echo ($desc != '') ? "<span class='description'>$desc</span>" : "";
		break;
		
		case 'textarea':
			$options[$id] = stripslashes($options[$id]);
			$options[$id] = esc_html( $options[$id]);
			echo "<textarea class='textarea$field_class' type='text' id='$id' name='" . QTS_OPTIONS_NAME . "[$id]' rows='5' cols='30'>$options[$id]</textarea>";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : ""; 		
		break;
		
		case 'select':
			echo "<select id='$id' class='select$field_class' name='" . QTS_OPTIONS_NAME . "[$id]'>";
				foreach($choices as $item) {
					$value 	= esc_attr($item, 'qts');
					$item 	= esc_html($item, 'qts');
					
					$selected = ($options[$id]==$value) ? 'selected="selected"' : '';
					echo "<option value='$value' $selected>$item</option>";
				}
			echo "</select>";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : ""; 
		break;
		
		case 'select2':
			echo "<select id='$id' class='select$field_class' name='" . QTS_OPTIONS_NAME . "[$id]'>";
			foreach($choices as $item) {
				
				$item = explode("|",$item);
				$item[0] = esc_html($item[0], 'qts');
				
				$selected = ($options[$id]==$item[1]) ? 'selected="selected"' : '';
				echo "<option value='$item[1]' $selected>$item[0]</option>";
			}
			echo "</select>";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
		break;
		
		case 'checkbox':
			echo "<input class='checkbox$field_class' type='checkbox' id='$id' name='" . QTS_OPTIONS_NAME . "[$id]' value='1' " . checked( $options[$id], 1, false ) . " />";
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
		break;
		
		case "multi-checkbox":
			foreach($choices as $item) {
				
				$item = explode("|",$item);
				$item[0] = esc_html($item[0], 'qts');
				
				$checked = '';
				
			    if ( isset($options[$id][$item[1]]) ) {
					if ( $options[$id][$item[1]] == 'true') {
			   			$checked = 'checked="checked"';
					}
				}
				
				echo "<input class='checkbox$field_class' type='checkbox' id='$id|$item[1]' name='" . QTS_OPTIONS_NAME . "[$id|$item[1]]' value='1' $checked /> $item[0] <br/>";
			}
			echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
		break;
	}
}



/**
 * Validates base slugs per 'type' (post_type | taxonomy) and 'language' 
 * 
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 */
function qts_sanitize_bases($base_slugs = false) {
	
	if ( !$base_slugs || empty($base_slugs) ) return;
	
	$base_slugs_processed = array();
	$post_types = array();
	$taxonomies = array();
	
	// changing array structure
	foreach ($base_slugs as $type => $value) {
		
		// matching post types..
		preg_match('/(\b_qts_post_type_)\w+\b/', $type, $matches);
		
		if (!empty($matches)) 
			foreach ($value as $lang => $base) $post_types[$lang][$type] = $base;
		
		// matching taxonomies..
		preg_match('/(\b_qts_taxonomy_)\w+\b/', $type, $matches);
		
		if (!empty($matches))
			foreach ($value as $lang => $base) $taxonomies[$lang][$type] = $base;
	}

	// processing post_types
	if ( count($post_types) > 1 )
		$post_types = qts_prevent_duplicates($post_types);
	
	// processing taxonomies
	if ( count($taxonomies) > 1 )
		$taxonomies = qts_prevent_duplicates($taxonomies);
	
	foreach ($post_types as $lang => $array_) 
		foreach ($array_ as $key => $value) $base_slugs_processed[$key][$lang] = $value;
		
	foreach ($taxonomies as $lang => $array_) 
		foreach ($array_ as $key => $value) $base_slugs_processed[$key][$lang] = $value;
	
	return $base_slugs_processed;
}



/**
 * Helper that prevents duplicates items on an array
 * 
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return array processed
 */

function qts_prevent_duplicates($array) {
	
	foreach ($array as $lang_ => $lang):
		
		$values = array();
		$processed = array_unique($lang);

		while ( count($processed) != count($lang) ) {
			$diff = count($lang) - count($processed);
			foreach ($lang as $key => $value) {
				if (!in_array($value, $values)) {
					$values[] = $value;
				} else {
					$lang[$key] = "$value-$diff";
				}
			}
			$processed = array_unique($lang);
		}
		$array[$lang_] = $lang;
		
	endforeach;
	
	return $array;
}



/**
 * Admin Settings Page HTML
 * 
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 * @return echoes output
 */
function qts_show_settings_page() {
	// get the settings sections array
	$settings_output = qts_get_settings();
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2><?php echo $settings_output['qts_page_title']; ?></h2>
		
		<p><?php  _e('If you activated previously the <a href="options-permalink.php">pretty permalinks</a>, in this section you can translate the <abbr title="en inglés, Universal Resource Locator">URLs</abbr> <strong>bases</strong> for <a href="http://codex.wordpress.org/Function_Reference/register_post_type#Arguments">public</a> post_types, categories, tags and taxonomies.', 'qts'); ?> </p>
		
		<form action="options.php" method="post">
			<?php 
			// upgrade notify
			qts_upgrade();
			// http://codex.wordpress.org/Function_Reference/settings_fields
			settings_fields($settings_output['qts_option_name']);
			// http://codex.wordpress.org/Function_Reference/do_settings_sections
			do_settings_sections(__FILE__);
			// rewrite rules
			flush_rewrite_rules();
			?>
			
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes','qts'); ?>" />
			</p>
			
		</form>
	</div><!-- wrap -->
<?php }



/**
 * Validate input
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0 
 * 
 * @return array
 */
function qts_validate_options($input) {
	
	// for enhanced security, create a new empty array
	$valid_input = array();
	
	// collect only the values we expect and fill the new $valid_input array i.e. whitelist our option IDs
	
		// get the settings sections array
		$settings_output = qts_get_settings();
		
		$options = $settings_output['qts_page_fields'];
		
		// run a foreach and switch on option type
		foreach ($options as $option):
		
			switch ( $option['type'] ):
				case 'text':
					//switch validation based on the class!
					switch ( $option['class'] ) {
						//for numeric 
						case 'numeric':
							//accept the input only when numeric!
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$valid_input[$option['id']] = (is_numeric($input[$option['id']])) ? $input[$option['id']] : 'Expecting a Numeric value!';
							
							// register error
							if(is_numeric($input[$option['id']]) == FALSE) {
								add_settings_error(
									$option['id'], // setting title
									QTS_PREFIX . '_txt_numeric_error', // error ID
									__('Expecting a Numeric value! Please fix.', 'qts'), // error message
									'error' // type of message
								);
							}
						break;
						
						//for multi-numeric values (separated by a comma)
						case 'multinumeric':
							//accept the input only when the numeric values are comma separated
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							
							if($input[$option['id']] !=''){
								// /^-?\d+(?:,\s?-?\d+)*$/ matches: -1 | 1 | -12,-23 | 12,23 | -123, -234 | 123, 234  | etc.
								$valid_input[$option['id']] = (preg_match('/^-?\d+(?:,\s?-?\d+)*$/', $input[$option['id']]) == 1) ? $input[$option['id']] : __('Expecting comma separated numeric values','qts');
							}else{
								$valid_input[$option['id']] = $input[$option['id']];
							}
							
							// register error
							if($input[$option['id']] !='' && preg_match('/^-?\d+(?:,\s?-?\d+)*$/', $input[$option['id']]) != 1) {
								add_settings_error(
									$option['id'], // setting title
									QTS_PREFIX . '_txt_multinumeric_error', // error ID
									__('Expecting comma separated numeric values! Please fix.','qts'), // error message
									'error' // type of message
								);
							}
						break;
						
						//for no html
						case 'nohtml':
							//accept the input only after stripping out all html, extra white space etc!
							$input[$option['id']] 		= sanitize_text_field($input[$option['id']]); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;
						
						//for url
						case 'url':
							//accept the input only when the url has been sanited for database usage with esc_url_raw()
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$valid_input[$option['id']] = esc_url_raw($input[$option['id']]);
						break;
						
						//for email
						case 'email':
							//accept the input only after the email has been validated
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							if($input[$option['id']] != ''){
								$valid_input[$option['id']] = (is_email($input[$option['id']])!== FALSE) ? $input[$option['id']] : __('Invalid email', 'qts');
							}elseif($input[$option['id']] == ''){
								$valid_input[$option['id']] = __('This setting field cannot be empty! Please enter a valid email address.', 'qts');
							}
							
							// register error
							if(is_email($input[$option['id']])== FALSE || $input[$option['id']] == '') {
								add_settings_error(
									$option['id'], // setting title
									QTS_PREFIX . '_txt_email_error', // error ID
									__('Please enter a valid email address.', 'qts'), // error message
									'error' // type of message
								);
							}
						break;
						
						// a "cover-all" fall-back when the class argument is not set
						default:
							// accept only a few inline html elements
							$allowed_html = array(
								'a' => array('href' => array (),'title' => array ()),
								'b' => array(),
								'em' => array (), 
								'i' => array (),
								'strong' => array()
							);
							
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$input[$option['id']] 		= force_balance_tags($input[$option['id']]); // find incorrectly nested or missing closing tags and fix markup
							$input[$option['id']] 		= wp_kses( $input[$option['id']], $allowed_html); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]); 
						break;
					}
				break;
				
				case "multi-text":
					// this will hold the text values as an array of 'key' => 'value'
					unset($textarray);
					
					$text_values = array();
					foreach ($option['choices'] as $k => $v ) {
						// explode the connective
						$pieces = explode("|", $v);
						
						$text_values[] = $pieces[1];
					}
					
					foreach ($text_values as $v ) {		
						
						// Check that the option isn't empty
						if (!empty($input[$option['id'] . '|' . $v])) {
							// If it's not null, make sure it's sanitized, add it to an array
							switch ($option['class']) {
								// different sanitation actions based on the class create you own cases as you need them
								
								//for numeric input
								case 'numeric':
									//accept the input only if is numberic!
									$input[$option['id'] . '|' . $v]= trim($input[$option['id'] . '|' . $v]); // trim whitespace
									$input[$option['id'] . '|' . $v]= (is_numeric($input[$option['id'] . '|' . $v])) ? $input[$option['id'] . '|' . $v] : '';
								break;
								
								case 'qts-slug':
									// strip all html tags and white-space.
									$type_ = end( explode('_', $option['id']) );
									$input[$option['id'] . '|' . $v]= sanitize_title( sanitize_text_field( $input[$option['id'] . '|' . $v] ) );
									$input[$option['id'] . '|' . $v]= addslashes($input[$option['id'] . '|' . $v]);
								break;
								
								// a "cover-all" fall-back when the class argument is not set
								default:
									// strip all html tags and white-space.
									$input[$option['id'] . '|' . $v]= sanitize_text_field($input[$option['id'] . '|' . $v]); // need to add slashes still before sending to the database
									$input[$option['id'] . '|' . $v]= addslashes($input[$option['id'] . '|' . $v]);
								break;
							}
							// pass the sanitized user input to our $textarray array
							$textarray[$v] = $input[$option['id'] . '|' . $v];
						
						} else {
							$textarray[$v] = '';
						}
					}
					// pass the non-empty $textarray to our $valid_input array
					if (!empty($textarray)) {
						$valid_input[$option['id']] = $textarray;
					}
				break;
				
				case 'textarea':
					//switch validation based on the class!
					switch ( $option['class'] ) {
						//for only inline html
						case 'inlinehtml':
							// accept only inline html
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$input[$option['id']] 		= force_balance_tags($input[$option['id']]); // find incorrectly nested or missing closing tags and fix markup
							$input[$option['id']] 		= addslashes($input[$option['id']]); //wp_filter_kses expects content to be escaped!
							$valid_input[$option['id']] = wp_filter_kses($input[$option['id']]); //calls stripslashes then addslashes
						break;
						
						//for no html
						case 'nohtml':
							//accept the input only after stripping out all html, extra white space etc!
							$input[$option['id']] 		= sanitize_text_field($input[$option['id']]); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;
						
						//for allowlinebreaks
						case 'allowlinebreaks':
							//accept the input only after stripping out all html, extra white space etc!
							$input[$option['id']] 		= wp_strip_all_tags($input[$option['id']]); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);
						break;
						
						// a "cover-all" fall-back when the class argument is not set
						default:
							// accept only limited html
							//my allowed html
							$allowed_html = array(
								'a' 			=> array('href' => array (),'title' => array ()),
								'b' 			=> array(),
								'blockquote' 	=> array('cite' => array ()),
								'br' 			=> array(),
								'dd' 			=> array(),
								'dl' 			=> array(),
								'dt' 			=> array(),
								'em' 			=> array (), 
								'i' 			=> array (),
								'li' 			=> array(),
								'ol' 			=> array(),
								'p' 			=> array(),
								'q' 			=> array('cite' => array ()),
								'strong' 		=> array(),
								'ul' 			=> array(),
								'h1' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h2' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h3' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h4' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h5' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ()),
								'h6' 			=> array('align' => array (),'class' => array (),'id' => array (), 'style' => array ())
							);
							
							$input[$option['id']] 		= trim($input[$option['id']]); // trim whitespace
							$input[$option['id']] 		= force_balance_tags($input[$option['id']]); // find incorrectly nested or missing closing tags and fix markup
							$input[$option['id']] 		= wp_kses( $input[$option['id']], $allowed_html); // need to add slashes still before sending to the database
							$valid_input[$option['id']] = addslashes($input[$option['id']]);							
						break;
					}
				break;
				
				case 'select':
					// check to see if the selected value is in our approved array of values!
					$valid_input[$option['id']] = (in_array( $input[$option['id']], $option['choices']) ? $input[$option['id']] : '' );
				break;
				
				case 'select2':
					// process $select_values
						$select_values = array();
						foreach ($option['choices'] as $k => $v) {
							// explode the connective
							$pieces = explode("|", $v);
							
							$select_values[] = $pieces[1];
						}
					// check to see if selected value is in our approved array of values!
					$valid_input[$option['id']] = (in_array( $input[$option['id']], $select_values) ? $input[$option['id']] : '' );
				break;
				
				case 'checkbox':
					// if it's not set, default to null!
					if (!isset($input[$option['id']])) {
						$input[$option['id']] = null;
					}
					// Our checkbox value is either 0 or 1
					$valid_input[$option['id']] = ( $input[$option['id']] == 1 ? 1 : 0 );
				break;
				
				case 'multi-checkbox':
					unset($checkboxarray);
					$check_values = array();
					foreach ($option['choices'] as $k => $v ) {
						// explode the connective
						$pieces = explode("|", $v);
						
						$check_values[] = $pieces[1];
					}
					
					foreach ($check_values as $v ) {		
						
						// Check that the option isn't null
						if (!empty($input[$option['id'] . '|' . $v])) {
							// If it's not null, make sure it's true, add it to an array
							$checkboxarray[$v] = 'true';
						}
						else {
							$checkboxarray[$v] = 'false';
						}
					}
					// Take all the items that were checked, and set them as the main option
					if (!empty($checkboxarray)) {
						$valid_input[$option['id']] = $checkboxarray;
					}
				break;
				
			endswitch;
		
		endforeach;
		
	return qts_sanitize_bases($valid_input);
}



/**
 * Helper function for creating admin messages
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0 
 *
 * src: http://www.wprecipes.com/how-to-show-an-urgent-message-in-the-wordpress-admin-area
 *
 * @param (string) $message The message to echo
 * @param (string) $msgclass The message class
 * @return echoes the message
 */
function qts_show_msg($message, $msgclass = 'info') {
	echo "<div id='message' class='$msgclass'>$message</div>";
}



/**
 * Callback function for displaying admin messages
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0 
 *
 * @return calls qts_show_msg()
 */
function qts_admin_msgs() {
	global $current_screen;
	
	// check for our settings page - need this in conditional further down
	$qts_settings_pg = isset($_GET['page']) ? strpos($_GET['page'], QTS_PAGE_BASENAME) : '';
	// collect setting errors/notices: //http://codex.wordpress.org/Function_Reference/get_settings_errors
	$set_errors = get_settings_errors(); 
	
	//display admin message only for the admin to see, only on our settings page and only when setting errors/notices are returned!	
	if(current_user_can ('manage_options') && $qts_settings_pg !== false && !empty($set_errors)){

		// have our settings succesfully been updated? 
		if($set_errors[0]['code'] == 'settings_updated' && isset($_GET['settings-updated'])){
			qts_show_msg("<p>" . $set_errors[0]['message'] . "</p>", 'updated');
		
		// have errors been found?
		}else{
			// there maybe more than one so run a foreach loop.
			foreach($set_errors as $set_error){
				// set the title attribute to match the error "setting title" - need this in js file
				qts_show_msg("<p class='setting-error-message' title='" . $set_error['setting'] . "'>" . $set_error['message'] . "</p>", 'error');
			}
		}
	}
}
add_action('admin_notices', 'qts_admin_msgs');
