//////////////////////////////////////////////////////////////////////////////
// zajmedia.com (c) 1999-2009 – custom scrollbar class
//////////////////////////////////////////////////////////////////////////////
// class: zajScroll
// written by: hontalan /aron budinszky - aron@zajmedia.com/
// version: 2.0b9
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is free, but requires permission.
// please send licence requests to kontakt@zajmedia.com
//////////////////////////////////////////////////////////////////////////////
// requires mootools, and a good or bad browser (IE6+, Firefox 2+, Safari 2+,
//					Opera 9+ tested)
// version history:
// - 2.0b9 - beta release
// - known issues: no buttons, no horizontal scrollbar, scrollwheel event is limited to vertical,
//				FF&Flash bug: requires <embed> tag with wmode=transparent
//////////////////////////////////////////////////////////////////////////////
/*  usage: 
	var scroller = new zajScroll('divid',{ options });

	events:
		
*/
//////////////////////////////////////////////////////////////////////////////
// zajScroll class

var zajScroll = new Class({
	Implements: [Options, Events],
	
	options: {
		vertical: true,
		horizontal: false,
		display_scroller: true,
		display_scrollbar: true,
		display_buttons: false,
		button_mode: 'win',
		scroll_speed: 10
	},	
	
	//////////////////////////////////////////////////////////////////////////////
	// constructor
		initialize: function(divid, options){
			// set default options
				this.setOptions(options);
				this.divid = divid;
			// Browser check: IE 6 needs to die
				//var is_ie6 = (window.external && typeof window.XMLHttpRequest == "undefined");
				//if(is_ie6){
				//	$(this.divid).setStyle('overflow', 'auto');					
				//	return false;
				//}

			// set div properties
				$(this.divid).setStyle('overflow', 'hidden');
			// get content properties
				this.originalsize = $(this.divid).getSize();
				this.originalposition = $(this.divid).getPosition();
				this.getSizes();	// this gets the sizes that can be modified
			
			// create and init scrollbars
				// create vertical scrollbar
					this.vscrollbar = new Element('div');
						this.vscroller = new Element('div');
							this.vscrollertop = new Element('div');
							this.vscrollermiddle = new Element('div');
							this.vscrollerbottom = new Element('div');
					this.vscrollup = new Element('div');
					this.vscrolldown = new Element('div');
				// create horizontal scrollbar
					this.hscrollbar = new Element('div');
						this.hscroller = new Element('div');
							this.hscrollerleft = new Element('div');
							this.hscrollermiddle = new Element('div');
							this.hscrollerright = new Element('div');
					this.hscrollleft = new Element('div');
					this.hscrollright = new Element('div');
	
				// add vertical scrollbar properties and styles
					// scrollbar
						this.vscrollbar.id = "zajscroll_vscrollbar_"+divid;
						this.vscrollbar.addClass('zajscroll_scrollbar');
						this.vscrollbar.addClass('zajscroll_vscrollbar');
						this.vscrollbar.fade('hide');
					// scroller
						this.vscroller.id = "zajscroll_vscroller_"+divid;
						this.vscroller.addClass('zajscroll_scroller');
						this.vscroller.addClass('zajscroll_vscroller');
						this.vscroller.setStyle('position','absolute');
						this.vscroller.setStyle('top',0);
						this.vscroller.setStyle('left',0);
						// * classes to inner divs 
						this.vscrollertop.addClass('zajscroll_scrollertop');
						this.vscrollermiddle.addClass('zajscroll_scrollermiddle');
						this.vscrollerbottom.addClass('zajscroll_scrollerbottom');
					// buttons

				// add horizontal scrollbar properties and styles
					this.hscrollbar.id = "zajscroll_hscrollbar_"+divid;
					this.hscrollbar.addClass('zajscroll_scrollbar');
					this.hscrollbar.addClass('zajscroll_hscrollbar');
					this.hscrollbar.fade('hide');
				// append  verticals
					document.body.appendChild(this.vscrollbar);
						this.vscrollbar.appendChild(this.vscroller);
							this.vscroller.appendChild(this.vscrollertop);
							this.vscroller.appendChild(this.vscrollermiddle);
							this.vscroller.appendChild(this.vscrollerbottom);
						this.vscrollbar.appendChild(this.vscrollup);
						this.vscrollbar.appendChild(this.vscrolldown);
				// append  horizontals
					document.body.appendChild(this.hscrollbar);
						this.hscrollbar.appendChild(this.hscroller);
							this.hscroller.appendChild(this.hscrollerleft);
							this.hscroller.appendChild(this.hscrollermiddle);
							this.hscroller.appendChild(this.hscrollerright);
						this.hscrollbar.appendChild(this.hscrollleft);
						this.hscrollbar.appendChild(this.hscrollright);

			// refresh the scrollbars
				this.refreshScrollbars();
				this.refreshScrollers();
			
			var self = this;

			// make scroller draggable
				var myDrag = new Drag.Move("zajscroll_vscroller_"+divid, {
				    snap: 0,
				    container: this.vscrollbar,
				    onSnap: function(el){
				        el.addClass('zajscroll_scroller_dragging');
				    },
				    onComplete: function(el){
				        el.removeClass('zajscroll_scroller_dragging');
				    },
				    onDrag: function(el){
				    	var pos = el.getPosition("zajscroll_vscrollbar_"+divid);
				    	var max = self.vscrollbar.clientHeight;
				    	var currentpercent = pos.y / max;
				    	self.moveByPercent(0, currentpercent);
				    }
				});
			
			// add events			
				this.vscrollup.addEvent('mousedown', (function(){ var speed = self.options.scroll_speed; self.pid = (function(){ self.up(speed);}).periodical(20, self); }));
				this.vscrollup.addEvent('mouseup', function(){ $clear(self.pid); });
				this.vscrolldown.addEvent('mousedown', (function(){ var speed = self.options.scroll_speed; self.pid = (function(){ self.down(speed);}).periodical(20, self); }));
				this.vscrolldown.addEvent('mouseup', function(){ $clear(self.pid); });
				this.hscrollleft.addEvent('mousedown', (function(){ var speed = self.options.scroll_speed; self.pid = (function(){ self.left(speed);}).periodical(20, self); }));
				this.hscrollleft.addEvent('mouseup', function(){ $clear(self.pid); });
				this.hscrollright.addEvent('mousedown', (function(){ var speed = self.options.scroll_speed; self.pid = (function(){ self.right(speed);}).periodical(20, self); }));
				this.hscrollright.addEvent('mouseup', function(){ $clear(self.pid); });
				$(this.divid).addEvent('mousewheel', function(ev){
					if(ev.wheel > 0){ var speed = ev.wheel * self.options.scroll_speed; self.up(speed); }
					else{ var speed = -ev.wheel * self.options.scroll_speed; self.down(speed); }
				});

			// create event handlers
				
		},

	//////////////////////////////////////////////////////////////////////////////
	// reload the sizes and scroll locations
		getSizes: function(){
			this.containersize = $(this.divid).getSize();
			this.containerposition = $(this.divid).getPosition();
			this.contentsize = $(this.divid).getScrollSize();
			this.contentlocation = $(this.divid).getScroll();
			return true;
		},
		
	//////////////////////////////////////////////////////////////////////////////
	// scroll helper functions
		down: function(pixels){
			this.moveContentBy(0, pixels);
		},
		up: function(pixels){
			this.moveContentBy(0, -1*(pixels));
		},
		right: function(pixels){
			this.moveContentBy(pixels, 0);
		},
		left: function(pixels){
			this.moveContentBy(-1*(pixels), 0);
		},

	//////////////////////////////////////////////////////////////////////////////
	// content scroll functions	
		moveContentBy: function(x, y){
			$(this.divid).scrollTo(this.contentlocation.x+x, this.contentlocation.y+y);
			this.refreshScrollbars();
			this.refreshScrollers();
		},
		moveContentTo: function(x, y){
			$(this.divid).scrollTo(x, y);
			this.refreshScrollbars();
			this.refreshScrollers();
		},
		moveByPercent: function(xpercent,ypercent){
			// convert to 0.2 format
				if(xpercent > 1) xpercent = xpercent / 100;
				if(ypercent > 1) ypercent = ypercent / 100;
			// now move by percent
				this.moveContentTo(0, ypercent*this.contentsize.y);
		},
		
	//////////////////////////////////////////////////////////////////////////////
	// scrollbar
		refreshScrollbars: function(){
			// get the sizes
				this.getSizes();
			// which scroller to hide
				if(this.contentsize.y <= this.containersize.y) this.options.vertical = false;
				if(this.contentsize.x <= this.containersize.x) this.options.horizontal = false;
			
			// now show the vertical
				if(this.options.vertical){
					$(this.divid).setStyle('width', (this.originalsize.x-(this.vscroller.clientWidth+$(this.divid).getStyle('padding-right').toInt())));
					// position scrollbar
						this.vscrollbar.setStyle('position', 'absolute');
						this.vscrollbar.setStyle('top', this.originalposition.y);
						this.vscrollbar.setStyle('left', this.originalposition.x+this.originalsize.x-this.vscroller.clientWidth);
						this.vscrollbar.setStyle('height',this.containersize.y);					
					// show scrollbar
						this.vscrollbar.fade('show');
					// show scroller
						
				}
			// now show the horizontal
				if(this.options.horizontal){

				}
			
			return true;
		},

	//////////////////////////////////////////////////////////////////////////////
	// scroller
		refreshScrollers: function(){
			// refresh content size
			// set size, location, percent, etc.
				this.getSizes();
				this.contentlocationpercent = this.contentlocation.y / (this.contentsize.y - this.containersize.y);
			// resize scroller based on content	
				var contentpercent = this.containersize.y / this.contentsize.y;
				this.vscroller.setStyle('height', this.vscrollbar.getStyle('height').toInt()*contentpercent);							
			// set height
				this.vscroller.setStyle('top', ((this.vscrollbar.getStyle('height').toInt()-this.vscroller.getStyle('height').toInt()) * this.contentlocationpercent));
				// * set the scrollermiddle height to fill the scroller 
				this.vscrollermiddle.setStyle('height',this.vscroller.getStyle('height').toInt()-this.vscrollertop.getStyle('height').toInt());
		}

});
