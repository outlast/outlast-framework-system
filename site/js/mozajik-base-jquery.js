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
	 * Layer for onready functions.
	 **/
 	zaj.ready = function(func){ $(document).ready(func); };

	/**
	 * Logs a message to the console. Ingored if console not available.
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

	// Go back!
	zaj.back = function(){ history.back(); };


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
	 * Reload the current url.
	 **/
		zaj.reload = function(){
			window.location.reload(false);
		};
		zaj.refresh = zaj.reload;
			
	/**
	 * Redirect to a page relative to baseurl or absolute.
	 * @param relative_or_absolute_url The URL relative to baseurl. If it starts with // or http or https it is considered an absolute url
	 **/
		zaj.redirect = function(relative_or_absolute_url){
			// Is it relative?
			if(relative_or_absolute_url.substr(0,2) != '//' && relative_or_absolute_url.substr(4, 3) != "://" && relative_or_absolute_url.substr(5, 3) != "://") window.location = zaj.baseurl+relative_or_absolute_url;
			else window.location = relative_or_absolute_url;
			return true;
		};



	/**
	 * Redirect to a page relative to baseurl or absolute.
	 * @param relative_or_absolute_url The URL relative to baseurl. If it starts with // or http or https it is considered an absolute url
	 **/
		zaj.ajax = {};
			zaj.ajax.get = function(request,result){
				zaj.ajax.request('get', request, result);
			};
			zaj.ajax.post = function(request,result){
				zaj.ajax.request('post', request, result);
			};
			zaj.ajax.request = function(mode,request,result){
				// Figure out query string
					if(mode == 'post'){
						var rdata = request.split('?');
						if(rdata.length > 2) zaj.warning("Found multiple question marks in query string!");
						request = rdata[0];
						datarequest = rdata[1];
					}
					else datarequest = '';
				// Now send request
				$.ajax(zaj.baseurl+request, {
					success: function(data, textStatus, jqXHR){
						if(typeof result == "function") result(data);
						else{
							if(data == 'ok') zaj.redirect(result);
							else alert(data);
						}
					},
					complete: function(jqXHR, textStatus){
						if(textStatus != "success") console.log("Ajax request failed with status ".textStatus);
					},
					data: datarequest,
					dataType: 'html',
					type: mode
				});				
			};
	
	/**
	 * A function which opens up a new window with the specified properties
	 * @param url The url of the window
	 * @param width The width in pixels.
	 * @param height The height in pixels
	 * @param options All other options as an object.
	 **/
		zaj.window = function(url, width, height, options){
			// Default options!
				if(typeof width == 'undefined') width = 500;
				if(typeof height == 'undefined') height = 300;
			// TODO: implement options
			window.open (url,"mywindow","status=0,toolbar=0,location=0,menubar=0,resizable=1,scrollbars=1,height="+height+",width="+width);
		};
		zaj.open = zaj.window;

	/**
	 * URLencodes a string so that it can safely be submitted in a GET query.
	 * @param url The url to encode.
	 * @return The url in encoded form.
	 **/
	 	zaj.urlencode = function(url){
	 		return encodeURIComponent(url);
	 	};
	 
	/**
	 * Now extend the jQuery object.
	 **/
	(function($){
	   $.fn.$zaj = $.fn.zaj = function(){
	  	var target = this;
	  	// Create my object and return
	  	return {
	  		// Get or post serialized data
	  		get: function(url, response){ return zaj.ajax.get(url+'?'+target.serialize(), response); },
	  		post: function(url, response){ return zaj.ajax.post(url+'?'+target.serialize(), response); }
	  	}
	  };
	})(jQuery);

