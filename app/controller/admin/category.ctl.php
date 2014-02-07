<?php
	/**
	 * Basic admin interface for category administration.
	 *
	 * @version 3.0
	 **/

	class zajapp_admin_category extends zajController{
		
		/**
		 * Use this method to validate the admin category
		 **/
		function __load(){
			// load my admin default loader
				$this->zajlib->load->controller('admin/default.ctl.php');
			// load language
				$this->zajlib->lang->load('category', 'admin');			
			// All seems to be okay, return true.
			return true;
		}

		/**
		 * Display the list
		 **/
		function main(){
			// fetch my objects
				$this->zajlib->variable->objects = Category::fetch()->paginate(50)->sort('ordernum', 'ASC');
			// if the list is filtered by keyword
				if(!empty($_GET['query'])){
					// search by app name
						$this->zajlib->variable->objects->search($_GET['query']);
				}
			// if the list is filtered by category parent
				if(!empty($_GET['category'])){
					// category
						if($_GET['category'] != 'all') $this->zajlib->variable->objects->filter('parentcategory', $_GET['category']);
					// fetch current
						$this->zajlib->variable->current_category = Category::fetch($_GET['category']);
				}
				else $this->zajlib->variable->objects->filter('parentcategory', '');
			// send to list template				
				if(!empty($_GET['mozajik-tool-search'])) return $this->zajlib->template->block("admin/category/category_list.html", "content");
				else return $this->zajlib->template->show("admin/category/category_list.html");
		}

		/**
		 * Add the category object
		 **/
		function add(){
			// Create a new one and redirect to edit
				$obj = Category::create();
				$obj->cache();
				return $this->zajlib->redirect('admin/category/edit/?id='.$obj->id.'&parentcategory='.$_GET['parentcategory']);
		}

		/**
		 * Edit the categorys
		 **/
		function edit(){
			// resume the object
				$this->zajlib->variable->object = Category::fetch($_GET['id']);
				if($_GET['parentcategory']) $this->zajlib->variable->currentcat = Category::fetch($_GET['parentcategory']);
				else $this->zajlib->variable->currentcat = $this->zajlib->variable->object->data->parentcategory;

			// load the template
				return $this->zajlib->template->show("admin/category/category_edit.html");
		}

		/**
		 * Save a category
		 **/
		function save(){			
			// Validate data
				if(empty($_POST['name'])) return $this->zajlib->ajax($this->zajlib->config->variable->save_error);
			// Resume tje object
				$obj = Category::fetch($_POST['id']);
			// Update the object
				$obj->set_these('name', 'description', 'parentcategory', 'friendlyurl');
			// Now update other stuff related to name
				$obj->set('abc', $this->zajlib->lang->convert_eng($_POST['name']));
			// Now save
				$obj->save();			
			// Return success
				return $this->zajlib->ajax("ok");
		}

		/**
		 * Delete a category
		 **/
		function delete(){
			// Resume product and delete
				$this->zajlib->variable->category = Category::fetch($_GET['id']);
				$this->zajlib->variable->category->delete();
			// Redirect to list or site editor
				return $this->zajlib->redirect('admin/category/');
		}
		
		/**
		 * Reorder the category
		 **/
		function reorder(){
			// reorder
				Category::reorder($_POST['reorder']);
			// okay
				return $this->zajlib->ajax('ok');
		}		
		
		/**
		 * Calculate friendly url.
		 **/
		function friendly_url($title = ""){
			// If from reroute
				if(empty($title)) $title = $_GET['title'];
				else $rerouted = true;
			// Convert and return 
				$title = $this->zajlib->url->friendly($title);
			// Check if used, if so, add a number
				$counter = 1;
				while(Category::fetch_by_friendlyurl($title)){
					$title .= $counter;
					$counter++;
				}
			// Return	
				if(!empty($rerouted)) return $title;
				else $this->zajlib->ajax($title);
		}		

	}
	

?>