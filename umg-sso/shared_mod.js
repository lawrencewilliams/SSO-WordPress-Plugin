var Microgroove = Microgroove || {};

/*************************************************************
* Misc utility scripts used by the Microgroove platform.
* This file is grouped into several sections.
*************************************************************/

Microgroove.helpers = {

    loadFile: function(filename) {
        if (filename.indexOf("js") != -1) {
            var fileref = document.createElement('script');
            fileref.setAttribute("type", "text/javascript");
            fileref.setAttribute("src", filename);
        }
        else if (filename.indexOf("css") != -1) {
            var fileref = document.createElement("link");
            fileref.setAttribute("rel", "stylesheet");
            fileref.setAttribute("type", "text/css");
            fileref.setAttribute("href", filename);
        }
        if (typeof fileref != "undefined") {
            document.getElementsByTagName("head")[0].appendChild(fileref);
        }
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

    setCookie: function(name, value, expire, path) {
        document.cookie = name + "=" + escape(value) + ((expire == null) ? "" : ("; expires=" + expire.toGMTString())) + ((path) ? "; path=" + path : "");
    },

    getCookie: function(name) {
        var search = name + "=";
        if (document.cookie.length > 0) {
            offset = document.cookie.indexOf(search);
            if (offset != -1) {
                offset += search.length;
                end = document.cookie.indexOf(";", offset);
                if (end == -1)
                    end = document.cookie.length;

                return unescape(document.cookie.substring(offset, end));
            }
        }
    },

    killCookie: function(name, path) {
        theValue = Microgroove.helpers.getCookie(name);
        if (theValue)
            document.cookie = name + '=' + theValue + '; expires=Fri, 13-Apr-1970 00:00:00 GMT' + ((path) ? '; path=' + path : '');
    },

    setMultiValueCookie: function(name, keys, values, expire, path) {
        if (keys.length != values.length)
            return;

        var htmlCookie = name + " = ";

        var htmlCookieValues = "";

        for (var i = 0; i < values.length; i++) {
            if (htmlCookieValues.length > 0)
                htmlCookieValues += "&";

            htmlCookieValues += keys[i] + "=" + escape(values[i]);
        }

        htmlCookie += htmlCookieValues;
        htmlCookie += expire == null ? "" : "; expires=" + expire.toUTCString();
        htmlCookie += (path) ? "; path=" + path : "";

        document.cookie = htmlCookie;
    },

    getMultiValueCookie: function(cookieName, cookieKey) {
        var cookieValue = Microgroove.helpers.getCookie(cookieName);

        if (cookieValue == "")
            return "";

        return Microgroove.helpers.paramaterFrom(cookieKey, cookieValue)
    },

    paramaterFrom: function(param, argument) {
        argument = '=' + argument + '&';
        if (argument.indexOf(param) != -1) {
            locStart = argument.indexOf(param) + param.length + 1;
            locEnd = argument.indexOf('&', locStart);
            if (locEnd < locStart)
                locEnd = argument.length;

            return argument.substring(locStart, locEnd);
        }
        else
            return null;
    },

    replaceString: function(fullS, oldS, newS) {
        for (var i = 0; i < fullS.length; i++) {
            if (fullS.substring(i, i + oldS.length) == oldS)
                fullS = fullS.substring(0, i) + newS + fullS.substring(i + oldS.length, fullS.length);
        }
        return fullS;
    },

    unPackUrl: function(url) {
        return Microgroove.helpers.replaceString(
            Microgroove.helpers.replaceString(
            Microgroove.helpers.replaceString(
            Microgroove.helpers.replaceString(url, "!", "?"),
            "^", "&"),
            "|", "="),
            "$", "/");
    },

    changeSortOrder: function(cookieName, sortOrder, reloadUrl) {
        Microgroove.helpers.setCookie(cookieName, sortOrder, null, "/");
        Microgroove.helpers.redirectLocation(reloadUrl);
    },

    redirectLocation: function(redirectUrl) {
        if (redirectUrl) {
            var withoutAnchor = redirectUrl.replace(/#[^&.]*/, "");
            window.location.href = withoutAnchor;
        }
    },

    getRef: function(elementName) {
        if (!document.getElementById)
            return document[elementName];
        else
            return document.getElementById(elementName);
    }
};

var setCookie = Microgroove.helpers.setCookie;
var getCookie = Microgroove.helpers.getCookie;

/*************************************************************
* Content view tracking and server posting scripts.
*************************************************************/

Microgroove.viewTracking = {

    trackView : function(contentTypeID, contentID, currentViewCount, timeToLive, hash, isReadOnly) {
        function GetCookie(cookieName) {
            var cookies = document.cookie.split(";");

            for (var i = 0; i < cookies.length; i++) {
                var nameValue = cookies[i].split("=");

                if (nameValue.length == 2 && nameValue[0].replace(" ", "") == cookieName) {
                    return nameValue[1];
                }
            }

            return null;
        }

        function SetCookie(cookieName, cookieValue) {
            document.cookie = cookieName + "=" + cookieValue + ";path=/;";
        }

        function GetViewCount(text, contentTypeID, contentID) {
            var countName = contentTypeID + "_" + contentID;
            var pairs = text.split("|");

            for (var i = 0; i < pairs.length; i++) {
                var nameValue = pairs[i].split(":");

                if (nameValue.length == 2 && nameValue[0] == countName) {
                    return nameValue[1];
                }
            }

            return 0;
        }

        function SetViewCount(text, contentTypeID, contentID, value) {
            var countName = contentTypeID + "_" + contentID;
            var pairs = text.split("|");
            var found = false;
            text = "";

            for (var i = 0; i < pairs.length; i++) {
                var nameValue = pairs[i].split(":");

                if (nameValue.length == 2) {
                    if (nameValue[0] == countName) {
                        text += countName + ":" + value + "|";
                        found = true;
                    }
                    else {
                        text += nameValue[0] + ":" + nameValue[1] + "|";
                    }
                }
            }

            if (!found) {
                text += countName + ":" + value + "|";
            }

            return text;
        }

        if (!isReadOnly) {
            // Call server procedure.
            var image = new Image();
            image.src = "/aspnet_client/microgroove/tracking/tracking.ashx?contentTypeID=" + contentTypeID
                + "&contentID=" + contentID
                + "&ttl=" + timeToLive
                + "&h=" + hash
                + "&t=" + (new Date()).getTime();
        }

        // Save current view count to cookie.
        var cookie = GetCookie("TrackViewCount");
        if (cookie == null) {
            cookie = SetViewCount("", contentTypeID, contentID, currentViewCount);
        }

        var value = GetViewCount(cookie, contentTypeID, contentID);
        if (value != 0) {
            currentViewCount = value;
        }

        if (!isReadOnly) {
            currentViewCount++;
        }

        cookie = SetViewCount(cookie, contentTypeID, contentID, currentViewCount);
        SetCookie("TrackViewCount", cookie);

        return currentViewCount;
    }
};

/*************************************************************
* Page tracking abstraction layer.
* This namespace is overriden by implementers.
*************************************************************/

Microgroove.pageTracking = function() {

    var _trackPage = function(page) {
    };

    var _trackModal = function(modal) {
    };

    var _getPath = function(pageBase, state) {
        return "/" + pageBase + "/" + state;
    };

    return {
        trackPage: _trackPage,
        trackModal: _trackModal,
        getTrackingPath: _getPath
    }

} ();

/*************************************************************
* AJAX-based author tooltips.
*************************************************************/

Microgroove.toolTips = function() {

    var _mouseX;
    var _mouseY;
    var _isTooltipActive = false;

    var callMemberService = function(memberID) {
        if (memberID != 0) {
            jQuery.getJSON("/webservices/v4.0/int/memberservice.aspx/GetMember", { memberID: memberID },
            function(item) {
                if (item) {
                    setTooltipContent("<div style='margin:10px;'><img src='" + item.ImageUrl + "'></div><div style='margin-top:10px;margin-left:10px;'><strong>User: </strong>" + item.Username + "</div><div style='margin-left:10px;'><strong>Location: </strong>" + item.Location + "</div><div style='margin-left:10px;margin-bottom:10px;'><strong>Joined: </strong>" + item.ConfirmedDate + "</div>");
                };
            });
        }
    };

    var init = function() {
        jQuery("body").prepend("<div id='ToolTip' style='position:absolute; width:100px; top:0px; left:0px; z-index:4; visibility:hidden;'></div>");

        if (!jQuery.browser.msie) {
            document.captureEvents(Event.MOUSEMOVE)
        }
        document.onmousemove = getMouseXY;
    };

    var getBrowserHeight = function() {
        if (jQuery.browser.msie) {
            return document.body.offsetHeight;
        }
        else {
            return window.innerHeight;
        }
    };

    var getBrowserWidth = function() {
        if (jQuery.browser.msie) {
            return document.body.offsetWidth;
        }
        else {
            return window.innerWidth;
        }
    };

    var updateTooltipPos = function() {
        if (!document.getElementById || !document.getElementById('ToolTip') || !_mouseX || !_mouseY) {
            return;
        }

        var toolTipLeft = (_mouseX + 20) + "px";
        if ((_mouseX + 270) > getBrowserWidth()) {
            toolTipLeft = (_mouseX - 240) + "px";
        }

        document.getElementById('ToolTip').style.left = toolTipLeft;
        document.getElementById('ToolTip').style.top = (_mouseY) + "px";
    };

    var getMouseXY = function(e) {
        if (!document.getElementById || !document.getElementById('ToolTip')) {
            return;
        }

        if (jQuery.browser.msie) {
            var top = (document.documentElement && document.documentElement.scrollTop) ? document.documentElement.scrollTop : document.body.scrollTop;
            // grab the x-y pos.s if browser is IE
            _mouseX = event.clientX + document.body.scrollLeft;
            _mouseY = event.clientY + top;
        }
        else {
            // grab the x-y pos.s if browser is NS
            _mouseX = e.pageX;
            _mouseY = e.pageY;
        }

        if (_mouseX < 0) {
            _mouseX = 0;
        }
        if (_mouseY < 0) {
            _mouseY = 0;
        }
        if (_isTooltipActive) {
            updateTooltipPos();
        }
    };

    var _getMember = function(memberID) {
        callMemberService(memberID);
        return "<div style='margin:10px;'>Loading...</div>";
    };


    var _showHideTooltip = function(which, content) {

        if (!document.getElementById || !document.getElementById('ToolTip')) {
            return;
        }

        if (which) {
            updateTooltipPos();
            _isTooltipActive = true;
            document.getElementById('ToolTip').style.visibility = "visible";
            setTooltipContent(content);
        }
        else {
            _isTooltipActive = false;
            document.getElementById('ToolTip').style.visibility = "hidden";
            setTooltipContent('');
        }
    };

    var setTooltipContent = function(content) {
        if (!document.getElementById || !document.getElementById('ToolTip')) {
            return;
        }
        document.getElementById('ToolTip').innerHTML = "<div style='width:220px;border:1px solid black;background-color:white;font-family:verdana, arial, sans-serif;font-size:8pt;color:black;'>" + content + "</div>";
    };

    jQuery(document).ready(function() {
        init();
    });

    return {

        getMember: _getMember,

        showHideTooltip: _showHideTooltip
    };
} ();

var ShowHideTooltip = Microgroove.toolTips.showHideTooltip;
var GetMember = Microgroove.toolTips.getMember;

/*************************************************************
* Form validation and show / hide scripts.
*************************************************************/

Microgroove.forms = {

    clearMemberImage: function(memberImageClearedTracker, memberImage, memberImageContainer) {
        jQuery("#" + memberImageClearedTracker).val('true');
        jQuery("#" + memberImage).hide();
        jQuery("#" + memberImageContainer).hide();
    },

    validateDropdowns: function(source, arguments) {
        if (arguments.Value === "null") {
            arguments.IsValid = false;
        }
        else {
            arguments.IsValid = true;
        }
    },

    /*************************************************************
    * Artist Fans
    *************************************************************/

    removeArtistFan: function(artistID, artistFanRow) {
        if (!confirm("Removing yourself as a fan will also remove you from that artist's email list. Do you want to continue?")) {
            return;
        }
        Microgroove.forms.clearArtistFanCheckbox(artistID);
        Microgroove.forms.hideArtistFanRow(artistID);
    },

    hideArtistFanRow: function(artistID) {
        jQuery("span[artistfanof*='" + artistID + "']").slideUp();
    },

    clearArtistFanCheckbox: function(artistID) {
        jQuery("input[artistfanof*='" + artistID + "']").attr('checked', false);
    }
};

var GetMail = function(elementName, joinFormUrl) {
    window.document.location.href = joinFormUrl + jQuery("#" + elementName).val();
};


/*************************************************************
* WebIM scripts for opening windows and checking for new
* messages on a schedule.
*************************************************************/

Microgroove.webIM = function() {

    var _INTERVAL = 10000;
    var _timerID = 0;
    var _memberID = 0;

    var _callService = function() {
        if (!_isChattingDisabled()) {
            jQuery.getJSON("/webservices/v4.0/int/messagingservice.aspx/IsMessageWaiting", { memberID: _memberID },
            function(item) {
                if (_memberID != 0 && !_isChattingDisabled() && item && item.SenderID && item.SenderID != 0 && !_getChatting(item.SenderID)) {
                    Microgroove.windowManager.open("/aspnet_client/microgroove/windows/chat/intro.aspx?t=" + item.SenderID + "&ta=" + Microgroove.helpers.urlEncode(item.Author) + "&tl=" + Microgroove.helpers.urlEncode(item.AuthorLocation) + "&ti=" + Microgroove.helpers.urlEncode(item.ImageUrl) + "&m=" + Microgroove.helpers.urlEncode(item.MessageBody), "IntroWindow");
                    setChatting(item.SenderID, true);
                };

                startTimer();
            });
        }
        else {
            startTimer();
        }
    };

    var _isChattingDisabled = function() {
        return (Microgroove.helpers.getCookie('Chatting') != null && Microgroove.helpers.getCookie('Chatting') == "Disabled");
    };

    var _enableChatting = function() {
        Microgroove.helpers.setCookie('Chatting', 'Enabled', null, '/');
    };

    var _disableChatting = function() {
        Microgroove.helpers.setCookie('Chatting', 'Disabled', null, '/');
    };

    var _setChatting = function(recipientID, isChatting) {
        var chattingCookie = "";

        if (Microgroove.helpers.getCookie("ChattingRecipients") != null) {
            chattingCookie = Microgroove.helpers.getCookie("ChattingRecipients");
        };

        var recipients = chattingCookie.split("|");
        var found = false;
        chattingCookie = ""; //reset as we're about to rebuild this

        for (var i = 0; i < recipients.length; i++) {
            if (recipients[i] == recipientID && isChatting) {
                //recipientID already in the cookie, keep them there
                chattingCookie += recipientID + "|";
                found = true;
                continue;
            }

            if (recipients[i] == recipientID && !isChatting) {
                //recipientID already in the cookie, so skipping adding it back in
                found = true;
                continue;
            }

            chattingCookie += recipients[i] + "|";
        }

        if (!found && isChatting) {
            chattingCookie += recipientID + "|";
        }

        Microgroove.helpers.setCookie('ChattingRecipients', chattingCookie, null, '/');
    };

    var _getChatting = function(recipientID) {
        var chattingCookie = "";

        if (Microgroove.helpers.getCookie("ChattingRecipients") != null) {
            chattingCookie = Microgroove.helpers.getCookie("ChattingRecipients");
        }
        else {
            return false;
        }

        var recipients = chattingCookie.split("|");

        for (var i = 0; i < recipients.length; i++) {
            if (recipients[i] == recipientID) {
                return true;
            }
        }

        return false;
    };

    var _openChatWindow = function(recipientID) {
        if (_memberID == recipientID) {
            alert("When you click this icon next to another member that is currently online, \ryou can start an Instant Messaging conversation with that member, \rfrom right within this site.");
            return;
        }

        if (recipientID == 0) {
            return;
        }

        var url = "/aspnet_client/microgroove/windows/chat/default.aspx?t=" + recipientID;

        var chasm = screen.availWidth;
        var mount = screen.availHeight;

        var w = 327;
        var h = 390;

        wHandle = null;
        wHandle = window.open(url, 'ChatWindow_' + recipientID, 'width=' + w + ',height=' + h + ',left=' + ((chasm - w - 10) * .5) + ',top=' + ((mount - h - 30) * .5) + ',scrollbars=no');
        if (wHandle.blur) wHandle.focus();
    };

    var startTimer = function() {
        _timerID = setTimeout("Microgroove.webIM.callService()", _INTERVAL);
    };

    var openNoChatWindow = function() {
        alert("You need to be a logged in member to WebIM other members. Please log in or register.");
    };

    var openLogInToChatWindow = function() {
        alert("You need to be a logged in member to WebIM other members. Please log in or register.");
    };

    var openAuthorOfflineChatWindow = function() {
        alert("The person you are trying to WebIM is currently offline.");
    };

    return {
        init: function(fromMemberID) {
            if (fromMemberID != 0) {
                _memberID = fromMemberID;
                startTimer();
            }
        },

        callService: _callService,

        isChattingDisabled: _isChattingDisabled,

        enableChatting : _enableChatting,

        disableChatting: _disableChatting,

        setChatting: _setChatting,

        getChatting: _getChatting,

        openChatWindow: _openChatWindow
    };
} ();

var OpenChatWindow = Microgroove.webIM.openChatWindow;

/*************************************************************
* Windowing scripts to support modal window client side
* behaviour.
*************************************************************/

var shadowboxWindowManager = function () {
    var windows = {};
    var isReloadOnClose = false;
    var onCloseWindowCallback, onOpenWindowCallback;

    var removeVisibleOnPageLoad = function (url) {
        var param = Microgroove.helpers.paramaterFrom("vpl", url);
        return url.replace(param, "false");
    };

    var attachJavaScriptProtocol = function (url) {
        return "javascript:" + url;
    };

    var bindWindowMethods = function () {

        var bindings = "*[href^='OpenModalWindow'], *[href^='RedirectLocation'], *[href^='Microgroove.windowManager.open'], *[href^='Microgroove.helpers.redirectLocation']";

        jQuery(bindings).each(function () {

            var href = jQuery(this).attr("href");
            jQuery(this).attr("href", attachJavaScriptProtocol(href));

        });
    };

    var findWindow = function (id) {
        for (var i = 0; i < windows.length; i++) {
            if (windows[i].id == id) {
                return windows[i];
            }
        }

        return null;
    };

    var onShadowCloseWindow = function () {
        if (isReloadOnClose) {
            Microgroove.helpers.redirectLocation(document.location.href);
            return;
        };

        isReloadOnClose = false;
    };

    var _onCloseWindow = function (callback) {
        onCloseWindowCallback = callback;
    };

    var _onOpenWindow = function (callback) {
        onOpenWindowCallback = callback;
    };

    var _init = function (clientSideWindows) {
        windows = clientSideWindows;

        //set the Shadowbox specific 'content' properties to our own 'url'
        //properties
        for (var i = 0; i < windows.length; i++) {
            windows[i].content = windows[i].url;
            windows[i].player = 'iframe';
        }
    };

    var _add = function (window) {
        var windowObject = findWindow(window.id);

        if (windowObject) {
            return;
        }

        window.content = window.url;
        window.player = 'iframe';
        windows.push(window);
    };

    var _open = function (url, id) {

        if (arguments.length > 2) {
            var windowObject = findWindow(id);

            if (!windowObject) {
                return;
            }

            if (url) {
                windowObject.content = url;
            }

            isReloadOnClose = windowObject.reloadOnClose;

            Shadowbox.open(windowObject);
            return;
        }

        if ((typeof onOpenWindowCallback != 'undefined') && (typeof onOpenWindowCallback == 'function')) {
            var isAsync = onOpenWindowCallback(function () {
                _open(url, id, true);
            });

            if (isAsync) {
                return;
            }
        };

        _open(url, id, true);
    };

    var _close = function () {
        isReloadOnClose = false;

        if (arguments.length > 0) {
            return _closeWindowWithRedirect(arguments[0]);
        }

        return _closeWindow();
    };

    var _hide = function () {
        jQuery("#sb-container").hide();
    };

    var _closeWindowWithRedirect = function (url) {

        if (arguments.length > 1) {
            var url = removeVisibleOnPageLoad(url);
            Microgroove.helpers.redirectLocation(url);
            return;
        }

        if ((typeof onCloseWindowCallback != 'undefined') && (typeof onCloseWindowCallback == 'function')) {
            _hide();
            var isAsync = onCloseWindowCallback(function () {
                _closeWindowWithRedirect(url, true);
            });

            if (isAsync) {
                return;
            }
        };

        _closeWindowWithRedirect(url, true);
    };

    var _closeWindow = function () {

        if (arguments.length > 0) {
            Shadowbox.close();
            return;
        }

        if ((typeof onCloseWindowCallback != 'undefined') && (typeof onCloseWindowCallback == 'function')) {
            _hide();
            var isAsync = onCloseWindowCallback(function () {
                _closeWindow(true);
            });

            if (isAsync) {
                return;
            }
        };

        _closeWindow(true);
    };

    //Microgroove.helpers.loadFile("/wp-content/plugins/umg-sso/shadowbox/shadowbox.css");
    Shadowbox.init({ showOverlay: false, language: 'en', skipSetup: true, modal: false, overlayOpacity: 0, useSizzle: false, onClose: onShadowCloseWindow, players: ["iframe"] });

    jQuery(document).ready(function () {
        bindWindowMethods();
    });

    return {
        init: _init,
        open: _open,
        close: _close,
        add: _add,
        hide: _hide,
        onCloseWindow: _onCloseWindow,
        onOpenWindow: _onOpenWindow
    };
};

Microgroove.windowManager = shadowboxWindowManager();

var OpenModalWindow = Microgroove.windowManager.open;
var OpenDeleteWindow = Microgroove.windowManager.open;

/*************************************************************
* Facebook Connect support.
*************************************************************/

Microgroove.facebookHelper = function() {

    var _init = function(facebookApiKey, facebookTemplateBundleID, clientData, realReceiver) {
    };

    return {
        init: _init
    }

} ();

/*************************************************************
* Member Playlists allow site members to build their own
* streaming audio playlists from tracks in the CMS authored
* playlists.
*************************************************************/

Microgroove.memberPlaylists = function() {

    var initMemberPlaylistSortables = function() {
        if (jQuery("#MemberPlaylist").sortable) {
            jQuery("#MemberPlaylist").sortable({
                handle: jQuery(".positioner"),
                stop: function(e, ui) {
                    jQuery.get("/webservices/v4.0/pub/memberplaylistservice.aspx/UpdateMemberPlaylist",
                    { trackIDs: jQuery('#MemberPlaylist').sortable('toArray').toString(),
                        memberID: jQuery('#MemberPlaylist').attr('MemberID'),
                        affinityID: jQuery('#MemberPlaylist').attr('AffinityID')
                    });
                }
            });
        }
    };

    jQuery(document).ready(function() {
        initMemberPlaylistSortables();
    });

    return {

        deleteMemberTrack : function(trackid, memberid, affinityid) {
            jQuery.get("/webservices/v4.0/pub/memberplaylistservice.aspx/DeleteTrackFromMemberPlaylist",
                    { trackID: trackid,
                        memberID: memberid,
                        affinityID: affinityid
                    });
            jQuery("#" + trackid).slideUp("slow", function() {
                jQuery("#" + trackid).remove();
            });
        },

        addMemberTrack : function(trackid, memberid, affinityid) {
            jQuery.get("/webservices/v4.0/pub/memberplaylistservice.aspx/AddTrackToMemberPlaylist",
                    { trackID: trackid,
                        memberID: memberid,
                        affinityID: affinityid
                    });
        },

        onAjaxResponseEnd : function(ajaxPanel, eventArgs) {
            initMemberPlaylistSortables();
        }
    };
} ();


/*************************************************************
* The onloadsOn() function will set the window.onload function to
* be onloadsOn() which will run all of your window.onload
* functions.
*************************************************************/
function onloadsOn() {
    window.onload = onloadsGo;
}

/*************************************************************
* The onloadsGo() function loops through the onloads array and
* runs each function in the array.
*************************************************************/
function onloadsGo() {
    for (var i = 0; i < _onloads.length; i++)
        eval(_onloads[i]);
}

/*************************************************************
* The onloadsAdd() function will add another function to the onloads
* array to be run when the page loads.
*************************************************************/
function onloadsAdd(func) {
    _onloads[_onloads.length] = func;
}

/*************************************************************
* The onloads array holds all of the functions you wish to run
* when the page loads.
*************************************************************/
var _onloads = new Array();

//install the onload handlers
onloadsOn();