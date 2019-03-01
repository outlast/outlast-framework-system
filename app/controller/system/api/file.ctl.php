<?php
    /**
     * This controller handles file uploads.
     * @package Controller
     * @subpackage BuiltinControllers
     **/

    // Set default configuration options
    class zajapp_system_api_file extends zajController {

        /**
         * Load method is called each time any system action is executed.
         * @todo Allow a complete disabling of this controller.
         **/
        public function __load() {
            // load up config file
            zajLib::me()->config->load('system/fields.conf.ini', 'photos');
            zajLib::me()->config->load('system/fields.conf.ini', 'files');

            // set defaults
            $this->ofw->ofwconf->plupload_photo_maxheight = $this->ofw->config->variable->field_photos_max_height_default;
            $this->ofw->ofwconf->plupload_photo_maxuploadwidth = $this->ofw->ofwconf->plupload_photo_maxwidth = $this->ofw->config->variable->field_photos_max_width_default;

            // convert file size from txt to number
            $size = substr($this->ofw->config->variable->field_photos_max_file_size_default, 0, -2);
            $type = substr($this->ofw->config->variable->field_photos_max_file_size_default, -2);
            if ($type == 'mb') {
                $size = $size * 1024 * 1024;
            }
            if ($type == 'gb') {
                $size = $size * 1024 * 1024 * 1024;
            }
            $this->ofw->ofwconf->plupload_photo_maxphotosize = $size;

            // now the same for max file size
            $size = substr($this->ofw->config->variable->field_files_max_file_size_default, 0, -2);
            $type = substr($this->ofw->config->variable->field_files_max_file_size_default, -2);
            if ($type == 'mb') {
                $size = $size * 1024 * 1024;
            }
            if ($type == 'gb') {
                $size = $size * 1024 * 1024 * 1024;
            }
            $this->ofw->ofwconf->plupload_photo_maxfilesize = $size;
        }

        /**
         * Enable automatic file uploads.
         * @param boolean $process_as_image If set to true, the file will be processed as an image and a resized thumbnail will be available in /data.
         **/
        public function upload($process_as_image = false) {
            $this->upload_standard($process_as_image);
        }

        /**
         * Enable automatic photo uploads.
         **/
        public function upload_photo() {
            $this->upload(true);
        }

        /**
         * Enable automatic file uploads.
         **/
        public function upload_file() {
            // Load up lang file for errors
            $this->ofw->lang->load('system/fields');
            // Verify file for errors
            $this->verify_upload();
            // Create from upload
            $file = File::create_from_upload('file', false, null, false);
            if ($file === false) {
                $this->ofw->warning("Unknown after file upload during File object creation.");

                return $this->send_error("Unknown error occurred during file upload.");
            }
            // Return details
            $result = [
                'status'  => 'success',
                'message' => 'Successfully uploaded.',
                'id'      => $file->id,
                'name'    => $file->name,
                'type'    => $file->class_name,
            ];

            return $this->ofw->json($result);
        }


        /** PRIVATE METHODS **/

        /**
         * Verify upload for errors.
         * @param boolean $verify_that_it_is_image Verify that it is an image.
         * @return boolean Returns boolean true if success or json error if failed.
         */
        private function verify_upload($verify_that_it_is_image = true) {
            // Load up lang file for errors
            if ($verify_that_it_is_image) {
                $this->ofw->lang->load('system/fields', 'photos');
            } else {
                $this->ofw->lang->load('system/fields', 'files');
            }
            // Look for standard errors. See @link http://php.net/manual/en/features.file-upload.errors.php
            switch ($_FILES['file']['error']) {
                /** All is ok **/
                case UPLOAD_ERR_OK:
                    // Continue if it is ok
                    break;

                /** User errors **/
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    if (!empty($_POST['MAX_FILE_SIZE'])) {
                        $this->ofw->warning(" The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
                        $ini_setting = $_POST['MAX_FILE_SIZE'];
                    } else {
                        $this->ofw->warning("The uploaded file is larger than what the INI setting allows.");
                        $ini_setting = ini_get('upload_max_filesize');
                    }
                    $too_big = str_ireplace('%1', $ini_setting, $this->ofw->lang->variable->system_field_file_too_big);
                    $too_big = str_ireplace('%2', $ini_setting, $too_big);

                    return $this->send_error($too_big);
                case UPLOAD_ERR_PARTIAL:
                    return $this->send_error($this->ofw->lang->variable->system_field_file_partial);
                case UPLOAD_ERR_NO_FILE:
                    return $this->send_error($this->ofw->lang->variable->system_field_file_none);

                /** System errors **/
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->ofw->warning("Missing a temporary folder for file uploads.");

                    return $this->send_error("Unknown error occurred during file upload.");
                case UPLOAD_ERR_CANT_WRITE:
                    $this->ofw->warning("Failed to write file to disk during upload.");

                    return $this->send_error("Unknown error occurred during file upload.");
                case UPLOAD_ERR_EXTENSION:
                    $this->ofw->warning("A PHP extension stopped the file upload.");

                    return $this->send_error("Unknown error occurred during file upload.");
                default:
                    $this->ofw->warning("An unhandled file upload error occurred.");

                    return $this->send_error("Unknown error occurred during file upload.");
            }
            // Look for picture errors
            if ($verify_that_it_is_image) {
                // @todo ADDTHIS!
            }

            return true;
        }

        /**
         * Send an error to the client.
         * @param string $message The message to send.
         * @return boolean Returns boolean or json.
         */
        private function send_error($message) {
            // Build array
            $result = [
                'status'  => 'error',
                'message' => $message,
            ];

            // Return the json result
            return $this->ofw->json($result);
        }

        /**
         * Processes an uploaded file by moving it to the cache folder with the proper file name. Images thumbs are moved to /data for direct access.
         * @param string $orig_name The original name of the file.
         * @param string $temp_name The temporary name of the file after it is uploaded
         * @param boolean $process_as_image If set to true, the file will be processed as an image and a resized thumbnail will be available in /data.
         * @return boolean|Photo|File Returns the Photo or File object, or false if error.
         **/
        private function upload_process($orig_name, $temp_name, $process_as_image = false) {
            // Create upload cache
            @mkdir($this->ofw->basepath.'cache/upload/', 0777, true);
            // Verify file
            if (!is_uploaded_file($temp_name)) {
                return false;
            }
            // Create a photo or file object
            if ($process_as_image) {
                // verify its an image
                if (!getimagesize($temp_name)) {
                    return false;
                }
                /** @var Photo $obj */
                $obj = Photo::create();
            } else {
                /** @var File $obj */
                $obj = File::create();
            }
            // Move to cache folder with id name
            $new_tmp_name = $this->ofw->basepath.'cache/upload/'.$obj->id.'.tmp';
            // check image type of source to preserve it
            $force_exif_imagetype = exif_imagetype($temp_name);
            // Resize if max size set and image
            if ($process_as_image && !empty($this->ofw->ofwconf->plupload_photo_maxwidth)) {
                $this->ofw->graphics->resize($temp_name, $new_tmp_name, $this->ofw->ofwconf->plupload_photo_maxwidth,
                    $this->ofw->ofwconf->plupload_photo_maxwidth * 2, 85, true, $force_exif_imagetype);
            } else {
                @move_uploaded_file($temp_name, $new_tmp_name);
            }
            // Set status to uploaded
            $obj->set('name', $orig_name);
            $obj->set('status', 'uploaded');
            $obj->temporary = true;
            $obj->save();

            return $obj;
        }

        /**
         * Uploads standard HTML
         * @param boolean $process_as_image If set to true, the file will be processed as an image and a resized thumbnail will be available in /data.
         * @return boolean Returns true if successful, false if error.
         **/
        private function upload_standard($process_as_image = false) {
            // Load up lang file for errors
            $this->ofw->lang->load('system/fields');
            // Process this one file
            $error = false;
            // Check if file uploaded
            if (empty($_FILES['file']['tmp_name'])) {
                $error = $this->ofw->lang->variable->system_field_file_upload_error;
                $this->ofw->warning("File could not be uploaded due to unknown error on a ".$this->ofw->request->client_agent());
            } else {
                // If process as image, then also return size
                $width = $height = 0;
                if ($process_as_image) {
                    list($width, $height, $type, $attr) = getimagesize($_FILES['file']['tmp_name']);
                }
                // Check file size of image or file
                if (
                    $process_as_image && $_FILES['file']['size'] > $this->ofw->ofwconf->plupload_photo_maxfilesize ||
                    !$process_as_image && $_FILES['file']['size'] > $this->ofw->ofwconf->plupload_files_maxfilesize
                ) {
                    $this->ofw->lang->variable->system_field_file_too_big = str_ireplace('%1',
                        $this->ofw->ofwconf->plupload_photo_maxfilesize,
                        $this->ofw->lang->variable->system_field_file_too_big);
                    $this->ofw->lang->variable->system_field_file_too_big = str_ireplace('%2', $_FILES['file']['size'],
                        $this->ofw->lang->variable->system_field_file_too_big);
                    $error = $this->ofw->lang->variable->system_field_file_too_big;
                }
                // Check for image width max
                if ($process_as_image && !empty($this->ofw->ofwconf->plupload_photo_maxuploadwidth) && $width > $this->ofw->ofwconf->plupload_photo_maxuploadwidth) {
                    $error = "Image width too large (maximum is ".$this->ofw->ofwconf->plupload_photo_maxuploadwidth."px wide / your image is ".$width."px wide)!";
                }
            }
            // Process this one file
            $orig_name = $_FILES['file']['name'];
            if (!$error) {
                // Process file
                $file = $this->upload_process($orig_name, $_FILES['file']['tmp_name'], $process_as_image);
                // Now recheck the file size (it may have been resized!)
                if (is_object($file) && $process_as_image) {
                    list($width, $height, $type, $attr) = getimagesize($this->ofw->basepath.'cache/upload/'.$file->id.'.tmp');
                }
            }
            // If there was an error
            if ($error || !$file) {
                if (!$error) {
                    $error = 'Invalid file format or size.';
                }
                $result = [
                    'status'  => 'error',
                    'message' => $error,
                ];
            } else {
                $result = [
                    'status'  => 'success',
                    'message' => 'Successfully uploaded.',
                    'id'      => $file->id,
                    'name'    => $file->name,
                    'type'    => $file->class_name,
                    'width'   => $width,
                    'height'  => $height,
                ];
            }

            // Return JSON data
            $this->ofw->json(json_encode($result));
            exit;
        }

        /**
         * Shows a preview of an image which has just been uploaded.
         **/
        public function preview() {
            // Retrieve image
            $pobj = Photo::fetch($_GET['id']);
            if ($pobj !== false) {
                $pobj->show('preview');
            }
            exit();
        }

    }