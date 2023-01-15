<?php
    /**
     * Basic admin interface for category administration.
     * @version 3.0
     **/

    class zajapp_admin_category extends zajController {

        /**
         * Use this method to validate the admin category
         **/
        function __load($request, $optional_attributes = []) {
            // load my admin default loader
            $this->ofw->load->controller('admin/default.ctl.php');
            // load language
            $this->ofw->lang->load('category', 'admin');
            $this->ofw->config->load('category.conf.ini', 'settings');

            // All seems to be okay, return true.
            return true;
        }

        /**
         * Display the list of categories
         **/
        function main() {
            // fetch my objects
            $this->ofw->variable->objects = Category::fetch()->sort('ordernum', 'ASC');
            // if the list is filtered by keyword
            if (!empty($_GET['query'])) {
                // search by app name
                $this->ofw->variable->objects->search($_GET['query']);
            } else {
                // if the list is filtered by category parent
                if (!empty($_GET['parentcategory'])) {
                    // category
                    if ($_GET['parentcategory'] != 'all') {
                        $this->ofw->variable->objects->filter('parentcategory', $_GET['parentcategory']);
                    }
                    // fetch current
                    $this->ofw->variable->current_category = Category::fetch($_GET['parentcategory']);
                } else {
                    $this->ofw->variable->objects->filter('parentcategory', '');
                }
            }
            // send to list template
            if ($this->ofw->request->is_ajax()) {
                return $this->ofw->template->block("admin/category/category_list.html", "autopagination");
            } else {
                return $this->ofw->template->show("admin/category/category_list.html");
            }
        }

        /**
         * Add the category object
         **/
        function add() {
            // Create a new one and redirect to edit
            $obj = Category::create();
            $obj->cache();

            return $this->ofw->redirect('admin/category/edit/?id='.$obj->id.'&parentcategory='.$_GET['parentcategory']);
        }

        /**
         * Edit the categorys
         **/
        function edit() {
            // resume the object
            $this->ofw->variable->object = Category::fetch($_GET['id']);
            if ($_GET['parentcategory']) {
                $this->ofw->variable->currentcat = Category::fetch($_GET['parentcategory']);
            } else {
                $this->ofw->variable->currentcat = $this->ofw->variable->object->data->parentcategory;
            }

            // load the template
            return $this->ofw->template->show("admin/category/category_edit.html");
        }

        /**
         * Save a category
         **/
        function save() {
            // Validate data
            if (empty($_POST['name'])) {
                return $this->ofw->json(['status' => 'error', 'message' => $this->ofw->config->variable->save_error]);
            }

            // Resume object
            $obj = Category::fetch($_POST['id']);

            // Check for existing friendly url
            if (!empty($_POST['friendlyurl'])) {
                if (Category::fetch()->filter('friendlyurl', $_POST['friendlyurl'])->filter('id', $obj->id,
                        'NOT LIKE')->next() != null) {
                    return $this->ofw->json([
                        'status'  => 'error',
                        'message' => $this->ofw->config->variable->category_friendlyurl_error,
                    ]);
                }
            }
            if ($_POST['parentcategory'] == $obj->id) {
                return $this->ofw->json([
                    'status'  => 'error',
                    'message' => $this->ofw->config->variable->category_parent_error,
                ]);
            }

            // Update the object
            $obj->set_with_data($_POST);

            // Now update other stuff related to name
            $obj->set('abc', $this->ofw->lang->convert_eng($_POST['name']));

            // Now save
            $obj->save();

            // Return success
            return $this->ofw->json(['status' => 'ok']);
        }

        /**
         * Delete a category
         **/
        function delete() {
            // Resume product and delete
            $this->ofw->variable->category = Category::fetch($_GET['id']);
            $this->ofw->variable->category->delete();

            // Redirect to list or site editor
            return $this->ofw->redirect('admin/category/?parentcategory='.$this->ofw->variable->category->data->parentcategory->id);
        }

        /**
         * Reorder the category
         **/
        function reorder() {
            // reorder
            Category::reorder($_POST['reorder']);

            // okay
            return $this->ofw->ajax('ok');
        }

        /**
         * Calculate friendly url.
         **/
        function friendly_url($title = "") {
            // If from reroute
            if (empty($title)) {
                $title = $_GET['title'];
            } else {
                $rerouted = true;
            }
            // Convert and return
            $title = $this->ofw->url->friendly($title);
            // Check if used, if so, add a number
            $counter = '';
            while (Category::fetch_by_friendlyurl($title.$counter)) {
                if (empty($counter)) {
                    $counter = 0;
                }
                $counter++;
            }
            $title .= $counter;
            // Return
            if (!empty($rerouted)) {
                return $title;
            } else {
                return $this->ofw->ajax($title);
            }
        }

        /**
         * Toggle if object is featured.
         **/
        public function toggle_featured() {
            // Fetch existing product
            /** @var Category $existing_object */
            $existing_object = Category::fetch($_REQUEST['id']);
            if ($existing_object == null) {
                return $this->ofw->json('error');
            }
            // Toggle!
            $existing_object->set('featured', !$existing_object->featured)->save();

            // redirect
            return $this->ofw->json($existing_object->featured);
        }

    }