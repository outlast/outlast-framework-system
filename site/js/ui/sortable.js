/**
 * Define my sortable ui.
 * @todo This is a quick, compatible repositioning of the script. Needs a review and partial rewrite!
 **/
define(["../ofw-jquery"], function() {

    /** Options **/
    var defaultOptions = {
	};
	var myOptions;

    /** Private properties **/
    var sortable;
    var $element;

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
		 * Enables sortable features on a list of items. Requires jquery-ui sortable feature.
		 * @param {string|jQuery} target The items to sort. Each item must have an data-sortable field corresponding to the id of item.
		 * @param {string} url The url which will handle this sortable request.
		 * @param {function} callback A callback function to call after sort. An array of ids in the new order are passed.
		 * @param {string|jQuery|boolean} handle The handle is the item which can be used to drag. This can be a selector, a jQuery object, or false. The default is false which means the whole item is draggable.
		 **/
		init: function(target, url, callback, handle){
			// Save target as element
			$element = $(target);

			// Destroy any previous
			if($element.hasClass('ui-sortable')) $element.sortable('destroy');

			// Defaults handle to false
			if(typeof handle == 'undefined') handle = false;

			// Make sortable
			sortable = $element.sortable({
				handle: handle,
			    start: function(event, ui) {
			    	ui.item.addClass('sortableinprogress');
			    },
			    stop: function(event, ui) {
			    	ui.item.removeClass('sortableinprogress');
					// Build array
						var my_array = [];
						$element.children().each(function(){
							var my_id = $(this).attr('data-sortable');
							if(!my_id) ofw.error("Cannot sort: data-sortable not set!");
							else my_array.push(my_id);
						});
						ofw.ajax.post(url+'?reorder='+JSON.stringify(my_array), function(){
							if(typeof callback == 'function') callback(my_array);
						});
			    }
			});
		}
	};

    /** Perform private initialization **/
    init();

    // Return my external API
    return api;
});