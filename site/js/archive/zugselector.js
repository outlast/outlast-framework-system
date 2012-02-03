//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – selector class
//////////////////////////////////////////////////////////////////////////////
// class: zajSelector
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
// - 2.0 - initial release, mootools compatible
//////////////////////////////////////////////////////////////////////////////
/* usage: 
var zajselector = new zajSelector(['divid'],options);
	- creates a new zajSelector class, adding all the children of divid by default
	- options:
		- selectedclass: name of class used when selected
		- onselect: this is a function called when the item has been clicked onselect(event)

1. zajselector.addElement('divid');
	- adds an element to the class

2. zajselector.getSelected();
	- gets all currently selected ids

3. zajselector.isSelected('divid');
	- returns true if the divid is selected

3. zajselector.deselectAll();
	- deselects all ids
*/
//////////////////////////////////////////////////////////////////////////////
// zajSelector class
function zajSelector(divid,useroptions){
	// options
	var options = {
		selectclass: '',
		onselect: ''
	}
	// element stuff
		var iMyElementIDs = new Array();			// array with element IDs - iMyElementIDs[i]
		var numofelements = 0;						// number of elements
	// selected stuff
		var iMyElementSelected = new Array();		// array with elements set to true if selected iMyElementSelected[elementid]
		var numofselectedelements = 0;				// number of elements selected	
	// public functions
		this.addElement = addElement;
		this.selectElement = selectElement;
		this.deselectElement = deselectElement;
		this.selectAll = selectAll;
		this.deselectAll = deselectAll;
		this.getSelected = getSelected;
		this.isSelected = isSelected;

	//////////////////////////////////////////////////////////////////////////////
	// constructor
		init(divid,useroptions);		
		function init(divid,useroptions){
			// merge options
				options = zajMergeOptions(options,useroptions);
			// add all child elements
				if($chk(divid) && divid != ''){
					var mychildren = $(divid).getChildren();
					for(var ch = 0; ch < mychildren.length; ch++) addElement(mychildren[ch].id);
				}
			return true;
		}

	//////////////////////////////////////////////////////////////////////////////
	// element management
		function addElement(divid){
			// first add to array
				iMyElementIDs[numofelements] = divid;
				numofelements++;
			// now add event
				$(divid).addEvent('click',onElementSelect);
		}

	//////////////////////////////////////////////////////////////////////////////
	// event handling
		function onElementSelect(ev){
			var me;

			// check to see what kind of input
				if($chk(ev.target)){
					me = ev.target;
					if(!ev.shift) deselectAll();
				}
				else me = ev;

			// if this is a child node, then send to parent
			if(me.id == '' || !iMyElementIDs.contains(me.id)){
				 me.getParent().fireEvent('click',this);
				 return true;
			}
			else{
				// add to selected elements
					selectElement(me.id);
				// callback function
					if(typeof options.onselect == 'function') options.onselect(me);
			}
		}
		
	//////////////////////////////////////////////////////////////////////////////
	// selection / deselection
		function selectElement(divid){
			// first add to array
				iMyElementSelected[divid] = true;
				numofselectedelements++;
			// change to selected class
				if(options.selectclass != ''){
					$(divid).addClass(options.selectclass);
					$(divid).fade(0.7);
					(function(){$(divid).fade(1)}).delay(800);
				}
		}
		function deselectElement(divid){
			// first remove from array
				iMyElementSelected[divid] = false;
				numofselectedelements--;
			// remove selected class
				if(options.selectclass != ''){
					$(divid).fade(0.5);
					(function(){$(divid).fade(1); $(divid).removeClass(options.selectclass);}).delay(700);
				}
		
		}
		function selectAll(){
			// loop thru all and select unselected
			for(var i = 0; i < numofelements; i++){
				if(!iMyElementSelected[iMyElementIDs[i]]) selectElement(iMyElementIDs[i]);
			}		
		}
		function deselectAll(){
			// loop thru all and deselect selected
			for(var i = 0; i < numofelements; i++){
				if(iMyElementSelected[iMyElementIDs[i]]) deselectElement(iMyElementIDs[i]);
			}
		}
	//////////////////////////////////////////////////////////////////////////////
	// get selected
		function getSelected(){
			var selected_elements = new Array();
			var j = 0;
			for(var i = 0; i < numofelements; i++){
				if(iMyElementSelected[iMyElementIDs[i]]) selected_elements[j++] = iMyElementIDs[i];
			}
			return selected_elements;
		}
		function isSelected(id){
			if(iMyElementSelected[id]) return true;
			else return false;
		}

}