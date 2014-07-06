<?php
// Define my photo sizes if not already done!
if(empty($GLOBALS['photosizes'])) $GLOBALS['photosizes'] = array('thumb'=>50,'small'=>300,'normal'=>700,'large'=>2000,'full'=>true);

/**
 * A built-in model to store photos.
 *
 * This is a pointer to the data items in this model...
 * @property zajDataPhoto $data
 * And here are the cached fields...
 * @property string $status
 * @property string $class The class of the parent.
 * @property string $parent The id of the parent.
 * @property string $field The field name of the parent.
 * @property integer $time_create
 * @property string $extension Extension, not including the dot
 * @property string $imagetype Can be IMAGETYPE_PNG, IMAGETYPE_GIF, or IMAGETYPE_JPG constant.
 * And the size properties
 * @property string $thumb
 * @property string $small
 * @property string $normal
 * @property string $large
 * @property string $full
 * Orientation properties
 * @property string $orientation Can be 'portrait' or 'landscape'.
 * @property boolean $portrait True if portrait.
 * @property boolean $landscape True if landscape.
 *
 * @method static Photo|zajFetcher fetch()
 **/
class Photo extends zajModel {

	/* If set to true, the file is not yet saved to the db. */
	public $temporary = false;
		
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$f = (object) array();
			$f->class = zajDb::text();
			$f->parent = zajDb::text();
			$f->field = zajDb::text();
			$f->name = zajDb::name();
			$f->imagetype = zajDb::integer();
			$f->description = zajDb::textbox();
			$f->filesizes = zajDb::json();
			$f->dimensions = zajDb::json();
			$f->cropdata = zajDb::json();   // Stores original photo data and associated cropping values. {"x":0,"y":0,"w":540,"h":525,"path":'data/something/beforecrop.jpg'}. Path is empty if original not saved during crop.
			$f->status = zajDb::select(array("new","uploaded","saved","deleted"),"new");

			// Deprecated because everything is timepath now! Always true.
			$f->timepath = zajDb::boolean(true);
			$f->original = zajDb::text();

		// do not modify the line below!
			$f = parent::__model(__CLASS__, $f); return $f;
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
			$this->class = $this->data->class;
			$this->parent = $this->data->parent;
			$this->field = $this->data->field;
			$this->time_create = $this->data->time_create;
		// See which file exists
			if(file_exists($this->zajlib->basepath.$this->get_file_path($this->id."-normal.png"))){
				$this->extension = 'png';
				$this->imagetype = IMAGETYPE_PNG;
			}
			elseif(file_exists($this->zajlib->basepath.$this->get_file_path($this->id."-normal.gif"))){
				$this->extension = 'gif';
				$this->imagetype = IMAGETYPE_GIF;
			}
			else{
				$this->extension = 'jpg';
				$this->imagetype = IMAGETYPE_JPEG;
			}
		// Calculate photo orientation
			$this->landscape = $this->portrait = false;
			if(is_object($this->data->dimensions)){
				if($this->data->dimensions->small->w >= $this->data->dimensions->small->h){
					$this->orientation = 'landscape';
					$this->landscape = true;
				}
				else{
					$this->orientation = 'portrait';
					$this->portrait = true;
				}
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
				if(!empty($GLOBALS['photosizes'][$relname])) return $this->get_file_path($this->id."-$relname.".$this->extension);
				else return parent::__get($name);
			}
	}

	/**
	 * Helper function which returns the path based on the current settings.
	 * @param string $filename Can be thumb, small, normal, etc.
	 * @param bool $create_folders Create the subfolders if needed.
	 * @return string Returns the file path, relative to basepath.
	 **/
	public function get_file_path($filename, $create_folders = false){
		// First, let's determine which function to use
			$path = $this->zajlib->file->get_time_path("data/Photo", $filename, $this->time_create, false);
		// Create folders if requested
			if($create_folders) $this->zajlib->file->create_path_for($path);
		// Now call and return!
			return $path;
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
	 * @param string $filename The name of the file within the cache upload folder.
	 * @return bool|Photo Returns the Photo object, false if error.
	 */
	public function set_image($filename = ""){
		// if filename is empty, use default temporary name
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
		// check image size and type of source
			$filesizes = $dimensions = array();
			$image_type = exif_imagetype($file_path);
		// select extension
			if($image_type == IMAGETYPE_PNG) $extension = 'png';
			elseif($image_type == IMAGETYPE_GIF) $extension = 'gif';
			else $extension = 'jpg';
		// no errors, resize and save
			foreach($GLOBALS['photosizes'] as $key => $size){
				if($size !== false){
					// save resized images perserving extension
						$new_path = $this->zajlib->basepath.$this->get_file_path($this->id."-$key.".$extension, true);
					// resize it now!
						$this->zajlib->graphics->resize($file_path, $new_path, $size);
					// let's get the new file size
						$filesizes[$key] = @filesize($new_path);
						$my_image_data = @getimagesize($new_path);
						$dimensions[$key] = array('w'=>$my_image_data[0], 'h'=>$my_image_data[1]);

				}
			}
		// now remove the original or copy to full location
			if($GLOBALS['photosizes']['full']){
				$new_path = $this->zajlib->basepath.$this->get_file_path($this->id."-full.".$extension, true);
				copy($file_path, $new_path);
				// @todo Shouldn't this be move?
				$filesizes['full'] = @filesize($new_path);
				$my_image_data = @getimagesize($new_path);
				$dimensions['full'] = array('w'=>$my_image_data[0], 'h'=>$my_image_data[1]);
			}
			else unlink($file_path);
		// Remove temporary location flag
			$this->temporary = false;
			$this->set('dimensions', $dimensions);
			$this->set('filesizes', $filesizes);
			$this->set('status', 'saved');
			$this->save();
		return $this;
	}

	/**
	 * Returns an image url based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 * @return string Image url.
	 */
	public function get_image($size = 'normal'){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		return $this->zajlib->baseurl.$this->get_file_path($this->id."-$size.".$this->extension);
	}

	/**
	 * Returns an image path based on the requested size.
	 * @param string $size One of the standard photo sizes.
	 * @return string Image path.
	 **/
	public function get_path($size = 'normal'){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		return $this->zajlib->basepath.$this->get_file_path($this->id."-$size.".$this->extension);
	}

	/**
	 * An alias of Photo->download($size, false), which will display the photo instead of forcing a download.
	 * @param string $size One of the standard photo sizes.
	 **/
	public function show($size = "normal"){
		$this->download($size, false);
	}

	/**
	 * Crop image.
	 * @param integer $x Cropped image offset from left.
	 * @param integer $y Cropped image offset from top.
	 * @param integer $w Cropped image width.
	 * @param integer $h Cropped image height.
	 * @param integer $jpeg_quality A number value of the jpg quality to be used in conversion. Only matters for jpg output.
	 * @param boolean $keep_a_copy_of_original If set to true (default), a copy of the original file will be kept.
	 * @param boolean $crop_from_original If set to true, the crop will be created from the saved original file.
	 * @return boolean True if successful, false otherwise.
	 */
	public function crop($x, $y, $w, $h, $jpeg_quality = 85, $keep_a_copy_of_original = true, $crop_from_original = false){
		// get master file
			$file_path = $this->get_master_file_path();
		// get extension
			$extension = $this->get_extension($file_path);
		// create data for crop
			// {"x":0,"y":0,"w":540,"h":525,"path":'data/something/beforecrop.jpg'}
			$cropdata = array(
				'x'=>$x,
				'y'=>$y,
				'w'=>$w,
				'h'=>$h,
			);
		// do we have an original and should we use it?
			$original_uncropped = $this->data->cropdata->path;
			if($crop_from_original && !empty($original_uncropped) && file_exists($this->zajlib->basepath.$original_uncropped)){
				// copy the original file over the current one
				copy($this->zajlib->basepath.$original_uncropped, $file_path);
				// set the original path again and no need to keep a copy of the original (since the original is already copied)
				$cropdata['path'] = $original_uncropped;
				$keep_a_copy_of_original = false;
			}
		// save a copy of the original
			if($keep_a_copy_of_original){
				$new_path = $this->get_file_path($this->id."-beforecrop-".date('Y-m-d-h-i-s').".".$extension, true);
				$cropdata['path'] = $new_path;
				copy($file_path, $this->zajlib->basepath.$new_path);
			}
		// now perform the crop and save over original
			$this->zajlib->graphics->crop($file_path, $file_path, $x, $y, $w, $h, $jpeg_quality);
		// now save my crop data
			$this->set('cropdata', $cropdata)->save();
		// now perform the resize
			$this->resize();
	}

	/**
	 * Create all of the defined size versions from the master file.
	 */
	public function resize(){
		// get master file
			$file_path = $this->get_master_file_path();
		// get extension
			$extension = $this->get_extension($file_path);
		// create a copy of the master file (if not already done)
			$new_path = $this->zajlib->basepath.$this->get_file_path($this->id."-full.".$extension, true);
			if($file_path != $new_path) copy($file_path, $new_path);
		// get sizes
			$sizes = $GLOBALS['photosizes'];
			unset($sizes['full']);
		// resize
			foreach($sizes as $key => $size){
				if($size !== false){
					// save resized images perserving extension
						$new_path = $this->zajlib->basepath.$this->get_file_path($this->id."-$key.".$extension, true);
					// resize it now!
						$this->zajlib->graphics->resize($file_path, $new_path, $size);
					// let's get the new file size
						$filesizes[$key] = @filesize($new_path);
						$my_image_data = @getimagesize($new_path);
						$dimensions[$key] = array('w'=>$my_image_data[0], 'h'=>$my_image_data[1]);
				}
			}
	}

	/**
	 * Get the file path of the original image.
	 */
	public function get_master_file_path(){
		// Use the temporary name if not yet saved
			if($this->temporary) $path = $this->zajlib->basepath."cache/upload/".$this->id.".tmp";
			else{
				// Determine the highest resolution version of the image
					$last_key = end(array_keys($GLOBALS['photosizes']));
					$path = $this->zajlib->basepath.$this->__get('rel_'.$last_key);
			}
		// Perform verification
			$this->zajlib->file->file_check($path);
			$my_image_data = @getimagesize($path);
			if($my_image_data === false) return $this->zajlib->error("Could not get photo file path. Path is not a photo: ".$path);
		// All is ok, return
			return $path;
	}

	/**
	 * Get the file extension type.
	 * @param string|boolean $file_path The path whoes extension we wish to check. Defaults to the master file path.
	 * @return string Will return png, gif, or jpg.
	 */
	public function get_extension($file_path = false){
		// Get my file
			if($file_path === false) $file_path = $this->get_master_file_path();
		// Verify the extension
			$image_type = exif_imagetype($file_path);
			if($image_type == IMAGETYPE_PNG) $extension = 'png';
			elseif($image_type == IMAGETYPE_GIF) $extension = 'gif';
			else $extension = 'jpg';
		return $extension;
	}

	/**
	 * Forces a download dialog for the browser.
	 * @param string $size One of the standard photo sizes.
	 * @param boolean $force_download If set to true (default), this will force a download for the user.
	 * @param string|boolean $file_name The file name to download as. Defaults to the uploaded file name.
	 * @return void|boolean This will force a download and exit. May return false if it fails.
	 */
	public function download($size = "normal", $force_download = true, $file_name = false){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		// look for bad characters in $size
			if(($size != "preview" && empty($GLOBALS['photosizes'][$size])) || substr_count($size, "..") > 0)  return $this->zajlib->error("File could not be found.");
			if(!$this->temporary && $size == "preview") $size = 'normal';
		// generate path
			$file_path = $this->zajlib->basepath.$this->get_file_path($this->id."-$size.".$this->extension);
		// if it is in preview mode (only if not yet finalized)
			$preview_path = $this->zajlib->basepath."cache/upload/".$this->id.".tmp";
			if($this->temporary && $size == "preview") $file_path = $preview_path;
		// final test, if file exists
			if(!file_exists($file_path)) return $this->zajlib->error("File could not be found.");
		// file name default
			if($file_name === false) $file_name = $this->data->name;
		// pass file thru to user
			if($force_download) header('Content-Disposition: attachment; filename="'.$file_name.'"');
		// create header
			switch ($this->extension){
				case 'png': header('Content-Type: image/png;'); break;
				case 'gif': header('Content-Type: image/gif;'); break;
				default: header('Content-Type: image/jpeg;'); break;
			}
		// open and pass through
			$f = fopen($file_path, "r");
				fpassthru($f);
			fclose($f);
		// now exit
		exit;
	}

	/**
	 * Overrides the global duplicate method. Unlike the standard duplicate method, this actually saves the object.
	 * @return Photo Returns a new photo object with all files duplicated.
	 */
	public function duplicate(){
		// First duplicate my object
			/** @var Photo $new_object */
			$new_object = parent::duplicate();
			$new_object->temporary = true;
			$new_object->set('status', 'uploaded')->save();
		// Create a copy of my original file
			$original_file = $this->get_master_file_path();
			$new_file = $this->zajlib->basepath."cache/upload/".$new_object->id.".tmp";
			copy($original_file, $new_file);
		// Create my object
			$new_object->upload();
		// Create a copy of the uncropped file (if needed)
			$original_uncropped = $this->data->cropdata->path;
			$new_uncropped = $new_object->get_file_path($new_object->id."-beforecrop-".date('Y-m-d-h-i-s').".".$this->extension, true);
			if(!empty($original_uncropped) && file_exists($this->zajlib->basepath.$original_uncropped)){
				// copy the original file over the current one
				copy($this->zajlib->basepath.$original_uncropped, $this->zajlib->basepath.$new_uncropped);
				// set the original path again and no need to keep a copy of the original (since the original is already copied)
				$newcropdata = $this->data->cropdata;
				$newcropdata->path = $new_uncropped;
				$new_object->set('cropdata', $newcropdata)->save();
			}
		return $new_object;
	}

	/**
	 * Overrides the global delete.
	 * @param bool $complete If set to true, the file will be deleted too and the full entry will be removed.
	 * @return bool Returns true if successful.
	 **/
	public function delete($complete = false){
		// Default the extension to jpg if not defined (backwards compatibility)
			if(empty($this->extension)) $this->extension = 'jpg';
		// remove photo files
			if($complete){
				foreach($GLOBALS['photosizes'] as $name=>$size){
					if($size) @unlink($this->zajlib->basepath.$this->get_file_path($this->id."-$name.".$this->extension));
				}
			}
		// call parent
			return parent::delete($complete);
	}


	///////////////////////////////////////////////////////////////
	// !Static methods
	///////////////////////////////////////////////////////////////
	// be careful when using the import function to check if filename or url is valid

	/**
	 * Creates a photo with a specific parent object and field.
	 * @param zajModel|string $parent The parent object.
	 * @param string $field The name of the field.
	 * @return Photo Returns a new bare photo object with the parent and field set.
	 */
	public static function create_with_parent($parent, $field){
		// Check parent object
			if(!is_object($parent) || !is_a($parent, 'zajModel')){
				// @todo Change this to an error once we are done updating code!
				//return zajLib::me()->error("You tried to create a new Photo with an invalid parent. You must pass an object instead of an id.");

				// Temporary warning for backwards compatibility!
				zajLib::me()->deprecated("You tried to create a new Photo, but didn't pass an object as a parent. You must pass an object (not an id string).");
				$parent = (object) array('id'=>$parent, 'class_name'=>'');
			}
		// Creata a new Photo object
			$pobj = Photo::create();
			$pobj->set('parent', $parent->id);
			$pobj->set('class', $parent->class_name);
			$pobj->set('field', $field);
		return $pobj;
	}

	/**
	 * Creates and saves a photo object from a file or url. Will return false if it is not an image or not found.
	 * @param string $url_or_file_name The url or file name.
	 * @param zajModel|bool $parent My parent object or id. If not specified, none will be set.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return Photo Returns the new photo object or false if none created.
	 **/
	public static function create_from_file($url_or_file_name, $parent = false, $field = null, $save_now_to_final_destination = true){
		// @todo remove this backwards compatibility code
			if($field === false){
				zajLib::me()->deprecated("You called create_from_upload with an old parameter order. Update your code with the proper parameters. See docs.");
				$save_now_to_final_destination = $field;
			}
		// First check to see if it is a photo
			$image_data = @getimagesize($url_or_file_name);
			if($image_data === false) return false;
		// Create object
			/** @var Photo $pobj **/
			if($parent !== false) $pobj = Photo::create_with_parent($parent, $field);
			else $pobj = Photo::create();
		// Set basics
			$pobj->set('name', basename($url_or_file_name));
		// Copy to tmp destination
			$tmp_destination = zajLib::me()->basepath."cache/upload/".$pobj->id.".tmp";
			zajLib::me()->file->create_path_for($tmp_destination);
			copy($url_or_file_name, $tmp_destination);
		// Save or just be temporary
			if($save_now_to_final_destination) $pobj->upload();
			else $pobj->temporary = true;
			$pobj->save();
		return $pobj;
	}
	/**
	 * Included for backwards-compatibility. Will be removed. Alias of create_from_file.
	 * @todo Remove from version release.
	 **/
	public static function import($urlORfilename){
		zajLib::me()->deprecated("Used deprecated static method 'import' for Photo model.");
		return self::create_from_file($urlORfilename);
	}
	
	/**
	 * Creates a photo object from php://input stream.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return Photo|bool Returns the Photo object on success, false if not.
	 **/
	public static function create_from_stream($parent = false, $field = null, $save_now_to_final_destination = true){
		// Get raw data
			$raw_data = file_get_contents("php://input");
		// Now create from raw data!
			return self::create_from_raw($raw_data, $parent, $field, $save_now_to_final_destination);
	}
	
	/**
	 * Creates a photo object from base64 data.
	 * @param string $base64_data This is the photo file data, base64-encoded.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return Photo|bool Returns the Photo object on success, false if not.
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
	 * Create a photo object from raw data.
	 * @param string|boolean $raw_data If specified, this will be used instead of input stream data.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return Photo|bool Returns the Photo object on success, false if not.
	 */
	public static function create_from_raw($raw_data, $parent = false, $field = null, $save_now_to_final_destination = true){
		// @todo remove this backwards compatibility code
			if($field === false){
				zajLib::me()->deprecated("You called create_from_upload with an old parameter order. Update your code with the proper parameters. See docs.");
				$save_now_to_final_destination = $field;
			}
		// Create a Photo object
			/** @var Photo $pobj **/
			if($parent !== false) $pobj = Photo::create_with_parent($parent, $field);
			else $pobj = Photo::create();
		// tmp folder
			$folder = zajLib::me()->basepath.'/cache/upload/';
			$filename = $pobj->id.'.tmp';
		// make temporary folder
			@mkdir($folder, 0777, true);
			@file_put_contents($folder.$filename, $raw_data);
		// is photo an image
			$image_data = getimagesize($folder.$filename);
			if($image_data === false){
				// not image, delete file return false
				@unlink($folder.$filename);
				return false;
			}
		// Now set stuff
			$pobj->set('name', 'Upload');
			if($save_now_to_final_destination) $pobj->set_image($filename);
			else $pobj->temporary = true;
			$pobj->save();
		return $pobj;
	}

	/**
	 * Creates a photo object from a standard upload HTML4
	 * @param string $field_name The name of the file input field.
	 * @param zajModel|bool $parent My parent object.
	 * @param string|bool $field The field name of the parent. This is required if $parent is set.
	 * @param boolean $save_now_to_final_destination If set to true (the default) it will be saved in the final folder immediately. Otherwise it will stay in the tmp folder.
	 * @return Photo|bool Returns the Photo object on success, false if not.
	 **/
	public static function create_from_upload($field_name, $parent = false, $field = null, $save_now_to_final_destination = true){
		// @todo remove this backwards compatibility code
			if($field === false){
				zajLib::me()->deprecated("You called create_from_upload with an old parameter order. Update your code with the proper parameters. See docs.");
				$save_now_to_final_destination = $field;
			}
		// File names
			$orig_name = $_FILES[$field_name]['name'];
			$tmp_name = $_FILES[$field_name]['tmp_name'];
		// If no file, return false
			if(empty($tmp_name)) return false;
		// Now create photo object and set me
			/** @var Photo $pobj **/
			if($parent !== false) $pobj = Photo::create_with_parent($parent, $field);
			else $pobj = Photo::create();
		// Move uploaded file to tmp
			@mkdir(zajLib::me()->basepath.'cache/upload/');
			$new_name = zajLib::me()->basepath.'cache/upload/'.$pobj->id.'.tmp';
		// Verify new name is jailed
			zajLib::me()->file->file_check($new_name);
			move_uploaded_file($tmp_name, $new_name);
		// Now set and save
			$pobj->set('name', $orig_name);
			if($save_now_to_final_destination) $pobj->upload();
			else $pobj->temporary = true;
			$pobj->save();
			@unlink($tmp_name);
		return $pobj;
	}
}