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
 * Refresh capture token
 */
$user_entity = load_user_entity();
if (isset($user_entity)) {
    if (isset($user_entity['stat']) && $user_entity['stat'] == 'ok') {
    	$capture_session = capture_session();
		$access_token = $capture_session['access_token'];
		if(isset($_GET['origin'])) {
			// origin set, redirect back to it
			$loc = urldecode($_GET['origin']);
			$loc = str_replace('$', '/', $loc);
			header("Location: " . $loc);
			exit;
		} else {
			// no origin set, output the token value
		?>
{"statusCode":200, "accessToken":"<?php echo $access_token; ?>"}
		<?php 
		}
    }
}
?>