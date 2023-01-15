<?php
    /**
     * This controller handles the photo uploads.
     * @package Controller
     * @subpackage BuiltinControllers
     **/

    // Set default configuration options
    class zajapp_system_api_photo extends zajController {

        /**
         * Load method is called each time any system action is executed.
         * @todo Allow a complete disabling of this controller.
         **/
        public function __load() {
            $this->ofw->load->controller('system/api/file.ctl.php');
        }

        /**
         * File upload endpoint.
         * @return boolean Will return json data.
         **/
        public function upload() {

            // @todo Allow disabling upload for non-users, or for users of a minimum rights level

            return $this->ofw->reroute('system/api/file/upload/', [true]);
        }

        /**
         * Save photo meta data endpoint. Requires authentication as a valid admin user.
         * @post id The id of the photo.
         * @post name The name of the photo (will be used in downloads and seo).
         * @post alt The alt text of the photo.
         * @return boolean Will return json data.
         * @todo Add ability to configure user access to this feature
         */
        public function save() {

            // Authenticate as admin
            $user = User::fetch_by_session();
            if ($user == null) {
                return $this->ofw->json(['status' => 'error', 'message' => 'Your user session has expired!']);
            }
            if ($user->has_permission('admin_site')) {
                return $this->ofw->json(['status'  => 'error',
                                         'message' => 'Your user account does not have rights to perform this action!',
                ]);
            }

            // Fetch the photo
            $photo = Photo::fetch($_POST['id']);
            $photo->set_with_data($_POST, ['name', 'alttext', 'caption']);
            $photo->save();

            return $this->ofw->json(['status' => 'ok']);
        }

        /**
         * Create a new crop from the original photo.
         * @todo Add ability to configure user access to this feature
         * @todo impement!
         */
        public function crop() {
            return $this->ofw->json(['status' => 'ok']);
        }

        /**
         * Shows an image which has been saved.
         * @get id The id.
         * @get size The size, defaults to preview.
         **/
        public function show() {
            // Default size is preview
            if (!empty($_GET['size'])) {
                $size = $_GET['size'];
            } else {
                $size = 'preview';
            }

            /** @var Photo $pobj */
            $pobj = Photo::fetch($_GET['id']);
            if ($pobj != null && !$pobj->temporary && $pobj->data->status == 'saved') {
                $pobj->show($size);
            } else {
                $this->ofw->reroute('__error', ['system_api_photo_show', []]);
            }
        }

        /**
         * Shows a preview of an image which has just been uploaded.
         **/
        public function preview() {
            /** @var Photo $pobj */
            $pobj = Photo::fetch($_GET['id']);
            // @todo Preview should only be visible to the user who uploaded (if user exists) and should only be saved by user who uploaded
            // @todo We can achieve this with form verification id's (which we should have anyway)
            if ($pobj != null && $pobj->temporary) {
                $pobj->show('preview');
            } else {
                $this->ofw->reroute('__error', ['system_api_photo_preview', []]);
            }
        }

    }