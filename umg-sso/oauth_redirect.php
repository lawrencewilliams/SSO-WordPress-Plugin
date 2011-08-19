<?php
/**
 * INIT
 */
// bootstrap WP env
// TODO: change this to more generic method, so plugins directory can move
require_once('../../../wp-load.php');

// get plugin options
$options = get_option('umg_sso_options');

// include capture api
require_once('api.php');

/**
 * handle Capture OAuth redirect
 */
$my_uri = get_bloginfo('url');	// default value
if(isset($_GET['code'])) {
	$auth_code = $_GET["code"];
	$from_sso = $_GET["from_sso"];
	$redirect_uri = WP_PLUGIN_URL . "/umg-sso/oauth_redirect.php";
	if(isset($options['sso_server']) && $from_sso) {
		$my_uri = urldecode($_GET['origin']);
		$last = $my_uri[strlen($my_uri)-1];
		if($last != "/"){
			$my_uri .= "/";
		}
		$redirect_uri = $redirect_uri . "?from_sso=1&origin=" . urlencode($my_uri);
	}
	try {
		new_access_token($auth_code, $redirect_uri);
		// update user entity with the domain of this site (if not already listed)
		$user_entity = load_user_entity();
		if (isset($user_entity)) {
			    if (isset($user_entity['stat']) && $user_entity['stat'] == 'ok') {
					$user_entity = $user_entity['result'];
					$domainListed = false;
					foreach($user_entity['domains'] as $domain) {
						if($domain['domain'] == get_bloginfo('url')) {
							$domainListed = true;
						}
					}
					if(!$domainListed) {
						$attributes = '{"domains":[{"domain":"' . get_bloginfo('url') . '"}]}';
						$command   = "entity.update";
						$arg_array = array('type_name'			=> 'user',
											'id'				=> $user_entity['id'],
										    'attributes'		=> $attributes,
											'client_id'     	=> $options['client_id'],
		                     				'client_secret' 	=> $options['client_secret']);
						$json_data = capture_api_call($command, $arg_array);
					}
			    }
		}
	} catch (Exception $e) {
	    // echo 'Caught exception: ',  $e->getMessage(), "\n";
	 	// there has been an error with the Capture API call - nothing else we can do other than simply redirect user back my_uri
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<body>
<?php
debug_out("*** Auth code: $auth_code <br>\n");
?>
<script type='text/javascript'>
	if (window.top == window.self) {
		<?php 
		// user comes through without an iframe if logged in via another UMG SSO enabled website (or mobile device)
		?>
		window.location = "<?php echo $my_uri; ?>";
	} else {
		<?php 
		// user comes through in an iframe if logging in via this site
		?>
		window.parent.location.reload();
	}
</script>
</body>
</html>