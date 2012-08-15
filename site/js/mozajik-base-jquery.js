/**
 * This is the basic Mozajik JS layer class for jQuery. The JS layer has three sections, 'base' (basic stuff for sending requests, logging, etc.), 'tool' (tabs, forms, etc.),
 *	and 'ui' (user interface elements)
 * @author Aron Budinszky /aron@mozajik.org/
 * @version 1.0
 * 
 * @changes 1.0 Now supports pushstate, but ajax methods' parameter order has changed: bind is now the fourth param, the third is the new url.
 **/

// Create a new class which will contain the sections
	var zaj = {baseurl:'',fullrequest:'',fullurl:'',app:'',mode:'',debugmode:false,protocol:'http',ready:function(){}};
	//var zaj = new Mozajik();

// Pushstate support (from pjax)
	zaj.pushstate = window.history && window.history.pushState && window.history.replaceState
					// pushState isn't reliable on iOS until 5.
					&& !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/)

/**
 * Mozajik zaj object implementations.
 **/
	/**
	 * Logs a message to the console.
	 * @param string message The message to log.
	 * @param string type Can be notice, warning, or error
	 * @param string context The context is any other element or object which will be logged.
	 **/
	zaj.log = function(message, type, context){
		if(typeof console != 'undefined' && typeof(console) == 'object'){
			if(typeof context == 'undefined') context = '';
			switch(type){
				case 'error': return console.error(message, context);
				case 'warn':
				case 'warning': return console.warn(message, context);
				case 'info':
				case 'notice': return console.info(message, context);
				default: console.log(message, context);
			}
		}
		return true;
	};
	zaj.error = function(message, context){
		return zaj.log(message, 'error', context);
	};
	zaj.warning = function(message, context){
		return zaj.log(message, 'warning', context);
	};
	zaj.notice = function(message, context){
		return zaj.log(message, 'notice', context);
	};

	/**
	 * Custom alerts, confirms, prompts
	 **/
	zaj.alert = function(message, options){
		return alert(message);
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
	 * Shortcuts to base
	 **/
	/**ajax: zaj.base.ajax,
	redirect: zaj.base.redirect,
	reload: zaj.base.reload,
	refresh: zaj.base.reload,
	window: zaj.base.window,
	urlencode: zaj.base.urlencode,
	back: zaj.base.back,
	open: zaj.base.window**/

