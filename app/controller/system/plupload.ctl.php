<?php
/**
 * This controller handles callbacks from plupload file uploader.
 * @package Controller
 * @subpackage BuiltinControllers
 **/

	class zajapp_system_plupload extends zajController{
		
		/**
		 * Load method is called each time any system action is executed.
		 * @todo Allow a complete disabling of this controller. 
		 **/
		public function __load(){
		}
		
		
		/**
		 * Enable automatic file uploads.
		 * @param boolean $process_as_image If set to true, the file will be processed as an image and a resized thumbnail will be available in /data.
		 **/
		public function upload($process_as_image = false){
			$this->upload_standard($process_as_image);
		}

		/**
		 * Enable automatic photo uploads.
		 **/
		public function upload_photo(){
			$this->upload(true);
		}

		/**
		 * Enable automatic file uploads.
		 **/
		public function upload_file(){
			$this->upload(false);
		}
		
		
	/** PRIVATE METHODS **/
		
		/**
		 * Processes an uploaded file by moving it to the cache folder with the proper file name. Images thumbs are moved to /data for direct access.
		 * @param string $orig_name The original name of the file.
		 * @param string $temp_name The temporary name of the file after it is uploaded
		 * @param boolean $process_as_image If set to true, the file will be processed as an image and a resized thumbnail will be available in /data.
		 * @return boolean|Photo|File Returns the Photo or File object, or false if error.
		 **/
		private function upload_process($orig_name, $temp_name, $process_as_image = false){
			// Create upload cache
				@mkdir($this->zajlib->basepath.'cache/upload/', 0777, true);
			// Verify file
				if(!is_uploaded_file($temp_name)) return false;
			// Create a photo or file object
				if($process_as_image){
					// verify its an image
					if(!getimagesize($temp_name)) return false;
					$obj = Photo::create();
				}
				else $obj = File::create();
			// Move to cache folder with id name
				@move_uploaded_file($temp_name, $this->zajlib->basepath.'cache/upload/'.$obj->id.'.tmp');
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
		private function upload_standard($process_as_image = false){
			// Process this one file
					// If process as image, then also return size
						$width = $height = 0;
						if($process_as_image) list($width, $height, $type, $attr) = getimagesize($_FILES['file']['tmp_name']);
					// Process this one file			 		 	
			 		 	$orig_name = $_FILES['file']['name'];
						$file = $this->upload_process($orig_name, $_FILES['file']['tmp_name'], $process_as_image);
		 		 	// If there was an error
		 		 		if(!$file){
		 		 			$result = array(
		 		 				'status'=>'error',
		 		 				'message'=>'Invalid file format or size.',
		 		 			);
		 		 		}
		 		 		else{
		 		 			$result = array(
		 		 				'status'=>'success',
		 		 				'message'=>'Successfully uploaded.',
		 		 				'id'=>$file->id,
		 		 				'name'=>$file->name,
		 		 				'type'=>$file->class_name,
		 		 				'width'=>$width,
		 		 				'height'=>$height,
		 		 			);
		 		 		}

		 	// Return JSON data
			 	$this->zajlib->json(json_encode($result));
				exit;
		}

		/**
		 * Shows a preview of an image which has just been uploaded.
		 **/
		public function preview(){
			// Retrieve image
				$pobj = Photo::fetch($_GET['id']);
				$pobj->show('preview');
			exit();	
		}

	}
?>