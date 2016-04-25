/**
 * Define this data attribute.
 * @attr data-single-click Defines any javascript that is to be executed once even if the user double clicks.
 * @attr data-single-click-delay Defines the number of ms before the user can click again. Defaults to 1500. (optional)
 **/
define('system/js/data/single-click', ["../ofw-jquery"], function() {

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
			$elements.click(function(){
				var el =  $(this);
				var delay = el.attr('data-single-click-delay');
				if(!delay) delay = 1500;
				// Stop clicks if already
				if(el.hasClass('single-click')) return false;
				// Save element for later and clear it in delay seconds
				el.addClass('single-click');
				setTimeout(function(){ el.removeClass('single-click'); }, delay);
				// Execute javascript
				return (function(){ eval(el.attr('data-single-click')); }).call(el);
			});
        }

    };

    /** Perform initialization **/
    init();

    // Return my external API
    return api;

});