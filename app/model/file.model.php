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
			if(empty($this->mime)) $this->mime = $this->zajlib->file->get_mime_type($this->name);
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
	 * Overrides the global duplicate method. Unlike the standard duplicate method, this actually saves the object.
	 * @return File Returns a new file object with the physical file duplicated as well.
	 */
	public function duplicate(){
		// First duplicate my object
			/** @var File $new_object */
			$new_object = parent::duplicate();
			$new_object->temporary = true;
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



}