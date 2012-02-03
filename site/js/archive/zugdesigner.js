//////////////////////////////////////////////////////////////////////////////
// zajmedia.com (c) 1999-2009 – custom scrollbar class
//////////////////////////////////////////////////////////////////////////////
// class: zajDesigner
// written by: hontalan /aron budinszky - aron@zajmedia.com/
// version: 2.0b
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is free, but requires permission.
// please send licence requests to kontakt@zajmedia.com
//////////////////////////////////////////////////////////////////////////////
// requires mootools, and a good browser (IE7+, FF 2+, Safari 2+, Opera 9+)
// version history:
// - 2.0b - beta release
//////////////////////////////////////////////////////////////////////////////
/*  usage: 
	var designer = new zajDesigner('divid',{ options });

	events:
		
*/
//////////////////////////////////////////////////////////////////////////////
// zajDesigner class (the container)

var zajDesigner = new Class({
	Implements: [Options, Events],
	
	options: {

	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
				this.mydiv = $(divid);
				this.numofelements = 0;
			// get my position and size
				this.containersize = this.mydiv.getSize();
				this.containerposition = this.mydiv.getPosition();
				
			// add mouseover event
				var self = this;
				this.mydiv.addEvent('mouseenter', function(){ if(self.numofelements <= 0) zajShowOverlay(self.mydiv,"<div>- elemek hozzáadása</div>",'zajdesigner_editoptionsbox'); });
		},

	//////////////////////////////////////////////////////////////////////////////
	// add or remove boxes
		addElement: function(){
			// create and append new element
				var newelement = new zajDesignerElement(this.mydiv);
				this.numofelements++;
		},
		removeElement: function(el){
			// destroy the element
				el.destroy();
				this.numofelements--;
		},

	//////////////////////////////////////////////////////////////////////////////
	// save me
		saveMe: function(){

		
		}

});

// End of zajDesigner class
//////////////////////////////////////////////////////////////////////////////




//////////////////////////////////////////////////////////////////////////////
// zajDesignerElement class (the element)

var zajDesignerElement = new Class({
	Implements: [Options, Events],
	
	Extends: zajDesigner,				// all elements can be containers as well
	
	options: {
		width: '100%',
		height: '100px'
	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(parent, options){
			// set default options
				this.setOptions(options);
				this.mydiv = new Element('div');
			// set element styles
				this.mydiv.addClass('zajdesigner_element');
				this.mydiv.setStyle('width', this.options.width);
				this.mydiv.setStyle('height', this.options.height);
				this.mydiv.setStyle('float', 'left');
			// append to parent
				parent.appendChild(this.mydiv);
			
			// get my position and size
				this.containersize = this.mydiv.getSize();
				this.containerposition = this.mydiv.getPosition();

			// add mouseover event
				var self = this;
				this.mydiv.addEvent('mouseenter', function(){ 				
								var el = zajShowOverlay(self.mydiv,'whateve2','zajdesigner_editoptionsbox');
								// add resizing box
								var resizer = new Element('div');
								el.appendChild(resizer);
								resizer.setStyle('position','absolute');
								resizer.setStyle('bottom','0');
								resizer.setStyle('right','0');
								resizer.setStyle('background-color','#FFFFFF');
								resizer.setStyle('width','15px');
								resizer.setStyle('height','15px');
								resizer.setStyle('cursor','se-resize');
																
								var myMove = new Drag.Move(el, {
								    droppables: '.zajdesigner_element',
									onDrop: function(element, droppable){
								        if (droppable) self.mydiv.inject(droppable, 'before');
								    },								    
    								onStart: function(){
								        el.removeEvents('mouseleave');
								    },
								    onComplete: function(){
								        // destroy editor
								        	el.destroy();
									}
								});


								// make the element resizable
								el.makeResizable({
								    handle: resizer,
								    onStart: function(){
								        el.removeEvents('mouseleave');
								    },
								    onBeforeStart: function(){
								        myMove.detach();
								    },
								    onComplete: function(){
								        	var minimumsize = 15;
								        // get position and size
								        	var elsize = el.getSize();
								        	var elpos = el.getPosition();
								        // set size for element
								        	if(elsize.x > minimumsize) self.mydiv.setStyle('width', elsize.x);
								        	if(elsize.y > minimumsize) self.mydiv.setStyle('height', elsize.y);
								        // destroy editor
								        	el.destroy();
									    }
								});

							});
		}
		

});
