/**
 * Define my text manipulation library.
 **/
define(["../ofw-jquery"], function() {

    /** Properties **/

    /** Private API **/

    /**
     * Object init
     */
    var init = function(){
    	// Add any init here
    };

    /** Public API **/

    var api = {

        /**
         * Activate all the data attributes in this context.
		 * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
		 * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
         */
        activate: function($elements, $context) {
			// Cross-browser transition end event trigger
			if($context.find('[data-action-event="trans-end"]').length) {
				$(document).on('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd', '[data-action-event="trans-end"]', function () {
					$(this).trigger('trans-end');
				});
			}

			if($context.find('[data-action-event="anim-end"]').length) {
				// Cross-browser animation end event trigger
				$(document).on('animationend webkitAnimationEnd oAnimationEnd MSAnimationEnd', '[data-action-event="anim-end"]', function () {
					$(this).trigger('anim-end');
				});
			}

			ofw.scroll_interval = null;
			ofw.scroll_elements = [];

			ofw.touch_positions = {
				startX: null,
				startY: null,
				currentX: null,
				currentY: null
			};
        }

    };

    /** Perform initialization **/
    init();

    // Return my external API
    return api;

});