/**
 * Define my search ui.
 * @todo This is a quick, compatible repositioning of the script. Needs a review and partial rewrite!
 **/
define(["../ofw-jquery"], function() {

    /** Options **/
    var defaultOptions = {
		delay: 300,						// Number of miliseconds before
		url: false,						// The url to send the request to. This should be relative. &query=your+query will be appended. If no url (false), it will not be submitted anywhere.
		callback: false,				// A function or an element.
		method: 'get',					// The method to send by. Values can be 'post' or 'get' (default).
		allow_empty_query: true,		// If set to true, an empty query will also execute
		pushstate_url: 'auto'			// If set to 'auto', the url will automatically change via pushstate. Set to false for not pushstate. Set to specific for custom.
	};
	var myOptions;

    /** Private properties **/
    var timer;
    var $element;
    var lastQuery;


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
		 * Public initialization method.
		 **/
		init: function(element, options){
			// Merge default options
			myOptions = $.extend(true, {}, defaultOptions, options);

			// Register events and timers
			timer = false;
			$element = $(element);
			$element.keyup(function(){
				api.keyup();
			});
			$element.blur(function(){
				api.send();
			});

			// Return api for chaining
			return api;
		},
		/**
		 * @deprecated Backwards compatibility!
		 */
		initialize: init,

		/**
		 * Sends a keyup event to retrigger search
		 **/
		keyup: function(){
			lastQuery = '';
			api.resetTimer();
		},

		/**
		 * Resets the timer.
		 **/
		resetTimer: function(){
			// reset earlier timer
			if(timer){ clearTimeout(timer); }
			// now set a new timer
			timer = setTimeout(function(){ api.send(); }, myOptions.delay);
		},

		/**
		 * Sends the query to the set url and processes.
		 **/
		send: function(){
			// if the element value is empty, do not do anything
			if(!myOptions.allow_empty_query && !$element.val()) return false;

			// if url not set, just do callback immediately!
			if(myOptions.url){
				// append element value to url
					var url = myOptions.url;
					if(myOptions.url.indexOf('?') >= 0) url += '&query='+$element.val();
					else url += '?query='+$element.val();
				// check if the current query is like last query, if so, dont resend
					if(lastQuery == $element.val()) return false;
					else lastQuery = $element.val();
				// pushstate?
					var pushstate_url = null;
					if(myOptions.pushstate_url == 'auto'){
						pushstate_url = ofw.baseurl+url;
					}
					else if(myOptions.pushstate_url !== false){
						pushstate_url = myOptions.pushstate_url;
					}
				// now send via the appropriate method
					if(myOptions.method == 'get') ofw.ajax.get(url, myOptions.callback, pushstate_url);
					else ofw.ajax.post(url, myOptions.callback, pushstate_url);
			}
			else{
				myOptions.callback($element.val());
			}
			return true;
		}
    };

    /** Perform initialization **/
    init();

    // Return my external API
    return api;

});