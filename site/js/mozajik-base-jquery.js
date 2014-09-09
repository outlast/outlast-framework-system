/**
 * This is the basic Mozajik JS layer class for jQuery. The JS layer has three sections, 'base' (basic stuff for sending requests, logging, etc.), 'tool' (tabs, forms, etc.),
 *	and 'ui' (user interface elements)
 * @author Aron Budinszky /aron@outlast.hu/
 * @version 1.0
 * 
 * @changes 1.0 Now supports pushstate, but ajax methods' parameter order has changed: bind is now the fourth param, the third is the new url.
 **/
 
// Create a new class which will contain the sections
	var zaj = {
		baseurl:'',
		fullrequest:'',
		fullurl:'',
		app:'',
		mode:'',
		debug_mode:false,
		protocol:'http',
		jslib:'jquery',
		jslibver:1.10,
		trackevents_analytics:true,
		trackevents_local:false,
		fields: {}
	};

// Detect various fixed features (pushstate)
	// Pushstate support (from pjax)
	zaj.pushstate = window.history && window.history.pushState && window.history.replaceState
					// pushState isn't reliable on iOS until 5.
					&& !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/)
	// Mobile
	zaj.mobile = (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent));

// Detect various dynamically loaded features (bootstrap, facebook, etc.)
	$(document).ready(function(){
		zaj.bootstrap = (typeof $().modal == 'function');
		zaj.bootstrap3 = (typeof $().emulateTransitionEnd == 'function');
		zaj.facebook = (window.parent != window) && FB && FB.Canvas;
		zaj.fbcanvas = false;
		if(zaj.facebook){
			FB.Canvas.getPageInfo(function(info){ zaj.fbcanvas = info; });
		}
	});

	/**
	 * Backwards compatibility for mootools
	 **/
	 var $$ = function(e){
	 	zaj.notice('Notice: Used $$ in jQuery.');
	 	return $(e);
	 };

/**
 * Enable JS error logging to Analytics.
 **/
	window.onerror=function(message, url, line){
		// Log to analytics
			if(typeof ga != 'undefined') ga('send', 'exception', {
					exDescription: 'Javascript error on line '+line+' of '+url+': '+message,
					exFatal: true
    			});
		// Allow event to propogate by returning false
			return false;
	};

	/**
	 * Send exception to Google Analytics.
	 * @param {string} message The message to send.
	 * @param {boolean} [fatal=false] Set this to true if this is a fatal error. False by default.
	 * @return {boolean} Always returns false.
	 */
	zaj.exception = function(message, fatal){
		// Set default value for fatal
			if(typeof fatal == 'undefined') fatal = false;
		// Check if new GA available
			if(typeof ga != 'undefined') ga('send', 'exception', {
					exDescription: message,
					exFatal: fatal
    			});
		return false;
	};

/**
 * Mozajik zaj object implementations.
 **/
	/**
	 * Layer for onready functions.
	 * @param {function} func The callback function.
	 **/
 	zaj.ready = function(func){ $(document).ready(func); };

	/**
	 * Logs a message to the console. Ingored if console not available.
	 * @param {string} message The message to log.
	 * @param {string} [type="error"] Can be notice, warning, or error
	 * @param {string} [context=null] The context is any other element or object which will be logged.
	 * @return {boolean} Returns true or console.log.
	 **/
	zaj.log = function(message, type, context){
		if(typeof console != 'undefined' && typeof(console) == 'object'){
			if(typeof context == 'undefined') context = '';
			switch(type){
				case 'error':
					zaj.exception(message, true);
					return console.error(message, context);
				case 'warn':
				case 'warning':
					zaj.exception(message, false);
					return console.warn(message, context);
				case 'info':
				case 'notice': return console.info(message, context);
				default: console.log(message, context);
			}
		}
		return true;
	};

	/**
	 * Logs an error message to the console.
	 * @param {string} message The message to log.
	 * @param {string} [context=null] The context is any other element or object which will be logged.
	 * @return {boolean} Returns true or console.log.
	 **/
	zaj.error = function(message, context){
		return zaj.log(message, 'error', context);
	};

	/**
	 * Logs a warning message to the console.
	 * @param {string} message The message to log.
	 * @param {string} [context=null] The context is any other element or object which will be logged.
	 * @return {boolean} Returns true or console.log.
	 **/
	zaj.warning = function(message, context){
		return zaj.log(message, 'warning', context);
	};

	/**
	 * Logs a notice message to the console.
	 * @param {string} message The message to log.
	 * @param {string} [context=null] The context is any other element or object which will be logged.
	 * @return {boolean} Returns true or console.log.
	 **/
	zaj.notice = function(message, context){
		return zaj.log(message, 'notice', context);
	};

	/**
	 * Go back in history.
	 * @deprecated
	 **/
	zaj.back = function(){ history.back(); };

	/**
	 * Custom alerts, confirms, prompts. If bootstrap is enabled, it wil use that. Otherwise the standard blocking alert() will be used.
 	 * @param {string} message The message to alert.
 	 * @param {string|function} [urlORfunction=null] A callback url or function on button push.
	 * @param {string} [buttonText="Ok"] The text of the button.
	 * @param {boolean} [top=false] Set to true if you want the url to load in window.top.location. Defaults to false.
	 * @return {boolean} Will always return false.
 	 */
	zaj.alert = function(message, urlORfunction, buttonText, top){
		if(zaj.bootstrap){
			// Alert sent via bootstrap
				zaj.track('Alert', 'Bootstrap', message);
			// Cache my jquery selectors
				// DON't do this, as it causes problems!

			// Create modal if not yet available
				if($('#zaj_bootstrap_modal').length <= 0){
					// Check to see which Bootstrap version
					if(zaj.bootstrap3) $('body').append('<div id="zaj_bootstrap_modal" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"></div><div class="modal-footer"><a type="button" class="btn modal-button btn-default" data-dismiss="modal">Ok</a></div></div></div></div>');
					else $('body').append('<div id="zaj_bootstrap_modal" class="modal hide fade"><div class="modal-body"></div><div class="modal-footer"><a data-dismiss="modal" class="modal-button btn">Ok</a></div></div>');
				}
			// Reset and init button
				// Set action
				$('#zaj_bootstrap_modal a.modal-button').unbind('click');
				if(typeof urlORfunction == 'function') $('#zaj_bootstrap_modal a.modal-button').click(function(){ $('#zaj_bootstrap_modal').modal('hide'); $('.modal-backdrop').remove(); urlORfunction(); });
				else if(typeof urlORfunction == 'string') $('#zaj_bootstrap_modal a.modal-button').click(function(){ zaj.redirect(urlORfunction, top); });
				else $('#zaj_bootstrap_modal a.modal-button').click(function(){ $('#zaj_bootstrap_modal').modal('hide'); $('.modal-backdrop').remove(); });
				// Set text (if needed)
				if(typeof buttonText == 'string') $('#zaj_bootstrap_modal a.modal-button').html(buttonText);
			// Backdrop closes on mobile
				var backdrop = 'static';
			// Set body and show it (requires selector again)
				$('#zaj_bootstrap_modal div.modal-body').html(message);
				$('#zaj_bootstrap_modal').modal({backdrop: backdrop, keyboard: false});
			// Reposition the modal if needed
				zaj.alert_reposition($('#zaj_bootstrap_modal'));
		}
		else{
			// Alert sent via bootstrap
				zaj.track('Alert', 'Standard', message);
			// Send alert
				alert(message);
				if(typeof urlORfunction == 'function') urlORfunction();
				else if(typeof urlORfunction == 'string') zaj.redirect(urlORfunction, top);
		}
		return false;
	};
	zaj.alert_reposition = function($modal){
		if(zaj.facebook){
			FB.Canvas.getPageInfo(function(e){
				// Top bar
					var fb_top_bar = e.offsetTop;
					var fb_bottom_bar = 250;
					var $modalbody = $modal.find('.modal-body');
					var overflow_mode = 'scroll';
				// Calculate my top position
					var topoffset = 20;
					if(e.scrollTop > fb_top_bar) topoffset += e.scrollTop - fb_top_bar;
				// Get my content height
					var content_height = $modalbody.height(0)[0].scrollHeight;
				// Set height
					var height = e.clientHeight - fb_bottom_bar;
					// If we are near the bottom
					if(topoffset + height > $(window).height()) height = $(window).height() - topoffset - fb_bottom_bar;
					// If we are near the top
					if(e.scrollTop < fb_top_bar) height = e.clientHeight - fb_top_bar - 150 + e.scrollTop;
				// Subtract modal footer from height
					height -= $modal.find('.modal-footer').height();
				// If the height in the end is larger than the content height, then just use content height
					if(height > content_height){
						height = content_height;
						overflow_mode = 'auto';
					}
				// Set the modal body to autosize
					$modal.find('.modal-body').css({width:'auto', height: height, 'overflow-y': overflow_mode});
					$modal.css({top: topoffset, overflow: 'hidden', 'margin-top': 0});
			});
			// clear and set
			setTimeout(function(){ zaj.alert_reposition($modal); }, 1000);
		}
	};
	zaj.confirm = function(message, urlORfunction){
		// if the passed param is a function, then return confirmation as its param
		if(typeof urlORfunction == 'function'){
			var result = confirm(message);
			urlORfunction(result);
		}
		// if the passed param is a url, redirect if confirm
		else{
			if(confirm(message)) window.location=zaj.baseurl+urlORfunction;
		}
	};
	zaj.prompt = function(message){
		return prompt(message);
	};

		/**
		 * Track events in GA and/or locally. Event labels/etc are whatever you want them to be.
		 * @param {string} category  A category.
		 * @param {string} action  An action.
		 * @param {string} label  A label.
		 * @param {string} [value] A value.
		 */
		zaj.track = function(category, action, label, value){
			// Track via Google Analytics (ga.js or analytics.js)
				if(zaj.trackevents_analytics){
					if(typeof _gaq != 'undefined') _gaq.push(['_trackEvent', category, action, label, value]);
					if(typeof ga != 'undefined') ga('send', 'event', category, action, label, value);
				}
			// Track to local database
				if(zaj.trackevents_local){
					// Don't use zaj.ajax.get because that tracks events, so we'd get into loop
					$.ajax(zaj.baseurl+'system/track/?category='+zaj.urlencode(category)+'&action='+zaj.urlencode(action)+'&label='+zaj.urlencode(label)+'&value='+zaj.urlencode(value), {
						dataType: 'html',
						type: 'GET'
					});
				}
		};

	/**
	 * Reload the current url.
	 **/
		zaj.reload = function(){
			window.location.reload(false);
		};
		zaj.refresh = zaj.reload;
			
	/**
	 * Redirect to a page relative to baseurl or absolute.
	 * @param {string} relative_or_absolute_url The URL relative to baseurl. If it starts with // or http or https it is considered an absolute url
	 * @param {boolean} [top=false] Set this to true if you want it to load in the top iframe.
	 **/
		zaj.redirect = function(relative_or_absolute_url, top){
			if(typeof relative_or_absolute_url == 'undefined' || !relative_or_absolute_url) return false;
			if(typeof top == 'undefined') top = false;
			// Is it relative?
			if(relative_or_absolute_url.substr(0,2) != '//' && relative_or_absolute_url.substr(4, 3) != "://" && relative_or_absolute_url.substr(5, 3) != "://") relative_or_absolute_url = zaj.baseurl+relative_or_absolute_url;
			// Top or window
			if(top) window.top.location = relative_or_absolute_url;
			else window.location = relative_or_absolute_url;
			return true;
		};

	/**
	 * Smooth scrolling in and outside of Facebook tabs and canvas pages.
	 * @param {Number|object|string} yORdomORselector The pixel value or the dom/jquery object or a selector of where to scroll to.
	 * @param {Number} [duration=1000] The number of miliseconds for the animation.
	 **/
		zaj.scroll = function(yORdomORselector, duration){
			// Get the y
				var y;
				switch(typeof yORdomORselector){
					case 'string':
					case 'object':
						y = $(yORdomORselector).offset().top;
						break;
					default:
						y = yORdomORselector;
						break;
				}
			// Default duration
				if(typeof duration == 'undefined') duration = 1000;
			// First, do the standard scrolling (will work outside of FB)
				$('html,body').animate({scrollTop: y}, {duration: duration});
			// If within FB Canvas context, we need more...
				if(zaj.facebook){
					var blue_bar_height = 42;
					FB.Canvas.getPageInfo(function(pageInfo){
						$({y: pageInfo.scrollTop}).animate(
							{y: y + pageInfo.offsetTop - blue_bar_height},
							{duration: duration, step: function(offset){ FB.Canvas.scrollTo(0, offset); }
						});
					});
				}
		};

	/**
	 * Ajax methods.
	 **/
		zaj.ajax = {};

			/**
			 * Helper for zaj.ajax.request.
			 */
			zaj.ajax.submitting = false;

			/**
			 * Send AJAX request via GET.
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 */
			zaj.ajax.get = function(request,result, pushstate){
				zaj.ajax.request('get', request, result, pushstate);
			};

			/**
			 * Send AJAX request via POST.
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 */
			zaj.ajax.post = function(request,result, pushstate){
				zaj.ajax.request('post', request, result, pushstate);
			};

			/**
			 * Sends a blocked AJAX request via POST.
			 * @link http://framework.outlast.hu/api/javascript-api/ajax-requests/#docs-blocking-form-requests
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 */
			zaj.ajax.submit = function(request,result,pushstate){
				// if submitting already, just block!
					if(zaj.ajax.submitting) return false;
				// toggle submitting status
					zaj.ajax.submitting = true;
					var el = $('[data-submit-toggle-class]');
					if(el.length > 0){
						el.toggleClass(el.attr('data-submit-toggle-class'));
					}
				return zaj.ajax.request('post', request, result, pushstate, true);
			};

			/**
			 * Send an AJAX request via POST or GET.
			 * @param {string} mode Can be post or get.
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 * @param {boolean} set_submitting If set to true, it will set zaj.ajax.submitting when the request returns with a response.
			 * @return {string} Returns the request url as sent.
			 */
			zaj.ajax.request = function(mode,request,result,pushstate,set_submitting){
				// is pushstate used now
					var psused = zaj.pushstate && (typeof pushstate == 'string' || typeof pushstate == 'object' || (typeof pushstate == 'boolean' && pushstate === true));
					var psdata = false;
					if(typeof pushstate == 'object' && pushstate != null && pushstate.data) psdata = pushstate.data;
				// Send to Analytics
					zaj.track('Ajax', mode, request);
				// Figure out query string
					var datarequest;
					if(mode == 'post'){
						var rdata = request.split('?');
						if(rdata.length > 2){
							// Display warning
								zaj.warning("Found multiple question marks in query string: "+request);
						}
						request = rdata[0];
						datarequest = rdata[1];
					}
					else datarequest = '';
				// Add baseurl if not protocol. If not on current url, you must enable CORS on server.
					if(request.substr(0, 5) != 'http:' && request.substr(0, 6) != 'https:') request = zaj.baseurl+request;
				// Now send request and call callback function, set callback element, or alert
					$.ajax(request, {
						success: function(data, textStatus, jqXHR){
							// Send to Analytics
								zaj.track('AjaxSuccess', mode, request);
							// Set my submitting to false
								if(set_submitting){
									zaj.ajax.submitting = false;
									var el = $('[data-submit-toggle-class]');
									if(el.length > 0) el.toggleClass(el.attr('data-submit-toggle-class'));
								}
							// Handle my results
								if(typeof result == "function") result(data);
								else if(typeof result == "object"){
									$(result).html(data);
								}
								else{
									var validationResult = zaj.validate(data);
									if(validationResult === true) zaj.redirect(result);
									else return validationResult;
								}
							// pushState actions
								if(psused){
									// if psdata not specified
										if(psdata == false) psdata = {url: window.location.href};
									// string mode - convert to object
										if(typeof pushstate == 'string') pushstate = {'data': psdata, 'title':"", 'url': pushstate};
									// boolean mode - use current request
										else if(typeof pushstate == 'boolean') pushstate = {'data': psdata, 'title':"", 'url': request};
									// now set everything and fire event
										pushstate = $.extend({}, {'title': false}, pushstate);	// default title is false
										if(pushstate.url) window.history.pushState(psdata, pushstate.title, pushstate.url);
										if(pushstate.title) document.title = pushstate.title;
								}
						},
						complete: function(jqXHR, textStatus){
							// Set error msgs
								if(textStatus != "success"){
									// Set my submitting to false
										if(set_submitting){
											zaj.ajax.submitting = false;
											var el = $('[data-submit-toggle-class]');
											if(el.length > 0) el.toggleClass(el.attr('data-submit-toggle-class'));
										}
									// Send a log
										zaj.error("Ajax request ("+request+") failed with status "+textStatus, true);
								}
						},
						data: datarequest,
						dataType: 'html',
						type: mode,
						cache: false
					});
				return request;
			};

			/**
			 * Perform validation with return data.
			 * @param {string} data This can be a json string or a non-json string.
			 * @return {boolean} Will return true if validation was successful, false if not.
			 */
			zaj.validate = function(data){
				/** @type {{status: String, message: String, highlight: Array, errors: <string, string>}|boolean} dataJson */
				var dataJson;
				// check to see if data is json
				try{
					dataJson = $.parseJSON(data);
				}
				// not json, so parse as string
				catch(err){
					dataJson = false;
				}

				// If data json is not false, then it is json
				if(dataJson !== false){
					if(dataJson.status == 'ok') return true;
					else{
						// Define vars
							var input, inputGroup, inputCleanup;
							/** @type {boolean|Number} scrollTo */
							var scrollTo = false;
						// Display a message (if set)
							if(dataJson.message != null) zaj.alert(dataJson.message);
						// Highlight the fields (if set)
							// @todo Make sure that fields are only selected if they are part of the request to begin with! But how?
							if(typeof dataJson.highlight == 'object'){
								$.each(dataJson.highlight, function(key, val){
									// Add the error class and remove on change
									input = $('[name="'+val+'"]');
									inputGroup = input.parent('.form-group');
									inputCleanup = function(){
										$(this).removeClass('has-error');
										$(this).parent('.form-group').removeClass('has-error');
									};
									input.addClass('has-error').change(inputCleanup).keyup(inputCleanup);
									inputGroup.addClass('has-error');
									// Check to see if input is higher than any previous input
									/** @type {Number|Window} inputOffset */
									var inputOffset = input.offset().top;
									if(scrollTo === false || inputOffset < scrollTo) scrollTo = inputOffset;
								});
							}
						// Display errors for each field
							// @todo Make sure that fields are only selected if they are part of the request to begin with! But how?
							if(typeof dataJson.errors == 'object'){
								$.each(dataJson.errors, function(key, msg){
									// Get input and input group
									input = $('[name="'+key+'"]');
									inputGroup = input.parent('.form-group');
									inputCleanup = function(){
										$(this).removeClass('has-error');
										$(this).parent('.form-group').removeClass('has-error');
										if(zaj.bootstrap3) $(this).tooltip('hide');
									};
									// @todo enable invisible fields to somehow work their magic! have a date-attribute with the selector of the visible field
									if(zaj.bootstrap3 && input.filter(':visible').length > 0){
										input.attr('title', msg).attr('data-original-title', msg).tooltip({trigger:'manual', animation: false}).tooltip('show');
										// Add the error class and remove on change
										input.addClass('has-error').change(inputCleanup).keyup(inputCleanup);
										inputGroup.addClass('has-error');
										// Check to see if input is higher than any previous input
										/** @type {Number|Window} inputOffset */
										var inputOffset = input.offset().top - input.next('.tooltip').height() - 10;
										if(scrollTo === false || inputOffset < scrollTo) scrollTo = inputOffset;
									}
									else zaj.alert(msg);
								});
							}
						// Scroll to top-most
							if(scrollTo !== false) zaj.scroll(scrollTo);
					}
				}
				// not json, so parse as string
				else{
					if(data == 'ok') return true;
					else zaj.alert(data);
				}
				// all other cases return false
				return false;
			};

		/**
		 * Class Search creates a search box which sends ajax requests at specified intervals to a given url.
		 * @author Aron Budinszky /aron@outlast.hu/
		 * @todo Add placeholder text
		 * @version 3.0
		 */
		zaj.search = {
			options: {
				delay: 300,						// Number of miliseconds before 
				url: false,						// The url to send the request to. This should be relative. &query=your+query will be appended. If no url (false), it will not be submitted anywhere.
				callback: false,				// A function or an element.
				method: 'get',					// The method to send by. Values can be 'post' (default) or 'get'.
				allow_empty_query: true,		// If set to true, an empty query will also execute
				pushstate_url: 'auto'			// If set to 'auto', the url will automatically change via pushstate. Set to false for not pushstate. Set to specific for custom.
			},
			
			/**
			 * Creates a new Search object
			 **/
				initialize: function(element, options){
					// set default options
						this.options = $.extend(this.options, options);
					// register events
						this.timer = false;
						this.element = $(element);
						var self = this;
						this.element.keyup(function(){ self.timeout() });
						this.element.blur(function(){
							self.send();
						});
					return self;
				},
			
			/**
			 * Sends a keyup event to retrigger search!
			 **/
				keyup: function(){
					// reset my last request
						this.last_query = '';
					// timeout
						this.timeout();
				},

			/**
			 * Starts a timeout.
			 **/
				timeout: function(){
					// set stuff
						var self = this;
					// reset earlier timer
						if(this.timer){ clearTimeout(this.timer); }
					// now set a new timer
						this.timer = setTimeout(function(){ self.send(); }, this.options.delay);
				},
			/**
			 * Sends the query to the set url and processes.
			 **/
				send: function(){
					// if the element value is empty, do not do anything
						if(!this.options.allow_empty_query && !this.element.val()) return false;
					// if url not set, just do callback immediately!
					if(this.options.url){			
						// append element value to url
							var url = this.options.url;
							if(this.options.url.indexOf('?') >= 0) url += '&query='+this.element.val();
							else url += '?query='+this.element.val();
						// check if the current query is like last query, if so, dont resend
							if(this.last_query == this.element.val()) return false;
							else this.last_query = this.element.val();
						// pushstate?
							var pushstate_url = null;
							if(this.options.pushstate_url == 'auto'){
								pushstate_url = zaj.baseurl+url;
							}
							else if(this.options.pushstate_url !== false){
								pushstate_url = this.options.pushstate_url;
							}
						// now send via the appropriate method
							if(this.options.method == 'get') zaj.ajax.get(url, this.options.callback, pushstate_url);
							else zaj.ajax.post(url, this.options.callback, pushstate_url);
					}
					else{
						this.options.callback(this.element.val());
					}
					return true;
				}
			};
	/**
	 * A function which opens up a new window with the specified properties
	 * @param {string} url The url of the window
	 * @param {integer} width The width in pixels.
	 * @param {integer} height The height in pixels
	 * @param {string} options All other options as an object.
	 * @return {window} Returns the window object.
	 **/
		zaj.window = function(url, width, height, options){
			// Default options!
				if(typeof width == 'undefined') width = 500;
				if(typeof height == 'undefined') height = 300;
			// TODO: implement options
			return window.open (url,"mywindow","status=0,toolbar=0,location=0,menubar=0,resizable=1,scrollbars=1,height="+height+",width="+width);
		};
		zaj.open = zaj.window;

	/**
	 * URLencodes a string so that it can safely be submitted in a GET query.
	 * @param {string} url The url to encode.
	 * @return {string} The url in encoded form.
	 **/
	 	zaj.urlencode = function(url){
	 		return encodeURIComponent(url);
	 	};

	/**
	 * Adds a ? or & to the end of the URL - whichever is needed before you add a query string.
	 * @param {string} url The url to inspect and prepare for a query string.
	 * @return {string} Returns a url with ? added if no query string or & added if it already has a query string.
	 */
		zaj.querymode  = function(url){
			if(url.indexOf('?') > -1) return url+'&';
			else return url+'?';
		};


	/**
	 * Enables sortable features on a list of items. Requires jquery-ui sortable feature.
	 * @param {string|jQuery} target The items to sort. Each item must have an data-sortable field corresponding to the id of item.
	 * @param {string} url The url which will handle this sortable request.
	 **/
		zaj.sortable = function(target, url){
			// Make sortable
			$(target).sortable({
			    start: function(event, ui) {
			    	ui.item.addClass('sortableinprogress');
			    },
			    stop: function(event, ui) {
			    	ui.item.removeClass('sortableinprogress');
					// Build array
						var my_array = [];
						$(target).children().each(function(){
							var my_id = $(this).attr('data-sortable');
							if(!my_id) zaj.error("Cannot sort: data-sortable not set!");
							else my_array.push(my_id);
						});
						zaj.ajax.post(url+'?reorder='+JSON.stringify(my_array));
			    }
			});
		};

	/**
	 * Checks to see if an element is in the viewport.
	 * @param {string|object} el A DOM element, jQuery object, or selector string.
	 * @param {boolean} [partially=true] If set to true (default), it will return true if element is at least in part visible.
	 * @return {boolean} Returns true if element is in viewport, false if it is not.
	 */
		zaj.inviewport = function(el, partially){
			// Jquery or non-jquery works
			el = $(el)[0];
			if(typeof partially == 'undefined') partially = true;
			// Calculate element offsets!
			var top = el.offsetTop;
			var left = el.offsetLeft;
			var width = el.offsetWidth;
			var height = el.offsetHeight;
			while(el.offsetParent) {
				el = el.offsetParent;
				top += el.offsetTop;
				left += el.offsetLeft;
			}
			// Facebook iframe or document info
			var iw, ih, st, sl;
			if(zaj.facebook && zaj.fbcanvas){
				FB.Canvas.getPageInfo(function(info) { zaj.fbcanvas = info; } );
				// IMPORTANT: here we may still be using previous fbcanvas info! Unreliable!
				iw = zaj.fbcanvas.clientWidth;
				ih = zaj.fbcanvas.clientHeight;
				st = zaj.fbcanvas.scrollTop;
				sl = zaj.fbcanvas.scrollLeft;
			}
			else{
				iw = window.innerWidth || document.documentElement.clientWidth;
				ih = window.innerHeight || document.documentElement.clientHeight;
				st = $(window).scrollTop();
				sl = $(window).scrollLeft();
			}
			// Now do it!
			if(partially){
				return (
					top < (st + ih) &&
					left < (sl + iw) &&
					(top + height) > st &&
					(left + width) > sl
				);
			}
			else{
				return (
					top >= st &&
					left >= sl &&
					(top + height) <= (st + ih) &&
					(left + width) <= (sl + iw)
				);
			}
		};

	/**
	 * Enable auto pagination
	 */
		zaj.autopagination = {
			/** Default options **/
			defaultOptions: {
				model: '',				// Name of the model
				url: zaj.fullrequest, 	// The pagination url (pagination url, without number)
				startPage: 1, 			// The start page
				pageCount: 2,			// The total number of pages
				watchElement: false,	// The element to watch. If it enters viewport, next page is loaded.
				watchInterval: 500,		// The ms time to watch element.
				targetBlock: false,		// The block to use for the data
				targetElement: false,	// The target element to use (where the paginated html should be appended)
				useMoreButton: null		// If set, watchElement will be ignored and a click event on useMoreButton will be watched instead. This should be a selector.
			},
			/** An array of autopagination objects on this page **/
			objects: [],
			/** An array of ready functions added **/
			readyFunctions: [],

			/**
			 * Init the autopagination object
			 * @param {object} _options An object of options. See above.
			 * @return {object} Returns an autopagination instance.
			 */
			initialize: function(_options){
				// Merge options
				_options = $.extend(this.defaultOptions, _options);
				// Create local vars
				var _loading = false, _target, _currentPage = parseInt(_options.startPage), _watchElement, _this = this;
				// My target element
				_target = $(_options.targetElement);
				// Create my bottom element (if not specified in options) and make invisible
				if(!_options.watchElement){
					_watchElement = $('<div class="ofw-watchelement '+_options.model+'"></div>');
					_target.append(_watchElement);
				}
				else _watchElement = $(_options.watchElement);
				_watchElement.css('visibility', 'hidden');
				// If button is set, make it invisible if no additional pages
				if(_options.useMoreButton != null && _options.startPage >= _options.pageCount){
					$(_options.useMoreButton).hide();
				}


				// Define public interface
				var pub = {
					/**
					 * Load up the next items.
					 */
					next: function(){
						// Check if loading
						if(_loading) return false;
						// Check if already at max page count
						if(_currentPage >= _options.pageCount){
							return false;
						}
						// Load page
						zaj.log("Loading next page. Current "+_currentPage);
						_loading = true;
						_currentPage += 1;
						// Set as visible
						_watchElement.css('visibility', 'visible');
						// Get next data
						zaj.ajax.get(_options.url+_currentPage+'&zaj_pushstate_block='+_options.targetBlock, function(res){
							_watchElement.before(res).css('visibility', 'hidden').css('width', '100%');
							_loading = false;
							zaj.log("Done loading, running callbacks.");
							// Check if already at max page count, if so hide button
							if(_currentPage >= _options.pageCount){
								if(_options.useMoreButton != null) $(_options.useMoreButton).hide();
							}
							// Call all of my readyFunctions
							$.each(_this.readyFunctions, function(i, func){ func(); });
						});
						return true;
					}
				};

				// Set watchElement interval or use button
				if(_options.useMoreButton == null){
					// Check in intervals to see if element is in viewport
					setInterval(function(){
						 if(!_loading && zaj.inviewport(_watchElement)) pub.next();
					}, _options.watchInterval);
				}
				else{
					$(_options.useMoreButton).click(pub.next);
				}

				return pub;
			},

			/**
			 * Adds a function that is to be executed after pagination completes.
			 * @param {function} func The function to be executed. You can add several.
			 */
			ready: function(func){
				this.readyFunctions.push(func);
			}
		};
	/**
	 * Provide helper for file uploads.
	 */

		/**
		 * Helper js for plupload to make it compatible with Outlast Framework upload forms.
		 * @param options
		 * Basics
		 * @option browse_button
		 * @option drop_element
		 * @option max_file_size
		 * @option input_name
		 * @option input_crop
		 * @option alert_toosmall
		 * @option alert_toolarge
		 * @option debug_mode
		 * Cropping
		 * @option {boolean} [cropbox=false] If set to true, crop box will be used instead of imgareaselect.
		 * @option {boolean} crop_disabled If set to true, cropping is disabled.
		 * @option {integer} min_width The minimum width in pixels
		 * @option {integer} min_height The minimum width in pixels
		 * @option {string} ratio The ration for cropping
		 * Webcam
		 * @option {boolean} webcam_enabled True if webcam is enabled
		 * @option {string} webcam_button Selector of the webcam button
		 * @option {string} webcam_id ID of the webcam (usually field id)
		 **/
		var open_graphapi_uploader_callback = {};
		zaj.plupload = {
			// Public variables
			ready: false,
			percent: 0,

			/**
			 * Cropper
			 * @param options The options object, specified above.
			 */
			cropper: function(options){
				// Make sure baseurl is defined
				if(typeof zaj.baseurl == 'undefined' || !zaj.baseurl) zaj.error("Baseurl not defined, cannot init uploader!");
				// Globals
				var selection_changed = false;
				var sel_instance;

				// Default options
				if(typeof options.cropbox == 'undefined') options.cropbox = false;

				// Create plupload object
				var uploader = new plupload.Uploader({
					runtimes : 'html5,flash,html4',
					browse_button : options.browse_button,
					drop_element : options.drop_element,
					max_file_size : options.max_file_size,
					url : zaj.baseurl+'system/plupload/upload/photo/',
					flash_swf_url : zaj.baseurl+'system/js/plupload/plupload.flash.swf'
				});
				var uploadergo = function(){
					uploader.start()
				};
				if(options.debug_mode) zaj.log("Uploader is in debug mode.");

				// Add uploader events
				uploader.bind('Init', function(up, params){
					zaj.log("Uploader initialized. Runtime is " + params.runtime);
				});
				uploader.bind('FilesAdded', function(up, files) {
					if(options.debug_mode) zaj.log("File added to uploader.");
					// If Flash is enabled, turn it off
					if(options.webcam_enabled) flash_off();
					// Go!
					setTimeout(uploadergo, 800);
				});
				uploader.bind('UploadProgress', function(up, file) {
					if(options.debug_mode) zaj.log("File at "+file.percent+"%.");
					zaj.plupload.percent = file.percent;
					$(options.file_percent).text(file.percent+'%');
				});
				uploader.bind('Error', function(up, err) {
					if(options.debug_mode) zaj.log("Error: " + err.code +", Message: " + err.message + (err.file ? ", File: " + err.file.name : ""));
					zaj.alert(options.alert_toolarge);
					up.refresh(); // Reposition Flash/Silverlight
				});
				uploader.bind('FileUploaded', function(up, file, result) {
					// Log and set variables
					if(options.debug_mode) zaj.log("Completed.");
					zaj.plupload.percent = 100;
					zaj.plupload.ready = true;
					setTimeout(function(){
						$(options.file_percent).text('');
					}, 1000);
					// Fire event on window
					$(window).trigger('ofwFileUploaded', uploader);
					// Parse results and share
					var res = jQuery.parseJSON(result.response);
					if(res.status == 'error') zaj.alert(options.alert_toolarge);
					else uploader_update(res);
				});

				/**
				 * Image area selector.
				 * @param {object} res Res has three properties: res.height, res.width, res.id
				 */
				uploader.imgAreaSelect = function(res){
					sel_instance = $(options.file_list+" img").imgAreaSelect({
						show: true,
						aspectRatio: options.ratio,
						imageHeight: res.height,
						imageWidth: res.width,
						minWidth: options.min_width,
						instance: true,
						handles: true,
						persistent: true,
						fadeSpeed: 600,
						onInit: function(){
							sel_instance.setSelection(0, 0, options.min_width, options.min_height);
							sel_instance.update(); },
						onSelectChange: function(img, selection) {
							selection_changed = true;
							var dimensions = '{"x":'+selection.x1+',"y":'+selection.y1+',"w":'+selection.width+',"h":'+selection.height+'}';
							$(options.input_crop).val(dimensions);
						},
						onSelectEnd: function(img, selection) {
							// Let's check to see if selection was destroyed (recreate!)
								if(selection.width == 0 || selection.height == 0){
									zaj.log("Selection was destroyed, recreating!");
									uploader.imgAreaSelect(res);
								}
							// Let's check to see if selection is less than the allowed minimum (allow a few pixels less for rounding)
								if(selection.width + 5 < options.min_width || selection.height + 5 < options.min_height){
									zaj.log("Selection too small, repositioning!");
									sel_instance.setSelection(0, 0, options.min_width, options.min_height);
									sel_instance.update();
								}
						}
					});
					uploader.imgAreaSelectInstance = sel_instance;
				};

				uploader.JCropbox = function(res){

					sel_instance = $(options.file_list+" img").cropbox({
				        width: options.min_width,
				        height: options.min_height,
				        showControls: 'always',
				        zoom: 5
				    }).on('cropbox', function(e, data) {
				        selection_changed = true;
							var dimensions = '{"x":'+data.cropX+',"y":'+data.cropY+',"w":'+data.cropW+',"h":'+data.cropH+'}';
							$(options.input_crop).val(dimensions);
				    });
				}


				/**
				 * Init initial image area selector.
				 */

				// Init crop default
				if(!options.crop_disabled) $(options.input_crop).val('{"x":0,"y":0,"w":'+options.min_width+',"h":'+options.min_height+'}');

				// Expects an object as such: res.height, res.width, res.id
				var uploader_update = function(res){
					if(options.debug_mode) zaj.log(res);
					// Is it wide/tall enough?
					if(res.width < options.min_width  || res.height < options.min_height) return zaj.alert(options.alert_toosmall);
					// Add my image
					if(options.debug_mode) zaj.log("Adding preview to "+options.file_list);
					var imgurl = zaj.baseurl+'system/plupload/preview/?id='+res.id;
					$(options.file_list).html("<img src='"+imgurl+"'>");
					// Create a new selection (discard old)
					if(!options.crop_disabled && sel_instance) sel_instance.remove();
					selection_changed = false;
					// Add landscape or portrait class to file_list
					$(options.file_list).removeClass('landscape').removeClass('portrait');
					if(res.width > res.height) $(options.file_list).addClass('landscape');
					else $(options.file_list).addClass('portrait');
					// Init my cropper
					if(!options.crop_disabled){
						// Enable the image area select or cropbox
						if(options.cropbox){
							// Init cropbox
								uploader.JCropbox(res);
						}
						else{
							// Add click event to img in case it is disabled
								$(options.file_list+" img").click(function(){
									uploader.imgAreaSelect(res);
								});
						}
					}
					// Set as my input value
					$(options.input_name).val(res.id);
					// If Flash is enabled, turn it off
					if(options.webcam_enabled) flash_off();

				};
				if(!options.uid) open_graphapi_uploader_callback = uploader_update;
				else open_graphapi_uploader_callback[options.uid] = uploader_update;

				/**
				 * Webcam stuff.
				 */

				// Base variables
				var flash_app;
				var flash_is_active = false;

				// Turn it on
				var flash_on = function(){
					if(!options.crop_disabled && sel_instance) sel_instance.remove();
					flash_is_active=true;
					$('div.foto.standard').hide();$('div.foto.flash').show();
				};
				// Turn it off
				var flash_off = function(){
					flash_is_active=false;
					$('div.foto.standard').show();$('div.foto.flash').hide();
				};

				if(options.webcam_enabled){
					// Add click event
					$(options.webcam_button).click(function(){
						// Snap or activate?
						if(flash_is_active){
							// Snap!
							flash_app.snap_me();
						}
						else{
							// Activate!
							flash_on();
							flash_app = $('#'+options.webcam_id+'-flash-swf')[0];
						}
					});
				}
				// Process upload
				uploader.flash_upload_done = function(url, ev){
					zaj.log(ev.target.data);
					var rdata = jQuery.parseJSON(ev.target.data);
					if(rdata.status == 'success'){
						// Log and set variables
						if(options.debug_mode) zaj.log("Completed.");
						zaj.plupload.percent = 100;
						zaj.plupload.ready = true;
						setTimeout(function(){
							$(options.file_percent).text('');
						}, 1000);
						// Fire event on window
						$(window).trigger('ofwFileUploaded', uploader);
						// Add to my filelist
						uploader_update(rdata);
					}
					else zaj.alert(rdata.message);
				};

				// Run init
				zaj.ready(function(){ uploader.init(); });
				return uploader;
			},
			uploader: function(options){
				this.cropper(options)
			}
		};


	/**
	 * Pushstate excitement
	 */
	window.onpopstate = function(event) {
		/**if(event && event.state) {
			console.log(event);
		}**/
	};

	/**
	 * Now extend the jQuery object.
	 **/
	(function($){
	   $.fn.$zaj = $.fn.zaj = $.fn.$ofw = function(){
	  	var target = this;
	  	// Create my object and return
		return {

	  		// Get or post serialized data
	  		get: function(url, response){ return zaj.ajax.get(zaj.querymode(url)+target.serialize(), response); },
	  		post: function(url, response){ return zaj.ajax.post(zaj.querymode(url)+target.serialize(), response); },
	  		submit: function(url, response){ return zaj.ajax.submit(zaj.querymode(url)+target.serialize(), response); },
	  		inviewport: function(partially){ return zaj.inviewport(target, partially); },
	  		sortable: function(receiver){ return zaj.sortable(target, receiver); },
	  		search: function(url, receiver){
	  			if(typeof receiver == 'function'){
					return zaj.search.initialize(target, { url: url, callback: receiver });

	  			}
	  			else{
					return zaj.search.initialize(target, { url: url, receiver: $(receiver), callback: function(r){
						$(receiver).html(r);
					} });
	  			}
	  		}
	  	};
	  };
	})(jQuery);

	/**
	 * Now add some attribute sniffer helpers
	 **/
	zaj.ready(function(){

		/**
		 * Single click handler.
		 * @attr data-single-click Defines any javascript that is to be executed once even if the user double clicks.
		 * @attr data-single-click-delay Defines the number of ms before the user can click again. Defaults to 1500. (optional)
		 **/
			$('[data-single-click]').click(function(){
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

		/**
		 * Autopagination handler
		 * @attr data-autopagination The data need for autopagination. This is generated by {{list.pagination.autopagination}}.
		 * @attr data-autopagination-block You can override the default 'autopagination' block name by specifying this attribute.
		 * @attr data-autopagination-button A selector for a button that will be used for loading more results. In this case additional results are not loaded on scroll.
		 **/
			$('[data-autopagination]').each(function(){
				// Set defaults and data
					var $el =  $(this);
					var _rawdata = $el.attr('data-autopagination');
					if(_rawdata == '') return true;
					var data = JSON.parse(_rawdata);
					var block = $el.attr('data-autopagination-block');
					var button = $el.attr('data-autopagination-button');
					if(typeof block == 'undefined') block = 'autopagination';
				// Use button or infinite scroll
					var _useMoreButton = null;
					if(typeof button != 'undefined') _useMoreButton = $(button);
				// Create my autopagination object
					zaj.autopagination.objects.push(zaj.autopagination.initialize({
						model: data.model,
						url: data.url,
						startPage: data.startPage,
						pageCount: data.pageCount,
						watchElement: false,
						watchInterval: 500,
						targetBlock: block,
						targetElement: this,
						useMoreButton: _useMoreButton
					}));
			});

	});
