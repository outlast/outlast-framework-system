/**
 * The basic Outlast Framework javascript object.
 **/

var OfwType = {}

define('system/js/ofw-jquery', [], function () {

	/** Init options **/
	var defaultOptions = {
		baseurl: '',
		fullrequest: '',
		fullurl: '',
		app: '',
		mode: '',
		debug_mode: false,
		protocol: 'http',
		jslib: 'jquery',
		jslibver: 1.10,
		trackEventsAnalytics: true,
		trackEventsLocal: false,
		trackExternalLinks: true,
		lang: {},
		config: {},
		plugins: {},
		readyFunctions: [],
		dataAttributeHandlers: [],
		dataAttributeReadyFunctions: {},
		dataAttributes: [
			{name: 'single-click', path: 'system/js/data'},
			{name: 'autopagination', path: 'system/js/data'},
			{name: 'autosave', path: 'system/js/data'},
			{name: 'action-value', path: 'system/js/data'},
			{name: 'track', path: 'system/js/data'},
			{name: 'block', path: 'system/js/data'},
			{name: 'featured', path: 'system/js/data'},
			{name: 'event', path: 'system/js/data'},
		],
		jqueryIsReady: false
	};
	var myOptions = {};

	/** Public properties that are defined in options. See more in publishPublicProperties. **/
	var publicProperties = [
		'baseurl',
		'fullrequest',
		'fullurl',
		'app',
		'mode',
		'debug_mode',
		'protocol',
		'jslib',
		'jslibver',
		'trackEventsAnalytics',
		'trackEventsLocal',
		'fields',
		'locale',
		'lang',
		'config'
	];

	var ajaxIsSubmitting = false;			// True if ajax is currently submitting
	var dataAttributes = [];				// The registered data attributes to look for
	var dataAttributesObjects = {};			// A key/value pair where key is attr name and value is the loaded data attribute object
	var dataAttributeReadyFunctions = {};	// A key/value pair where key is the plugin name and value is an array of ready functions to call

	var queueReadyFunctions = false;		// True if the ready and require functions are being queued
	var originalRequireFunction;			// The original requirejs() function
	var requireFunctions = [];				// A queue of the requirejs functions that should be run when ready
	var loadedCssStylesheets = {};			// A list of loaded sheets. Key is relative url, value is the number of times it was requested

	/** Private API **/

	/**
	 * Object init
	 */
	var init = function (options) {
		// Merge default options
		myOptions = $.extend(true, {}, defaultOptions, options);

		// Make sure that jquery is ready is true if it is ready
		$(document).ready(function () {
			myOptions.jqueryIsReady = true;
		});

		// Call other init functions
		initJqueryFunctions();
		publishPublicProperties();

		// Backwards compatiblity
		if (typeof options.trackevents_analytics != 'undefined') myOptions.trackEventsAnalytics = options.trackevents_analytics;

		// Set calculated properties (and again when jquery is ready)
		setCalculatedProperties();
		if (!myOptions.jqueryIsReady) $(document).ready(setCalculatedProperties);

		// Set up my data attributes and run activate (it has to be delayed so that init() is finished and I exist)
		dataAttributes = myOptions.dataAttributes;
		if (!myOptions.jqueryIsReady) $(document).ready(function () {
			activateDataAttributeHandlers();
		});
		else activateDataAttributeHandlers();
		// @todo If we do a proper define() method for OutlastFrameworkSystem then we can probably get rid of the delay

		// Now run ready functions
		setTimeout(runReadyFunctions, 10);
		setTimeout(runAttributeHandlers, 10);
	};

	/**
	 * Turn on queue ready functions. Will queue ofw.ready and requirejs.
	 */
	var startToQueueReadyFunctions = function () {
		// If already queueing, return
		if (queueReadyFunctions) return;

		// Set queue ready
		queueReadyFunctions = true;
		// Set requirejs queuing
		originalRequireFunction = requirejs;
		requirejs = function (req, func) {
			requireFunctions.push({requirements: req, func: func});
		};
	};

	/**
	 * Call ready functions in case jquery is alredy ready.
	 */
	var runReadyFunctions = function () {
		var i;

		// Set to false
		queueReadyFunctions = false;

		// If jquery is already ready, then fire away!
		if (myOptions.jqueryIsReady) {
			for (i = 0; i < myOptions.readyFunctions.length; i++) {
				myOptions.readyFunctions[i]();
			}
		}
		// Otherwise, add to jquery
		else {
			for (i = 0; i < myOptions.readyFunctions.length; i++) {
				$(document).ready(myOptions.readyFunctions[i]);
			}
		}

		// Run requirejs functions and restore requirejs
		if (originalRequireFunction) requirejs = originalRequireFunction;
		for (i = 0; i < requireFunctions.length; i++) {
			requirejs(requireFunctions[i].requirements, requireFunctions[i].func);
		}

		// Reset all my variables
		originalRequireFunction = null;
		requireFunctions = [];
		myOptions.readyFunctions = [];
	};

	/**
	 * Call attribute handlers.
	 */
	var runAttributeHandlers = function () {
		// Run all the handlers
		for (var i = 0; i < myOptions.dataAttributeHandlers.length; i++) {
			var dataAttributeHandler = myOptions.dataAttributeHandlers[i];
			api.addDataAttributeHandler(dataAttributeHandler[0], dataAttributeHandler[1], dataAttributeHandler[2]);
		}
		// Reset
		myOptions.dataAttributeHandlers = [];
	};

	/**
	 * Extend the jquery object.
	 */
	var initJqueryFunctions = function () {
		$.fn.$zaj = $.fn.zaj = $.fn.$ofw = function () {
			var $target = $(this);
			// Create my object and return
			return {
				// Get or post serialized data
				get: function (url, response, pushstate) {
					return api.ajax.get(api.querymode(url) + $target.serialize(), response, pushstate);
				},
				post: function (url, response, pushstate) {
					return api.ajax.post(api.querymode(url) + $target.serialize(), response, pushstate, $target);
				},
				submit: function (url, response, pushstate) {
					return api.ajax.submit(api.querymode(url) + $target.serialize(), response, pushstate, $target);
				},
				inviewport: function (partially) {
					return api.inviewport($target, partially);
				},
				alert: function (msg, buttonText, top, callback) {
					return api.alert(msg, $target, buttonText, top, callback);
				},
				sortable: function (receiver, callback, handle) {
					// Load up dependency
					requirejs(["system/js/ui/sortable"], function (sortable) {
						sortable.init($target, receiver, callback, handle);
					});
				},
				search: function (url, receiver, options) {
					if (typeof receiver == 'function') {
						options = $.extend({url: url, callback: receiver}, options);
					} else {
						options = $.extend({
							url: url, receiver: $(receiver), callback: function (r) {
								$(receiver).html(r);
								activateDataAttributeHandlers($(receiver));
							}
						}, options);
					}

					// Load up dependency
					requirejs(["system/js/ui/search"], function (search) {
						search.init($target, options);
					});
				}
			};
		};
	};

	/**
	 * Initialize public properties.
	 */
	var publishPublicProperties = function () {
		// Publish properites that are options
		for (var i = 0; i < publicProperties.length; i++) {
			api[publicProperties[i]] = myOptions[publicProperties[i]];
		}
	};

	/**
	 * Set calculated properties.
	 */
	var setCalculatedProperties = function () {
		// Set calculated properties
		api.bootstrap = (typeof $().modal == 'function');
		api.bootstrap3 = (typeof $().emulateTransitionEnd == 'function');
		api.facebook = (window.parent != window) && typeof FB != 'undefined' && typeof FB.Canvas != 'undefined';
		api.fbcanvas = false;
		if (api.facebook) {
			FB.Canvas.getPageInfo(function (info) {
				api.fbcanvas = info;
			});
		}
	};

	/**
	 * Reposition the modal according to Facebook position.
	 * @param $modal A jQuery object of the Bootstrap modal.
	 */
	var alertReposition = function ($modal) {
		if (api.facebook) {
			FB.Canvas.getPageInfo(function (e) {
				// Top bar
				var fb_top_bar = e.offsetTop;
				var fb_bottom_bar = 250;
				var $modalbody = $modal.find('.modal-body');
				var overflow_mode = 'scroll';
				// Calculate my top position
				var topoffset = 90;
				if (e.scrollTop > fb_top_bar) topoffset += e.scrollTop - fb_top_bar;
				// Get my content height
				var content_height = $modalbody.height(0)[0].scrollHeight;
				// Set height
				var height = e.clientHeight - fb_bottom_bar;
				// If we are near the bottom
				if (topoffset + height > $(window).height()) height = $(window).height() - topoffset - fb_bottom_bar;
				// If we are near the top
				if (e.scrollTop < fb_top_bar) height = e.clientHeight - fb_top_bar - 150 + e.scrollTop;
				// Subtract modal footer from height
				height -= $modal.find('.modal-footer').height();
				// If the height in the end is larger than the content height, then just use content height
				if (height > content_height) {
					height = content_height;
					overflow_mode = 'auto';
				}
				// Set the modal body to autosize
				$modal.find('.modal-body').css({width: 'auto', height: height, 'overflow-y': overflow_mode});
				$modal.css({top: topoffset, overflow: 'hidden', 'margin-top': 0});
			});
			// clear and set @todo this should cancel eventually
			setTimeout(function () {
				alertReposition($modal);
			}, 1000);
		}
	};

	/**
	 * Display a confirm or prompt dialogue.
	 * @todo Add Bootstrap override for each type.
	 * @param {string} message A message that tells what you are confirming.
	 * @param {string|function|null} [urlORfunction=null] If this is not defined, confirm will work as a standard, blocking js confirm. Otherwise this will be the success.
	 * @param {function} [interactivePopupFunction=null] Can be confirm() or prompt().
	 * @return {boolean|*} Will return different values based on parameters. Usually it returns true if the confirmation succeeds. If a function is passed, the function's return value is returned.
	 */
	var interactivePopup = function (message, urlORfunction, interactivePopupFunction) {
		// If the passed param is a function, then return confirmation as its param
		if (typeof urlORfunction == 'function') {
			var result = interactivePopupFunction(message);
			return urlORfunction(result);
		}
		// If the passed param is a url, redirect if confirm
		else if (typeof urlORfunction == 'string') {
			if (interactivePopupFunction(message)) {
				api.redirect(urlORfunction);
				return true;
			} else return false;
		}
		// If no passed param - just work like a standard confirm
		else {
			return interactivePopupFunction(message);
		}
	};

	/**
	 * Wrapper to send an AJAX request.
	 * @param {string} method Can be POST or GET.
	 * @param {string|object} request The relative or absolute url. Anything that starts with http or https is considered an absolute url, others will process as relative. You can pass request data as a query string or if it is an object, then it should have a 'url' and 'data' properties.
	 * @param {function|string|object} callback The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
	 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
	 * @param {boolean} set_submitting If set to true, it will set ajaxIsSubmitting when the request returns with a response.
	 * @param {jQuery} [$eventContext=$(document)] The event context is the jQuery object on which ajax success events are fired. When no context, events are fired on document.
	 * @return {string} Returns the request url as sent.
	 */
	var ajaxRequest = function (method, request, callback, pushstate, set_submitting, $eventContext) {
		// Pushstate defaults
		var psused = api.pushstate && (typeof pushstate === 'string' || typeof pushstate === 'object' || (typeof pushstate === 'boolean' && pushstate === true));
		var psdata = false;
		if (typeof pushstate === 'object' && pushstate !== null && pushstate.data) psdata = pushstate.data;

		// Method to lower case
		method = method.toLowerCase();

		// Default to document event context
		if($eventContext == null) {
			$eventContext = $(document);
		}

		// Figure out query string or request data
		var datarequest = '';
		if (typeof request === 'object') {
			datarequest = request['data'];
			request = request['url'];
		} else {
			if (method === 'post') {
				var rdata = request.split('?');
				if (rdata.length > 2) {
					// Display warning
					api.warning("Found multiple question marks in query string: " + request);
				}
				request = rdata[0];
				datarequest = rdata[1];
			}
		}

		// Add baseurl if not protocol. If not on current url, you must enable CORS on server.
		if (request.substr(0, 5) !== 'http:' && request.substr(0, 6) !== 'https:') request = myOptions.baseurl + request;

		// Now send request and call callback function, set callback element, or alert
		$.ajax(request, {
			success: function (data, textStatus, jqXHR) {
				// Set my submitting to false
				if (set_submitting) {
					ajaxIsSubmitting = false;
					var el = $('[data-submit-toggle-class]');
					if (el.length > 0) el.toggleClass(el.attr('data-submit-toggle-class'));
				}

				// Try to decode as json data
				var jsondata = null;
				try {
					jsondata = $.parseJSON(data);
				} catch (error) {
				}

				// Trigger events
				if ($eventContext) {
					$eventContext.trigger('ofw-ajax-response', [data, jsondata]);
					if (jsondata && typeof jsondata == 'object') {
						if (jsondata.status === 'ok' || jsondata.status === 'success') {
							$eventContext.trigger('ofw-ajax-success', [data, jsondata]);
						}
						else if (jsondata.status === 'error') {
							$eventContext.trigger('ofw-ajax-error', [data, jsondata]);
						}
						else {
							$eventContext.trigger('ofw-ajax-unknown', [data, jsondata]);
						}
					}
				}

				// Handle my callback
				var callbackResult = handleCallback(data, jsondata, callback);
				if (callbackResult !== null) return callbackResult;

				// Push state actions
				if (psused) {
					// if psdata not specified
					if (psdata === false) psdata = {url: window.location.href};
					// string mode - convert to object
					if (typeof pushstate === 'string') pushstate = {'data': psdata, 'title': "", 'url': pushstate};
					// boolean mode - use current request
					else if (typeof pushstate === 'boolean') pushstate = {'data': psdata, 'title': "", 'url': request};
					// now set everything and fire event
					pushstate = $.extend({}, {'title': false}, pushstate);	// default title is false
					if (pushstate.url) window.history.pushState(psdata, pushstate.title, pushstate.url);
					if (pushstate.title) document.title = pushstate.title;
				}

			},
			complete: function (jqXHR, textStatus) {
				// Set error msgs
				if (textStatus !== "success") {

					// Set my submitting to false
					if (set_submitting) {
						ajaxIsSubmitting = false;
						var el = $('[data-submit-toggle-class]');
						if (el.length > 0) el.toggleClass(el.attr('data-submit-toggle-class'));
					}

					// Trigger events
					if ($eventContext) {
						$eventContext.trigger('ofw-ajax-response', [jqXHR.responseText, null]);
						$eventContext.trigger('ofw-ajax-error', [jqXHR.responseText, null]);
					}

					// If we are in debug mode popup
					if (textStatus === 'error' && myOptions.debug_mode) api.alert("Ajax request failed with error:<hr/>" + jqXHR.responseText);

				}

			},
			data: datarequest,
			dataType: 'html',
			headers: {'X-Requested-With': 'XMLHttpRequest'},
			type: method,
			cache: false
		});

		// Return the request object
		return request;
	};

	/**
	 * Handles callback properly. The callback can be function (first param will be result, second json-decoded result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
	 * @param {string} data The raw data returned by the ajax call.
	 * @param {Object} jsondata Decoded json data from the response. Expects an object with at least a status property.
	 * @param {function|string|object} callback The item which should process the results. Can be function (first param will be result, second json-decoded result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
	 * @returns {null|boolean} Returns null if nothing to return or boolean if there is a result.
	 */
	var handleCallback = function (data, jsondata, callback) {
		if (typeof callback == "function") callback(data, jsondata);
		else if (typeof callback == "object") {
			if (jsondata && jsondata.message) api.alert(jsondata.message);
			$(callback).html(data);
			activateDataAttributeHandlers($(callback));
		} else {
			var validationResult = api.ajax.validate(data);
			if (validationResult === true) {
				if (jsondata && jsondata.message) {
					api.alert(jsondata.message, function () {
						api.redirect(callback);
					});
				} else api.redirect(callback);
			} else return validationResult;
		}
		return null;
	};

	/**
	 * Run through the context (defaults to body) and activate all registered data attribute handlers.
	 * @param {jQuery} [$context=$(document)] The jQuery object in which the handlers are searched for.
	 **/
	var activateDataAttributeHandlers = function ($context) {
		// Run through my data attributes
		for (var i = 0; i < dataAttributes.length; i++) {
			activateSingleDataAttributeHandler(dataAttributes[i], $context);
		}

		// activate event tracking for external links
		if (myOptions.trackExternalLinks) {
			activateExternalLinkTracking();
		}
	};

	/**
	 * Run through the parent (defaults to body) and activate any registered data attribute handlers.
	 * @param {object} dataAttribute An object with the name and path of handler plus the callback function. The name of the handler should be the data attribute to look for without data-. So for data-autopagination it is 'autopagination'.
	 * @param {jQuery} [$context=$(document)] The jQuery object in which the handlers are searched for.
	 **/
	var activateSingleDataAttributeHandler = function (dataAttribute, $context) {
		// Default value of context
		if (typeof $context === 'undefined' || $context == null) $context = $(document);

		// Set name and path (and remove trailing slashes)
		var handlerName = dataAttribute['name'].replace(/^\/|\/$/g, '');
		var handlerPath = dataAttribute['path'].replace(/^\/|\/$/g, '');
		var callback = dataAttribute['callback'];

		// Add ready function if the object is not yet initialized
		if (dataAttributesObjects[handlerName] == null) {
			dataAttributeReadyFunctions[handlerName] = []
			dataAttributesObjects[handlerName] = {
				ready: function(f){
					dataAttributeReadyFunctions[handlerName].push(f)
				}
			};
		}

		// Let's see if we find any in context
		var $elements = $context.find('[data-' + handlerName + ']');
		if ($elements.length > 0) {
			// Load and init
			requirejs([handlerPath + '/' + handlerName], function (handlerObject) {
				// Add ready function which calls immediately
				handlerObject.ready = function(f) { f() }
				// Set the handler object
				dataAttributesObjects[handlerName] = handlerObject;
				// Activate
				handlerObject.activate($elements, $context);
				// Call ready function for data attribute
				dataAttributeReadyFunctions[handlerName].forEach(function(f){ f() })
				dataAttributeReadyFunctions[handlerName] = []
				// If callback set
				if (typeof callback === "function") callback(handlerObject, $context);
			});
		}
	};

	var activateExternalLinkTracking = function ($context) {
		// Default value of context
		if (typeof $context == 'undefined') $context = $('body');
		$context.find('a').each(function () {
			var $el = $(this);

			if (typeof ($el.attr('href')) == 'undefined') {
				return;
			}

			var protocol_regexp = new RegExp("^(http(s)?:)?(\/\/)(www\.)?");
			var baseurl = myOptions.baseurl.replace(/^(http(s)?:)?/g, '');

			if ($el.attr('href').indexOf(baseurl) == -1 && protocol_regexp.test($el.attr('href'))) {
				$el.click(function () {
					api.track('External link', 'click', $el.attr('href'));
				});
			}
		})
	};


	/** Public API **/

	var api = {

		/**
		 * Public properties @todo move these to libraries!
		 */
		pushstate: window.history && window.history.pushState && window.history.replaceState && !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/),
		mobile: (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)),

		// Below values are set during init
		bootstrap: null,
		bootstrap3: null,
		facebook: null,
		fbcanvas: null,

		/***** INIT METHODS ******/

		/**
		 * Init with options.
		 * @param {object} options
		 */
		init: function (options) {
			init(options);
		},

		/**
		 * Layer for onready functions. If jquery is already ready, it is fired immediately.
		 * @param {function} func The callback function.
		 **/
		ready: function (func) {
			// Add to queue
			myOptions.readyFunctions.push(func);

			// Run now?
			if (!queueReadyFunctions) runReadyFunctions();
		},

		/**
		 * Returns the options.
		 */
		getOptions: function () {
			return myOptions;
		},

		/**
		 * Localizations
		 * @param {string|object} keyOrArray This can be an array of objects where each item has a key, section, and value. Or it can be just a key.
		 * @param {string} value If you are using a key in the first param, this is the value.
		 * @param {string} [section=null] The section in which the lang variable is found. This is optional.
		 */
		setLang: function (keyOrArray, value, section) {
			if (typeof keyOrArray == 'object') {
				$.each(keyOrArray, function (index, value) {
					api.setLang(value.key, value.value, value.section);
				});
			} else {
				// Set key/value globally
				api.lang[keyOrArray] = value;
				// ...and for section if needed
				if (section) {
					if (typeof api.lang.section == 'undefined') api.lang.section = {};
					if (typeof api.lang.section[section] == 'undefined') api.lang.section[section] = {};
					api.lang.section[section][keyOrArray] = value;
				}
			}
			// Also synt to my options and config
			myOptions.config = myOptions.lang = api.config = api.lang;
			// Backwards compatibility
			zaj.lang = api.lang;
			zaj.config = api.config;
		},

		/***** POPUP METHODS ******/

		/**
		 * Custom alerts, confirms, prompts. If bootstrap is enabled, it wil use that. Otherwise the standard blocking alert() will be used.
		 * @param {string} message The message to alert. This can be full HTML.
		 * @param {string|function|jQuery} [urlORfunctionORdom=null] A callback url or function on button push. If you set this to a jQuery dom object then it will be used as the modal markup. If you set it to a function, the dialog will not close if explicit false is returned.
		 * @param {string|boolean} [buttonText="Ok"] The text of the button. Set to false to hide the button.
		 * @param {boolean} [top=false] Set to true if you want the url to load in window.top.location. Defaults to false.
		 * @param {function} [callback=null] A callback function to be displayed after the bs modal is shown.
		 * @return {jQuery} Will return the modal object.
		 */
		alert: function (message, urlORfunctionORdom, buttonText, top, callback) {
			if (api.bootstrap) {

				// Cache my jquery selectors
				var $modal;
				var defaultButtonText = 'Ok';

				// If a modal markup was set with urlORfunctionORdom, then use that. If none, use #zaj_bootstrap_modal.
				if (typeof urlORfunctionORdom === 'object') $modal = urlORfunctionORdom;
				else $modal = $('#zaj_bootstrap_modal');

				// Create modal if not yet available
				if (typeof $modal == 'undefined' || $modal == null || $modal.length <= 0) {
					// Check to see which Bootstrap version and create markup
					if (api.bootstrap3) {
						$modal = $('<div id="zaj_bootstrap_modal" data-ofw="bootstrapModal" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"></div><div class="modal-footer"><a type="button" class="btn modal-button btn-default" data-dismiss="modal">' + defaultButtonText + '</a></div></div></div></div>');
					} else $modal = $('<div id="zaj_bootstrap_modal" data-ofw="bootstrapModal" class="modal hide fade"><div class="modal-body"></div><div class="modal-footer"><a data-dismiss="modal" class="modal-button btn">Ok</a></div></div>');
					// Append it!
					$('body').append($modal);

					// Prevent 'stuck scroll' bug
					$(window).on('shown.bs.modal', function () {
						$('#zaj_bootstrap_modal').css('overflow', 'hidden').css('overflow', 'auto');
					});
				}

				// Add modal callback
				if (callback) {
					var modalCallbackFunction = function () {
						callback($modal);
						$(window).off('shown.bs.modal', modalCallbackFunction);
					};
					$(window).on('shown.bs.modal', modalCallbackFunction);
				}

				// Hide footer if button is set to false
				if (buttonText === false) $modal.find('.modal-footer').addClass('hide');
				else $modal.find('.modal-footer').removeClass('hide');

				// Reset and init button
				// Set action
				var $modal_button = $modal.find('a.modal-button');
				$modal_button.unbind('click');
				if (typeof urlORfunctionORdom === 'function') {
					$modal_button.attr('data-dismiss', '');
					$modal_button.click(function () {
						var r = urlORfunctionORdom($modal);
						if (r !== false) {
							$modal.modal('hide');
							$('.modal-backdrop').remove();
						}
					});
				} else if (typeof urlORfunctionORdom === 'string') $modal_button.click(function () {
					api.redirect(urlORfunctionORdom, top);
				});
				else $modal_button.click(function () {
						$modal.modal('hide');
						$('.modal-backdrop').remove();
					});
				// Set text (if needed)
				if (typeof buttonText === 'string') $modal_button.text(buttonText);
				else $modal_button.text(defaultButtonText);

				// Backdrop closes on mobile
				var backdrop = 'static';
				// Set body and show it (requires selector again)
				var $modalBody = $modal.find('div.modal-body');
				$modalBody.html(message);
				activateDataAttributeHandlers($modalBody);
				$modal.modal({backdrop: backdrop, keyboard: false});
				// Reposition the modal if needed
				alertReposition($modal);
			} else {
				// Alert sent via bootstrap
				api.track('OFW', 'Standard Alert', message.substr(0, 50));
				// Send alert
				alert(message);
				if (typeof urlORfunctionORdom === 'function') urlORfunctionORdom();
				else if (typeof urlORfunctionORdom === 'string') api.redirect(urlORfunctionORdom, top);
			}
			return $modal;
		},

		/**
		 * A replacement for the standard js confirm() method.
		 * @param {string} message A message that tells what you are confirming.
		 * @param {string|function|null} [urlORfunction=null] If this is not defined, confirm will work as a standard, blocking js confirm. Otherwise this will be the success.
		 * @return {boolean|*} Will return different values based on parameters. Usually it returns true if the confirmation succeeds. If a function is passed, the function's return value is returned.
		 */
		confirm: function (message, urlORfunction) {
			return interactivePopup(message, urlORfunction, confirm);
		},

		/**
		 * A replacement for the standard js prompt() method.
		 * @param {string} message The prompt message.
		 * @param {string|function|null} [urlORfunction=null] If this is not defined, prompt will work as a standard, blocking js prompt. Otherwise this will be the result.
		 * @return {*} Returns the standard js prompt() value.
		 */
		prompt: function (message, urlORfunction) {
			return interactivePopup(message, urlORfunction, prompt);
		},

		/**
		 * A function which opens up a new window with the specified properties
		 * @param {string} url The url of the window (full url).
		 * @param {Number} width The width in pixels.
		 * @param {Number} height The height in pixels
		 * @param {string} options All other options as an object.
		 * @return {window} Returns the window object.
		 **/
		window: function (url, width, height, options) {
			// Default options!
			if (typeof width === 'undefined') width = 500;
			if (typeof height === 'undefined') height = 300;
			// TODO: implement options
			return window.open(url, "mywindow", "status=0,toolbar=0,location=0,menubar=0,resizable=1,scrollbars=1,height=" + height + ",width=" + width);
		},

		/***** AJAX METHODS ******/
		ajax: {

			/**
			 * Send request with structured data to an API endpoint and receive the results.
			 * @param {string} endpoint The relative or absolute url of the endpoint.
			 * @param {object} data An object containing all of the data that should be passed.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string} [method="POST"] Can be POST (the default) or GET depending on the necessary HTTP request method.
			 */
			api: function (endpoint, data, result, method) {
				// Default for method
				if (typeof method === 'undefined' || !method) method = 'POST';

				// Set submitting
				var setSubmitting = false;
				if (method === 'POST') setSubmitting = true;

				// Call ajax request
				ajaxRequest(method, {url: endpoint, data: data}, result, false, setSubmitting);
			},

			/**
			 * Send AJAX request via GET.
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 */
			get: function (request, result, pushstate) {
				ajaxRequest('get', request, result, pushstate);
			},

			/**
			 * Send AJAX request via GET and alert it.
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {string|function|jQuery} [urlORfunctionORdom=null] A callback url or function on button push. If you set this to a jQuery dom object then it will be used as the modal markup. If you set it to a function, the dialog will not close if explicit false is returned.
			 * @param {string|boolean} [buttonText="Ok"] The text of the button. Set to false to hide the button.
			 * @param {boolean} [top=false] Set to true if you want the url to load in window.top.location. Defaults to false.
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 */
			alert: function (request, urlORfunctionORdom, buttonText, top, pushstate) {
				startToQueueReadyFunctions();
				ajaxRequest('get', request, function (r) {
					api.alert(r, urlORfunctionORdom, buttonText, top, function ($modal) {
						runReadyFunctions();
						activateDataAttributeHandlers($modal.find('div.modal-body'));
					});
				}, pushstate);
			},

			/**
			 * Send AJAX request via POST.
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 * @param {jQuery} [eventContext=null] The event context is the jQuery object on which ajax success events are fired.
			 */
			post: function (request, result, pushstate, eventContext) {
				ajaxRequest('post', request, result, pushstate, false, eventContext);
			},

			/**
			 * Sends a blocked AJAX request via POST.
			 * @link http://framework.outlast.hu/api/javascript-api/ajax-requests/#docs-blocking-form-requests
			 * @param {string} request The relative or absolute url. Anything that starts with http or https is considered an absolute url. Others will be prepended with the project baseurl.
			 * @param {function|string|object} result The item which should process the results. Can be function (first param will be result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string|object|boolean} [pushstate=false] If it is just a string, it will be the url for the pushState. If it is a boolean true, the current request will be used. If it is an object, you can specify all three params of pushState: data, title, url. If boolean false (the default), pushstate will not be used.
			 * @param {jQuery} [eventContext=null] The event context is the jQuery object on which ajax success events are fired.
			 */
			submit: function (request, result, pushstate, eventContext) {
				// if submitting already, just block!
				if (ajaxIsSubmitting) return false;
				// toggle submitting status
				ajaxIsSubmitting = true;
				var el = $('[data-submit-toggle-class]');
				if (el.length > 0) {
					el.toggleClass(el.attr('data-submit-toggle-class'));
				}
				return ajaxRequest('post', request, result, pushstate, true, eventContext);
			},

			/**
			 * Perform validation with returned json data.
			 * @param {string} data This can be a json string or a non-json string.
			 * @param {boolean} [showAlerts=false] Show the alerts.
			 * @return {boolean} Will return true if validation was successful, false if not.
			 */
			validate: function (data, showAlerts) {
				/** @type {{status: String, message: String, highlight: Array, errors: <string, string>}|boolean} dataJson */
				var dataJson;

				// Check to see if json
				try {
					dataJson = $.parseJSON(data);
				} catch (err) {
					dataJson = false;
				}

				// If data json is not false, then it is json
				if (dataJson !== false) {
					if (dataJson.status === 'ok') return true;
					else {
						// Define vars
						var input, inputGroup, inputCleanup;
						/** @type {boolean|Number} scrollTo */
						var scrollTo = false;
						// Display a message (if set)
						if (dataJson.message != null && showAlerts) api.alert(dataJson.message);
						// Highlight the fields (if set)
						// @todo Make sure that fields are only selected if they are part of the request to begin with! But how?
						if (typeof dataJson.highlight === 'object') {
							$.each(dataJson.highlight, function (key, val) {
								// Add the error class and remove on change
								input = $('[name="' + val + '"]');
								inputGroup = input.parent('.form-group');
								inputCleanup = function () {
									$(this).removeClass('has-error');
									$(this).parent('.form-group').removeClass('has-error');
								};
								input.addClass('has-error').change(inputCleanup).keyup(inputCleanup);
								inputGroup.addClass('has-error');
								// Check to see if input is higher than any previous input
								/** @type {Number|Window} inputOffset */
								var inputOffset = input.offset().top;
								if (scrollTo === false || inputOffset < scrollTo) scrollTo = inputOffset;
							});
						}
						// Display errors for each field
						// @todo Make sure that fields are only selected if they are part of the request to begin with! But how?
						if (typeof dataJson.errors == 'object') {
							$.each(dataJson.errors, function (key, msg) {
								// Get input and input group
								input = $('[name="' + key + '"]');
								inputGroup = input.parent('.form-group');
								inputCleanup = function () {
									$(this).removeClass('has-error');
									$(this).parent('.form-group').removeClass('has-error');
									if (api.bootstrap3) $(this).tooltip('hide');
								};
								// @todo enable invisible fields to somehow work their magic! have a date-attribute with the selector of the visible field
								if (api.bootstrap3 && input.filter(':visible').length > 0) {
									input.attr('title', msg).attr('data-original-title', msg).tooltip({
										trigger: 'manual',
										animation: false
									}).tooltip('show');
									// Add the error class and remove on change
									input.addClass('has-error').change(inputCleanup).keyup(inputCleanup);
									inputGroup.addClass('has-error');
									// Check to see if input is higher than any previous input
									/** @type {Number|Window} inputOffset */
									var inputOffset = input.offset().top - input.next('.tooltip').height() - 10;
									if (scrollTo === false || inputOffset < scrollTo) scrollTo = inputOffset;
								} else {
									if (showAlerts) api.alert(msg);
								}
							});
						}
						// Scroll to top-most
						if (scrollTo !== false) api.scroll(scrollTo);
					}
				}
				// not json, so parse as string
				else {
					if (data == 'ok') return true;
					else {
						if(showAlerts) api.alert(data);
					}
				}
				// all other cases return false
				return false;
			},

			/**
			 * Handles callback properly. The callback can be function (first param will be result, second json-decoded result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @param {string} data The raw data returned by the ajax call.
			 * @param {Object} jsondata Decoded json data from the response. Expects an object with at least a status property.
			 * @param {function|string|object} callback The item which should process the results. Can be function (first param will be result, second json-decoded result), a string (considered a url to redirect to), or a DOM element object (results will be filled in here).
			 * @returns {null|boolean} Returns null if nothing to return or boolean if there is a result.
			 */
			handleCallback: function (data, jsondata, callback) {
				return handleCallback(data, jsondata, callback);
			}

		},

		/***** NAVIGATION METHODS ******/

		/**
		 * Reload the current url.
		 **/
		reload: function () {
			window.location.reload(false);
		},

		/**
		 * Redirect to a page relative to baseurl or absolute.
		 * @param {string} relative_or_absolute_url The URL relative to baseurl. If it starts with // or http or https it is considered an absolute url
		 * @param {boolean} [top=false] Set this to true if you want it to load in the top iframe.
		 **/
		redirect: function (relative_or_absolute_url, top) {
			if (typeof relative_or_absolute_url == 'undefined' || !relative_or_absolute_url) return false;
			if (typeof top == 'undefined') top = false;
			// Is it relative?
			if (relative_or_absolute_url.substr(0, 2) != '//' && relative_or_absolute_url.substr(4, 3) != "://" && relative_or_absolute_url.substr(5, 3) != "://") relative_or_absolute_url = myOptions.baseurl + relative_or_absolute_url;
			// Top or window
			if (top) window.top.location = relative_or_absolute_url;
			else window.location = relative_or_absolute_url;
			return true;
		},

		/** LOADING METHODS **/

		/**
		 * Dynamically load a css stylesheet. Do not overuse this! When possible, use template inheritance.
		 * @param {string} relativeUrl The url, relative to baseurl.
		 */
		loadCss: function (relativeUrl) {
			// Add to loaded list
			if (typeof loadedCssStylesheets[relativeUrl] === 'undefined') {
				loadedCssStylesheets[relativeUrl] = 1;
			} else {
				loadedCssStylesheets[relativeUrl]++;
				return;
			}

			// Inject
			$("<link/>", {
				rel: "stylesheet",
				type: "text/css",
				href: myOptions.baseurl + relativeUrl
			}).appendTo("head");
		},
		loadCssStylesheet: function (relativeUrl) {
			api.loadCss(relativeUrl)
		},


		/***** TEXT PROCESSING METHODS @todo move some of these to library/text ******/

		/**
		 * URLencodes a string so that it can safely be submitted in a GET query.
		 * @param {string} url The url to encode.
		 * @return {string} The url in encoded form.
		 **/
		urlEncode: function (url) {
			return encodeURIComponent(url);
		},

		/**
		 * Adds a ? or & to the end of the URL - whichever is needed before you add a query string.
		 * @param {string} url The url to inspect and prepare for a query string.
		 * @return {string} Returns a url with ? added if no query string or & added if it already has a query string.
		 */
		queryMode: function (url) {
			if (url.indexOf('?') > -1) return url + '&';
			else return url + '?';
		},

		/**
		 * Is email valid?
		 * @param {string} email The email address to test.
		 * @return {boolean} True if valid, false if not.
		 */
		isEmailValid: function (email) {
			var patt = /^[_A-z0-9-]+(\.[_A-z0-9-]+)*@[A-z0-9-]+(\.[A-z0-9-]+)*(\.[A-z]{2,10})$/i;
			return patt.test(email);
		},

		/**
		 * Is URL valid?
		 * @param {string} url The URL to test.
		 * @return {boolean} True if valid, false if not.
		 */
		isUrlValid: function (url) {
			var patt = /^((https?|ftp):)?\/\/[^\s\/$.?#].[\S ]*$/i;
			return patt.test(url);
		},

		/**
		 * Encode html characters.
		 * @param {string} str The incoming string.
		 * @return {string} Returns a string in which html entities are escaped.
		 **/
		htmlEscape: function (str) {
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#39;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;');
		},

		/**
		 * Decode html characters.
		 * @param {string} str The incoming string.
		 * @return {string} Returns a string in which escaped html entities are converted back to their normal state.
		 **/
		htmlUnescape: function (str) {
			return String(str)
				.replace(/&quot;/g, '"')
				.replace(/&#39;/g, "'")
				.replace(/&lt;/g, '<')
				.replace(/&gt;/g, '>')
				.replace(/&amp;/g, '&');
		},

		/***** LOGGING METHODS ******/

		/**
		 * Track events in GA and/or locally. Event labels/etc are whatever you want them to be.
		 * @param {string} category  A category.
		 * @param {string} action  An action.
		 * @param {string} label  A label.
		 * @param {string} [value] A value.
		 */
		track: function (category, action, label, value) {
			// Log in api mode.
			if (myOptions.debug_mode) api.log("Event sent: " + category + ", " + action + ", " + label + ", " + value);
			// Track via Google Analytics (ga.js or analytics.js)
			if (myOptions.trackEventsAnalytics) {
				if (typeof _gaq != 'undefined') _gaq.push(['_trackEvent', category, action, label, value]);
				if (typeof ga != 'undefined') ga('send', 'event', category, action, label, value);
			}
			// Track to local database
			if (myOptions.trackEventsLocal) {
				// Don't use api.ajax.get because that tracks events, so we'd get into loop
				$.ajax(myOptions.baseurl + 'system/track/?category=' + api.urlEncode(category) + '&action=' + api.urlEncode(action) + '&label=' + api.urlEncode(label) + '&value=' + api.urlEncode(value), {
					dataType: 'html',
					type: 'GET'
				});
			}
		},

		/**
		 * Logs a message to the console. Ingored if console not available.
		 * @param {string} message The message to log.
		 * @param {string} [type="error"] Can be notice, warning, or error
		 * @param {string} [context=null] The context is any other element or object which will be logged.
		 * @return {boolean} Returns true or console.log.
		 **/
		log: function (message, type, context) {
			if (typeof console != 'undefined' && typeof (console) == 'object') {
				if (typeof context == 'undefined') context = '';
				switch (type) {
					case 'error':
						api.exception(message, true);
						return console.error(message, context);
					case 'warn':
					case 'warning':
						api.exception(message, false);
						return console.warn(message, context);
					case 'info':
					case 'notice':
						return console.info(message, context);
					default:
						console.log(message, context);
				}
			}
			return true;
		},

		/**
		 * Logs an error message to the console.
		 * @param {string} message The message to log.
		 * @param {string} [context=null] The context is any other element or object which will be logged.
		 * @return {boolean} Returns true or console.log.
		 **/
		error: function (message, context) {
			return api.log(message, 'error', context);
		},

		/**
		 * Logs a warning message to the console.
		 * @param {string} message The message to log.
		 * @param {string} [context=null] The context is any other element or object which will be logged.
		 * @return {boolean} Returns true or console.log.
		 **/
		warning: function (message, context) {
			return api.log(message, 'warning', context);
		},

		/**
		 * Logs a notice message to the console.
		 * @param {string} message The message to log.
		 * @param {string} [context=null] The context is any other element or object which will be logged.
		 * @return {boolean} Returns true or console.log.
		 **/
		notice: function (message, context) {
			return api.log(message, 'notice', context);
		},

		/**
		 * Send exception.
		 * @param {string} message The message to send.
		 * @param {boolean} [fatal=false] Set this to true if this is a fatal error. False by default.
		 * @return {boolean} Always returns false.
		 */
		exception: function (message, fatal) {
			// Set default value for fatal
			if (typeof fatal == 'undefined') fatal = false;

			// Check if not in debug mode and new GA available
			if (!myOptions.debug_mode && typeof ga != 'undefined') {
				ga('send', 'exception', {
					exDescription: message,
					exFatal: fatal
				});
			}
			return false;
		},

		/***** VIEWPORT MANIPULATION AND DETECTION METHODS *******/

		/**
		 * Smooth scrolling in and outside of Facebook tabs and canvas pages.
		 * @param {Number|object|string} yORdomORselector The pixel value or the dom/jquery object or a selector of where to scroll to.
		 * @param {Number} [duration=1000] The number of miliseconds for the animation.
		 **/
		scroll: function (yORdomORselector, duration) {
			// Get the y
			var y;
			switch (typeof yORdomORselector) {
				case 'string':
				case 'object':
					y = $(yORdomORselector).offset().top;
					break;
				default:
					y = yORdomORselector;
					break;
			}
			// Default duration
			if (typeof duration == 'undefined') duration = 1000;
			// First, do the standard scrolling (will work outside of FB)
			$('html,body').animate({scrollTop: y}, {duration: duration});
			// If within FB Canvas context, we need more...
			if (myOptions.facebook) {
				var blue_bar_height = 42;
				FB.Canvas.getPageInfo(function (pageInfo) {
					$({y: pageInfo.scrollTop}).animate(
						{y: y + pageInfo.offsetTop - blue_bar_height},
						{
							duration: duration, step: function (offset) {
								FB.Canvas.scrollTo(0, offset);
							}
						});
				});
			}
		},

		/**
		 * Checks to see if an element is in the viewport.
		 * @param {string|object} el A DOM element, jQuery object, or selector string.
		 * @param {boolean} [partially=true] If set to true (default), it will return true if element is at least in part visible.
		 * @return {boolean} Returns true if element is in viewport, false if it is not.
		 */
		inViewport: function (el, partially) {
			// Jquery or non-jquery works
			el = $(el)[0];
			if (typeof partially == 'undefined') partially = true;
			// Calculate element offsets!
			var top = el.offsetTop;
			var left = el.offsetLeft;
			var width = el.offsetWidth;
			var height = el.offsetHeight;
			while (el.offsetParent) {
				el = el.offsetParent;
				top += el.offsetTop;
				left += el.offsetLeft;
			}
			// Facebook iframe or document info
			var iw, ih, st, sl;
			if (zaj.facebook && zaj.fbcanvas) {
				FB.Canvas.getPageInfo(function (info) {
					zaj.fbcanvas = info;
				});
				// IMPORTANT: here we may still be using previous fbcanvas info! Unreliable!
				iw = zaj.fbcanvas.clientWidth;
				ih = zaj.fbcanvas.clientHeight;
				st = zaj.fbcanvas.scrollTop;
				sl = zaj.fbcanvas.scrollLeft;
			} else {
				iw = window.innerWidth || document.documentElement.clientWidth;
				ih = window.innerHeight || document.documentElement.clientHeight;
				st = $(window).scrollTop();
				sl = $(window).scrollLeft();
			}
			// Now do it!
			if (partially) {
				return (
					top < (st + ih) &&
					left < (sl + iw) &&
					(top + height) > st &&
					(left + width) > sl
				);
			} else {
				return (
					top >= st &&
					left >= sl &&
					(top + height) <= (st + ih) &&
					(left + width) <= (sl + iw)
				);
			}
		},

		/***** DATA ATTRIBUTE METHODS *****/

		/**
		 * Trigger inprogress class on inprogress elements
		 * @param {boolean} show Set to true or false to add or  remove inprogress class to/from the element.
		 */
		inProgress: function (show) {
			if (show) {
				$('[data-inprogress-class]').each(function () {
					$(this).addClass($(this).data('inprogress-class'))
				});
			} else {
				$('[data-inprogress-class]').each(function () {
					$(this).removeClass($(this).data('inprogress-class'))
				});
			}
		},

		/**
		 * Run through the context (defaults to body) and activate all registered data attribute handlers.
		 * @param {jQuery} [$context=$(document)] The jQuery object in which the handlers are searched for.
		 **/
		activateDataAttributeHandlers: function ($context) {
			activateDataAttributeHandlers($context);
		},

		/**
		 * Add a data attribute handler. If the data attribute is found on the page, the associated helper js is loaded.
		 * @param {string} handlerName The name of the handler which should be the data attribute to look for without data-. So for data-autopagination it is 'autopagination'.
		 * @param {string} handlerPath The path to the data attribute handler.
		 * @param {function} [callback=null] The callback function which is called when the data attribute handler object was loaded. The object is passed.
		 */
		addDataAttributeHandler: function (handlerName, handlerPath, callback) {
			var attribute = {name: handlerName, path: handlerPath, callback: callback};
			dataAttributes.push(attribute);
			activateSingleDataAttributeHandler(attribute, null);
		},

		/***** DEPRECATED METHODS ******/

		/**
		 * Go back in history.
		 * @deprecated Just use history.back(), it is now reliable in all browsers!
		 **/
		back: function () {
			history.back();
		},

		/**
		 * Sortable.
		 * @deprecated Use data attributes or $().$ofw().sortable() instead.
		 */
		sortable: function (target, receiver, callback, handle) {
			// Load up dependency
			requirejs(["system/js/ui/sortable"], function (sortable) {
				sortable.init(target, receiver, callback, handle);
			});
		}

	};

	/** Define api aliases @todo add deprecation notices where necessary **/
	api.request = ajaxRequest;
	api.open = api.window;
	api.refresh = api.reload;
	api.plugins = dataAttributesObjects;
	// Deprecated
	api.urlencode = api.urlEncode;
	api.querymode = api.queryMode;
	api.isURLValid = api.isUrlValid;
	api.validate = api.ajax.validate;
	api.inviewport = api.inViewport;


	// Return my external API
	return api;

});

/**
 * Enable JS error logging to Analytics.
 **/
window.onerror = function (message, url, line) {
	// Log to analytics
	if (typeof ga != 'undefined') ga('send', 'exception', {
		exDescription: 'Javascript error on line ' + line + ' of ' + url + ': ' + message,
		exFatal: true
	});
	// Allow event to propogate by returning false
	return false;
};

/**
 * Pushstate support.
 */
window.onpopstate = function (event) {
	/**if(event && event.state) {
		console.log(event);
	}**/
};
