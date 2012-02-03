//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – drag class
//////////////////////////////////////////////////////////////////////////////
// class: zajPhoto
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 1.0
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// this should work Firefox 1.5+, IE 5.5+, Opera 7+. send bugs to above email.
// check for new versions, go to hontalan.com.
//////////////////////////////////////////////////////////////////////////////
// version history.
// - 1.0 - initial release
//////////////////////////////////////////////////////////////////////////////
/* usage: 
1.	zajphoto.addGetRequest('thephpfile.php?variable=value', 'resultdiv');
	- this executes thephpfile.php?variable=value and places the result in resultdiv

2.	zajphoto.addGetRequest('thephpfile.php?variable=value', 'resultdiv', processFunction);
	- this executes thephpfile.php?variable=value, then calls processFunction(resultOfPhp, resultdiv)...

3.  zajphoto.addPostRequest('thephpfile.php', 'formid', 'resultdiv');


4.  zajphoto.addPostRequest('thephpfile.php', 'formid', 'resultdiv', processFunction);


*/
//////////////////////////////////////////////////////////////////////////////
// zajPhoto class
var zajphoto = new zajPhoto();

function zajPhoto(){
	// define the static vars
		var myscript = 'zajmag.php';
	// define variables
		var currentAlbum = "";
		var currentPhoto = 0;
		var numOfPhotos = 0;
		var isLoading = false;	
	// arrays of photos
		var iMyPhotoIds = new Array();
		var iMyPhotoUrls = new Array();
		var iMyThumbUrls = new Array();
		var iMyPhotoWidths = new Array();
		var iMyPhotoHeights = new Array();
		
	// define member functions
		this.openAlbum = zajOpenAlbum;
		this.closeAlbum = zajCloseAlbum;
		this.nextPhoto = zajNextPhoto;
		this.prevPhoto = zajPrevPhoto;
		this.showPhoto = zajShowPhoto;

 //////////////////////////////////////////////////////////////////////////////
 // Public member functions
	///////////////////////////////////
	// Opens up an album - if one
	// 		already open, then it
	//		overwrites current
	function zajOpenAlbum(id){
		// load photos
			zajajax.addGetRequest(myscript+'?photo=photoarray&id='+id,'',zajOpenAlbumProcess);			
	}
	function zajOpenAlbumProcess(result){
		// unserialize the data
			photodata = unserialize(result);
			iMyPhotoIds = photodata['photoids'];
			iMyPhotoUrls = photodata['photourls'];
			iMyPhotoWidths = photodata['photowidths'];
			iMyPhotoHeights = photodata['photoheights'];
			iMyThumbUrls = photodata['thumburls'];
			numOfPhotos = iMyPhotoIds.length;
		// check for error
			if(numOfPhotos == 0) zajpopup.addWarning("nincs fotó.");
			
			
		// now create the html page
			zajpopup.addPopup("<a href='javascript:zajphoto.prevPhoto();'><img src='img/white/buttons/arrow-left.gif'></a> <a href='javascript:zajphoto.nextPhoto();'><img src='img/white/buttons/arrow-right.gif'></a>", photodata['photohtml'], 800, 600);
		// now show the first photo
			zajShowPhoto(currentPhoto);
		// display thumbnails
			for(var i = 0; i < iMyPhotoIds.length; i++) zajAddThumbnail(i);
	}
	
	///////////////////////////////////
	// Get next photo
	function zajNextPhoto(){
		if(currentPhoto < (numOfPhotos-1)) currentPhoto++;
		zajShowPhoto(currentPhoto);
	}
	///////////////////////////////////
	// Get previous photo
	function zajPrevPhoto(){
		if(currentPhoto > 0) currentPhoto--;
		zajShowPhoto(currentPhoto);
	}
	
	///////////////////////////////////
	// Shows a specific photo
	function zajShowPhoto(number){
		// now set photo url to first photo
			document.getElementById('photoAlbumPhoto').style.background = 'url('+iMyPhotoUrls[iMyPhotoIds[number]]+')';
			document.getElementById('photoAlbumPhoto').style.width = iMyPhotoWidths[iMyPhotoIds[number]]+'px';
			document.getElementById('photoAlbumPhoto').style.height = iMyPhotoHeights[iMyPhotoIds[number]]+'px';
	}

	///////////////////////////////////
	// Add thumbnail
	function zajAddThumbnail(number){
		// create and set properties
			var thumbdiv = document.createElement('div');
			thumbdiv.className = 'photoAlbumThumb';
			thumbdiv.id = 'thumbdiv'+number;
			thumbdiv.style.background = 'url('+iMyThumbUrls[iMyPhotoIds[number]]+')';
		// add to thumbnail container
			document.getElementById('photoAlbumThumbs').appendChild(thumbdiv);
		// add a click event for this object	
			$(thumbdiv).addEvent('click',function(){zajShowPhoto(number);});
	}
	
	///////////////////////////////////
	// Closes the current album
	function zajCloseAlbum(){
		
	}

 //////////////////////////////////////////////////////////////////////////////
 // Private member functions
	function zajLoadAlbum(id){
	
	}
	function zajLoadPhoto(id){
	
	}
}
// end zajPhoto class
//////////////////////////////////////////////////////////////////////////////
