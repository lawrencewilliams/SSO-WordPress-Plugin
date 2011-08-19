<?php
/**
 * INIT
 */
// bootstrap WP env
// TODO: change this to more generic method, so plugins directory can move
require_once('../../../wp-load.php');

// get plugin options
$options = get_option('umg_sso_options');

// pick up returnUrl if set
if(isset($_GET['returnUrl'])) {
	$location = $_GET['returnUrl'];
} else {
	$location = str_replace('/', '$', $options['my_addr']);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Get Exclusive Updates</title>
<link href="http://cache.umusic.com/web_assets/_global/css/ucid/capture_forms.css" rel="stylesheet" type="text/css" />
</head>
<body>

<div class="loading_screen" style="margin-left:auto;margin-right:auto;width:10em;margin-top:100px;">
    <h2>Loading...</h2>
</div>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
<script src='<?php echo WP_PLUGIN_URL; ?>/umg-sso/shadowbox/shadowbox.js' type='text/javascript'></script>
<link rel="stylesheet" type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/umg-sso/shadowbox/shadowbox.css" />
<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/umg-sso/shared_mod.js"></script>

<script type="text/javascript">
    
    var p = (("https:" == document.location.protocol) ? "https://" : "http://");
    var h = p + document.location.host;

    //Use this on Microgroove sites to enable sign in and optins
    document.write(unescape("%3Cscript src='<?php echo WP_PLUGIN_URL; ?>/umg-sso/capture_mod.php'") + unescape(" type='text/javascript'%3E%3C/script%3E"));

    $(document).ready(function () {
        $.getJSON(Capture.urlHelpers.getAccessTokenUrl(), function (data) {
            if (data.statusCode === 200) {
                Capture.urlHelpers.redirectToOptinsUrl(data.accessToken, '<?php echo $location; ?>');
            }
            else {
                alert(data.message);
            }
        });
    });

</script>

</body>
</html>