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
 * handle logout
 */
clear_capture_session();
wp_logout();
wp_set_current_user(0);
wp_redirect($options['my_addr']);
exit;
?>