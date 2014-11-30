<?php
/**
 * A built-in model to handle files and uploads.
 * @package Model
 * @subpackage BuiltinModels
 * @todo Add additional checks to disable certain file types.
 */

/**
 * @property zajDataFile $data
 * @property string $status
 * @property string $class The class of the parent.
 * @property string $parent The id of the parent.
 * @property string $field The field name of the parent.
 * @property integer $time_create
 * @property string $mime The mime type of the file.
 * @property string $extension Extension, not including the dot
 *
 * Magic properties
 * @property string $relative Returns path/url relative to base.
 * @property string $path Alias of relative.
 * @property boolean $temporary If set to true, the file is not yet saved to the database.
 *
 * Methods
 * @method static File|zajFetcher fetch()
 **/
class File extends zajModel {

	/**
	 * Temporary path of files from basepath.
	 * @var string
	 * @todo move this to data folder!
	 **/
	protected static $temporary_path = 'cache/upload/';

	/**
	 * Relative path of files from basepath.
	 * @var string
	 **/
	protected static $relative_path = 'data/private/File/';

	/**
	 * __model function. creates the database fields available for objects of this class.
	 */
	public static function __model(){
		// define custom database fields
			$f = (object) array();
			$f->class = zajDb::text();
			$f->parent = zajDb::text();
			$f->field = zajDb::text();
			$f->name = zajDb::name();
			$f->mime = zajDb::text();
			$f->size = zajDb::integer();
			$f->description = zajDb::textbox();
			$f->status = zajDb::select(array("new","uploaded","saved","deleted"),"new");

		// deprecated because everything is timepath now! Always true.
			$f->timepath = zajDb::boolean(true);
			$f->original = zajDb::text();
		// do not modify the line below!
			$f = parent::__model(__CLASS__, $f); return $f;
	}

	/**
	 * Contruction and static calling methods. These are required and not to be modified!
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	/**
	 * Cache stuff.
	 **/
	public function __afterFetch(){
		// Set status and parents
			$this->status = $this->data->status;
			$this->class = $this->data->class;
			$this->parent = $this->data->parent;
			$this->field = $this->data->field;
			$this->time_create = $this->data->time_create;
		// Get file path info
			$this->extension = $this->zajlib->file->get_extension($this->name);
			// Magic property 'relative', 'path'
			// Magic property 'temporary'
	}

	/**
	 * Returns the relative or full path.
	 * @param string $name The name of the variable.
	 * @return mixed Returns its value.
	 **/
	public function __get($name){
		switch($name){
			case 'tempoary':
				return $this->temporary;
			case 'path':
			case 'relative':
				return $this->get_file_path($this->id);
			default:
				return parent::__get($name);
		}
	}

	/**
	 * Helper function which returns the final path based on the current settings.
	 * @param string|boolean $filename Defaults to the standard file name.
	 * @param boolean $create_folders Create the subfolders if needed.
	 * @return string Returns the file path, relative to basepath.
	 **/
	public function get_file_path($filename = false, $create_folders = false){
		// Default filename
			if($filename === false) $filename = $this->id.'.file';
		// First, let's determine which function to use
			$path = $this->zajlib->file->get_time_path(self::$relative_path, $filename, $this->time_create, false);
		// Create folders if requested
			if($create_folders) $this->zajlib->file->create_path_for($this->zajlib->basepath.$path);
		// Now call and return!
			return $path;
	}

	/**
	 * Helper function which returns the temporary path where the file is stored after upload but before save.
	 * @param boolean $create_folders Create the subfolders if needed.
	 * @return string Returns the file path, relative to basepath.
	 **/
	public function get_temporary_path($create_folders = false){
		// Default filename
			$filename = $this->id.'.tmp';
		// Get path
			$path = self::$temporary_path.$filename;
		// Create folders if requested
			if($create_folders) $this->zajlib->file->create_path_for($this->zajlib->basepath.$path);
		// Return temporary path
			return $path;
	}

	/**
	 * Get the file extension type.
	 * @param string|boolean $file_path The path whoes extension we wish to check. Defaults to the master file path.
	 * @return string Will return the file's extension.
	 */
	public function get_extension($file_path = false){
		if($file_path !== false) return $this->zajlib->file->get_extension($file_path);
		else return $this->extension;
	}

	/**
	 * Forces a download dialog for the browser.
	 * @param boolean $force_download If set to true (default), this will force a download for the user.
	 * @param string|boolean $download_as The file name to download as. Defaults to the uploaded file name.
	 * @return void|boolean This will force a download and exit. May return false if it fails.
	 */
	public function download($force_download = true, $download_as = false){
		// get default file name
			if(!$this->exists || $this->data->status == 'uploaded') $file_relative_path = $this->get_temporary_path();
			else $file_relative_path = $this->get_file_path();
		// double check that file name is ok
			$file_relative_path = $this->zajlib->file->file_check($file_relative_path, "Invalid file requested for download.");
		// get full path
			$file_full_path = $this->zajlib->basepath.$file_relative_path;
		// set download file name
			if($download_as === false) $download_as = $this->name;
		// get mime type, try to determine if not set
			$mime = $this->data->mime;
			if(empty($mime)) $mime = $this->zajlib->file->get_mime_type($download_as, $file_relative_path);
		// pass file thru to user
			header('Content-Type: '.$mime);
			header('Content-Length: '.filesize($file_full_path));
			if($force_download) header('Content-Disposition: attachment; filename="'.$download_as.'"');
			else header('Content-Disposition: inline; filename="'.$download_as.'"');
			ob_clean();
			flush();
   			readfile($file_full_path);
		// now exit
		exit;
	}

	/**
	 * Shows the file in the browser inline (if available)
	 * @param string|boolean $file_name The file name to download as. Defaults to the uploaded file name.
	 * @return void|boolean This will try to show the file inline and exit.
	 */
	public function show($file_name = false){
		return $this->download(false, $file_name);
	}

	/**
	 * Overrides the global duplicate method. Unlike the standard duplicate method, this actually saves the object.
	 * @return File Returns a new file object with the physical file duplicated as well.
	 */
	public function duplicate(){
		// First duplicate my object
			/** @var File $new_object */
			$new_object = parent::duplicate();
			$new_object->set('status', 'uploaded')->save();
		// Create a copy of my original file
			$original_file = $this->zajlib->basepath.$this->get_file_path();
			$new_file = $this->zajlib->basepath."cache/upload/".$new_object->id.".tmp";
			copy($original_file, $new_file);
		// Create my object
			$new_object->upload();
		return $new_object;
	}

	/**
	 * Overrides the global delete.
	 * @param bool $complete If set to true, the file will be deleted too and the full entry will be removed.
	 * @return bool Returns true if successful.
	 **/
	public function delete($complete = false){
		// Remove the files as well?
			if($complete) @unlink($this->zajlib->basepath.$this->get_file_path());
		// call parent
			return parent::delete($complete);
	}

	/**
	 * Method which saves file to its final location.
	 **/
	public function upload(){
		// Make sure this file is currently not saved
			if($this->data->status == 'saved') return false;
		// Get temporary and final names
			$temp_path = $this->get_temporary_path();
			$new_path = $this->get_file_path(false, true);
		// Move tmpname to new location and set permissions
			rename($this->zajlib->basepath.$temp_path, $this->zajlib->basepath.$new_path);
			chmod($this->zajlib->basepath.$new_path, 0644);
		// Save my final meta data
			$this->set('mime', $this->zajlib->file->get_mime_type($this->name));
			$this->set('size', filesize($this->zajlib->basepath.$new_path));
			$this->set('status','saved');
			$this->save();
		return $this;
	}

	/*****************************************
	 * Static methods used to create uploads.
	 *****************************************/

	/**
	 * Creates a file with a specific parent object and field.
	 * @param zajModel|string $parent The parent object.
	 * @param string $field The name of the field.
	 * @return self Returns a new bare object with the parent and field set.
	 */
	public static function create_with_parent($parent, $field){
		// Check parent object
			if(!is_object($parent) || !is_a($parent, 'zajModel')){
				$class_name = get_called_class();
				return zajLib::me()->error("You tried to create a new $class_name with an invalid parent. You must pass an object instead of an id.");
			}
		// Creata a new object
			$pobj = self::create();
			$pobj->set('parent', $parent->id);
			$pobj->set('class', $parent->class_name);
			$pobj->set('field', $field);
		return $pobj;
	}

	/**
	 * Creates and saves a file object from a file or url. Will return false if it is not an image or not found.
	 * @param string $url_or_file_name The url or file name.
	 * @param zajModel|bool $parent My parent object or id. If not specified, none will be set.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return self Returns the new file object or false if none created.
	 **/
	public static function create_from_file($url_or_file_name, $parent = false, $field = null, $save_now_to_final_destination = true){
		// Create object
			/** @var self $pobj **/
			if($parent !== false) $pobj = self::create_with_parent($parent, $field);
			else $pobj = self::create();
		// Set basics
			$pobj->set('name', basename($url_or_file_name));
			$pobj->name = basename($url_or_file_name);
		// Copy to tmp destination
			$tmp_path = $pobj->get_temporary_path(true);
			copy($url_or_file_name, zajLib::me()->basepath.$tmp_path);
			chmod(zajLib::me()->basepath.$tmp_path, 0644);
		// Save or just be temporary
			if($save_now_to_final_destination) $pobj->upload();
			else $pobj->temporary = true;
			$pobj->save();
		return $pobj;
	}

	/**
	 * Creates and saves a file object from a url. Will return false if it is not an image or not found.
	 * @param string $url The url. Will return false if not valid url.
	 * @param zajModel|bool $parent My parent object or id. If not specified, none will be set.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return self Returns the new file object or false if none created.
	 **/
	public static function create_from_url($url, $parent = false, $field = null, $save_now_to_final_destination = true){
		if(!zajLib::me()->url->valid($url)) return false;
		return self::create_from_file($url, $parent, $field, $save_now_to_final_destination);
	}

	/**
	 * Creates a file object from a standard upload HTML4
	 * @param string $field_name The name of the file input field.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return self|bool Returns the file object on success, false if not.
	 **/
	public static function create_from_upload($field_name, $parent = false, $field = null, $save_now_to_final_destination = true){
		// File names
			$orig_name = $_FILES[$field_name]['name'];
			$tmp_name = $_FILES[$field_name]['tmp_name'];
		// If no file, return false
			if(empty($tmp_name)) return false;
		// Now create file object and set me
			/** @var self $pobj **/
			if($parent !== false) $pobj = self::create_with_parent($parent, $field);
			else $pobj = self::create();
		// Move uploaded file to temporary folder
			$tmp_path = $pobj->get_temporary_path(true);
			move_uploaded_file($tmp_name, zajLib::me()->basepath.$tmp_path);
			chmod(zajLib::me()->basepath.$tmp_path, 0644);
		// Now set and save
			$pobj->set('name', $orig_name);
			$pobj->name = $orig_name;
			$pobj->set('status', 'uploaded');
			if($save_now_to_final_destination) $pobj->upload();
			else $pobj->save();
		// Remove temporary file
			@unlink($tmp_name);
		return $pobj;
	}

	/**
	 * Creates a file object from php://input stream.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return self|bool Returns the file object on success, false if not.
	 **/
	public static function create_from_stream($parent = false, $field = null, $save_now_to_final_destination = true){
		// Get raw data
			$raw_data = file_get_contents("php://input");
		// Now create from raw data!
			return self::create_from_raw($raw_data, $parent, $field, $save_now_to_final_destination);
	}

	/**
	 * Creates a file object from base64 data.
	 * @param string $base64_data This is the file data, base64-encoded.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return self|bool Returns the file object on success, false if not.
	 **/
	public static function create_from_base64($base64_data, $parent = false, $field = null, $save_now_to_final_destination = true){
		// Allow data-urls with base64 data
			$base64_data = preg_replace("|data:[A-z0-9/]+;base64,|", "", $base64_data);
		// Get raw data
			$raw_data = base64_decode($base64_data);
		// Now create from raw data!
			return self::create_from_raw($raw_data, $parent, $field, $save_now_to_final_destination);
	}

	/**
	 * Create a file object from raw data.
	 * @param string|boolean $raw_data If specified, this will be used instead of input stream data.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return self|bool Returns the file object on success, false if not.
	 */
	public static function create_from_raw($raw_data, $parent = false, $field = null, $save_now_to_final_destination = true){
		// Create a self object
			/** @var self $pobj **/
			if($parent !== false) $pobj = self::create_with_parent($parent, $field);
			else $pobj = self::create();
		// Create the temporary file
			$tmp_path = $pobj->get_temporary_path(false, true);
			file_put_contents(zajLib::me()->basepath.$tmp_path, $raw_data);
			chmod(zajLib::me()->basepath.$tmp_path, 0644);
		// Now set stuff
			$pobj->set('name', 'Upload');
			$pobj->set('status', 'uploaded');
			if($save_now_to_final_destination) $pobj->upload();
			else $pobj->save();
		return $pobj;
	}

}