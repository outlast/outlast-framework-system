/**
 * Popup campaign object
 **/
define('system/js/ui/popup-campaign', ["../ofw-jquery"], function() {

    return function PopupCampaign(options){
        /** Options **/
        var defaultOptions = {
            url: null,     // The controller that displays the popup HTML (in ofw.alert)
            selector: null,       // OR you can use this instead of url to simple removeClass('hide') on the popup campaign div
            timeDelay: 25000,                   // 25 seconds delay before the popup is shown. You should use localStorage for the start time, so that the timer is preserved across page views.
            cookieName: 'popupcampaign',        // Optional, defaults to 'popupcampaign' - The name of the cookie (or localstorage key?) that stores the number of times this user has seen this item. Only needed if you have several per page.
            cookieExpiryDays: 90,               // Optional, defaults to 90 - The number of days after which the cookie / localstorage expires.
            showCount: 1,                       // Optional, defaults to 1 - The number of times this visitor sees the popup campaign before it is no longer shown again (until the cookie expires).
            showAgainAfterDays: 3,              // Optional, defaults to 3 - The number of days after which a visitor should again see the popup (only relevant if showCount > 1)
            openButton: null,      // Optional, defaults to null - If set, a click event will be added to this selector that triggers openPopup()
            closeButton: null      // Optional, defaults to null - If set, a click event will be added to this selector that triggers closePopup()

        };

        var myOptions = {};

        /** Private properties **/



        /** Private API **/

        /**
         * Object init
         */
        var init = function(){
            // Merge default options
            myOptions = $.extend(true, {}, defaultOptions, options);

            setTimeout(function(){
                createPopup();
            }, myOptions.timeDelay);
        };

        var createPopup = function(){
            // popup campaign called without a controller
            if(myOptions.url == null && myOptions.selector == null){
                return console.error('Popup campaign called without controller and selector. Check the documentation and define the url or selector parameter.');
            }

            // a controller was defined
            if(myOptions.url != null){

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

            },
            disable: function(){

            },
            setCloseCount: function(number){

            },
            reset: function(){

            },
            closePopup: function(){

            },
            openPopup: function(){

            }

        };

        // Return my external API
        return api;
    };

});
