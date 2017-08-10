<?php

use SeanJohn\Utils\Utils;
// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// check if class already exists
if( !class_exists('acf_field_instagram') ) :

	class acf_field_instagram extends acf_field {


		/*
		*  __construct
		*
		*  This function will setup the field type data
		*
		*  @type	function
		*  @date	5/03/2014
		*  @since	5.0.0
		*
		*  @param	n/a
		*  @return	n/a
		*/

		function __construct($settings) {

			/*
			*  name (string) Single word, no spaces. Underscores allowed
			*/

			$this->name = 'instagram';


			/*
			*  label (string) Multiple words, can include spaces, visible when selecting a field type
			*/

			$this->label = __('Instagram', 'acf-instagram');


			/*
			*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
			*/

			$this->category = __('Social Media', 'acf-instagram');


			/*
			*  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
			*/

			$this->defaults = array(
				'client_id' => '',
				'client_secret' => '',
				'cache_lifetime' => 300
			);

			$this->default_values = [
				'shortcode' => '',
				'media' => '',
				'raw_json' => ''
			];

			/*
			*  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
			*  var message = acf._e('tweet', 'error');
			*/

			$this->l10n = array(
				'error'	=> __('Error! Please enter a higher value', 'acf-instagram'),
			);

			$this->settings = $settings;

			// do not delete!
	    	parent::__construct();

		}


		/*
		*  render_field_settings()
		*
		*  Create extra settings for your field. These are visible when editing a field
		*
		*  @type	action
		*  @since	3.6
		*  @date	23/01/13
		*
		*  @param	$field (array) the $field being edited
		*  @return	n/a
		*/

		function render_field_settings( $field ) {

			/*
			*  acf_render_field_setting
			*
			*  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
			*  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
			*
			*  More than one setting can be added by copy/paste the above code.
			*  Please note that you must also have a matching $defaults value for the field name (font_size)
			*/

			acf_render_field_setting( $field, array(
				'required'  => true,
				'label'			=> __('Client Id','acf-instagram'),
				'instructions'	=> __('Create an Instagram app and get your credentials at: ','acf-instagram') . ' <a href="http://instagram.com/developer/">http://instagram.com/developer/</a>',
				'type'			=> 'text',
				'name'			=> 'client_id'
			));

			acf_render_field_setting( $field, array(
				'required'  => true,
				'label'			=> __('Client Secret','acf-instagram'),
				'type'			=> 'text',
				'name'			=> 'client_secret'
			));

			acf_render_field_setting( $field, array(
				'required'  => true,
				'label'			=> __('Cache lifetime','acf-instagram'),
				'instructions'	=> __('Number of seconds before we try to fetch the media info again','acf-instagram'),
				'default_value'  => 300,
				'min'       => 0,
				'max'       => 1000000,
				'type'			=> 'number',
				// '_append'		=> 'sec.',
				'name'			=> 'cache_lifetime'
			));

		}



		/*
		*  render_field()
		*
		*  Create the HTML interface for your field
		*
		*  @param	$field (array) the $field being rendered
		*
		*  @type	action
		*  @since	3.6
		*  @date	23/01/13
		*
		*  @param	$field (array) the $field being edited
		*  @return	n/a
		*/

		function render_field( $field ) {

			/*
			*  Create a simple text input
			*/
			// d($field);
			// validate value
			Utils::data2file(get_template_directory() . '/dump.json', $field);
			if( empty($field['value']) ) {
				
				$field['value'] = wp_parse_args($field['value'], $this->default_values);
				
			}

			?>

			
			<input type="text" name="<?php echo esc_attr($field['name']) ?>" value="<?php echo esc_attr($field['value']['shortcode']) ?>" />
			<?php


			if ( $media = $field['value']['media'] ) {
				$html = '';

				// Check Transient
				$html_transient = 'instagram-html-'.$media['id'];
				if ( false === ( $media_html = get_transient( $html_transient ) ) ) {

					require_once(dirname(__FILE__) . '/Instagram.php');

						$instagram = new MetzWeb\Instagram\Instagram($field['client_id']);
						$response = json_decode(json_encode($instagram->getoEmbed($media['link'])), true);

						if ( $response ) {

							$html = $response['html'];
							// Save Transient
							set_transient( $html_transient, $response['html'], 300 );

						} else {
							throw new \Exception($response['meta']['error_type'] . ':' . $response['meta']['code'] . ':' . $response['meta']['error_message']);
						}

				} else {
					$html = $media_html;
				}

				echo '<div class="instagram_embed">';
				echo $html;
				echo '</div>';

			}

		}


		/*
		*  input_admin_enqueue_scripts()
		*
		*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
		*  Use this action to add CSS + JavaScript to assist your render_field() action.
		*
		*  @type	action (admin_enqueue_scripts)
		*  @since	3.6
		*  @date	23/01/13
		*
		*  @param	n/a
		*  @return	n/a
		*/



		function input_admin_enqueue_scripts() {
		
			// vars
			$url = $this->settings['url'];
			$version = $this->settings['version'];
			
			
			// register & include JS
			// wp_register_script( 'acf-input-instagram', "{$url}assets/js/input.js", array('acf-input'), $version );
			// wp_enqueue_script('acf-input-instagram');
			
			
			// register & include CSS
			wp_register_style( 'acf-input-instagram', "{$url}css/input.css", array('acf-input'), $version );
			wp_enqueue_style('acf-input-instagram');
			
		}


		/*
		*  load_value()
		*
		*  This filter is applied to the $value after it is loaded from the db
		*
		*  @type	filter
		*  @since	3.6
		*  @date	23/01/13
		*
		*  @param	$value (mixed) the value found in the database
		*  @param	$post_id (mixed) the $post_id from which the value was loaded
		*  @param	$field (array) the field array holding all the field options
		*  @return	$value
		*/


		function load_value( $value, $post_id, $field ) {
			if (!isset($value)) {
				$value = [];
			}
			$value = wp_parse_args($value, $this->default_values);
			$value = apply_filters('acf/load_value/type=' . $field['type'], $value, $post_id, $field);
			
			return $value;
		}


		/*
		*  update_value()
		*
		*  This filter is applied to the $value before it is saved in the db
		*
		*  @type	filter
		*  @since	3.6
		*  @date	23/01/13
		*
		*  @param	$value (mixed) the value found in the database
		*  @param	$post_id (mixed) the $post_id from which the value was loaded
		*  @param	$field (array) the field array holding all the field options
		*  @return	$value
		*/


		function update_value( $value, $post_id, $field ) {
			if (!isset($value)) {
				$value = [];
			}
			$value = wp_parse_args($value, $this->default_values);

			// Check Transient
			$transient_name = 'instagram-media-'.$value['shortcode'];
			if ( false === ( $media = get_transient( $transient_name ) ) ) {

				// Fetch Media info
				if ( $field['client_id'] && $field['client_secret'] ) {

					require_once(dirname(__FILE__) . '/Instagram.php');

					$instagram = new MetzWeb\Instagram\Instagram($field['client_id']);
					$response = $instagram->getMediaShortcode( $value['shortcode'] );

					if ( $response->meta->code == 200 ) {

						// Save Media Object
						$json = base64_encode(json_encode($response->data));
						$value['raw_json'] = $json;

						// Save Transient
						set_transient( $transient_name, $json, $field['cache_lifetime'] );

					} else {
						throw new \Exception($response->meta->error_type . ':' . $response->meta->code . ':' . $response->meta->error_message);
					}
				}

			} else {

				$value['raw_json'] = $media;

			}

			return $value;
		}

		/*
		*  format_value
		*
		*  @description: uses the basic value and allows the field type to format it
		*  @since: 3.6
		*  @created: 26/01/13
		*/
		
		function format_value( $value, $post_id, $field )
		{
			if (is_array($value) && isset($value['raw_json'])) {
				$value['media'] = json_decode(base64_decode($value['raw_json']), true);
			}
			$value = apply_filters('acf/format_value/type=' . $field['type'], $value, $post_id, $field);
			
			return $value;
		}

	}

	new acf_field_instagram($this->settings);
 // class_exists check
	endif;
?>
