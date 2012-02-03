//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajUpload
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 2.0
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+, Flash 9 & 10. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// requires mootools, FancyUpload2
// - 2.0 - initial release, mootools compatible
//////////////////////////////////////////////////////////////////////////////
/* usage: 
	var zajUpload = new zajUpload('this.divid',{useroptions},{usertexts});


	
*/
//////////////////////////////////////////////////////////////////////////////
// zajUpload class
//var zajUpload = new zajUpload();


var zajUpload = new Class({
	Implements: [Options, Events],
	
	options: {
		limitSize: false,
		limitFiles: 5,
		instantStart: false,
		allowDuplicates: false
	},
	texts: {
		upload: 'új fotó',
		cancel: 'mégsem'
	},
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
			// set variables
				this.id = 'zajupload-'+divid;
				this.divid = divid;
				this.myNewUploads = new Array();
				self = this;
			// load external files
				// load external css
					new Asset.css('css/zajupload.css', { });
				// load external js
					var pb_js = Asset.javascript('ext/fancyupload/source/Fx.ProgressBar.js?v1', {});
					var su_js = Asset.javascript('ext/fancyupload/source/Swiff.Uploader.js?v1', {});
					var fu_js = Asset.javascript('ext/fancyupload/source/FancyUpload2.js?v17', { onload: function(){self.initializeProcess();} });
			return true;
		},
		initializeProcess: function(){
			// now create html				
				$(this.divid).innerHTML = "<div id='"+this.id+"-status' class='zajupload-status'></div>";
					$(this.id+'-status').innerHTML += "<a href='#' id='"+this.id+"-browse'>fájlok hozzáadása</a> | <a href='#' id='"+this.id+"-clear'>mégsem</a> | <a href='#' id='"+this.id+"-upload'>feltöltés</a>";
					$(this.id+'-status').innerHTML += "<div><strong class='overall-title'>összesen</strong><br /><img src='"+zajlib.baseurl+"system/js/forms/img/bar.gif' class='progress overall-progress' /></div>";
					$(this.id+'-status').innerHTML += "<div><strong class='current-title'>fájl státusz</strong><br /><img src='"+zajlib.baseurl+"system/js/forms/img/bar.gif' class='progress current-progress' /></div>";
					$(this.id+'-status').innerHTML += "<div class='current-text'></div>";
				$(this.divid).innerHTML += "<ul id='"+this.id+"-list' class='zajupload-list'></ul>";
			var self = this;
			// instantiate swiffy
				this.myFancyUpload = new FancyUpload2($(this.id+'-status'), $(this.id+'-list'), {
					url: '../../../sys/zajupload.sys.php',
					fieldName: 'zajuploadfile',
					path: 'ext/fancyupload/source/Swiff.Uploader.swf',
					limitSize: 20 * 1024 * 1024, // 20Mb
					typeFilter: {'Images (*.jpg, *.jpeg, *.gif, *.png)': '*.jpg; *.jpeg; *.gif; *.png; *.mov; *.mp3'},
					onLoad: function() { },
					// The changed parts!
					debug: true, // enable logs, uses console.log
					target: this.id+'-browse' // the element for the overlay (Flash 10 only)
				});
			// add events
				// browse click
					$(this.id+'-browse').addEvent('click', function() {
						self.myFancyUpload.browse();
						return false;
					});
				// clear click
					$(this.id+'-clear').addEvent('click', function() {
						self.myFancyUpload.removeFile();
						self.fireEvent('cancel');
						self.fireEvent('clear');
						return false;
					});			
				// upload click
					$(this.id+'-upload').addEvent('click', function() {
						self.myFancyUpload.upload();
						return false;
					});
					
					this.myFancyUpload.addEvent('complete', function(){
						var myfname = self.myFancyUpload.filenames.pop();
						// add this file to myNewUploads
							self.myNewUploads.push(myfname);
						// fire complete event
							self.fireEvent('complete',myfname);
					});		
					this.myFancyUpload.addEvent('allComplete', function(){
						self.fireEvent('allComplete');
					});		
		}
});

// end of class
////////////////////////////////////////////////////////////////////////////
