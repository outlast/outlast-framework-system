/**
 * Define this data attribute.
 * @attr data-featured The id of the item.
 * @attr data-featured-model The model's name.
 * @attr data-featured-value The featured value.
 **/
define('system/js/data/featured', ["../ofw-jquery"], function() {

    /** Properties **/
    var onClass = 'fa-star';
    var offClass = 'fa-star-o';
    var thinkingClass = 'fa-refresh';

    /** Private API **/

    /**
     * Object init
     */
    var init = function(){
    	// Add any init here
    };

	/**
	 * Toggle the item.
	 * @param {string} model The model name.
	 * @param {string} id The object id.
	 */
	var toggleItem = function(model, id){
		// Set to thinking
		var $el = $('[data-featured='+id+']');
		turnUiThinking($el);

		// Set it!
		ofw.ajax.post('admin/'+model+'/toggle/featured/?id='+id, function(r, rData){
			if(rData.status == 'ok'){
				if(rData.featured == 'yes') turnUiOn($el);
				else turnUiOff($el);
			}
			else ofw.alert(rData.status);
		});

	};

	/**
	 * Turns the ui on / off
	 * @param {jQuery} $el The element.
	 */
	var turnUiOn = function($el){
		var $myi = $el.children('i').first();
		$myi.removeClass(offClass).removeClass(thinkingClass);
		$myi.addClass(onClass);
	};
	var turnUiOff = function($el){
		var $myi = $el.children('i').first();
		$myi.removeClass(onClass).removeClass(thinkingClass);
		$myi.addClass(offClass);
	};
	var turnUiThinking = function($el){
		var $myi = $el.children('i').first();
		$myi.removeClass(onClass).removeClass(offClass);
		$myi.addClass(thinkingClass);
	};

	/**
	 * Update ui based on the value.
	 */
	var updateUi = function($element){
		if($element.data('featured-value')) turnUiOn($element);
		else turnUiOff($element);
	};

    /** Public API **/

    var api = {

        /**
         * Activate all the data attributes in this context.
		 * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
		 * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
         */
        activate: function($elements, $context) {

        	$elements.each(function(){
        		var $element = $(this);

        		// Toggle the element ui
				updateUi($element);

        		// Add click event
        		$element.click(function(){
					toggleItem($element.data('featured-model'), $element.data('featured'));
        		});

        	});
        },

		/**
		 * Toggle the item.
		 * @param {string} model The model name.
		 * @param {string} id The object id.
		 */
		toggleItem: toggleItem

	};

	/** Perform initialization **/
    init();

    // Return my external API
    return api;

});