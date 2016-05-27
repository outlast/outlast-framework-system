/**
 * Define this data attribute.
 * @attr data-track-category Event category, required
 * @attr data-track-label  Event label, required
 * @attr data-track-action  Event action, optional
 * @attr data-track-action  Event value, optional
 **/
define('system/js/data/track', ["../ofw-jquery"], function() {

    /** Private properties **/
    var _objects = [];			// An array of autopagination objects on this page

    /** Private API **/

    /**
     * Object init
     */
    var init = function(){
        // Nothing happens here for now, everything in public init.
    };

    /** Public API **/
    var api = {

        /**
         * Activate all the data attributes in this context.
         * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
         * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
         */
        activate: function($elements, $context) {
            $elements.click(function(){
                var el =  $(this);
                var category = el.attr('data-track-category');
                if(typeof(category) == 'undefined' || category == '') {
                    ofw.log('track.js: data-track-category attribute is required to send events.');
                }

                var label = el.attr('data-track-label');
                if(typeof(label) == 'undefined' || label == '') {
                    ofw.log('track.js: data-track-label attribute is required to send events.');
                }

                if(typeof(el.attr('data-track-action')) != 'undefined'){
                    var action = el.attr('data-track-action');
                }
                else{
                    var action = '';
                }

                if(typeof(el.attr('data-track-value')) != 'undefined'){
                    var value = el.attr('data-track-value');
                }
                else{
                    var value = '';
                }

                ofw.track(category, action, label, value);
            });
        }
    };

    /** Perform private initialization **/
    init();

    // Return my external API
    return api;

});