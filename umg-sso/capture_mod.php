<?php 
/**
 * dynamically delivered JavaScript
 * 
 */

// bootstrap WP env
// TODO: change this to more generic method, so plugins directory can move
require_once('../../../wp-load.php');

// get plugin options
$options = get_option('umg_sso_options');

?>


var Capture = Capture || {};



Capture.urlHelpers = {

    getOptinsUrl: function(token,returnUrl,optional) {
        optional = (optional === undefined) ? '' : '&' + optional;
        var packedReturnUrl = Capture.urlHelpers.packUrl(returnUrl);
        return "http://reg.sso.universalmusic.com/user/optins/" + token + "?returnUrl=" + Capture.urlHelpers.urlEncode(packedReturnUrl) + "&bundle=<?php echo $options['optins_bundle']?>" + optional;
    },

    getOptinsRedirectUrl: function(returnUrl) {
        var packedReturnUrl = Capture.urlHelpers.packUrl(returnUrl);
        return "<?php echo WP_PLUGIN_URL; ?>/umg-sso/capture_optins.php?returnUrl=" + Capture.urlHelpers.urlEncode(packedReturnUrl);
    },

    redirectToOptinsUrl: function(token,returnUrl,optional) {
        optional = (optional === undefined) ? '' : '&' + optional;
        var url = Capture.urlHelpers.getOptinsUrl(token,returnUrl,optional);
        document.location.href = url;
    },
    

    updateDirection: {
        memberToUser: 0,
        userToMember: 1
    },

    getAccessTokenUrl: function() {
        return "<?php echo WP_PLUGIN_URL; ?>/umg-sso/capture_refresh.php";
    },
    
    getSignInUrl: function(returnUrl) {
        var optional = (typeof Backplane === "undefined") ? "" : "&bp_channel=" + Capture.urlHelpers.urlEncode(Backplane.getChannelID());
        returnUrl = (returnUrl === undefined) ? '<?php echo urlencode(WP_PLUGIN_URL . '/umg-sso/oauth_redirect.php'); ?>' : Capture.urlHelpers.urlEncode(returnUrl);
        
        return "https://profile.universalmusic.com/oauth/signin?response_type=code&client_id=<?php echo $options['client_id']; ?>&xd_receiver=<?php echo urlencode(WP_PLUGIN_URL . '/umg-sso/xdcomm.html'); ?>&redirect_uri=" + returnUrl + optional;
    },

    getEditProfileUrl: function(token, callback) {
        return "https://profile.universalmusic.com/oauth/profile?client_id=<?php echo $options['client_id']; ?>&xd_receiver=<?php echo urlencode(WP_PLUGIN_URL . '/umg-sso/xdcomm.html'); ?>&access_token=" + Capture.urlHelpers.urlEncode(token) + "&callback=" + Capture.urlHelpers.urlEncode(callback);
    },

    getUpdateUrl: function(returnUrl, direction) {
        var packedReturnUrl = Capture.urlHelpers.packUrl(returnUrl);
        return "<?php echo WP_PLUGIN_URL; ?>/umg-sso/capture_refresh.php?origin=" + Capture.urlHelpers.urlEncode(packedReturnUrl) + "&direction=" + direction;
    },

    urlEncode: function(plaintext) {
        // The Javascript escape and unescape functions do not correspond
        // with what browsers actually do...
        var SAFECHARS = "0123456789" +              // Numeric
                        "ABCDEFGHIJKLMNOPQRSTUVWXYZ" + // Alphabetic
                        "abcdefghijklmnopqrstuvwxyz" +
                        "-_.!~*'()";                // RFC2396 Mark characters
        var HEX = "0123456789ABCDEF";

        var encoded = "";
        for (var i = 0; i < plaintext.length; i++) {
            var ch = plaintext.charAt(i);
            if (ch == " ") {
                encoded += "+";             // x-www-urlencoded, rather than %20
            } else if (SAFECHARS.indexOf(ch) != -1) {
                encoded += ch;
            } else {
                var charCode = ch.charCodeAt(0);
                if (charCode > 255) {
                    alert("Unicode Character '"
                            + ch
                            + "' cannot be encoded using standard URL encoding.\n" +
                                "(URL encoding only supports 8-bit characters.)\n" +
                                "A space (+) will be substituted.");
                    encoded += "+";
                } else {
                    encoded += "%";
                    encoded += HEX.charAt((charCode >> 4) & 0xF);
                    encoded += HEX.charAt(charCode & 0xF);
                }
            }
        } // for

        return encoded;
    },

    replaceString: function(fullS, oldS, newS) {
        for (var i = 0; i < fullS.length; i++) {
            if (fullS.substring(i, i + oldS.length) == oldS)
                fullS = fullS.substring(0, i) + newS + fullS.substring(i + oldS.length, fullS.length);
        }
        return fullS;
    },

    packUrl: function(url) {
        return Capture.urlHelpers.replaceString(
            Capture.urlHelpers.replaceString(
            Capture.urlHelpers.replaceString(
            Capture.urlHelpers.replaceString(url, "?", "!"),
            "&", "^"),
            "=", "|"),
            "/", "$");
    },

    unPackUrl: function(url) {
        return Capture.urlHelpers.replaceString(
            Capture.urlHelpers.replaceString(
            Capture.urlHelpers.replaceString(
            Capture.urlHelpers.replaceString(url, "!", "?"),
            "^", "&"),
            "|", "="),
            "$", "/");
    }
};

Capture.windowHelpers = function () {

    var didInit = false;

    var _addSignInWindow = function () {
        var window = {
            "id": "LogInWindow",
            "title": "Welcome!",
            "url": Capture.urlHelpers.getSignInUrl(),
            "width": 580,
            "height": 500,
            "reloadOnClose": false
        };

        Microgroove.windowManager.add(window);
    };

    var _addOptinsWindow = function () {
        var window = {
            "id": "OptinsWindow",
            "title": "Get Exclusive Updates",
            "url": null,
            "width": 580,
            "height": 400,
            "reloadOnClose": false
        };

        Microgroove.windowManager.add(window);
    };

    var _onEditProfileComplete = function () {
        var redirectUrl = Capture.urlHelpers.getUpdateUrl(document.location.href, Capture.urlHelpers.updateDirection.userToMember);
        Microgroove.helpers.redirectLocation(redirectUrl);
    };

    var _init = function () {
        if ( didInit ) {
            return;
        }

        _addSignInWindow();
        _addOptinsWindow();

        didInit = true;
    };

    return {
        init: _init,
        onEditProfileComplete: _onEditProfileComplete
    };
} ();

(function( jQuery ){

  var methods = {
    init: function() {
        alert( "enableCapture() must be called with a method name." );
        return this;
    },
    
    signinLink: function(options) {
        var settings = {
        };

        return this.each(function() {
            if ( options ) { 
                jQuery.extend( settings, options );
            }

            var $this = jQuery(this);

            $this.attr('href', 'javascript:void(0);').bind("click", function () {
                Microgroove.windowManager.open('', "LogInWindow");
            });
        });
    },

    signoutLink: function(options) {
        var settings = {
            'returnUrl': document.location.href
        };

        return this.each(function() {
            if ( options ) { 
                jQuery.extend( settings, options );
            }

            var $this = jQuery(this);

            $this.attr('href', 'javascript:void(0);').bind("click", function () {
                if ( typeof Backplane != 'undefined' ) {
                    Backplane.resetCookieChannel();
                }
                
                if ( (typeof JANRAIN != 'undefined') && (typeof JANRAIN.SSO != 'undefined') ) {
                    JANRAIN.SSO.CAPTURE.logout({
                        sso_server: 'https://sso.universalmusic.com',
                        logout_uri: settings.returnUrl
                    });
                }
                else {
                    Microgroove.helpers.redirectLocation(settings.returnUrl);
                }
            });
        });
    },

    optinsLink: function(options) {
        var settings = {
            'returnUrl': document.location.href
        };

        return this.each(function() {
            if ( options ) { 
                jQuery.extend( settings, options );
            }

            var $this = jQuery(this);
            var optinsUrl = Capture.urlHelpers.getOptinsRedirectUrl(settings.returnUrl);
            
            $this.attr('href', 'javascript:void(0);').bind("click", function () {
                Microgroove.windowManager.open(optinsUrl, "OptinsWindow");
            });
        });
    },

    editProfileLink: function(options) {
        var settings = {
            'page': '/capture.aspx'
        };

        return this.each(function() {
            if ( options ) { 
                jQuery.extend( settings, options );
            }

            var $this = jQuery(this);
            
            $this.attr('href', 'javascript:void(0);').bind("click", function () {
                Microgroove.helpers.redirectLocation(settings.page);
            });
        });
    },

    editProfileForm: function(options) {
        var settings = {
            'callback': 'Capture.windowHelpers.onEditProfileComplete',
            'width': 580,
            'height': 400,
            'transparent': true,
            'frameId': 'capture_edit_profile'
        };

        return this.each(function() {
            if ( options ) { 
                jQuery.extend( settings, options );
            }

            var $this = jQuery(this);

            jQuery.getJSON(Capture.urlHelpers.getAccessTokenUrl(), function(data) {
                if ( data.statusCode === 200 ) {
                    var editProfileUrl = Capture.urlHelpers.getEditProfileUrl(data.accessToken, settings.callback);
                        
                    $this.append("<iframe id='" + settings.frameId + "' width='" + settings.width + "' height='" + settings.height + "' allowtransparency='" + settings.transparent + "' frameborder='0'></iframe>");
                    jQuery("iframe#" + settings.frameId).attr("src", editProfileUrl);
                }
                else {
                    alert(data.message);
                }
            });
        });
    },

    editAccountForm: function(options) {
        var settings = {
            'hiddenFields': [
                'usernameinput',
                'fnameinput',
                'lnameinput',
                'birthdateinput',
                'cityinput',
                'statedropdown'
            ],
            'optionalFields': [
                'genderradio',
                'zipinput',
                'countrydropdown'
            ],
            'optionalFieldSelectors': [
                'span#mg-genderradioid input[checked="checked"]',
                'span#mg-zipinputid input',
                'span#mg-countrydropdownid select',
            ]
        };

        return this.each(function() {
            if ( options ) { 
                jQuery.extend( settings, options );
            }

            jQuery.each(settings.hiddenFields, function(index, value) { 
                jQuery("span#mg-" + value + "id").hide(); 
            });

            jQuery.each(settings.optionalFieldSelectors, function(index, value) {
                if ( jQuery(value).val() ) {
                    jQuery("span#mg-" + settings.optionalFields[index] + "id").hide();
                }
            });

            if ((typeof Microgroove.state !== "undefined") && Microgroove.state === "post") {
                var updateUrl = Capture.urlHelpers.getUpdateUrl(document.location.href, Capture.urlHelpers.updateDirection.memberToUser);
                jQuery.get(updateUrl);
            };
        });
    }
  };

  jQuery.fn.enableCapture = function(method) {

    Capture.windowHelpers.init();

    if ( methods[method] ) {
      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      jQuery.error( 'Method ' +  method + ' does not exist on jQuery.enableCapture' );
    }

  };
})( jQuery );