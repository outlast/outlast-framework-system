<?php
/**
 * Controller for editing Sample objects.
 * @package Controller
 * @subpackage Sample
 **/

class zajapp_admin_customfieldentry extends zajController{

	/**
	 * The __load() magic method is run each time this particular controller is used to process the request. You should place code here which is general for all
	 *		related requests. For example, an admin.ctl.php file's __load() method will likely contain an authentication process, so that anyone requesting
	 *		any admin pages will need to login first...
	 **/
	public function __load(){
		// This is set any time any request is made to this controller (so every time we call /sample/anything/ or even just /sample/)
		$this->zajlib->load->controller('admin/default.ctl.php');
	}

	/**
	 * The main() method is the default for any controller.
	 **/
	public function main(){
		// Fetch my objects
		$this->zajlib->variable->customfieldentries = CustomFieldEntry::fetch()->paginate(25);
		// Search
		if(!empty($_GET['query'])) $this->zajlib->variable->customfieldentries->search($_GET['query']);
		// Display list template
		if($this->zajlib->request->is_ajax()) return $this->zajlib->template->block("admin/customfieldentry/customfieldentry_list.html", "customfieldentrylist");
		else return $this->zajlib->template->show("admin/customfieldentry/customfieldentry_list.html");
	}

	/**
	 * Add new template
	 **/
	function add(){
		// Create and cache
		$this->zajlib->variable->customfieldentry = CustomFieldEntry::create();
		$this->zajlib->variable->customfieldentry->cache();
		return $this->zajlib->redirect("admin/customfieldentry/edit/?id=".$this->zajlib->variable->customfieldentry->id);
	}

	/**
	 * Edit template
	 **/
	function edit(){
		// Resume the object
		$this->zajlib->variable->customfieldentry = CustomFieldEntry::fetch($_GET['id']);
		return $this->zajlib->template->show("admin/customfieldentry/customfieldentry_edit.html");
	}

	/**
	 * Save template
	 */
	function save(){
		// Resume the object
		/** @var CustomFieldEntry $obj */
		$obj = CustomFieldEntry::fetch($_POST['id']);
		// Validate
		if(empty($_POST['customfield'])) return $this->zajlib->ajax("A custom field is required!");
		if(empty($_POST['value'])) return $this->zajlib->ajax("A value is required!");
		/** Validate other stuff **/
		// Update the object
		$obj->set_these('customfield','value')->save();
		// Return success
		return $this->zajlib->ajax("ok");
	}

	/**
	 * Delete template
	 */
	function delete(){
		// Resume product and delete
		$this->zajlib->variable->customfieldentry = CustomFieldEntry::fetch($_GET['id']);
		$this->zajlib->variable->customfieldentry->delete();
		// Redirect to list or site editor
		return $this->zajlib->redirect('admin/customfieldentry/');
	}

	/**
	 * Reorder objects
	 */
	function reorder(){
		// reorder
		CustomFieldEntry::reorder($_POST['reorder']);
		// okay
		return $this->zajlib->ajax('ok');
	}

}