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
	 * If set to true, the file is not yet saved to the database.
	 * @var boolean
	 **/
	protected $temporary = false;

	/**
	 * Temporary path of files from basepath.
	 * @var string
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
		// Set the mime type
			$this->mime = $this->data->mime;
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
	 * Helper function which returns the path based on the current settings.
	 * @param string|boolean $filename Defaults to the standard file name.
	 * @param boolean $create_folders Create the subfolders if needed.
	 * @return string Returns the file path, relative to basepath.
	 **/
	public function get_file_path($filename = false, $create_folders = false){
		// Default filename
			if($filename === false) $filename = $this->id.'.'.$this->extension;
		// First, let's determine which function to use
			$path = $this->zajlib->file->get_time_path(self::$relative_path, $filename, $this->time_create, false);
		// Create folders if requested
			if($create_folders) $this->zajlib->file->create_path_for($path);
		// Now call and return!
			return $path;
	}

	/**
	 * Helper function which returns the temporary path where the file is stored after upload but before save.
	 * @param string|boolean $filename Defaults to the standard file name.
	 * @param boolean $create_folders Create the subfolders if needed.
	 * @return string Returns the file path, relative to basepath.
	 **/
	public function get_temporary_path($filename = false, $create_folders = false){
		// Default filename
			if($filename === false) $filename = $this->id.'.'.$this->extension;
		// Get path
			$path = self::$temporary_path.$filename;
		// Create folders if requested
			if($create_folders) $this->zajlib->file->create_path_for($path);
		// Return temporary path
			return $path;
	}

	/**
	 * Forces a download dialog for the browser.
	 * @param boolean $force_download If set to true (default), this will force a download for the user.
	 * @param string|boolean $file_name The file name to download as. Defaults to the uploaded file name.
	 * @return void|boolean This will force a download and exit. May return false if it fails.
	 */
	public function download($force_download = true, $file_name = false){
		// get default file name
			if($this->temporary) $file_name = $this->get_temporary_path();
			else $file_name = $this->get_file_path($file_name);
		// double check that file name is ok
			$file_name = $this->zajlib->file->file_check($file_name, "Invalid file requested for download.");
		// get full path
			$file_path = $this->zajlib->basepath.$file_name;
		// pass file thru to user			
			header('Content-Type: '.$this->mime);
			header('Content-Length: '.filesize($file_path));
			if($force_download) header('Content-Disposition: attachment; filename="'.$this->name.'"');
			else header('Content-Disposition: inline; filename="'.$this->name.'"');
			ob_clean();
			flush();
   			readfile($file_path);
		// now exit
		exit;
	}























	
	/**
	 * This is an alias to set_file, because Photo also has one like it.
	 **/
	public function upload($filename = ""){ return $this->set_file($filename); }
	public function set_file($filename=""){
		// if filename is empty, use default tempoary name
			if(empty($filename)) $filename = $this->id.".tmp";
		// get tmpname
			$tmpname = $this->zajlib->basepath."cache/upload/".$filename;
		// now enable time-based folders
			$this->set('timepath', true);
			$this->timepath = true;
		// generate new path
			$new_path = $this->zajlib->basepath.$this->get_file_path($this->id, true);
			//@mkdir($this->zajlib->basepath."data/private/File/");
		// move tmpname to new location
			rename($tmpname, $new_path);
		// now set restrictive permissions
			chmod($new_path, 0644);
		// now set and save me
			// TODO: add mime-type detection here!
			// $this->set('mime',$mimetype);
			$this->set('size',filesize($new_path));
			$this->set('status','saved');
			$this->save();
		return $this;
	}

	/**
	 * Override the delete method.
	 * @param bool $complete
	 * @return bool|void Return true if successful.
	 */
	public function delete($complete = false){
		// remove photo files
			if($complete){
				// generate path
					$this->zajlib->load->library('file');
					$file_path = $this->zajlib->basepath.$this->get_file_path($this->id);
				// delete file
					@unlink($file_path);
			}
		// call parent
			parent::delete($complete);
	}

	/**
	 * Creates a file object from a file or url.
	 * @param string $urlORfilename The url or file name.
	 * @param zajModel|boolean $parent My parent object. If not specified, none will be set.
	 * @param string|boolean $field The parent-field in which the file is to be stored.
	 * @return File Returns the new file object or false if none created.
	 **/
	public static function create_from_file($urlORfilename, $parent = false, $field = false){
		// ok, now copy it to uploads folder
			$updest = basename($urlORfilename);
			@mkdir(zajLib::me()->basepath."cache/upload/", 0777, true);
			copy($urlORfilename, zajLib::me()->basepath."cache/upload/".$updest);
		// now create and set image
			/** @var File $pobj */
			$pobj = File::create();
			if(is_object($parent)) $parent = $parent->id;
			if($parent !== false) $pobj->set('parent', $parent);
			if($field !== false) $pobj->set('field', $field);
			return $pobj->set_file($updest);
	}
	/**
	 * Included for backwards-compatibility. Will be removed. Alias of create_from_file.
	 * @todo Remove from version release.
	 **/
	public static function import($urlORfilename){ return self::create_from_file($urlORfilename); }
	
	
	/**
	 * Creates a photo object from php://input stream.
	 * @param string|boolean $parent_field The name of the field in the parent model. Defaults to $field_name.
	 * @param zajModel|boolean $parent My parent object.
	 * @return File Returns the file.
	 **/
	public static function create_from_stream($parent_field = false, $parent = false){
		// tmp folder
			$folder = zajLib::me()->basepath.'/cache/upload/';
			$filename = uniqid().'.upload';
		// make temporary folder
			@mkdir($folder, 0777, true);
		// write to temporary file in upload folder
			$photofile = file_get_contents("php://input");
			@file_put_contents($folder.$filename, $photofile);
		// now create object and return the object
			/** @var File $pobj */
			$pobj = File::create();
		// parent and field?
			if($parent !== false) $pobj->set('parent', $parent);
			if($parent_field !== false) $pobj->set('field', $parent);
		// set file and delete temp
		 	$pobj->set_file($filename);
			@unlink($folder.$filename);
		return $pobj;
	}
	
	/**
	 * Creates a file object from a standard upload HTML4
	 * @param string $field_name The name of the file input field.
	 * @param zajModel|boolean $parent My parent object.
	 * @param string|boolean $parent_field The name of the field in the parent model. Defaults to $field_name.
	 * @return File Returns the file.
	 **/
	public static function create_from_upload($field_name, $parent = false, $parent_field = false){
		// File names
			$orig_name = $_FILES[$field_name]['name'];
			$tmp_name = $_FILES[$field_name]['tmp_name'];
		// If no file, return false
			if(empty($tmp_name)) return false;
		// Now create photo object and set me
			/** @var File $obj */
			$obj = File::create();
		// Move uploaded file to tmp
			@mkdir(zajLib::me()->basepath.'cache/upload/');
			move_uploaded_file($tmp_name, zajLib::me()->basepath.'cache/upload/'.$obj->id.'.tmp');
		// Now set and save
			$obj->set('name', $orig_name);
			if($parent !== false){
				$obj->set('parent', $parent);
				if(!$parent_field) $obj->set('field', $field_name);
				else $obj->set('field', $parent_field);
			}
			$obj->set_file();
			@unlink($tmp_name);
		return $obj;
	}	

}