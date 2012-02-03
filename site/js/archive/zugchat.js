//////////////////////////////////////////////////////////////////////////////
// zajlik.hu (c) 1999-2007 – zajlik chat functions
//////////////////////////////////////////////////////////////////////////////
// written by: hontalan /aron budinszky - hontalan@gmail.com/
// version: 1.0
//////////////////////////////////////////////////////////////////////////////
// files needed:
//  - zaj.js
//	- zajajax.js
//	- zajpopup.js
//////////////////////////////////////////////////////////////////////////////
// copyright notice: use of this class is permitted, but requires a notice to
// be sent to webmester@zajlik.hu. (in other words, i just want to know if you
// found it and want to use it...thanks :))
//////////////////////////////////////////////////////////////////////////////
// version history.
// - 1.0 - initial release
//////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////
// zajChat class
var zajchat = new zajChat();

function zajChat(){
	//////////////////////////////////////////////
	// Define variables
		// Array of messages
		parent.currentmessage = 0;
		// Chat's id
	//////////////////////////////////////////////
	// Define member functions
		this.sendmessage = zajSendMessage;
		this.getmessages = zajGetMessages;
		this.login = zajLogin;
		this.logout = zajLogout;
	//////////////////////////////////////////////
	// Functions
	function zajSendMessage(message){
		zajajax.addGetRequest('zajprofile.php?chat=sendmessage&message='+message+'&lastmessage='+parent.currentmessage+'&id='+parent.id, '', zajGetMessagesProcess);
	}
	function zajGetMessages(){
		zajajax.addGetRequest('zajprofile.php?chat=getmessages&lastmessage='+parent.currentmessage+'&id='+parent.id, '', zajGetMessagesProcess);		
	}
	function zajGetMessagesProcess(result){
		var x = result.split("%E%");
		document.getElementById('chat_output').innerHTML = x[1]+document.getElementById('chat_output').innerHTML;
		parent.currentmessage = x[0];
	}
	function zajLogin(id){
		// login to this specific chat
		if(!id) zajajax.addGetRequest('zajprofile.php?chat=newchat', '', zajLoginProcess);		
		else zajajax.addGetRequest('zajprofile.php?chat=adduser&lastmessage='+parent.currentmessage+'&id='+id, '', zajLoginProcess);		
	}
	function zajLoginProcess(result){
		parent.id = result;
		zajGetMessages();
	}
	function zajLogout(){
		zajajax.addGetRequest('zajprofile.php?chat=deleteuser&id='+parent.id, '', zajpopup.deletePopup);
	}
}

// start a new chat
function newZajChat(){
	zajajax.addGetRequest('zajprofile.php?chat=newchat', '', openZajChat);
}
function openZajChat(chatid){
	zajpopup.addPopupUrl('zajlik chat', 'zajprofile.php?chat=most&id='+chatid, 300, 400, false);
	zajchat.login(chatid);
}
function sendZajChatMessage(fieldid){
	zajchat.sendmessage(document.getElementById(fieldid).value);
	document.getElementById(fieldid).value = "";
}