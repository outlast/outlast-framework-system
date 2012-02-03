//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajEditorItemAdmin
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 2.0
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// requires zajUpload, zajPhotoAdmin, mootools, FancyUpload2
// - 2.0 - initial release, mootools compatible
//////////////////////////////////////////////////////////////////////////////
/*  usage: 
	var zajitemadmin = new zajEditorItemAdmin('divid','parentid','parentobj',{useroptions},{texts});

	events:
	
*/
//////////////////////////////////////////////////////////////////////////////
// zajPhotoAdmin class

var zajEditorItemAdmin = new Class({
	Implements: [Options, Events],
	
	options: {
		
	},
	texts: {
		title: 'hír szerkesztése',
		savetitle: 'adatok és elmentés',
		saveandstay: 'elmentem',
		saveandgo: 'elmentem és bezárom'
	},
	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, parentid, parentobj, options, texts){
			// set default options
				this.setOptions(options);
				this.texts = $merge(this.texts,texts);
				this.parentid = parentid;
				this.parentobj = parentobj;
				this.photoadmin = false;
				var self = this;			
			// load css
				Asset.css('css/zajeditoritemadmin.css?v5');
			// load js
				Asset.javascript('js/zajphotoadmin.js?v3',{ onload: function(){ self.fireEvent('photoadminloaded'); } });
			// now set all default values
				this.divid = 'zajeditoritemadmin-'+divid;
			// now create the divs
				$(divid).innerHTML = "<div class='zajeditoritemadmin_content formline' id='"+this.divid+"'></div><div class='formheader'>"+this.texts.savetitle+"</div><div class='formline helptext' id='logmessage'></div><div class='formline'><input type='button' value='"+this.texts.saveandstay+"'> <input type='button' value='"+this.texts.saveandgo+"'></div>";
		},

	//////////////////////////////////////////////////////////////////////////////
	// categories
		addCategory: function(name, id, options){
			// merge default options with options given
				var default_options = new Object();
				default_options.cssclass = "";
				options = $merge(default_options,options);
				var self = this;
			// add category to div
				$(this.divid).innerHTML += "<div class='formline' id='"+this.divid+"-category-"+id+"'><div class='formheader' id='"+this.divid+"-category-"+id+"-title'>"+name+"</div><div class='formline' id='"+this.divid+"-category-"+id+"-content'></div></div>";
				if($chk(options.cssclass)) $(this.divid+"-category-"+id).addClass(options.cssclass);
		},
		deleteCategory: function(id){
			$(this.divid+"-category-"+id).destroy();
		},
	//////////////////////////////////////////////////////////////////////////////
	// fields
		addField: function(categoryid, id, label, type, value, options){
			// merge default options with options given
				var default_options = new Object();
				default_options.cssclass = "";
				default_options.dbfieldname = "";
				default_options.selectoptions = "";
				default_options.selectonchange = "";
				default_options.checked = false;
				default_options.disabled = false;
				options = $merge(default_options,options);
				var self = this;
			// create field div id
				var fielddivid = this.divid+"-field-"+id;
			// now add div to category
				$(this.divid+"-category-"+categoryid+"-content").innerHTML += "<div class='zajeditoritemadmin_field formline' id='"+fielddivid+"'></div>";
			// based on type, create div content
				switch(type){
					case "checkbox":
						$(fielddivid).innerHTML = label+": <input type='checkbox' name='"+id+"' id='"+id+"' value='"+value+"'>";
						if(options.checked) $(id).checked = true;
						break;
					case "select":
						$(fielddivid).innerHTML = label+": ";
						$(fielddivid).appendChild(selectMe(id,JSON.decode(options.selectoptions),value,options.selectonchange));
						$(id).addClass('zajeditoritemadmin_formbox');
						break;
					case "radio":
						break;
					case "files":
						break;
					case "photos":
						// has the photo admin been loaded yet?
						if(typeof zajPhotoAdmin == "function"){
							// create div and photo admin
								$(fielddivid).innerHTML = "<div class='formline' id='"+id+"'></div>";
								this.photoadmin = new zajPhotoAdmin(id);
							// add the existing photos
								var pharray = JSON.decode(value);
								if(pharray != null) for(var i = 0; i < pharray.length; i++) this.photoadmin.addPhoto(pharray[i]['photoid'], pharray[i]['photourl']);
						}
						else this.addEvent('photoadminloaded', function(){ self.addField(categoryid, label, type, id, value, options); });
						break;
					case "related":
						break;
					case "links":
						// add existing links
							var larray = JSON.decode(value);
							if(larray != null) for(var i = 0; i < larray.length; i++) $(fielddivid).innerHTML += "<div class='formline'><input type='text'><inpu type='text'></div>";
						// add additional link adder
						
						// and finally the link add/remove javascript
						
						break;
					case "smalltextarea":
						$(fielddivid).innerHTML = "<textarea name='"+id+"' id='"+id+"'>"+value+"</textarea>";
						$(id).addClass('zajeditoritemadmin_smalltextarea');
						break;
					case "largetextarea":
						$(fielddivid).innerHTML = "<textarea name='"+id+"' id='"+id+"'>"+value+"</textarea>";
						$(id).addClass('zajeditoritemadmin_largetextarea');
						break;
					case "password":
						break;
					case "custom":
						$(fielddivid).innerHTML = value;
						break;
					default:
						$(fielddivid).innerHTML = label+": <input type='text' name='"+id+"' id='"+id+"' value='"+value+"'>";
						$(id).addClass('zajeditoritemadmin_formbox');
						break;
				}				
			return true;
		},
		deleteField: function(id){
			$(this.divid+"-field-"+id).destroy();
		},
	//////////////////////////////////////////////////////////////////////////////
	// automatic save
		autosaveMe: function(){
		
		},
		autosaveMeProcess: function(result){
		
		},
	//////////////////////////////////////////////////////////////////////////////
	// explicit save
		saveMe: function(){
		
		},
		saveMeProcess: function(result){
		
		}
});


// end of class
////////////////////////////////////////////////////////////////////////////

function zajEditorItemAdminImport(parentid,parentobj){
	zajajax.addGetRequest('ifc/zajeditoritemadmin.ifc.php?req=edititem&parentid='+parentid+'&parentobj='+parentobj, '', zajEditorItemAdminImportProcess);
}
function zajEditorItemAdminImportProcess(json_data){
	var d = JSON.decode(json_data);
	var divid = "zajeditoritemadminimport-"+d['parentid'];
	// create popup
		zajpopup.addPopup('teszttitle',"<div id='"+divid+"'></div>", 850, 500);
	// create class
		var zobj = new zajEditorItemAdmin(divid,d['parentid'],d['parentobj']);
	// now add categories
		for(var i = 0; i < d['categories'].length; i++){
			var myoptions = new Object();
			zobj.addCategory(d['categories'][i]['name'], i, d['categories'][i]['options']);
			for(var j = 0; j < d['categories'][i]['fields'].length; j++){
  	 			zobj.addField(i, d['categories'][i]['fields'][j][0], d['categories'][i]['fields'][j][1], d['categories'][i]['fields'][j][2], d['categories'][i]['fields'][j][3], d['categories'][i]['fields'][j][4]);
			}
		}
	return zobj;
}
