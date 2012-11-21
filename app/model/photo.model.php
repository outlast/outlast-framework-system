<?php
// Define my photo sizes if not already done!
if(empty($GLOBALS['photosizes'])) $GLOBALS['photosizes'] = array('thumb'=>50,'small'=>300,'normal'=>700,'large'=>2000,'full'=>true);

/**
 * A built-in model to handle photos.
 *
 * You should not directly use this model unless you are developing extensions.
 *
 * @package Model
 * @subpackage BuiltinModels
 * @todo Add saving of 'imagetype' and 'class' - this is delayed for a later version to ensure database updates on all projects...
 **/
class Photo extends zajModel {
		
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$fields->class = zajDb::text();
			$fields->parent = zajDb::text();
			$fields->field = zajDb::text();
			$fields->name = zajDb::name();
			$fields->imagetype = zajDb::integer();
			$fields->original = zajDb::text();
			$fields->description = zajDb::textbox();
			$fields->status = zajDb::select(array("new","uploaded","saved","deleted"),"new");
		// do not modify the line below!
			$fields = parent::__model(__CLASS__, $fields); return $fields;
	}
	///////////////////////////////////////////////////////////////
	// !Construction and other required methods
	///////////////////////////////////////////////////////////////
	public function __construct($id = ""){ parent::__construct($id, __CLASS__);	}
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	///////////////////////////////////////////////////////////////
	// !Magic methods
	///////////////////////////////////////////////////////////////
	public function __afterFetch(){
		// Set status and parents
			$this->status = $this->data->status;
			$this->parent = $this->data->parent;
			$this->field = $this->data->field;
		// See which file exists
			if(file_exists($this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-normal.png"))){
				$this->extension = 'png';
				$this->imagetype = IMAGETYPE_PNG;
			}
			elseif(file_exists($this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-normal.gif"))){
				$this->extension = 'gif';
				$this->imagetype = IMAGETYPE_GIF;
			}
			else{
				$this->extension = 'jpg';
				$this->imagetype = IMAGETYPE_JPEG;
			}
	}

	/**
	 * Returns the url based on size ($photo->small) or the relative url ($photo->rel_small)
	 **/
	public function __get($name){
		// Default the extension to jpg if not defined
			if(empty($this->extension)) $this->extension = 'jpg';
		// Figure out direct or relative file name
			$relname = str_ireplace('rel_', '', $name);
			if(!empty($GLOBALS['photosizes'][$name])) return $this->get_image($name);
			else{
				if(!empty($GLOBALS['photosizes'][$relname])) return $this->zajlib->file->get_id_path("data/Photo", $this->id."-$relname.".$this->extension);
				else return parent::__get($name);
			}
	}



	///////////////////////////////////////////////////////////////
	// !Model methods
	///////////////////////////////////////////////////////////////

	/**
	 * This is an alias to set_image, because file also has one like it.
	 **/
	public function upload($filename = ""){ return $this->set_image($filename); }

	/**
	 * Resizes and saves the image. The status is always changed to saved and this method automatically saves changes to the database. Only call this when you are absolutely ready to commit the photo for public use.
	 * @param $filename The name of the file within the cache upload folder.
	 **/
	public function set_image($filename = ""){
		// if filename is empty, use default tempoary name
			if(empty($filename)) $filename = $this->id.".tmp";
		// jail file
			if(strpos($filename, '..') !== false || strpos($filename, '/') !== false) $this->zajlib->error("invalid filename given when trying to save final image.");
		// set variables
			$file_path = $this->zajlib->basepath."cache/upload/".$filename;
			$image_data = getimagesize($file_path);
		// check for errors
			if(strpos($filename,"/") !== false) return $this->zajlib->error('uploaded photo cannot be saved: must specify relative path to cache/upload folder.');
			if(!file_exists($this->zajlib->basepath."cache/upload/".$filename)) return $this->zajlib->error("uploaded photo $filename does not exist!");
			if($image_data === false) return $this->zajlib->error('uploaded file is not a photo. you should always check this before calling set_image/upload!');
		// check image type of source
			$image_type = exif_imagetype($file_path);
		// select extension
			if($image_type == IMAGETYPE_PNG) $extension = 'png';
			elseif($image_type == IMAGETYPE_GIF) $extension = 'gif';
			else $extension = 'jpg';
		// no errors, resize and save
			foreach($GLOBALS['photosizes'] as $key => $size){
				if($size !== false){
					// save resized images perserving extension
						$new_path = $this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$key.".$extension);
					// resize it now!
						$this->zajlib->graphics->resize($file_path, $new_path, $size);
				}
			}
		// now remove the original or copy to full location
			if($GLOBALS['photosizes']['full']) copy($file_path, $this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-full.".$extension));
			else unlink($file_path);
		// Remove temporary location flag
			$this->temporary = false;
			//$this->set('imagetype', $image_type);
			$this->set('status', 'saved');
			$this->save();
		return $this;
	}

	/**
	 * Returns an image url based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 **/
	public function get_image($size = 'normal'){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		return $this->zajlib->baseurl.$this->zajlib->file->get_id_path("data/Photo", $this->id."-$size.".$this->extension);
	}

	/**
	 * Returns an image path based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 **/
	public function get_path($size = 'normal'){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		return $this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$size.".$this->extension);
	}

	/**
	 * An alias of Photo->download($size, false), which will display the photo instead of forcing a download.
	 * @param string $size One of the standard photo sizes.
	 **/
	public function show($size = "normal"){
		$this->download($size, false);
	}

	/**
	 * Forces a download dialog for the browser.
	 * @param string $size One of the standard photo sizes.
	 * @param boolean $force_download If set to true (default), this will force a download for the user.
	 **/
	public function download($size = "normal", $force_download = true){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		// look for bad characters in $size
			if(($size != "preview" && empty($GLOBALS['photosizes'][$size])) || substr_count($size, "..") > 0) return false;
			if(!$this->temporary && $size == "preview") $size = 'normal';
		// generate path
			$file_path = $this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$size.".$this->extension);
		// if it is in preview mode (only if not yet finalized)
			$preview_path = $this->zajlib->basepath."cache/upload/".$this->id.".tmp";
			if($this->temporary && $size == "preview") $file_path = $preview_path;
		// final test, if file exists
			if(!file_exists($file_path)) exit("File could not be found.");
		// pass file thru to user
			if($force_download) header('Content-Disposition: attachment; filename="'.$this->data->name.'"');
		// create header
			switch ($this->extension){
				case 'png': header('Content-Type: image/png;'); break;
				case 'gif': header('Content-Type: image/gif;'); break;
				default: header('Content-Type: image/jpeg;'); break;
			}

			
			$f = fopen($file_path, "r");
				fpassthru($f);
			fclose($f);
		// now exit
		exit;
	}
	public function delete($complete = false){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		// remove photo files
			if($complete){
				$this->zajlib->load->library('file');
				foreach($GLOBALS['photosizes'] as $name=>$size){
					if($size) @unlink($this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$name.".$this->extension));
				}
			}
		// call parent
			parent::delete($complete);
	}


	///////////////////////////////////////////////////////////////
	// !Static methods
	///////////////////////////////////////////////////////////////
	// be careful when using the import function to check if filename or url is valid


	/**
	 * Creates a photo object from a file or url. Will return false if it is not an image or not found.
	 * @param string $urlORfilename The url or file name.
	 * @param zajObject $parent My parent object. If not specified, none will be set.
	 * @return Photo Returns the new photo object or false if none created.
	 **/
	public static function create_from_file($urlORfilename, $parent = false){
		// first check to see if it is a photo
			$image_data = @getimagesize($urlORfilename);
			if($image_data === false) return false;
		// ok, now copy it to uploads folder
			$updest = basename($urlORfilename);
			@mkdir($GLOBALS['zajlib']->basepath."cache/upload/", 0777, true);
			copy($urlORfilename, $GLOBALS['zajlib']->basepath."cache/upload/".$updest);
		// now create and set image
			$pobj = Photo::create();
			if($parent !== false) $pobj->set('parent', $parent);
			return $pobj->set_image($updest);
	}
	/**
	 * Included for backwards-compatibility. Will be removed. Alias of create_from_file.
	 * @todo Remove from version release.
	 **/
	public static function import($urlORfilename){ return $this->create_from_file($urlORfilename); }
	
	/**
	 * Creates a photo object from php://input stream.
	 **/
	public static function create_from_stream(){
		// tmp folder
			$folder = $GLOBALS['zajlib']->basepath.'/cache/upload/';
			$filename = uniqid().'.upload';
		// make temporary folder
			@mkdir($folder, 0777, true);
		// write to temporary file in upload folder
			$photofile = file_get_contents("php://input");
			@file_put_contents($folder.$filename, $photofile);
		// is photo an image
			$image_data = getimagesize($folder.$filename);
			if($image_data === false){
				// not image, delete file return false
				@unlink($folder.$filename);
				return false;
			}
		// now create object and return the object
			$pobj = Photo::create();
		 	$pobj->set_image($filename);
			@unlink($folder.$filename);
			return $pobj;
	}
	
	/**
	 * Creates a photo object from a standard upload HTML4
	 * @param string $field_name The name of the file input field.
	 * @param boolean $save_now If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @param zajObject $parent My parent object.
	 **/
	public static function create_from_upload($field_name, $parent = false, $save_now = true){
		// File names
			$orig_name = $_FILES[$field_name]['name'];
			$tmp_name = $_FILES[$field_name]['tmp_name'];
		// If no file, return false
			if(empty($tmp_name)) return false;
		// Now create photo object and set me
			$obj = Photo::create();
		// Move uploaded file to tmp
			@mkdir($GLOBALS['zajlib']->basepath.'cache/upload/');
			$new_name = $GLOBALS['zajlib']->basepath.'cache/upload/'.$obj->id.'.tmp';
			move_uploaded_file($tmp_name, $new_name);
		// Now set and save
			$obj->set('name', $orig_name);
			if($parent !== false) $obj->set('parent', $parent);
			//$obj->set('status', 'saved'); (done by set_image)
			if($save_now) $obj->upload();
			else $obj->temporary = true;
			$obj->save();
			@unlink($tmp_name);
		return $obj;
	}
}
?>