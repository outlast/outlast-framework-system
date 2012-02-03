////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2008 - basic javascript
////////////////////////////////////////////////////////////////////////////
// zajeditoritem.js
// version 1.0
////////////////////////////////////////////////////////////////////////////
// version history
// 1.0 - initial release
////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////
// Add/remove from favorites
	function zajAddFavorite(objectname,objectid){
		// change div to subtract
			hideThis('addfav_'+objectid)
			showThisBlock('deletefav_'+objectid);
		// change fav text to visible
			showThis('favtext_'+objectid);
		// add one to top fan counter
			$('fancounter_'+objectid).innerHTML = parseInt($('fancounter_'+objectid).innerHTML)+1;
		// now send ajax request
			zajajax.addGetRequest('zajprofile.php?fav=add&objectname='+objectname+'&objectid='+objectid,'',zajFavoriteProcess);
	}
	function zajDeleteFavorite(objectname,objectid){
		// change div to add
			hideThis('deletefav_'+objectid)
			showThisBlock('addfav_'+objectid);
		// change fav text to visible
			hideThis('favtext_'+objectid);
		// subtract one to top fan counter
			$('fancounter_'+objectid).innerHTML = parseInt($('fancounter_'+objectid).innerHTML)-1;
		// now send ajax request
			zajajax.addGetRequest('zajprofile.php?fav=delete&objectname='+objectname+'&objectid='+objectid,'',zajFavoriteProcess);
	}
	function zajFavoriteProcess(result){
		// display if error
		
		// if logout needed
		$('zajeditor_info_count_box_text_fan').innerHTML = result;
	}