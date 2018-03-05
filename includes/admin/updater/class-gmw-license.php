<?php
/**
 * License handler for GEO my WP
 *
 * This class should simplify the process of adding license information
 * to GEO my WP add-ons.
 * 
 * @author Eyal Fitoussi. Inspired by a class written by Pippin Williamson
 * @version 1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

//abort if this page already loaded
if ( !class_exists( 'GMW_License' ) ) :
	
/**
 * GMW_License Class
 * 
 * Responsiable for updates of the premium add-ons as well for the action links
 * of the Plugins page.
 * 
*/
class GMW_License {

	private $file;
	private $license_name;
	private $item_name;
	private $item_id;
	private $license_key;
	private $version;
	private $author;
	private $api_url = 'https://geomywp.com';

	/**
	 * Class constructor
	 *
	 * @param string  $_file
	 * @param string  $_item_name
	 * @param string  $_version
	 * @param string  $_author
	 * @param string  $_optname
	 * @param string  $_api_url
	 */					
	function __construct( $_file, $_item_name, $_license_name, $_version, $_author = 'Eyal Fitoussi' , $_api_url = null, $_item_id = null ) {

		$this->file           = $_file;
		$this->license_name   = $_license_name;
		$this->item_name      = $_item_name;
		$this->item_id        = $_item_id;
		$this->license_key    = gmw_get_license_data( $_license_name );
		$this->license_status = gmw_get_license_data( $_license_name, 'status' );
		$this->version        = $_version;
		$this->author         = $_author;
		$this->api_url        = is_null( $_api_url ) ? $this->api_url : $_api_url;

		//action links
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this, 'extension_action_links' ), 10 );
		add_action( 'after_plugin_row_' . plugin_basename( $this->file ), array( $this, 'license_key_element' ), 10 );
		
		// Setup hooks
		$this->includes();
		$this->auto_updater();	
	}
		
	/**
	 * add gmw add-ons action links in plugins page
	 * @param  $links
	 * @return $links
	 */
	public function extension_action_links( $links ) {
			
		//if license is not activated display the "Activate License" message
		if ( empty( $this->license_key ) || $this->license_status != 'valid' ) {
			return $links;
		} 
		
		//if license activate display "Diactivate license before...." message
		$links['deactivate'] = __( 'Deactivate the license key before deactivating the plugin', 'geo-my-wp' );
						
		return $links;
	}
	
	/**
	 * Append license key input box in plugins page
	 * @return [type] [description]
	 */
	public function license_key_element() {
		
		$license_key = new GMW_License_Key( 
			$this->file, 
			$this->item_name, 
			$this->license_name, 
			$this->item_id 
		);
		
		$license_key->license_key_output();	 
	}

	/**
	 * Include the updater class
	 *
	 * @access  private
	 * @return  void
	 */
	private function includes() {
		if ( ! class_exists( 'GMW_Premium_Plugin_Updater' ) ) {
			require_once 'class-gmw-plugins-updater.php';
		}
	}

	/**
	 * Auto updater
	 *
	 * @access  private
	 * @return  void
	 */
	private function auto_updater() {

		if ( empty( $this->license_key ) ) {
			return;
		}

		if ( $this->license_status != 'valid' ) {
			return;
		}

		// Setup the updater
		$gmw_updater = new GMW_Premium_Plugin_Updater(
			$this->api_url,
			$this->file,
			array(
				'version'   => $this->version,
				'license'   => $this->license_key,
				'item_name' => $this->item_name,
				'item_id'	=> $this->item_id,
				'author'    => $this->author
			)
		);
	}
}

/**
 * GMW_License_Key input field Class
 * 
 * create input field for a license key
 */
class GMW_License_Key {

	private $file;
	private $item_name;
	private $license_name;
	private $item_id;
	private $basename;
	private $messages;
	
	/**
	 * Temporary items id holder. 
	 *
	 * This is for older verison where the item ID is not provided
	 * with the extension
	 * 
	 * @var array
	 */
	public $item_ids = array( 
		'xprofile_fields' 		 	  => 670,
		'formidable_geolocation' 	  => 54725,
		'global_maps'			 	  => 2602,
		'gravity_forms_geo_fields' 	  => 2273,
		'bp_groups_locator'			  => 4647,
		'gmw_kleo_geolocation'		  => 42902,
		'nearby_posts'				  => 7991,
		'premium_settings'			  => 668,
		'geo_job_manager'			  => 5417,
		'wp_users_geo-location'		  => 11188,
		'resume_manager_geo-location' => 8547,
		'geo_members_directory'       => 2347,
		'exclude_members' 			  => 800
	);

	/**
	 * [__construct description]
	 * @param [type] $file         [description]
	 * @param [type] $item_name    [description]
	 * @param [type] $license_name [description]
	 * @param [type] $item_id      [description]
	 */
	public function __construct( $file ,$item_name, $license_name, $item_id = null ) {
		
		$this->file      	  = basename( dirname( $file ) );
		$this->basename		  = plugin_basename( $file );
		$this->item_name	  = $item_name;
		$this->item_id		  = $item_id;
		$this->license_name   = $license_name;
		$this->license_key    = gmw_get_license_data( $license_name );
		$this->license_status = gmw_get_license_data( $license_name, 'status' );
		$this->messages		  = gmw_license_update_notices();

		// if item ID missing get it from the array of items id
		if ( empty( $this->item_id ) && ! empty( $this->item_ids[$license_name] ) ) {
			$this->item_id = $this->item_ids[$license_name];
		}
	}

	/**
	 * Generate license key element
	 * 
	 * @return [type] [description]
	 */
	public function get_license_key_element() {
		
		// check if in plugins page
		$plugins_page = ( ! empty( get_current_screen()->base ) && get_current_screen()->base == 'plugins' ) ? true : false;

		$output = '';
		
		$license_name  = esc_attr( $this->license_name );
		$item_name     = esc_attr( $this->item_name );
		$item_id       = esc_attr( $this->item_id );
		$basename      = esc_attr( $this->basename );
		$nonce 		   = wp_create_nonce( 'gmw_'.$license_name.'_license_nonce' );
		$license_value = ! empty( $this->license_key ) ? esc_attr( sanitize_text_field( $this->license_key ) ) : '';
	 	
		// if license valid
		if ( ! empty( $this->license_key ) && $this->license_status == 'valid' ) {	
			
			// generate data
			$action  = 'deactivate_license'; 
			$button  = 'button-secondary';
			$label   = __( 'Deactivate License', 'geo-my-wp' );
			$message = esc_html( $this->messages['valid'] );
			$icon    = '<i class="dashicons dashicons-yes"></i>';
			$status  = 'valid';

			// hidden input fields
			$key_field = '<input class="gmw-license-key-disabled" disabled="disabled" type="text" size="31" value="'.$license_value.'" />';
			$key_field .= '<input type="hidden" class="gmw-license-key" name="gmw_licenses['.$license_name.'][license_key]" value="'.$license_value.'" />';
				
		} else { 

			// generate data
			$action = 'activate_license';
			$class   = '';
			$message = $this->messages['activate'];
			$button  = 'button-primary';
			$label   = __( 'Activate License', 'geo-my-wp' );
			$allow   = array( 'a' => array( 'href'  => array(), 'title' => array() ) );
			$message = wp_kses( $message, $allow );	
			$icon    = '<i class="dashicons dashicons-warning"></i>';
			$status  = 'inactive';

			// generate error message
			if ( ! empty( $this->license_key ) && ! empty( $this->license_status ) && $this->license_status != 'inactive' ) {
				
				$status  .= ' gmw-license-error';
				$message  = array_key_exists( $this->license_status, $this->messages ) ? $this->messages[$this->license_status] : $this->messages['missing'];	
			} 
			
			// generate input fields									
			$key_field = '<input  class="gmw-license-key" name="gmw_licenses['.$license_name.'][license_key]" type="text" class="regular-text" size="31" placeholder="'.__( 'License key', 'geo-my-wp' ).'" value="'.$license_value.'" />';
			
		}
		
		$field_data    = '';
		$license_label = __( 'License: ', 'geo-my-wp' );

		// if not in plugins page
		if ( ! $plugins_page ) {
			$field_data = 'data-action="'.$action.'" data-license_name="'.$license_name.'" data-item_id="'.$item_id.'" data-item_name="'.$item_name.'" data-nonce="'.$nonce.'" data-basename="'.$basename.'"';

			$license_label = '';
		}

		// generate the license element
		$output .= '<div class="gmw-license-wrapper '.$status.'">';
		$output .= '<div class="field-wrapper">';
		$output .= '<span class="gmw-icon-key">'.$license_label.'</span>';
		$output .= $key_field;
		$output .= '</div>';
		$output .= '<div class="actions-wrapper">';
		$output .= '<button type="submit" name="gmw_license_submit" class="'.$button.' '.$action.' gmw-license-action-button" style="padding: 0 9px !important;" value="'.$license_name.'" '.$field_data.'>'.$label.'</button>';
		$output .= '<span style="display: none" class="button processing actions-message"></span>';
		$output .= '</div>';

		$output .= '<p class="description">'.$icon.$message.'</p>';

		$output .= '<input type="hidden" name="gmw_licenses['.$license_name.'][action]" value="'.$action.'" />';
		$output .= '<input type="hidden" name="gmw_licenses['.$license_name.'][nonce]" value="'.$nonce.'" />';
		$output .= '<input type="hidden" name="gmw_licenses['.$license_name.'][license_name]" value ="'.$license_name.'" />';
		$output .= '<input type="hidden" name="gmw_licenses['.$license_name.'][item_id]" value="'.$item_id.'" />';
		$output .= '<input type="hidden" name="gmw_licenses['.$license_name.'][item_name]" value="'.$item_name.'" />';
		$output .= '</div>';
							
		return $output;
	}

	/**
	 * Display license key field in plugins page
	 * 
	 */
	public function license_key_output() {

		$file = esc_attr( $this->file );
		?>
		<tr id="<?php echo $file; ?>-license-key-row" class="gmw-license-key-row">
			
			<td class="plugin-update" colspan="3">
		
				<?php echo $this->get_license_key_element(); ?>

				<script>
					jQuery( function($) {
						
						onkeydown="if (event.keyCode == 13) { jQuery(this).closest(\'form\').find( \'.activate-license-btn\' ).click(); return false; }"

						$( 'tr#<?php echo $file; ?>-license-key-row' ).prev().addClass( 'gmw-license-key-addon-wrapper' );
						
						if ( $( 'tr#<?php echo $file; ?>-license-key-row' ).prev().hasClass( 'update' ) ) {
							
							$( 'tr#<?php echo $file; ?>-license-key-row' ).addClass( 'update' ); 
						}	

						$( 'tr#<?php echo $file; ?>-license-key-row' ).find( '.gmw-license-action-button' ).click( function() {

							jQuery( this ).closest( 'tr' ).prev( 'tr' ).find( 'th input[type=checkbox]' ).prop( 'checked', true );
						});

						$( 'tr#<?php echo $file; ?>-license-key-row' ).find( '.gmw-license-key' ).on( 'keydown', function( e ) {
							if ( e.keyCode == 13 ) {
								jQuery( this ).closest( 'tr' ).prev( 'tr' ).find( 'th input[type=checkbox]' ).prop( 'checked', true );
							}
						});

					});
				</script>

			</td>
		</tr>

		<?php 
		if ( ! wp_style_is( 'gmw-updater', 'enqueued' ) ) {
			wp_enqueue_style( 'gmw-updater', untrailingslashit( plugins_url( '', __FILE__ ) ) .'/assets/css/gmw.updater.css', array() );
		}
	}
}

/**
 * [gmw_get_license_data Get license key or status
 * 
 * @param  string $license_name license slug/name
 * @param  string $data         key || status
 * 
 * @return [type]               
 */
function gmw_get_license_data( $license_name = '', $data = 'key' ) {

	if ( empty( $license_name ) ) {
		return false;
	}

	$license_keys = get_option( 'gmw_license_data' );

	if ( $data == 'status' ) {
		$output = ! empty( $license_keys[$license_name]['status'] ) ? $license_keys[$license_name]['status'] : 'inactive';
	} else {

		$output = ! empty( $license_keys[$license_name]['key'] ) ? trim( $license_keys[$license_name]['key'] ) : '';
	}
				
	return $output;
}

/**
 * Check license status
 * @param unknown_type $addon
 * @return boolean
 */
function gmw_is_license_valid( $addon ) {

	$license_keys = get_option( 'gmw_license_data' );

	if ( ! empty( $license_keys[$addon]['key'] ) && ! empty( $license_keys[$addon]['status'] ) && $license_keys[$addon]['status'] == 'valid' ) {
		return true;
	} else {
		return false;
	}
}

/**
 * GMW Cheack Licenses
 * 
 * Do check of licenses every 72 hours to varify that thier status is correct
 * 
 * @since  2.5
 * @author Eyal Fitoussi
 */
function gmw_check_license() {

	//run licenses check every 24 hours just to make sure that their status is correct
	if ( get_transient( 'gmw_verify_license_keys' ) == true ) {
		return;
	}
	
	//set new transient
	set_transient( 'gmw_verify_license_keys', true, DAY_IN_SECONDS );
		
	// get license keys 
	$license_keys = get_option( 'gmw_license_data' );
	
	if ( empty( $license_keys ) ) {
		return;
	}

	// loop through and check all license keys
	foreach ( $license_keys as $license_name => $values ) {
		
		// key addon data
		$addon_data 	= gmw_get_addon_data( $license_name );
		$license_key    = trim( $values['key'] );
		$license_status = $values['status'];

		if ( ! empty( $license_key ) ) {
								
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license_key,
				'item_id'    => ! empty( $addon_data['item_id'] ) ? absint( $addon_data['item_id'] ) : '',
				'url'        => home_url(),
				'item_name'	 => ! empty( $addon_data['item_name'] ) ? urlencode( $addon_data['item_name'] ) : '',
			);
		
			// Call the custom API.
			$response = wp_remote_post( 
				GMW_REMOTE_SITE_URL, 
				array(
				'timeout' 	=> 15,
				'sslverify' => false,
				'body'		=> $api_params
			));
			
			if ( is_wp_error( $response ) ) {
				return false;
			}
			
			// get license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// update license status if changed.
			if ( $license_data->license != $license_status ) {
				$license_keys[$license_name]['status'] = $license_data->license;
			}
		}
	}

	// udpate new data to database
	update_option( 'gmw_license_data', $license_keys );

}
add_action( 'admin_init', 'gmw_check_license');

/**
 * GMW Update license key API activate/deactivate
 *
 * @since  2.5
 * @author Eyal Fitoussi
 */
function gmw_license_key_actions( $form_args = array() ) {
	
	// default args
	$defaults = array(
		'action'       => 'activate_license',
		'license_name' => false,
		'item_id'	   => false,
		'license_key'  => '',
		'item_name'    => false
	);

	$form_args = wp_parse_args( $form_args, $defaults );

	// verify that at least item name and license data exist
	if ( empty( $form_args['item_id'] ) || empty( $form_args['license_name'] ) ) {
		return;
	}

	// get licenses data from database
	$license_keys = get_option( 'gmw_license_data' );

	$action		  = $form_args['action'];
	$license_name = $form_args['license_name'];
	$license_key  = sanitize_text_field( trim( $form_args['license_key'] ) );
	$item_name	  = $form_args['item_name'];
	$item_id	  = ! empty( $form_args['item_id'] ) ? $form_args['item_id'] : false;
	$license_data = ( object ) array();

	//if license key field is empty and trying to activate, clear key in database
	if ( empty( $license_key ) && $action == 'activate_license' ) {
		
		unset( $license_keys[$license_name] );

		update_option( 'gmw_license_data', $license_keys );
		
		$license_data->license_name      = $form_args['license_name'];
		$license_data->notice_message    = 'no_key_entered';
		$license_data->notice_action     = 'error';
		$license_data->remote_connection = 'blank_key';

		return $license_data;	
	}
	
	if ( empty( $license_key ) ) {
		return $license_data;
	}
	
	// data to send in our API request
	$api_params = array(
		'edd_action' => $action,
		'license'    => $license_key,
		'item_name'  => urlencode( $item_name ),
		'item_id'	 => $item_id
	);

	// Call the custom API.
	$response = wp_remote_post( 
		GMW_REMOTE_SITE_URL,
		array( 
			'timeout'   => 15, 
			'sslverify' => false, 
			'body' 		=> $api_params 
		) 
	);

	// If connection failed
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

		$license_data = $response;
		$license_data->remote_connection = false;
		$license_data->license_name   	 = $form_args['license_name'];
		$license_data->notice_message 	 = 'connection_failed';
		$license_data->notice_action  	 = 'error';

		/*if ( is_wp_error( $response ) ) {
			
			$license_data = $response->get_error_message();
		
		} else {
		
			$license_data = __( 'An error occurred, please try again.' );
		} */

	// otherwise, if succeed
	} else {

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		/*
		if ( false === $license_data->success ) {

			switch( $license_data->error ) {

				case 'expired' :

					$message = sprintf(
						__( 'Your license key expired on %s.' ),
						date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
					);
					break;

				case 'revoked' :

					$message = __( 'Your license key has been disabled.' );
					break;

				case 'missing' :

					$message = __( 'Invalid license.' );
					break;

				case 'invalid' :
				case 'site_inactive' :

					$message = __( 'Your license is not active for this URL.' );
					break;

				case 'item_name_mismatch' :

					$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), EDD_SAMPLE_ITEM_NAME );
					break;

				case 'no_activations_left':

					$message = __( 'Your license key has reached its activation limit.' );
					break;

				default :

					$message = __( 'An error occurred, please try again.' );
					break;
			}

		} */

		$license_data->remote_connection = true;
		$license_data->license_name 	 = $form_args['license_name'];

		if ( $license_data->license == 'valid' ) {
			
			$license_data->notice_message = 'activated';
			$license_data->notice_action  = 'updated';

			$license_keys[$license_name]['key']    = $license_key;
			$license_keys[$license_name]['status'] = 'valid';
			
			update_option( 'gmw_license_data', $license_keys );
				
		} elseif ( $license_data->license == 'invalid' ) {
			
			$license_data->notice_message = $license_data->error;
			$license_data->notice_action  = 'error';

			$license_keys[$license_name]['key']    = $license_key;
			$license_keys[$license_name]['status'] = $license_data->error;
			
			update_option( 'gmw_license_data', $license_keys );
					
		} elseif ( $license_data->license == 'deactivated' || $license_data->license == 'failed' ) {
			
			$license_data->notice_message = 'deactivated';
			$license_data->notice_action  = 'updated';
			
			$license_keys[$license_name]['key']    = $license_key;
			$license_keys[$license_name]['status'] = 'inactive';
			
			update_option( 'gmw_license_data', $license_keys );
		}
	}

	return $license_data;
}

/**
 * To be used with license key action on page load
 * 
 * @return [type] [description]
 */
function gmw_pre_license_key_actions() {

	//check for license data
	if ( empty( $_POST['gmw_license_submit'] ) || empty( $_POST['gmw_licenses'][$_POST['gmw_license_submit']] ) ) {
		return;
	}
	
	// current page
	$page = ( isset( $_GET['page'] ) && $_GET['page'] == 'gmw-extensions' ) ? 'admin.php?page=gmw-extensions&' : 'plugins.php?';

	// get license data
	$license_data = $_POST['gmw_licenses'][$_POST['gmw_license_submit']];

	// varify nonce
	if ( empty( $license_data['nonce'] ) || ! wp_verify_nonce( $license_data['nonce'], 'gmw_'.$license_data['license_name'].'_license_nonce' ) ) {
		wp_die( __( 'Cheatin\' eh?!', 'geo-my-wp' ) );
    }

	// run license action
	$license_data = gmw_license_key_actions( $license_data );

	$url = $page.'gmw_license_status_notice='.esc_attr( $license_data->notice_message ).'&license_name='.esc_attr( $license_data->license_name ).'&gmw_notice_status='.esc_attr( $license_data->notice_action );
	
	//reload the page to prevent resubmission
	wp_safe_redirect( 
		admin_url( $url ) 
	);

	exit;
}
add_action( 'admin_init', 'gmw_pre_license_key_actions' );

/**
 * Messages for license status and notices
 * 
 * @since  2.5
 * 
 * @author Eyal Fitoussi
 */
function gmw_license_update_notices() {
	
	return $messages = apply_filters( 'gmw_license_update_notices', array(
		'activate'				=> __( 'Activate your license key to receive support and updates.', 'geo-my-wp' ),
		'activated'				=> __( 'License activated. Thank you for your support!', 'geo-my-wp' ),
		'deactivated'			=> __( 'License deactivated.', 'geo-my-wp' ),
		'valid'					=> __( 'License activated. Thank you for your support!', 'geo-my-wp' ),
		'no_key_entered'		=> __( 'No license key entered.', 'geo-my-wp' ),
		'expired' 				=> sprintf( __( 'Your license has expired. <a href="%s" target="_blank">Renew your license</a> to receive updates and support.', 'geo-my-wp' ), 'https://geomywp.com/your-account/' ),
		'revoked' 				=> sprintf( __( 'Your license has been disabled. Contact <a href="%s" target="_blank">support</a> for more information.', 'geo-my-wp' ), 'https://geomywp.com/support/#extension-support' ),
		'missing'				=> sprintf( __( 'Something wrong with the license key you entered. <a href="%s" target="_blank">Verify your key</a> and try again.', 'geo-my-wp' ), 'https://geomywp.com/your-account/' ),
		'invalid'				=> __( 'Your license is not active for this URL.', 'geo-my-wp' ),
		'site_inactive'			=> __( 'Your license is not active for this URL.', 'geo-my-wp' ),
		'invalid_item_id'		=> __( 'The license key you entered does not belong to this extension.', 'geo-my-wp' ),
		'item_name_mismatch'	=> __( 'An error occurred while trying to activate your license. ERROR item_name_mismatch', 'geo-my-wp' ),
		'no_activations_left' 	=> sprintf( __( 'Your license key has reached its activation limit. <a %s>Manage licenses</a>.', 'geo-my-wp' ), 'href="https://geomywp.com/your-account/" target="_blank"' ),
		
		'retrieve_key'			=> sprintf( __( 'Lost or forgot your license key? <a %s >Retrieve it here.</a>', 'geo-my-wp' ), 'href="http://geomywp.com/purchase-history/" target="_blank"' ),
		'activation_error'		=> __( 'Your license for %s plugin could not be activated. See error message below.', 'geo-my-wp' ),
		'default'				=> sprintf( __( 'An error occurred. Try again or contact <a href="%s" target="_blank">support</a>.', 'geo-my-wp' ), 'https://geomywp.com/support/#extension-support' ),
		'connection_failed' 	=> sprintf( __( 'Connection to remote server failed. Try again or contact <a href="%s" target="_blank">support</a>.', 'geo-my-wp' ), 'https://geomywp.com/support/#general-questions' ),
			
	) );
}

/**
 * Generate notices
 * @return [type] [description]
 */
function gmw_display_license_update_notice() {

	//check if updating license key
	if (  empty( $_GET['gmw_license_status_notice'] ) ) {
		return;
	}

	$messages = gmw_license_update_notices();
	$message  = ! empty( $messages[$_GET['gmw_license_status_notice']] ) ? $messages[$_GET['gmw_license_status_notice']] : $messages['default'];
	$allow    = array( 'a' => array( 'href'  => array() ) );
	$message  = wp_kses( $message , $allow );	
	?>
	<div class="<?php echo $_GET['gmw_notice_status']; ?>">
		<p>
			<?php echo $message; ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'gmw_display_license_update_notice' );

endif;