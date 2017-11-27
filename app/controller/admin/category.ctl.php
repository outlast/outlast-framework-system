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
		function __load($request, $optional_attributes=[]){
			// load my admin default loader
				$this->zajlib->load->controller('admin/default.ctl.php');
			// load language
				$this->zajlib->lang->load('category', 'admin');
				$this->zajlib->config->load('category.conf.ini', 'settings');
			// All seems to be okay, return true.
			return true;
		}

		/**
		 * Display the list of categories
		 **/
		function main(){
			// fetch my objects
				$this->zajlib->variable->objects = Category::fetch()->sort('ordernum', 'ASC');
			// if the list is filtered by keyword
				if(!empty($_GET['query'])){
					// search by app name
						$this->zajlib->variable->objects->search($_GET['query']);
				}
				else{
					// if the list is filtered by category parent
						if(!empty($_GET['parentcategory'])){
							// category
								if($_GET['parentcategory'] != 'all') $this->zajlib->variable->objects->filter('parentcategory', $_GET['parentcategory']);
							// fetch current
								$this->zajlib->variable->current_category = Category::fetch($_GET['parentcategory']);
						}
						else $this->zajlib->variable->objects->filter('parentcategory', '');
				}
			// send to list template
				if($this->zajlib->request->is_ajax()) return $this->zajlib->template->block("admin/category/category_list.html", "autopagination");
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
            if(empty($_POST['name'])) return $this->zajlib->json(['status'=>'error', 'message'=>$this->zajlib->config->variable->save_error]);

			// Resume object
            $obj = Category::fetch($_POST['id']);

			// Check for existing friendlyurl
            if(Category::fetch()->filter('friendlyurl', $_POST['friendlyurl'])->filter('id', $obj->id, 'NOT LIKE')->next() !== false) return $this->zajlib->ajax($this->zajlib->config->variable->category_friendlyurl_error);
            if($_POST['parentcategory'] == $obj->id) return $this->zajlib->ajax($this->zajlib->config->variable->category_parent_error);

			// Update the object
            $obj->set_with_data($_POST);

			// Now update other stuff related to name
            $obj->set('abc', $this->zajlib->lang->convert_eng($_POST['name']));

			// Now save
			$obj->save();

			// Return success
			return $this->zajlib->json(['status'=>'ok']);
		}

		/**
		 * Delete a category
		 **/
		function delete(){
			// Resume product and delete
				$this->zajlib->variable->category = Category::fetch($_GET['id']);
				$this->zajlib->variable->category->delete();
			// Redirect to list or site editor
				return $this->zajlib->redirect('admin/category/?parentcategory='.$this->zajlib->variable->category->data->parentcategory->id);
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
				$counter = '';
				while(Category::fetch_by_friendlyurl($title.$counter)){
					if(empty($counter)) $counter = 0;
					$counter++;
				}
				$title .= $counter;
			// Return
				if(!empty($rerouted)) return $title;
				else return $this->zajlib->ajax($title);
		}

		/**
		 * Toggle if object is featured.
		 **/
		public function toggle_featured(){
			// Fetch existintg product
				/** @var Category $existing_object */
				$existing_object = Category::fetch($_REQUEST['id']);
				if($existing_object === false) return $this->zajlib->json('error');
			// Toggle!
				$existing_object->set('featured', !$existing_object->featured)->save();
			// redirect
				return $this->zajlib->json($existing_object->featured);
		}

	}