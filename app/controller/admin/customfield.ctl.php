<?php
/**
 * Controller for editing CustomField objects.
 * @package Controller
 * @subpackage CustomField
 **/

class zajapp_admin_customfield extends zajController{

	/**
	 * The __load() magic method is run each time this particular controller is used to process the request. You should place code here which is general for all
	 *        related requests. For example, an admin.ctl.php file's __load() method will likely contain an authentication process, so that anyone requesting
	 *        any admin pages will need to login first...
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
		$this->zajlib->variable->customfields = CustomField::fetch()->paginate(25);
		// Search
		if (!empty($_GET['query'])) $this->zajlib->variable->customfields->search($_GET['query']);
		// Display list template
		if ($this->zajlib->request->is_ajax()) return $this->zajlib->template->block("admin/customfield/customfield_list.html", "customfieldlist");
		else return $this->zajlib->template->show("admin/customfield/customfield_list.html");
	}

	/**
	 * Add new template
	 **/
	function add(){
		// Create and cache
		$this->zajlib->variable->customfield = CustomField::create();
		$this->zajlib->variable->customfield->cache();
		return $this->zajlib->redirect("admin/customfield/edit/?id=" . $this->zajlib->variable->customfield->id);
	}

	/**
	 * Edit template
	 **/
	function edit(){
		// Resume the object
		$this->zajlib->variable->customfield = CustomField::fetch($_GET['id']);
		return $this->zajlib->template->show("admin/customfield/customfield_edit.html");
	}

	/**
	 * Save template
	 */
	function save(){
		// Resume the object
		/** @var CustomField $obj */
		$obj = CustomField::fetch($_POST['id']);
		// Validate
		// Check name
		if (empty($_POST['name'])) return $this->zajlib->ajax("The name is required!");
		// Update the object
		$obj->set_these('name','type','featured')->save();
		// Return success
		return $this->zajlib->ajax("ok");
	}

	/**
	 * Delete template
	 */
	function delete(){
		// Resume product and delete
		$this->zajlib->variable->customfield = CustomField::fetch($_GET['id']);
		$this->zajlib->variable->customfield->delete();
		// Redirect to list or site editor
		return $this->zajlib->redirect('admin/customfield/');
	}

	/**
	 * Reorder objects
	 */
	function reorder(){
		// reorder
		CustomField::reorder($_POST['reorder']);
		// okay
		return $this->zajlib->ajax('ok');
	}

	/**
	 * Toggle if object is featured.
	 **/
	public function toggle_featured(){
		// Fetch existintg product
		/** @var Category $existing_object */
		$existing_object = CustomField::fetch($_REQUEST['id']);
		if($existing_object === false) return $this->zajlib->json('error');
		// Toggle!
		$existing_object->set('featured', !$existing_object->data->featured)->save();
		// redirect
		return $this->zajlib->json($existing_object->data->featured);
	}

}