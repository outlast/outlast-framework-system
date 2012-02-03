//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – ajax class
//////////////////////////////////////////////////////////////////////////////
// class: zajTable, zajColumn, zajRow
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 1.2
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 2+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// - 1.0 - initial release
// - 1.1 - much improved filtering
// - 1.2 - zaj.js compatibility
//////////////////////////////////////////////////////////////////////////////
/* usage: 
1.	zajTable.addRow
	
*/
//////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////
// zajTable
function zajTable(divid,useroptions){
	// define private properties
		// global stuff
			var handlingScript = "csfadmin.php";						// the script to handle refresh and listing
			var handlingDefault = "csfadmin.php?order=most";			// default mode
			var handlingScriptFullEdit = "csfadmin.php?order=edit&id="; // edit (full popup version)
			var handlingExport = "order=export";						// exporting options
			var divid;
			var options = {
				zebra: true,
				onSave: false,
				onCancel: false,
				onEdit: false,
				onExport: false
			};
			var id;
		// elements
			var myelement;							// the table element
			var headelement;						// the heading element
			var filterelement;						// the filtering element
			var bodyelement;						// the data content element
		// column stuff
			var iMyColumns = new Array();			// assoc array with column objects - iMyColumns[columnid]
			var iMyColumnIDs = new Array();			// array with column IDs - iMyColumnIDs[i]
			var numofcolumns = 0;					// number of columns
			var isSuperColumn = new Array();		// supercolumns (true if key is id of supercolumn)
		// row stuff
			var iMyRows = new Array();				// assoc array with row objects - iMyRows[rowid]
			var iMyRowIDs = new Array();			// array with row IDs - iMyRowIDs[i]
			var numofrows = 0;						// number of rows
		// selected stuff
			var iMyColumnsSelected = new Array();	// assoc array with select column objects - iMyColumnsSelected[columnid]
			var numofselectedcolumns = 0;			// number of columns selected
			var iMyRowsSelected = new Array();		// assoc array with select row objects - iMyRowsSelected[rowid]
			var numofselectedrows = 0;				// number of rows selected	
		// hidden stuff
			var iMyColumnsHidden = new Array();		// assoc array with hidden column objects - iMyColumnsHidden[columnid]
			var numofhiddencolumns = 0;				// number of columns hidden
			var iMyRowsHidden = new Array();		// assoc array with hidden row objects - iMyRowsHidden[rowid]
			var numofhiddenrows = 0;				// number of rows hidden	
		
	// define public properties
		var self = this;
		this.numofcolumns = numofcolumns;
		this.numofrows = numofrows;
		this.iMyColumns = iMyColumns;
		this.iMyColumnIDs = iMyColumnIDs;
	// define public member functions
		this.addColumn = addColumn;
		this.addRow = addRow;
		this.selectRow = selectRow;
		this.deselectRow = deselectRow;
		this.selectAll = selectAll;
		this.deselectAll = deselectAll;
		this.saveRow = saveRow;
		this.cancelAll = cancelAll;
		this.validateRow = validateRow;
		this.onSelect = onSelect;
		this.showColumn = showColumn;
		this.hideColumn = hideColumn;

		this.getSelectedRows = getSelectedRows;
		this.getVisibleRows = getVisibleRows;
	// call constructor
		init(divid,useroptions);

	//////////////////////////////////////////////////////////////////////////
	// CONSTUCTOR and ADD COLUMNS & ROWS
	//////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////
	// Constructor
		function init(divid,useroptions){
			options = zajMergeOptions(options,useroptions);
			id = 'zajtable_'+divid;
			// create control panel
				savedFilterSelect = selectMe(id+'_panel_filterselect',['-aktív megrendelések'],'',function(){alert('még nincs!');});
			// create basic table frame
				document.getElementById(divid).innerHTML = "<div class='zajtable_panel' id='"+id+"_panel'>filter: <select></select>&nbsp;<div class='zajtable_panel_edit'>&nbsp;</div><div class='zajtable_panel_exportbox'>export / import: &nbsp;<div class='zajtable_panel_exportcsv' id='"+id+"_exportcsv'>csv</div> <div class='zajtable_panel_exportxls' id='"+id+"_exportxls'>xls</div> <div class='zajtable_panel_exportdbf' id='"+id+"_exportdbf'>dbf</div></div><a href='javascript:window.location=window.location;'>refresh</a></div><table class='zajtable' id='"+id+"' cellpadding='0' cellspacing='0'><thead><tr class='zajtable_header' id='"+id+"_header'></tr><tr class='zajtable_filter' id='"+id+"_filter'></tr><tr><td class='zajtable_infobox' id='"+id+"_infobox'><div class='zajtable_infobox_text' id='"+id+"_infobox_text'>13 megrendelés.</div></td></tr></thead><tbody id='"+id+"_body' class='zajtable_tbody'></tbody></table>";
				myelement = document.getElementById(id);
				headelement = document.getElementById(id+"_header");
				filterelement = document.getElementById(id+"_filter");
				bodyelement = document.getElementById(id+"_body");
			// set size of body element (to fix the header)
				var mypos = bodyelement.getPosition();
				var pagesize = document.getSize();
				bodyelement.setStyle("height",(pagesize.y-mypos.y-100));
			// add events
				window.addEvent('scroll', onScroll);
				window.addEvent('domready', onLoad);
				window.addEvent('unload', onUnloadTable);
				$(id+'_exportcsv').addEvent('click',function(){ exportGo('csv','visible'); });
				$(id+'_exportxls').addEvent('click',function(){ exportGo('xls','visible'); });
				$(id+'_exportdbf').addEvent('click',function(){ exportGo('dbf','visible'); });
			// add other keyboard events
				zajAddKeyboardEvent(13, deselectAll, true);
				zajAddKeyboardEvent(27, cancelAll, true);
			// center elements
				onScroll();
				onTextChange();
		}

	//////////////////////////////////////////////////////////////////////////
	// Add column
		function addColumn(columnid,columnoptions,filteroptions){			
			// create the column
				iMyColumnIDs[numofcolumns] = columnid;
				iMyColumns[columnid] = new zajColumn(id,columnid,columnoptions,filteroptions);
				numofcolumns++;
			// add to supercolumns if needed
				if($defined(filteroptions) && filteroptions.supercolumn) isSuperColumn[columnid] = true;
				else isSuperColumn[columnid] = false;
			// display
				headelement.appendChild(iMyColumns[columnid].columnelement);
				filterelement.appendChild(iMyColumns[columnid].filterelement);
			// now add events
				if(iMyColumns[columnid].filter.supercolumn){
					$(id+'_filterinput_'+columnid).addEvent('change',onSuperFilter);
				}
				else if(iMyColumns[columnid].filter.enabled){
					if(iMyColumns[columnid].filterinput.tagName == "INPUT") $(id+'_filterinput_'+columnid).addEvent('keyup',onFilter);
					else $(id+'_filterinput_'+columnid).addEvent('change',onFilter);
				}
			// change colspan of infobox
				$(id+'_infobox').colSpan = numofcolumns;
			// now add to selected columns
				iMyColumnsSelected[columnid] = true;
				numofselectedcolumns++;
			// now add to hidden columns
				iMyColumnsHidden[columnid] = false;
			// should this column be hidden?
				if(!columnoptions.visible) hideColumn(columnid);
			// call events
				onScroll();
				onTextChange();
		}

	//////////////////////////////////////////////////////////////////////////
	// Add row
		function addRow(rowid,rowdata,rowoptions){
			// create the row
				iMyRowIDs[numofrows] = rowid;
				iMyRows[rowid] = new zajRow(this,rowid,rowdata,rowoptions);
				iMyRowsSelected[rowid] = false;
				numofrows++;
			// display
				bodyelement.appendChild(iMyRows[rowid].rowelement);
			// now add events
				$('zajtable_row_'+rowid).addEvent('click',onSelect);
				$('zajtable_row_'+rowid).addEvent('dblclick',onEdit);
				for(var i=0; i<iMyColumnIDs.length; i++){
					if($defined(iMyColumns[iMyColumnIDs[i]].options.onedit)){
						$('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]).addEvent('change',iMyColumns[iMyColumnIDs[i]].options.onedit);
					}
				}
			// now add to hidden rows
				iMyRowsHidden[rowid] = false;
			// call events
				onScroll();
				onTextChange();
		}

	//////////////////////////////////////////////////////////////////////////
	// EVENTS
	//////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////
	// This is called after table has finished loading all data
		function onLoad(ev){
			makeSortable();
			// load filters and sorts
				var s = zajGetQueryString();
				var j = JSON.decode(s['json']);
			// set the fields
				for(var i = 0; i < iMyColumnIDs.length; i++){
					if($defined(iMyColumns[iMyColumnIDs[i]].filterinput)){
						if(iMyColumns[iMyColumnIDs[i]].filter.supercolumn) iMyColumns[iMyColumnIDs[i]].filterinput.value = s[iMyColumnIDs[i]];
						//else 
					}
				}
			// set the centering for control box
				onScroll();
		}
		function onUnloadTable(ev){
			if(numofselectedrows > 0){
				cancelAll();
				alert('figyelem! nem mentetted el a kiválasztott megrendeléseket. az ezeken végrehajtott változtatások (ha vannak) elvesznek.');
			}
		}
		function onFilter(ev){
			// get column id based on target
				iddata = ev.target.id.split("_");
				var cid = iddata.getLast();
			// if delete/backspace, then reapply all filters
				if(ev.key == 'backspace' || ev.key == 'delete') applyShowFilters(cid);
				else if(ev.target.tagName == "SELECT") applyHideFilters(cid);
				else applyHideFilter(cid);
		}
		function onSuperFilter(ev){
			// generate query string for superfilters
				var json_data;
				var qs = "";
				for(var i = 0; i < iMyColumnIDs.length; i++){
					if($defined(iMyColumns[iMyColumnIDs[i]].filterinput)){
						//if(iMyColumns[iMyColumnIDs[i]].filter.supercolumn) qs = '&'+iMyColumnIDs[i]+'='+iMyColumns[iMyColumnIDs[i]].filterinput.value+qs;
						//else json_data[iMyColumnIDs[i]] = iMyColumns[iMyColumnIDs[i]].filterinput.value;
						qs = '&'+iMyColumnIDs[i]+'='+iMyColumns[iMyColumnIDs[i]].filterinput.value+qs;
					}
				}
			// now creaate json data
				var jsqs = JSON.encode(json_data);
			// now reload handling script
				window.location = handlingDefault+'&json='+jsqs+qs;
		}
		function onSelect(ev){
			// remove text selection
				zajClearTextSelection();
			// get id of current
				var iddata = ev.target.id.split("_");
				var rowid = iddata[3];
			// check various situations
				// this row is already selected & shift or ctrl used, then deselect only this row
					if(iMyRowsSelected[rowid] && (ev.shift || ev.control)) deselectRow(rowid);
				// this row is not selected & modifier key is used
					else if(!iMyRowsSelected[rowid] && (ev.shift || ev.control)) selectRow(rowid);
				// if no modifier keys are used and another is selected
					else if(!iMyRowsSelected[rowid] && !(ev.shift || ev.control)){
						deselectAll();
						selectRow(rowid);
					}
				// now highlight if exists
				if($defined($(ev.target.id+'_input'))) $(ev.target.id+'_input').focus();
		}
		function onEdit(ev){
			// remove text selection
				zajClearTextSelection();
			// get id of current
				var iddata = ev.target.id.split("_");
				var rowid = iddata[3];			
			// open up editing window
				zajpopup.addPopupUrl('edit',handlingScriptFullEdit+rowid,800,500);
			// deselect all, without saving
				cancelAll();			
		}
		function onInputEdit(ev){
			// if only one selected, then return false
				if(numofselectedrows <= 1) return false;
			// get id of current
				var iddata = ev.target.id.split("_");
				var rowid = iddata[3];
				var columnid = iddata[4];
			// go through all other selected rows and change value to same
				var srows = getSelectedRows();
				for(var i = 0; i < srows.length; i++){
					if(srows[i] != rowid){
						var aevent = new Array();
						aevent.target = $('zajtable_row_column_'+srows[i]+'_'+columnid+'_input');

						if($defined($('zajtable_row_column_'+srows[i]+'_'+columnid+'_input').type) && $('zajtable_row_column_'+srows[i]+'_'+columnid+'_input').type == "checkbox") $('zajtable_row_column_'+srows[i]+'_'+columnid+'_input').checked = ev.target.checked;
						else $('zajtable_row_column_'+srows[i]+'_'+columnid+'_input').value = ev.target.value;
						
						if(typeof iMyColumns[columnid].options.onedit == "function") iMyColumns[columnid].options.onedit(aevent);
					}
				}
		}
		function onScroll(ev){
			var pagesize = window.getSize();
			var currentscroll = window.getScroll();
			$(id+'_panel').style.position = 'relative';
			$(id+'_panel').style.left = ((pagesize.x / 2) - ($(id+'_panel').getSize().x / 2) + currentscroll.x) + 'px';
			$(id+'_infobox_text').style.position = 'relative';
			$(id+'_infobox_text').style.left = ((pagesize.x / 2) - ($(id+'_infobox_text').getSize().x / 2) + currentscroll.x) + 'px';
		}
		function onTextChange(){
			var numofvisiblerows = numofrows - numofhiddenrows;
			$(id+'_infobox_text').innerHTML = numofvisiblerows+'/'+numofrows+' megrendelés. ';
			if(numofselectedrows > 0) $(id+'_infobox_text').innerHTML += numofselectedrows+' kiválasztva.';
			onScroll();
		}

	//////////////////////////////////////////////////////////////////////////
	// EXPORTING DATA
	//////////////////////////////////////////////////////////////////////////
		function exportGo(format,modeORid){
			if(numofselectedrows > 0){
				zajpopup.addWarning('hiba: vannak kijelölt megrendelések. ilyenkor nem lehet exportálni.');
				return false;
			}
			
			var myData = new Array();
			var myHeader = exportHeader();
			// first decide what data to assemble
				if(modeORid == "selected") myData = exportSelectedRows();
				else if(modeORid == "visible") myData = exportVisibleRows();
				else myData = exportRow(modeORid);
			// now convert to json
				var jsonheader = JSON.encode(myHeader);
				var jsondata = JSON.encode(myData);
				//var myRequest = new Request({method: 'post', url: handlingScript, isSuccess: exportGoProcess });
				//myRequest.send(handlingExport+'&format='+format+'&jsonheader='+jsonheader+'&jsondata='+jsondata);
				zajajax.addPostRequest(handlingScript+'?'+handlingExport+'&format='+format+'&jsonheader='+jsonheader+'&jsondata='+jsondata,'',exportGoProcess);
		}
		function exportGoProcess(result){
			var rdata = result.split("*");
			if(rdata[0] == "ok") window.location = rdata[1];
			else zajpopup.addWarning(rdata[1]);
		}
		function exportHeader(){
			var headerData = new Array();
			var j = 0;
			// go through all visible columns and create array
				for(var i = 0; i < iMyColumnIDs.length; i++){
					// is column selected
					if(iMyColumnsSelected[iMyColumnIDs[i]] == true){
						// export the exportlabel if exists
						if(iMyColumns[iMyColumnIDs[i]].options.exportlabel != 'default') headerData[j++] = iMyColumns[iMyColumnIDs[i]].options.exportlabel;
						else headerData[j++] = $(id+'_column_'+iMyColumnIDs[i]).innerHTML;
					}
				}
			return headerData;
		}
		function exportSelectedRows(){
			var myRows = new Array();
			var j = 0;
			// go through all rows and create an array
				for(var i = 0; i < iMyRowIDs.length; i++){
					// is row visible
					if(iMyRowsSelected[iMyRowIDs[i]] == true) myRows[j++] = exportRow(iMyRowIDs[i]);
				}
			return myRows;		
		}
		function exportVisibleRows(){
			var myRows = new Array();
			var j = 0;
			// go through all rows and create an array
				for(var i = 0; i < iMyRowIDs.length; i++){
					// is row visible
					if($('zajtable_row_'+iMyRowIDs[i]).style.display != 'none') myRows[j++] = exportRow(iMyRowIDs[i]);
				}
			return myRows;
		}
		function exportRow(rowid){
			var rowData = new Array();
			var j = 0;
			// go through all visible columns and create array
				for(var i = 0; i < iMyColumnIDs.length; i++){
					// is column selected
					if(iMyColumnsSelected[iMyColumnIDs[i]] == true){
						rowData[j++] = encodeURIComponent($('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]).innerHTML);
					}
				}
			return rowData;
		}

	//////////////////////////////////////////////////////////////////////////
	// LOADING / SAVING DATA
	//////////////////////////////////////////////////////////////////////////
		function editRow(rowid){
			// change class to selected
				$('zajtable_row_'+rowid).addClass('zajtable_row_selected');
			// remove the click event
				$('zajtable_row_'+rowid).removeEvent('click',onSelect);
			// now create all the editable parts
				for(var i = 0; i < iMyColumnIDs.length; i++){
					var res = iMyColumns[iMyColumnIDs[i]].getEditInput(rowid);
					if(res !== false){
						$('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]).innerHTML = "";
						$('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]).appendChild(res);
						if($('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]+'_input').tagName == "SELECT" || $('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]+'_input').type != "text") $('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]+'_input').addEvent('change',function(ev){onInputEdit(ev);});
						else $('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]+'_input').addEvent('keyup',function(ev){onInputEdit(ev);});
					}
				}
		}
		function saveRow(rowid){
			// Now send an ajax request to 
				var url = handlingScript+'?'+iMyRows[rowid].rowelement.toQueryString()+'&order=quicksave&rowid='+rowid;
				zajajax.addPostRequest(url, '', saveRowProcess);
			// Reset row
				resetRow(rowid);
		}
		function saveRowProcess(result){
			var resultdata = JSON.decode(result);
			var rowid = resultdata['id'];

			if(resultdata['zajtablesaveerror']){
				zajpopup.addWarning(resultdata['zajtablesaveerror']);
				undoResetRow(rowid);
			}
			else{
				// remove these selects from dom
					var allinputs = iMyRows[rowid].rowelement.getElements("input");
					var allselects = iMyRows[rowid].rowelement.getElements("select");
					for(var mykey = 0; mykey < allinputs.length; mykey++) allinputs[mykey].destroy();
					for(var mykey = 0; mykey < allselects.length; mykey++) allselects[mykey].destroy();
				// reset the data
					for(var i = 0; i < iMyColumnIDs.length; i++){
						document.getElementById('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]).innerHTML = resultdata[iMyColumnIDs[i]];
						iMyRows[rowid].storeRowValues($('zajtable_row_column_'+rowid+'_'+iMyColumnIDs[i]),iMyColumnIDs[i],resultdata[iMyColumnIDs[i]]);
					}
				// fade back
				iMyRows[rowid].rowelement.fade(1);
			}
		}
		function validateRow(rowid, validateRowProcess){
			// Now send an ajax request to 
				var url = handlingScript+'?'+iMyRows[rowid].rowelement.toQueryString()+'&order=quickvalidate&rowid='+rowid;
				zajajax.addPostRequest(url, '', validateRowProcess);
		}
		function reloadRow(rowid){
			// Reset row
				resetRow(rowid);
			// Now send an ajax request to 
				var url = handlingScript+'?order=quickload&rowid='+rowid;
				zajajax.addGetRequest(url, '', saveRowProcess);
		}
		function resetRow(rowid){
			// change class back to unselected
				iMyRows[rowid].rowelement.removeClass('zajtable_row_selected');
			// add the click event
				$('zajtable_row_'+rowid).addEvent('click',onSelect);
			// now disable clicks
				var allinputs = iMyRows[rowid].rowelement.getElements("input");
				var allselects = iMyRows[rowid].rowelement.getElements("select");
				for(var mykey = 0; mykey < allinputs.length; mykey++) allinputs[mykey].disabled = true;
				for(var mykey = 0; mykey < allselects.length; mykey++) allselects[mykey].disabled = true;
			// set to fade
				iMyRows[rowid].rowelement.fade(0.85);
		}
		function undoResetRow(rowid){
			// change class back to selected
				$('zajtable_row_'+rowid).addClass('zajtable_row_selected');
			// remove the click event
				$('zajtable_row_'+rowid).removeEvent('click',onSelect);
			// now enable clicks
				var allinputs = $('zajtable_row_'+rowid).getElements("input");
				var allselects = $('zajtable_row_'+rowid).getElements("select");
				for(var mykey = 0; mykey < allinputs.length; mykey++) allinputs[mykey].disabled = false;
				for(var mykey = 0; mykey < allselects.length; mykey++) allselects[mykey].disabled = false;
			// add back to selected rows
				iMyRowsSelected[rowid] = true;
				numofselectedrows++;
			// set to fade
				iMyRows[rowid].rowelement.fade(1);
		}


	//////////////////////////////////////////////////////////////////////////
	// SORTING
	//////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////
	// Make sortable
		function makeSortable(){
			// create a new SortingTable object for this table
			new SortingTable( id, {
				zebra: false,
				paginator: false,
				dont_sort_class: 'zajtable_nosort',
				forward_sort_class: 'zajtable_header_forward_sort',
				reverse_sort_class: 'zajtable_header_reverse_sort'
			});
		 }

	//////////////////////////////////////////////////////////////////////////
	// FILTERING
	//////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////
	// Show row(s)
		function showRow(rowid){
			if(iMyRowsHidden[rowid]){
				iMyRows[rowid].rowelement.style.display = '';
				iMyRowsHidden[rowid] = false;
				numofhiddenrows--;
			}
		}
		function showAllRows(){
			for(var i = 0; i < iMyRowIDs.length; i++) showRow(iMyRowIDs[i]);
		}
	//////////////////////////////////////////////////////////////////////////
	// Hide row(s)
		function hideRow(rowid){
			if(!iMyRowsHidden[rowid]){
				iMyRows[rowid].rowelement.style.display = 'none';
				iMyRowsHidden[rowid] = true;
				numofhiddenrows++;
			}
		}
		function hideAllRows(){
			for(var i = 0; i < iMyRowIDs.length; i++) hideRow(iMyRowIDs[i]);
		}
	//////////////////////////////////////////////////////////////////////////
	// Apply hiding filters
		function applyHideFilters(actioncolumnid){
			// show for the one selected column
				//applyShowFilter(actioncolumnid);
				showAllRows();
			// now loop through all columns
				for(var i = 0; i < iMyColumnIDs.length; i++) applyHideFilter(iMyColumnIDs[i]);		
		}
		function applyShowFilters(actioncolumnid){
			// show for the one selected column
				if(iMyColumns[actioncolumnid].getFilterValue() == "") showAllRows();
				else applyShowFilter(actioncolumnid);
			// now loop through all columns, and hide for all others
				for(var i = 0; i < iMyColumnIDs.length; i++){
					if(actioncolumnid != iMyColumnIDs[i]) applyHideFilter(iMyColumnIDs[i]);
				}
		}
	//////////////////////////////////////////////////////////////////////////
	// Apply filters
		function applyHideFilter(columnid){
			// is there a filter?
				if(!iMyColumns[columnid].filter.enabled) return false;
			// if it is a supercolumn, then no need to filter it
				if(iMyColumns[columnid].filter.supercolumn) return false;
			// get filter values
				var value = iMyColumns[columnid].getFilterValue();
				var fv = value.substr(0,1);
			// if no filter, then return false
				if(value == "") return false;
			// now loop through all the rows
				for(var i = 0; i < iMyRowIDs.length; i++){
					// if not hidden, check if needs to be hidden
					if(!iMyRowsHidden[iMyRowIDs[i]]){
						if(!checkFilterValue(value, fv, iMyRows[iMyRowIDs[i]].rowcolumnelements[columnid].innerHTML)) hideRow(iMyRowIDs[i]);
					}
				}
			// now change text
				onTextChange();
			return true;
		}
		function applyShowFilter(columnid){
			// is there a filter?
				if(!iMyColumns[columnid].filter.enabled) return false;
			// loop though the rows and compare value
				var value = iMyColumns[columnid].getFilterValue();
				var fv = value.substr(0,1);
			// if the filter value is none, show all rows
				if(value == ""){
					showAllRows();
					return true;
				}
			// now loop through all the rows
				for(var i = 0; i < iMyRowIDs.length; i++){
					// if hidden, check if needs to be shown
					if(iMyRowsHidden[iMyRowIDs[i]]){
						if(checkFilterValue(value, fv, iMyRows[iMyRowIDs[i]].rowcolumnelements[columnid].innerHTML)) showRow(iMyRowIDs[i]);
					}
				}
			// now change text
				onTextChange();
			return true;
		}


		// Return false if row needs to be hidden, true if needs to be shown
		function checkFilterValue(filtervalue, filtervaluestartletter, rowvalue){
			if(filtervalue == "") return true;
			else if(filtervaluestartletter != '-' && filtervaluestartletter != '*' && filtervaluestartletter != '!' && !rowvalue.test(filtervalue, "i") && rowvalue.innerHTML != filtervalue) return false;
			else if(filtervaluestartletter == "*" && rowvalue == "") return false;
			else if(filtervaluestartletter == "!" && rowvalue != "") return false;
			return true;
		}

	//////////////////////////////////////////////////////////////////////////
	// Hide columns
	//////////////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////////////////
	// Hide column(s)
		function hideColumn(columnid){
			// hide the header and filter
				$('zajtable_mydivid_column_'+columnid).addClass('zajtable_hidden');
				$('zajtable_mydivid_filter_'+columnid).addClass('zajtable_hidden');
			// hide all the row columns header and filter
				for(var i = 0; i < iMyRowIDs.length; i++){
					$('zajtable_row_column_'+iMyRowIDs[i]+'_'+columnid).addClass('zajtable_hidden');
				}
			// add to hidden array
				iMyColumnsHidden[columnid] = true;
				numofhiddencolumns++;
		}
		function hideAllColumns(){
			for(var i = 0; i < iMyColumnIDs.length; i++) hideColumn(iMyColumnIDs[i]);
		}
	//////////////////////////////////////////////////////////////////////////
	// Show column(s)
		function showColumn(columnid){
			// hide the header and filter
				$('zajtable_mydivid_column_'+columnid).removeClass('zajtable_hidden');
				$('zajtable_mydivid_filter_'+columnid).removeClass('zajtable_hidden');
			// hide all the row columns header and filter
				for(var i = 0; i < iMyRowIDs.length; i++){
					$('zajtable_row_column_'+iMyRowIDs[i]+'_'+columnid).removeClass('zajtable_hidden');
				}
			// remove from hidden array
				iMyColumnsHidden[columnid] = false;
				numofhiddencolumns--;
		}
		function showAllColumns(){
			for(var i = 0; i < iMyColumnIDs.length; i++) showColumn(iMyColumnIDs[i]);		
		}


	//////////////////////////////////////////////////////////////////////////
	// SELECTING AND EDITING
	//////////////////////////////////////////////////////////////////////////
		function selectRow(rowid){
			if(!$defined(iMyRows[rowid])) return false;
			if(iMyRowsSelected[rowid]) return false;		
			// add to array of selected rows
				iMyRowsSelected[rowid] = true;
				numofselectedrows++;
			// make editable
				editRow(rowid);
			// now change text
				onTextChange();
		}
		function deselectRow(rowid, dontsave){
			if(!$defined(iMyRows[rowid])) return false;
			if(!iMyRowsSelected[rowid]) return false;
			// remove from array of selected rows
				iMyRowsSelected[rowid] = false;
				numofselectedrows--;
			// and now save (if needed)
				if(dontsave) reloadRow(rowid);
				else saveRow(rowid);
			// now change text
				onTextChange();
		}
		function cancelRow(rowid){
			if(!$defined(iMyRows[rowid])) return false;
			if(!iMyRowsSelected[rowid]) return false;
			// now deselect the row without saving
				deselectRow(rowid, true);
			// callback function (if defined)
				if($defined(options.onCancel) && options.onCancel) options.onCancel(rowid);
		}
		function selectAll(){
			for(var i = 0; i < iMyRowIDs.length; i++) selectRow(iMyRowIDs[i]);	
		}
		function deselectAll(){
			for(var i = 0; i < iMyRowIDs.length; i++) deselectRow(iMyRowIDs[i]);
		}
		function cancelAll(){
			for(var i = 0; i < iMyRowIDs.length; i++) cancelRow(iMyRowIDs[i], true);
		}
		
	//////////////////////////////////////////////////////////////////////////
	// RETURNING SELECTED, VISIBLE, ETC
	//////////////////////////////////////////////////////////////////////////
		function getSelectedRows(){
			var selectedrows = new Array();
			var j = 0;
			for(var i = 0; i < iMyRowIDs.length; i++){
				if(iMyRowsSelected[iMyRowIDs[i]]) selectedrows[j++] = iMyRowIDs[i];
			}
			return selectedrows;
		}
		function getVisibleRows(){
			var visiblerows = new Array();
			var j = 0;
			for(var i = 0; i < iMyRowIDs.length; i++){
				if(!iMyRowsHidden[iMyRowIDs[i]]) visiblerows[j++] = iMyRowIDs[i];
			}
			return visiblerows;
		}

}

//////////////////////////////////////////////////////////////////////////////
// zajColumn
//////////////////////////////////////////////////////////////////////////////
// options.type { text,select,timestamp,checkbox,textarea,file,number,currency,date }
function zajColumn(tableid,columnid,columnoptions,filteroptions){
	// define private properties
		var options = {
			label: 'default',
			exportlabel: 'default',
			type: 'text',
			sortable: true,
			visible: true,
			editable: true,
			onedit: null
		};
		var filter = {
			selectoptions: '',
			supercolumn: false,
			enabled: true
		}
		var parentid;
		var id;
	// elements
		var columnelement = document.createElement('th');
		var filterelement = document.createElement('td');
		var filterinput;
		var editinput;
		
	// define public properties
		var self = this;
		this.columnelement = columnelement;
		this.filterelement = filterelement;
		this.options = options;
		this.filter = filter;
		this.filterinput;
		this.editinput;
		
	// define public member functions
		this.getFilterValue = getFilterValue;
		this.getEditInput = getEditInput;
		
	// call constructor
		init(tableid,columnid,columnoptions,filteroptions);

	//////////////////////////////////////////////////////////////////////////
	// Constructor
		function init(tableid,columnid,columnoptions,filteroptions){
			// init variables
				parentid = tableid;
				options = zajMergeOptions(options,columnoptions);
				filter = zajMergeOptions(filter,filteroptions);
				id = columnid;
			// set the properties of column
				columnelement.id = parentid+'_column_'+columnid;
				columnelement.className = 'zajtable_column_header nowrap';
				if(!options.sortable) columnelement.addClass('zajtable_nosort');
				columnelement.innerHTML = options.label;
			// set the properties of the filter input
				switch(options.type){
					case 'select': 	var sarray = JSON.decode(self.filter.selectoptions);
									filterinput = selectMe('',sarray,'');
									editinput = filterinput.clone();
									break;
					case 'timestamp': var sarray = JSON.decode(self.filter.selectoptions);
									filterinput = selectMe('',sarray,'');
									editinput = filterinput.clone();
									break;
					case 'checkbox':var sarray = { 'all':'*','x':'x','o':'o'};
									filterinput = selectMe('',sarray,'');
									editinput = document.createElement('input');
									editinput.type = 'checkbox';
									editinput.value = 'yes';
									break;
					case 'file':	var sarray = { 'all':'*','x':'x','o':'o'};
									filterinput = selectMe('',sarray,'');
									editinput = document.createElement('input');
									editinput.type = 'file';
									break;
					default:		filterinput = document.createElement('input');
									filterinput.type='text';
									editinput = filterinput.clone();
									break;
				}

			// set the properties of filter
				filterelement.id = parentid+'_filter_'+columnid;
				filterelement.className = 'zajtable_filter nowrap';
				filterinput.addClass('zajtable_filter_'+options.type);
				filterinput.id = parentid+'_filterinput_'+columnid;
				if(filter.enabled) self.filterinput = filterelement.appendChild(filterinput);
		}

	//////////////////////////////////////////////////////////////////////////
	// Get filter input value
		function getFilterValue(){
			if(filterinput.type=='text') value = filterinput.value;
			else{
				myoptions = filterinput.getSelected();
				value = myoptions[0].text;
			}
			return value;
		}

	//////////////////////////////////////////////////////////////////////////
	// Get edit input
		function getEditInput(rowid){
			if(!options.editable) return false;
			
			// clone the editinput object
				neweditinput = editinput.clone();

			// get the current id and value of row column
				var currentid = 'zajtable_row_column_'+rowid+'_'+id;
				var currentvalue = $(currentid).retrieve('editvalue',$(currentid).innerHTML);
			// now set the name & id of the editinput box
				neweditinput.id = 'zajtable_row_column_'+rowid+'_'+id+'_input';
				neweditinput.name = id;
			// set specific editinput options
				if(neweditinput.tagName == "SELECT"){
					// remove -options- (all, etc.)
						myoptions = neweditinput.getChildren();
						for(var i = 0; i<myoptions.length; i++){
							if(myoptions[i].text.substr(0,1) == "-") myoptions[i].destroy();
							if(myoptions[i].text == currentvalue) neweditinput.value = myoptions[i].value;
						}
						neweditinput.addEvent("click",function(){return false;});
						neweditinput.className ='zajtable_filter_select';
				}
				else{
					if(neweditinput.type == "checkbox"){
						if(currentvalue == "x") neweditinput.checked = true;
						else neweditinput.checked = false;
						neweditinput.className ='zajtable_filter_checkbox';
					}
					else{
						neweditinput.value = currentvalue;					
						neweditinput.className ='zajtable_filter_text';
					}
				}
				neweditinput.addEvent("dblclick",function(){return false;});
			return neweditinput;
		}

}

//////////////////////////////////////////////////////////////////////////////
// zajRow
function zajRow(mytable,rowid,rowdata,rowoptions){
	// define private properties
		var options = {
			show: true
		};
		var mydata;
		var parentid;
		var id;
	// elements
		var rowelement = document.createElement('tr');
		var rowcolumnelements = new Array();
	// define public properties
		this.rowelement = rowelement;
		this.rowcolumnelements = rowcolumnelements;

	// define public member functions
		this.storeRowValues = storeRowValues;
		
	// call constructor
		init(mytable,rowid,rowdata,rowoptions);

	//////////////////////////////////////////////////////////////////////////
	// Constructor
		function init(mytable,rowid,rowdata,rowoptions){
			// init variables
				options = zajMergeOptions(options,rowoptions);
				id = rowid;
				parentid = mytable.id;
				var columnids = mytable.iMyColumnIDs;
				rowdata = JSON.decode(rowdata);
			// init row element
				rowelement.id = 'zajtable_row_'+id;
				rowelement.addClass('zajtable_row');

			// load data into row
				for(var i=0; i<columnids.length; i++){
					// create a new row column element
						var newtd = $(document.createElement('td'));
					// set its properties
						newtd.id = 'zajtable_row_column_'+id+'_'+columnids[i];
						newtd.addClass('zajtable_row_column');
						newtd.addClass('zajtable_row_column_'+columnids[i]);
					// is it hidden?
						if(!mytable.iMyColumns[columnids[i]].options.visible) newtd.addClass('zajtable_hidden');
					// now set the row data
						storeRowValues(newtd,columnids[i],rowdata[columnids[i]]);
					// now add to the current row
						rowelement.appendChild(newtd);
					// add to rowcolumnelements array
						rowcolumnelements[columnids[i]] = newtd;
					// add event if needed
						//if(mytable.iMyColumns[columnids[i]].options.onedit != null) rowcolumnelements[columnids[i]].addEvent('change',mytable.iMyColumns[columnids[i]].options.onedit);
				}
		}

	//////////////////////////////////////////////////////////////////////////
	// Store edit and export values
		function storeRowValues(rowtd,columnid,value){
				switch(mytable.iMyColumns[columnid].options.type){
					case 'currency': 
						rowtd.store('editvalue',parseFloat(value));
						rowtd.store('exportvalue',value);
						break;			
					default:
						rowtd.store('editvalue',value);
						rowtd.store('exportvalue',value);
						break;
				}
				rowtd.innerHTML = value;
				return true;
		}
}

