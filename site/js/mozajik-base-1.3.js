/**
 * This is the basic Mozajik JS layer class. The JS layer has three sections, 'base' (basic stuff for sending requests, logging, etc.), 'tool' (tabs, forms, etc.),
 *	and 'ui' (user interface elements)
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.3
 **/

// Create a new class which will contain the sections
	var Mozajik = new Class({baseurl:'',fullrequest:'',fullurl:'',app:'',mode:'',debugmode:false,protocol:'http',ready:function(){}});
	var zaj = new Mozajik();

/**
 * Backwards-compatible functions implemented temporarily. The implementation is imperfect, but compatible - on purpose! Depricated, remove from release!
 **/
	var $chk = function(obj){
    	zaj.log('Depricated method chk or defined used!');
    	if(typeOf(obj) == 'element') return true;
    	else return false;
	};
	var $defined = $chk;	

/**
 * Enable JS error logging.
 * @todo Do not send request if js logging is not enabled!
 **/	
window.onerror=function(message, url, line){
	// determine my relative url
		var my_uri = new URI();
		var error_url = zaj.baseurl+'system/javascript/error/';
	// send error to console
		 zaj.notice('Logged JS error: '+message+' on line '+line+' of '+url);
 	// send an ajax request to log the error if it is a modern, supported browser
		var r = new Request.JSON({ 'url' : error_url, 'method':'get','data': 'js=error&message='+message+'&url='+url+'&location='+zaj.fullrequest+'&line='+line, link: 'chain' });
		r.send();
}

/**
 * Base class contains the most important and often used features of the Mozajik JS layer: ajax requests, history management, logging, etc.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/
var MozajikBase = new Class({
	Implements: [Options, Events],
	
	/**
	 * Default options
	 **/
		options: {			
		},

	/**
	 * A shortcut to the ajax class.
	 **/
		ajax: false,
	
	/**
	 * A list of ready functions to be executed upon successful load / ajax load.
	 **/
	 	ready_functions: new Array(),	 
	
	/**
	 * Creates the MozajikBase class and sets its options.
	 * @constructor
	 **/
		initialize: function(options){
			// set default options
				this.setOptions(options);

			// create my objects
				this.ajax = new MozajikBaseAjax();
				this.history = new MozajikBaseHistory();

		},
		
	/**
	 * Logs a message to the console.
	 * @param string message The message to log.
	 * @param string type Can be notice, warning, or error
	 **/
		log: function(message, type){
			if(typeof console != 'undefined' && typeOf(console) == 'object'){
				switch(type){
					case 'error': return console.error(message);
					case 'warn':
					case 'warning': return console.warn(message);
					case 'info':
					case 'notice': return console.info(message);
					default: console.log(message);
				}
			}
			return true;
		},


	/**
	 * Toggles two DOM elements, showing one and hiding another.
	 * @param show The DOM element to show.
	 * @param hide The DOM element to hide.
	 **/
		toggle: function(show, hide){
			$(hide).dissolve();		
			$(show).reveal();		
		},

	/**
	 * Redirect to a page relative to baseurl.
	 * @param relative_url The URL relative to baseurl.
	 **/
		redirect: function(relative_url){
			window.location = zaj.baseurl+relative_url;
			return true;
		},
		
	/**
	 * Reload the current url.
	 **/
		reload: function(){
			window.location.reload(false);
		},
		refresh: function(){ this.reload(); },
		
	/**
	 * A function which serves to unify dojo.ready, window.addEvent('domready'), etcetc. This also fires after any ajax requests are completed and ready.
	 **/
	 	ready: function(func){
	 		// TODO: add ajax functionality via ready_functions array.
	 		window.addEvent('domready', func);
	 	},

	/**
	 * A function which opens up a new window with the specified properties
	 * @param url The url of the window
	 * @param width The width in pixels.
	 * @param height The height in pixels
	 * @param options All other options as an object.
	 **/
		window: function(url, width, height, options){
			// Default options!
				if(typeof width == 'undefined') width = 500;
				if(typeof height == 'undefined') height = 300;
			// TODO: implement options
			window.open (url,"mywindow","status=0,toolbar=0,location=0,menubar=0,resizable=1,scrollbars=1,height="+height+",width="+width);
		}
});

/**
 * The Ajax class handles requests back and forth between the JS layer and Mozajik controllers.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/
var MozajikBaseAjax = new Class({
	Implements: [Options, Events],
	
	/**
	 * Default options
	 **/
		options: {
			loadOverlay: true,
			logActions: true,
			popupOnRequestError: false
		},
	
	/**
	 * Creates the MozajikBaseAjax class and sets its options.
	 * @constructor
	 **/
		initialize: function(options){
			// set default options
				this.setOptions(options);
			return true;
		},

	/**
	 * Creates a GET request and sends it to the specified URL (relative to baseurl), which is then routed to the appropriate controller
	 * @param string request The request url relative to baseurl.
	 * @param function|element|string result The result can be a function, an element, or a url.
	 * @param object bind Used to bind 'this' within the function. Only matters if result is a function.
	 **/
		get: function(request,result,bind){
			this.send('get',request,result,bind);
		},

	/**
	 * Creates a POST request and sends it to the specified URL (relative to baseurl), which is then routed to the appropriate controller
	 * @param string request The request url relative to baseurl.
	 * @param function|element|string result The result can be a function, an element, or a url.
	 * @param object bind Used to bind 'this' within the function. Only matters if result is a function.
	 **/
		post: function(request, result,bind){
			this.send('post',request,result,bind);
		},

	/**
	 * Sends a file via an ajax POST request. For now it only supports 'file' types and requires latest Gecko or WebKit to work.
	 * @todo Make Moo-compatible once moo allows file as data.
	 **/
		postfile: function(fileinput, request, result){
			// add the baseurl if needed
				if(request.substr(0,2) != '//' && request.substr(4, 3) != "://" && request.substr(5, 3) != "://") request = zaj.baseurl+'/'+request;
			// grab the file object
				var file = $(fileinput).files[0];
			// last request save
				this.last_request = request;
			// create a request
				var xhr = new XMLHttpRequest;
				var self = this;
				upload = xhr.upload;
			// add events
				//upload.onload = function(){ };
				xhr.onreadystatechange = function(){ if (xhr.readyState === 4){ self.process(result, xhr.responseText); } };
				xhr.upload.addEventListener('progress',function(progress){ self.fireEvent('progress', progress); } );
			// send request
				xhr.open("post", request, true);
				xhr.send(file);
		},

	/**
	 * Sends the actual request via method and returns the result to a div, a function, or url
	 **/
		send: function(method, request, result, bind){
			// init variables
				var request_url = "";
				var request_data = "";
				// add the baseurl if needed
					if(request.substr(0,2) != '//' && request.substr(4, 3) != "://" && request.substr(5, 3) != "://") request = zaj.baseurl+'/'+request;
				// request is a div id or request is a url
					if(typeOf(request) == 'element') request_url = $(request).getProperty('action')+'?'+$(request).toQueryString();
					else request_url = request;
				// set request data if post
					if(method == 'post'){
						// construct request_data
							//request_data = url_obj.get('query');
						// construct the request url (without query string)
							request_url_parts = request_url.split('?');
							if(request_url_parts.length > 2) zaj.notice('Invalid query string! (more than one ? found!) '+request_url);
							request_url = request_url_parts[0];
							request_data = request_url_parts[1];
					}
					// somehow use the fragment section to transfer data related to the current request
			// last request save
				this.last_request = request_url;
			// create a new request object
				var new_request = new Request.HTML({
					method: method,
					url: request_url,
					data: request_data,
					link: 'chain',
					evalScripts: true,
					evalResponse: true,
					noCache: true
				});
			// set my process function
				var self = this;
				new_request.addEvent('success',function(responseTree, responseElements, responseHTML, responseJavaScript){self.process(result, responseHTML, bind);});
				new_request.addEvent('failure',function(xhr){self.error(xhr);});
			// fire request event
				this.fireEvent('request');
			// send request
				new_request.send();
		},


	/**
	 * Handle response and pass to a div, a function, or url
	 **/
		process: function(result, responseText, bind){
			// now what is result?
				// is result even defined?
					if(!typeOf(result)){
						zaj.notice('No result container specified.');
						return true;
					} 
				// is result a function?
					if(typeOf(result) == 'function'){
						// if bind requested
							if(bind != undefined && bind) result = result.bind(bind);
						// call result
							result(responseText.trim());	
						// we are done!
							this.fireEvent('complete');
						return true;
					}
				// is it a div id?
					if(typeOf($(result)) == 'element'){
						// set div contents
							$(result).set('html', responseText);
						// we are done!
							this.fireEvent('complete');
							//(function(){ window.fireEvent('domready'); }).delay(500);
						return true;
					}
				// is result a URL?
					if(typeOf(result) == 'string'){
						if(responseText.trim() == "ok"){
							// add the baseurl if needed
								if(result.substr(0, 2) != '//' && result.substr(4, 3) != "://" && result.substr(5, 3) != "://") result = zaj.baseurl+'/'+result;
							// now send
								window.location = result;
						}
						else zaj.alert(responseText);
					}
					return true;
		},

	/**
	 * Handle the error
	 **/
		error: function(xhr){
			// should i display the warning?
				if(this.options.popupOnRequestError != '') zaj.alert(this.options.popupOnRequestError);
				else zaj.warning('The ajax request failed: '+this.last_request);
			// fire the error event
				this.fireEvent('error');
		}
});


/**
 * The History class is used to manage back and forward buttons when using ajax requests.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 * @todo Implement this!
 **/
var MozajikBaseHistory = new Class({
	Implements: [Options, Events],
	
	/**
	 * Default options
	 **/
		options: {			
		},
	
	/**
	 * Creates the MozajikBaseHistory class and sets its options.
	 * @constructor
	 **/
		initialize: function(options){
			// set default options
				this.setOptions(options);
		}
});


/**
 * The Element class allows the extension of mootools elements via the zaj object.
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 **/
var MozajikBaseElement = new Class({
	Implements: [Events],
	/**
	 * Creates the MozajikBaseElement class and sets its options.
	 * @constructor
	 **/
		initialize: function(el){this.element = el;}	
});

/**
 * Class Loader allows you to load javascript, css, or image files async
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 3.0
 */
Mozajik.Loader = new Class({
	Implements: [Events],
	/**
	 * Events:
	 * - load: fired after all the requested objects have been loaded (css not counted).
	 **/

	/**
	 * Creates a new Loader object
	 **/
		initialize: function(element, options){
			// set default options
				this.assets = new Array();
				this.added = 0;
				this.loaded = 0;
				this.loaded_assets = new Array();
		},

	/**
	 * Add an image, javascript, or css to the queue
	 * @param string url Image url relative to base.
	 **/
	 	image: function(url, id){ this.assets.push({'type':'image','url':url, 'id':id}); this.added++; },
	 	css: function(url, id){ this.assets.push({'type':'css','url':url, 'id':id}); },
	 	javascript: function(url, id){ this.assets.push({'type':'javascript','url':url, 'id':id});  this.added++; },
	 	//js: this.javascript,
	 
	/**
	 * Starts loading all of my requested assets
	 **/
	 	start: function(){
	 		// is my asset empty?
	 			if(this.assets.length <= 0) return false;
	 		// get my first asset
		 		var asset = this.assets.shift();
		 	// check to see if asset is not yet loaded
				var self = this;
		 		if(this.loaded_assets.indexOf(asset.url) < 0){
				 	// load me
				 		switch(asset.type){
				 			case 'image':		zaj.notice('Loading image '+zaj.baseurl+asset.url);
				 								Asset.image(zaj.baseurl+asset.url, {'id': asset.id, 'events': { 'load': function(){ self.loaded++; zaj.notice('Loaded '+asset.url); if(self.loaded >= self.added) self.fireEvent('load'); } } });
				 								break;
				 			case 'css':			zaj.notice('Loading css '+zaj.baseurl+asset.url);
				 								Asset.css(zaj.baseurl+asset.url, {'id': asset.id });
				 								break;
				 			case 'javascript':	zaj.notice('Loading javascript '+zaj.baseurl+asset.url);
				 								Asset.javascript(zaj.baseurl+asset.url, {'id': asset.id, 'events': { 'load': function(){ self.loaded++; zaj.notice('Loaded '+asset.url); if(self.loaded >= self.added) self.fireEvent('load'); } } });
				 								break;		 			
				 		}
		 		}
		 		else{
		 			// remove one from added (if not css)
			 			if(asset.type != 'css') self.added--;
			 		// fire load event
			 			if(self.loaded >= self.added){
			 				self.fireEvent('load');
			 				zaj.notice('Skipped loads, but now firing load event!');	
			 			}
		 			// give notice of skip
			 			zaj.notice('Skipping '+zaj.baseurl+asset.url+', already loaded!');
		 		}
		 	// add asset to loaded assets
		 		this.loaded_assets.push(asset.url);
	 		// recursive call
	 			return this.start();
	 	}
});
zaj.loader = new Mozajik.Loader();


/**
 * This is the actual instance of the base class.
 **/
zaj.base = new MozajikBase();

/**
 * Create the zaj layer of Element extensions
 **/
Element.implement({
	/* Implement the zaj object */
	$zaj: function(){ return new MozajikBaseElement(this); }
});

/**
 * Implement shortcuts from zaj to base
 **/
Mozajik.implement({
	/**
	 * Log messages.
	 **/
	log: function(message, type){
		return zaj.base.log(message, type);
	},
	error: function(message){
		return this.log(message, 'error');
	},
	warning: function(message){
		return this.log(message, 'warning');
	},
	notice: function(message){
		return this.log(message, 'notice');
	},
	/**
	 * Custom alerts, confirms, prompts
	 **/
	alert: function(message, options){
		if(typeof zaj.popup == 'object') return zaj.popup.show(message, options);
		else return alert(message);
	},
	confirm: function(message, urlORfunction){
		// if the passed param is a function, then return confirmation as its param
		if(typeof urlORfunction == 'function'){
			var result = confirm(message);
			urlORfunction(result);
		}
		// if the passed param is a url, redirect if confirm
		else{
			if(confirm(message)) window.location=zaj.baseurl+urlORfunction;
		}
	},
	prompt: function(message){
		return prompt(message);
	},
	/**
	 * Shortcuts to base
	 **/
	ajax: zaj.base.ajax,
	redirect: zaj.base.redirect,
	reload: zaj.base.reload,
	refresh: zaj.base.reload,
	window: zaj.base.window,
	open: zaj.base.window
});

/**
 * Implement Element shortcuts
 **/
MozajikBaseElement.implement({
	/**
	 * Ajax requests
	 **/
		get: function(request, result){
			if(this.element.toQueryString() == "") zaj.error("Request error: Your query string is empty! ("+request+")");
			else zaj.ajax.get(request+'?'+this.element.toQueryString(), result);
		},
		post: function(request, result){
			if(this.element.toQueryString() == "") zaj.error("Request error: Your query string is empty! ("+request+")");
			else zaj.ajax.post(request+'?'+this.element.toQueryString(), result);
		}
});

/**
 * Check if Mozajik was properly loaded
 **/
window.addEvent('domready', function(){ if(zaj.baseurl == '') zaj.log('Mozajik JS layer loaded, but not initialized. Requests will not work properly!'); });
