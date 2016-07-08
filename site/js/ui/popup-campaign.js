/**
 * Popup Campaign object
 **/

define('system/js/ui/popup-campaign', ["../ofw-jquery"], function(options) {

    /** Init options **/
    var defaultOptions = {
        url: null,                                  // The controller that displays the popup HTML (in ofw.alert)
        popupSelector: '#mypopup',                  // OR you can use this instead of url to simple removeClass('hide') on the popup campaign div
        timeDelay: 25000,                           // 25 seconds delay before the popup is shown. You should use localStorage for the start time, so that the timer is preserved across page views.
        cookieName: 'popupcampaign',                // Optional, defaults to 'popupcampaign' - The name of the cookie that stores the number of times this user has seen this item. Only needed if you have several per page.
        cookieExpiryDays: 90,                       // Optional, defaults to 90 - The number of days after which the cookie / localstorage expires.
        showCount: 1,                               // Optional, defaults to 1 - The number of times this visitor sees the popup campaign before it is no longer shown again (until the cookie expires).
        showAgainAfterDays: null,                   // Optional, defaults to 3 - The number of days after which a visitor should again see the popup (only relevant if showCount > 1)
        openButtonSelector: '#someopenbutton',      // Optional, defaults to null - If set, a click event will be added to this selector that triggers openPopup()
        closeButtonSelector: '#mypopup .close'      // Optional, defaults to null - If set, a click event will be added to this selector that triggers closePopup()
    };

    var myOptions = {};

    var closeCount = 0;

    /** Private API **/

    /**
     * Object init
     */
    var construct = function(options){
        // Merge default options
        myOptions = $.extend(true, {}, defaultOptions, options);
        return api;
    };

    var setCookieData = function(key, value) {

    }

    /** Public API **/

    var api = {

        /**
         * Enable campaign
         *
         */
        enable: function() {

        },

        /**
         * Disable campaign
         *
         */
        disable: function() {

        },

        /**
         * Start campaign
         *
         */
        start: function() {

        },

        /**
         * Reset campaign
         *
         */
        reset: function() {

        },

        /**
         * Open popup
         *
         */
        openPopup: function() {

        },

        /**
         * Close popup
         *
         */
        closePopup: function() {
            closeCount++;
        },

        /**
         * Set popup close count
         *
         * @param {integer} number Number of counts
         */
        setCloseCount: function(number) {
            closeCount = number;
        }
    };

    /** Perform initialization **/

    // Return my external API
    return construct(options);

});

