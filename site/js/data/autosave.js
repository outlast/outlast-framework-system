/**
 * Autosave allows you to save the value of an input automatically on the client side in local storage.
 * @attr data-autosave This is a unique key which defines under which key the localStorage stores the data.
 **/
define('system/js/data/autosave', ["../ofw-jquery"], function() {

    /** Private properties **/
	var _keys = [];			// An array of keys found on this page
	var _keyPrefix = 'data-autosave-';

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
         * Activate the data attributes in this context.
		 * @param {jQuery|Array} $elements An array of jQuery objects that have the data attribute.
		 * @param {jQuery} [$context=$(document)] The current jQuery object context in which the handlers are searched for.
		 * @todo add <select> support!
         */
        activate: function($elements, $context) {
        	$elements.each(function(){
        		// Get my info
        		var $this = $(this);
        		var myKey = _keyPrefix+$this.attr('data-autosave');
        		if(!window.localStorage) return false;
        		if(!myKey){ return ofw.error("No key supplied for [data-autosave], autosave not activated!"); }

	        	// Restore the value
	        	var previousValue = window.localStorage.getItem(myKey);
	        	if(previousValue != null){
	        		$this.val(previousValue);
	        	}

	        	// Add keyup event
	        	$this.keyup(function(){
	        		window.localStorage.setItem(myKey, $this.val());
	        	});

	        	// Add clear event on submit of parent form
	        	_keys.push(myKey);

	        	// Automatically clear items within the form if successfully posted
	        	$this.parents('form').on('ofw-ajax-success', function(){
	        		var $form = $(this);
	        		var keys = [];
	        		$form.find('[data-autosave]').each(function(){
						keys.push(_keyPrefix+$(this).attr('data-autosave'));
	        		});
	        		api.clear(keys);
	        	});

        	});
    	},

		/**
		 * Clear my keys from this page or a specific key or a set of keys.
		 * @param {string|Array} [key=null] Can be a specific key or an array of keys. If key is not specified, all keys found on this page will be cleared.
		 */
		clear: function(key){

			if(!window.localStorage) return false;
			
			// Single or all
			var myKeys;
			if(typeof key == 'object') myKeys = key;
			else if(key != null) myKeys = [key];
			else myKeys = _keys;

			// Remove each item
			myKeys.forEach(function(el){
				window.localStorage.removeItem(el);
			});
		}

	};

	/** Perform private initialization **/
    init();

    // Return my external API
    return api;

});