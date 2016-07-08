/**
 * Popup campaign object
 **/
define('system/js/ui/popup-campaign', ["../ofw-jquery"], function() {

    return function PopupCampaign(options){
        /** Options **/
        var defaultOptions = {
            url: null,     // The controller that displays the popup HTML (in ofw.alert)
            selector: null,       // OR you can use this instead of url to simple removeClass('hide') on the popup campaign div
            timeDelay: 1000,                   // 25 seconds delay before the popup is shown. You should use localStorage for the start time, so that the timer is preserved across page views.
            cookieName: 'popupcampaign',        // Optional, defaults to 'popupcampaign' - The name of the cookie (or localstorage key?) that stores the number of times this user has seen this item. Only needed if you have several per page.
            cookieExpiryDays: 90,               // Optional, defaults to 90 - The number of days after which the cookie / localstorage expires.
            showCount: 1,                       // Optional, defaults to 1 - The number of times this visitor sees the popup campaign before it is no longer shown again (until the cookie expires).
            showAgainAfterDays: 3,              // Optional, defaults to 3 - The number of days after which a visitor should again see the popup (only relevant if showCount > 1)
            openButton: null,      // Optional, defaults to null - If set, a click event will be added to this selector that triggers openPopup()
            closeButton: null      // Optional, defaults to null - If set, a click event will be added to this selector that triggers closePopup()

        };

        var myOptions = {};

        /** Private properties **/
        var closeCount = 0;
        var maxCloseCount = 0;
        var popupEnabled = true;


        /** Private API **/

        /**
         * Object init
         */
        var init = function(){
            // Merge default options
            myOptions = $.extend(true, {}, defaultOptions, options);

            // save showCount
            maxCloseCount = myOptions.showCount;

            // check cookies, localstorage, showCount
            if(checkPopup()) return;

            // if openButton is set, open popup on button click
            if(myOptions.openButton != null){
                $(myOptions.openButton).click(function(e){
                   createPopup();
                });
            }
            // if openButton is null, open popup after seconds defined in timeDelay parameter
            else{
                setTimeout(function(){
                    createPopup();
                }, myOptions.timeDelay);
            }
        };

        /**
         * Create and open popup
         * @returns {*}
         */
        var createPopup = function(){
            // popup campaign called without a controller
            if(myOptions.url == null && myOptions.selector == null){
                return console.error('Popup campaign called without url and selector. Check the documentation and define the url or selector parameter.');
            }

            // a controller was defined
            if(myOptions.url != null){
                ofw.ajax.alert(myOptions.url, function(){
                    onPopupClose();
                });
            }

            // a selector was defined
            if(myOptions.url == null && myOptions.selector != null){
                $(myOptions.selector).removeClass('hide');
            }
        };

        /**
         * Check cookie and localstorage
         */
        var checkPopup = function(){
            var cookieSet = checkCookie();
            var localStorageSet = checkLocalStorage();
            return cookieSet || localStorageSet;
        };

        /**
         * Check cookie
         * @returns {boolean}
         */
        var checkCookie = function(){
            // get all cookies
            var cookies = document.cookie.split(';');
            for(var i = 0; i <cookies.length; i++) {
                var c = cookies[i];
                while (c.charAt(0)==' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(myOptions.cookieName) == 0) {
                    var cookieData = c.split('=');
                    // cookie found
                    if(cookieData[0] == myOptions.cookieName) {
                        return true;
                    }
                }
            }
            return false;
        };

        /**
         * Check localstorage
         */
        var checkLocalStorage = function(){
            if(window.localStorage){
                var now = new Date();
                var item = localStorage.getItem(myOptions.cookieName);

                // item not found
                if(typeof(item) == 'undefined' || item == null){
                    return false;
                }
                // item found, but time has expired
                if(item < (now.getTime()/1000)){
                    // delete item and return false
                    deleteLocalStorage();
                    return false;
                }
                console.log('popup found in localstorage');
                // item found with valid time
                return true;
            }
            return false;
        };

        /**
         * Delete item from localstorage
         */
        var deleteLocalStorage = function(){
            if(window.localStorage){
                if(localStorage.getItem(myOptions.cookieName)){
                    localStorage.removeItem(myOptions.cookieName);
                }
            }
        };

        /**
         * Delete cookie
         */
        var deleteCookie = function(){
           if(checkCookie()){
               document.cookie = myOptions.cookieName + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
           }
        };

        /**
         * Save a popup name to cookie and localstorage
         */
        var onPopupClose = function(){
            var expiry_date = new Date();
            expiry_date = Math.round((expiry_date.getTime()/1000) + myOptions.cookieExpiryDays*24*60*60);
            // set cookie
            document.cookie = myOptions.cookieName+"="+myOptions.cookieName+"_closed; expires="+expiry_date+"; path=/";
            if(window.localStorage){
                localStorage.setItem(myOptions.cookieName, expiry_date);
            }
        };

        /** Public API **/
        var api = {

            /**
             * Public initialization method.
             **/
            start: function(){
                init();
            },

            enable: function(){
                popupEnabled = true;
            },
            disable: function(){
                popupEnabled = false;
            },
            /**
             * Set how many times the visitor should see the popup
             * @param number int
             */
            setCloseCount: function(number){
                maxCloseCount = number;
            },
            /**
             * Reset campaign
             */
            reset: function(){
                deleteLocalStorage();
                deleteCookie();
            },
            closePopup: function(){

            },
            openPopup: function(){
                createPopup();
            }

        };

        // Return my external API
        return api;
    };

});
