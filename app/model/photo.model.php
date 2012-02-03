<?php
$GLOBALS['photosizes'] = array('thumb'=>50,'small'=>300,'normal'=>700,'large'=>2000,'full'=>false);

/**
 * A built-in model to handle photos.
 *
 * You should not directly use this model unless you are developing extensions.
 *
 * @package Model
 * @subpackage BuiltinModels
 * @todo Add support for keeping the full size version.
 */
class Photo extends zajModel {
		
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$fields->parent = zajDb::text();
			$fields->name = zajDb::name();
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
		//foreach($GLOBALS['photosizes'] as $key=>$size){
			//$this->$key = $this->get_image($key);
		//}
		$this->status = $this->data->status;
		$this->parent = $this->data->parent;
	}

	public function __get($name){
		if(!empty($GLOBALS['photosizes'][$name])) return $this->zajlib->file->get_id_path($this->zajlib->baseurl."data/Photo", $this->id."-$name.jpg");
		else return parent::__get($name);
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
		// no errors, resize and save
			foreach($GLOBALS['photosizes'] as $key => $size){
				if($size !== false){
					// save resized images
					$new_path = $this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$key.jpg");
					$this->zajlib->graphics->resize($file_path, $new_path, $size);
				}
			}
		// now remove the original
			unlink($file_path);
		$this->set('status', 'saved');
		$this->save();
		return $this;
	}

	/**
	 * Returns an image url based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 **/
	public function get_image($size = 'normal'){
		return $this->zajlib->file->get_id_path($this->zajlib->baseurl."data/Photo", $this->id."-$size.jpg");
	}

	/**
	 * Returns an image path based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 **/
	public function get_path($size = 'normal'){
		return $this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$size.jpg");
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
		// look for bad characters in $size
			if(($size != "preview" && empty($GLOBALS['photosizes'][$size])) || substr_count($size, "..") > 0) return false;
		// generate path
			$this->zajlib->load->library('file');
			$file_path = $this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$size.jpg");
		// if it is in preview mode (only if not yet finalized)
			if(!$this->exists && $size == "preview") $file_path = $this->tmppath;
		// pass file thru to user
			if($force_download) header('Content-Disposition: attachment; filename="'.$this->data->name.'"');
			header('Content-Type: image/jpeg;');
			$f = fopen($file_path, "r");
				fpassthru($f);
			fclose($f);
		// now exit
		exit;
	}
	public function delete($complete = false){
		// remove photo files
			if($complete){
				$this->zajlib->load->library('file');
				foreach($GLOBALS['photosizes'] as $name=>$size){
					if($size) @unlink($this->zajlib->file->get_id_path($this->zajlib->basepath."data/Photo", $this->id."-$name.jpg"));
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
	 * @param zajObject $parent My parent object.
	 **/
	public static function create_from_upload($field_name, $parent = false){
		// File names
			$orig_name = $_FILES[$field_name]['name'];
			$tmp_name = $_FILES[$field_name]['tmp_name'];
		// If no file, return false
			if(empty($tmp_name)) return false;
		// Now create photo object and set me
			$obj = Photo::create();
		// Move uploaded file to tmp
			@mkdir($GLOBALS['zajlib']->basepath.'cache/upload/');
			move_uploaded_file($tmp_name, $GLOBALS['zajlib']->basepath.'cache/upload/'.$obj->id.'.tmp');
		// Now set and save
			$obj->set('name', $orig_name);
			if($parent !== false) $obj->set('parent', $parent);
			//$obj->set('status', 'saved'); (done by set_image)
			$obj->upload();
			@unlink($tmp_name);
		return $obj;
	}
}
?>