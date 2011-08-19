<?php
/*
Plugin Name: 		UMG Single Sign On
Plugin URI: 		http://www.pushentertainment.com
Description: 		Single Sign On powered by JanRain's Capture/SSO with Backplane support
Version: 			1.0.3
Author: 			Push Entertainment
Author URI: 		http://www.pushentertainment.com
*/


/**
 * Don't expose anything if called directly
 */
if(!function_exists('add_action')) {
	exit;
}

/**
* Activate the plugin, create our default options
*/
function umg_sso_activate()
{
    $variables = array(
        'capture_addr' => 'https://profile.universalmusic.com',
        'sso_server' => 'https://sso.universalmusic.com'
    );
    add_option('umg_sso_options', $variables, '', 'no');
}
register_activation_hook( __FILE__, 'umg_sso_activate');

/**
 * INIT plugin
 */
add_action('init', 'umg_sso_init');
add_action('admin_menu', 'umg_sso_admin_menu');
add_action('admin_init', 'umg_sso_admin_init');
add_action('wp_head', 'umg_sso_wp_head', 20);
add_action('wp_footer', 'umg_sso_wp_footer', 20);
add_shortcode('umg-sso', 'umg_sso_print_login');

function umg_sso_init()
{
	if(!is_admin()) {
		session_start();	
		$options = get_option('umg_sso_options');
		require_once('api.php');
		
        wp_enqueue_script('jquery');
	  	wp_register_script('umg_backplane', 'http://cache.umusic.com/web_assets/_global/js/echo/backplane.js', array(), NULL);
	   	wp_enqueue_script('umg_backplane');
		add_action('wp_print_styles', 'enqueue_umg_sso_styles');
		
		$user_entity = load_user_entity();
		if (isset($user_entity)) {
		    if (isset($user_entity['stat']) && $user_entity['stat'] == 'ok') {
		    	
		    	// get user entity from Capture SSO
				$user_entity = $user_entity['result'];
		    	$capture_session = capture_session();
				$access_token = $capture_session['access_token'];
				$args = array( 'token'       => $access_token,
			                 	'callback'    => 'CAPTURE.closeProfileEditor',
			                 	'xd_receiver' => WP_PLUGIN_URL . "/umg-sso/xdcomm.html");
				$_SESSION['umg-sso']['args'] = $args;
				$_SESSION['umg-sso']['user_entity'] = $user_entity;
				
				// log user into WP
				require_once(ABSPATH . WPINC . '/registration.php');
				$new_user = false;
				// use the email or preferredUsername for the users login
		        $email = (isset($user_entity['email']) && trim($user_entity['email']) != '') ? $user_entity['email'] : '';
		        $username = (isset($user_entity['preferredUserName']) && trim($user_entity['preferredUserName']) != '') ? $user_entity['preferredUserName'] : '';
		        $user_id = 0;
		        // check if this user already exists, try to load their data
		        if($user = email_exists($email)) {
		            $user_id = $user;
		        } else {
		            // user data not found so create them an account
		            $new_user = true;
		            $random_password = wp_generate_password(12, false);
		            $user_id = wp_create_user($username, $random_password, $email);
		        }
		        // log in the user automatically with no password
		        wp_set_current_user($user_id);
		        wp_set_auth_cookie($user_id);
		        
		        // store/update user meta data from capture profile
		        update_user_meta($user_id, 'capture_id', $user_entity['id']);
		        update_user_meta($user_id, 'capture_uuid', $user_entity['uuid']);
				update_user_meta($user_id, 'first_name', $user_entity['firstName']);
		        update_user_meta($user_id, 'last_name', $user_entity['lastName']);
		        update_user_meta($user_id, 'gender', $user_entity['gender']);
		        update_user_meta($user_id, 'date_of_birth', $user_entity['birthdate']);
		        // profile pics
		        foreach($user_entity['photos'] as $photo) {
		        	if($photo['type'] == 'square_thumbnail') {
				        update_user_meta($user_id, 'capture_photo_square_thumbnail', $photo['value']);	
		        	}
		        	if($photo['type'] == 'normal') {
				        update_user_meta($user_id, 'capture_photo_normal', $photo['value']);	
		        	}
		        	if($photo['type'] == 'large') {
				        update_user_meta($user_id, 'capture_photo_large', $photo['value']);	
		        	}
		        }

		        if($new_user) {
		        	// can do something different for new users if required
		        }
			} else {
				debug_out("*** Bad entity!<br>\n");
			    debug_raw_data($user_entity);
			}
	  	} else {
	  		// user is not logged in via Capture SSO
			$args = array ( 'response_type'   => 'code',
		                  	'redirect_uri'    => WP_PLUGIN_URL . "/umg-sso/oauth_redirect.php",
		                  	'client_id'       => $options['client_id'],
		                  	'xd_receiver'     => WP_PLUGIN_URL . "/umg-sso/xdcomm.html");
			$_SESSION['umg-sso']['args'] = $args;
			$_SESSION['umg-sso']['user_entity'] = false;
	  	}
	}
}

/**
 * INIT Options
 */
function umg_sso_admin_menu() 
{
	add_options_page('Single Sign On Page', 'UMG Single Sign On', 'manage_options', 'umg_sso', 'umg_sso_options_page');
}
function umg_sso_admin_init()
{
	register_setting('umg_sso_options', 'umg_sso_options', 'umg_sso_options_validate');
	add_settings_section('umg_sso_basic_options', 'Basic Settings', 'umg_sso_basic_options_instructions', 'umg_sso');
	add_settings_field('umg_sso_options_capture_addr', 'Capture Address', 'umg_sso_options_capture_addr', 'umg_sso', 'umg_sso_basic_options');
	add_settings_field('umg_sso_options_sso_server', 'UMG SSO Server', 'umg_sso_options_sso_server', 'umg_sso', 'umg_sso_basic_options');
	add_settings_field('umg_sso_options_client_id', 'Client ID', 'umg_sso_options_client_id', 'umg_sso', 'umg_sso_basic_options');
	add_settings_field('umg_sso_options_client_secret', 'Client Secret', 'umg_sso_options_client_secret', 'umg_sso', 'umg_sso_basic_options');
	add_settings_field('umg_sso_options_optins_bundle', 'Opt-Ins Bundle', 'umg_sso_options_optins_bundle', 'umg_sso', 'umg_sso_basic_options');
	add_settings_field('umg_sso_options_sso_profile_page', 'Profile Page', 'umg_sso_options_profile_page', 'umg_sso', 'umg_sso_basic_options');
	add_settings_field('umg_sso_options_my_addr', 'Logout Redirect', 'umg_sso_options_my_addr', 'umg_sso', 'umg_sso_basic_options');
	add_settings_field('umg_sso_options_debug', 'Debug', 'umg_sso_options_debug', 'umg_sso', 'umg_sso_basic_options');
}

/**
 * Show Options
 */
function umg_sso_options_page()
{
	?>
	<div class="wrap">
	<h2>UMG Single Sign On</h2>
	<form method="post" action="options.php">
	<?php 
	settings_fields('umg_sso_options');
	do_settings_sections('umg_sso');
	?>
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
	</p>
	</form>
	</div>
	<?php 
}
function umg_sso_basic_options_instructions() 
{
	
}

/**
 * Client ID field
 */
function umg_sso_options_client_id()
{
	$options = get_option('umg_sso_options');
	echo "<input id='umg_sso_client_id' name='umg_sso_options[client_id]' size='80' type='text' value='{$options['client_id']}' />";
}

/**
 * Client Secret field
 */
function umg_sso_options_client_secret()
{
	$options = get_option('umg_sso_options');
	echo "<input id='umg_sso_client_secret' name='umg_sso_options[client_secret]' size='80' type='text' value='{$options['client_secret']}' />";
}

/**
 * Opt-Ins Bundle field
 */
function umg_sso_options_optins_bundle()
{
	$options = get_option('umg_sso_options');
	echo "<input id='umg_sso_optins_bundle' name='umg_sso_options[optins_bundle]' size='80' type='text' value='{$options['optins_bundle']}' />";
}

/**
 * Capture Address field
 */
function umg_sso_options_capture_addr()
{
	$options = get_option('umg_sso_options');
	echo "<input id='umg_sso_capture_addr' name='umg_sso_options[capture_addr]' size='80' type='text' value='{$options['capture_addr']}' />";
}

/**
 * SSO Server field
 */
function umg_sso_options_sso_server()
{
	$options = get_option('umg_sso_options');
	echo "<input id='umg_sso_sso_server' name='umg_sso_options[sso_server]' size='80' type='text' value='{$options['sso_server']}' />";
}

/**
 * Profile Page field
 */
function umg_sso_options_profile_page()
{
	$options = get_option('umg_sso_options');
	echo "<input id='umg_sso_profile_page' name='umg_sso_options[profile_page]' size='80' type='text' value='{$options['profile_page']}' />";
}

/**
 * My Address field
 */
function umg_sso_options_my_addr()
{
	$options = get_option('umg_sso_options');
	echo "<input id='umg_sso_my_addr' name='umg_sso_options[my_addr]' size='80' type='text' value='{$options['my_addr']}' />";
}

/**
 * Debug field
 */
function umg_sso_options_debug()
{	
	$options = get_option('umg_sso_options');
    echo '<select name="umg_sso_options[debug]" id="umg_sso_debug">';
    echo '<option '.(($options['debug']=='1') ? 'selected' : '').' value="1">Yes</option>';
    echo '<option '.(($options['debug']=='0') ? 'selected' : '').' value="0">No</option>';
    echo '</select>';
}

/**
 * Validate field input
 */
function umg_sso_options_validate($input)
{
	return $input;
}

/**
* Cleanup options when plugin deactivated
*/
function umg_sso_deactivate()
{
    delete_option('umg_sso_options');
}
register_deactivation_hook( __FILE__, 'umg_sso_deactivate');



/**
 * Front End - Styles, Scripts and HTML
 */
function enqueue_umg_sso_styles()
{
	wp_enqueue_style('umg_sso_styles', get_bloginfo('template_url') . '/umg_sso_style.css', false, '1.0', 'screen');
}
function umg_sso_wp_head()
{
	$options = get_option('umg_sso_options');
	?>
	
	<script type="text/javascript">
        // <![CDATA[
        Backplane.init({
            "serverBaseURL" : "http://api.js-kit.com/v1",
            "busName": "umg"
        });
        // ]]>
	</script>
	<script src="<?php echo $options['sso_server']; ?>/sso.js" type="text/javascript"></script>
	<script src='<?php echo WP_PLUGIN_URL; ?>/umg-sso/shadowbox/shadowbox.js' type='text/javascript'></script>
	<link rel="stylesheet" type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/umg-sso/shadowbox/shadowbox.css" />
	<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/umg-sso/shared_mod.js"></script>
	<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/umg-sso/capture_mod.php"></script>
	<script>
		jQuery(document).ready(function(){
			Microgroove.windowManager.init([{"id":"PostEventAttendance","title":"Attend an Event","url":null,"width":600,"height":450,"reloadOnClose":false},{"id":"DeleteEventAttendance","title":"Not Attending","url":null,"width":400,"height":160,"reloadOnClose":false},{"id":"ReportContentWindow","title":"Report This Content","url":null,"width":600,"height":350,"reloadOnClose":false},{"id":"TagContentWindow","title":"Tag This Content","url":null,"width":500,"height":550,"reloadOnClose":true}]);
		});	
	</script>
	<script type="text/javascript">
	    var capture_redirectURL = window.location.href;
	    if (capture_redirectURL.indexOf('#') != -1) {
	        capture_redirectURL = capture_redirectURL.substring(0, capture_redirectURL.indexOf('#'));
	    }
	    var checkForCaptureLogin = function () {
	        var p = (("https:" == document.location.protocol) ? "https://" : "http://");
	        var h = p + document.location.host;
	        JANRAIN.SSO.CAPTURE.check_login({
	            sso_server: "<?php echo $options['sso_server']; ?>",
	            client_id: "<?php echo $options['client_id']; ?>",
	            redirect_uri: "<?php echo WP_PLUGIN_URL; ?>/umg-sso/oauth_redirect.php?from_sso=1",
	            xd_receiver: "<?php echo WP_PLUGIN_URL; ?>/umg-sso/xdcomm.html",
	            logout_uri: "<?php echo WP_PLUGIN_URL; ?>/umg-sso/capture_logout.php",
	            bp_channel: Backplane.getChannelID()
	        });
	    } ();
	</script>
	<script type="text/javascript">
	    var p = (("https:" == document.location.protocol) ? "https://" : "http://");
	    var h = p + document.location.host;
	    var k = "<?php echo $options['client_id']; ?>";
	    var CAPTURE = {};
    	CAPTURE.resize = function(json) {
	        var o = JSON.parse(json);
    	    jQuery("div#captureProfile iframe").removeAttr('width').removeAttr('height').width(o.w).height(o.h + 15);
    	};
    	jQuery(document).ready(function () {
	        //link to initial log-in or registration modal
		    jQuery("a.login").enableCapture('signinLink');
	        //sign out of capture and WP
		    jQuery("a.logout").enableCapture('signoutLink', { 'returnUrl': '<?php echo WP_PLUGIN_URL . "/umg-sso/capture_logout.php"; ?>' });
	        //link to email lists modal
	        jQuery("a.optin").enableCapture('optinsLink', { 'returnUrl': capture_redirectURL });
	        //link to basic profile edit page
	       	jQuery("a.editprofile").enableCapture('editProfileLink', { 'page': '/<?php echo $options['profile_page']; ?>' });
	        //basic profile edit page
	        jQuery("div#captureProfile").enableCapture('editProfileForm', { 'width': 950, 'height': 1500 });
	        //inline account edit form field hidding
	        jQuery(".pg-user-account .primary").enableCapture('editAccountForm');
	    });
	</script>
	<?php
}
function umg_sso_wp_footer()
{
	$options = get_option('umg_sso_options');
	?>
	
	<?php 
}
function umg_sso_print_login($atts, $content=null)
{
	extract(shortcode_atts(array(), $atts));
	$options = get_option('umg_sso_options');
	if ($_SESSION['umg-sso']['user_entity'] !== false) {
		echo '<div id="sso_loggedin" class="sso_membership">';
		echo '<strong>' . $_SESSION['umg-sso']['user_entity']['preferredUserName'] . '</strong>';
		echo '&nbsp;|&nbsp;';
		echo '<a class="editprofile" href="#">Profile</a>';
		if($options['optins_bundle'] != '') {
			echo '&nbsp;|&nbsp; <a href="javascript:void(0);" class="optin">Subscriptions</a>';
		}
	    echo '&nbsp;|&nbsp; <a class="logout" href="#">Sign Out</a>';
	    echo '</div>';
  	} else {
		echo '<div id="sso_loggedout" class="sso_membership">';
		echo '<a class="login" href="#">';
		echo '<span class="sso_text">Login / Sign-up</span>';
		echo '<span class="sso_icons"></span>';
		echo '</a>';
		echo '</div>';
  	}
}
?>