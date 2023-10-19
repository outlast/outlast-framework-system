<?php
/**
 * File-system related methods for manipulating and getting information about files and folders.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

define("OFW_DOWNLOAD_DISABLED_EXTENSIONS", 'php,ini,exe,sh,cache,tmp');

class zajlib_file extends zajLibExtension {

	/**
	 * Cleans a path and makes sure that it is jailed to my project folder basepath. This can take full paths (within the basepath) or relatives to the basepath.
	 * @param string $path The path of the file either relative to the basepath, or a full path within the basepath.
	 * @param string $custom_error The error message to fail on if an incorrect path is found.
	 * @param boolean $add_trailing_slash If this is a folder, then the trailing slash is added after sanitization. Defaults to true.
	 * @param boolean $fatal_error If this is a true, fatal error will stop execution on failure. Defaults to true.
	 * @return string|boolean Returns the sanitized path relative to the basepath if successful. Fatal error if not. If parameter $fatal_error was set to false, then boolean false is returned for invalid path.
	 **/
	public function folder_check($path, $custom_error = "Invalid folder path given.", $add_trailing_slash = true, $fatal_error = true){
		// Save original
			$opath = $path;
		// First we need to get rid of our full path (if it exists) and trim /-es, \-es, and spaces.
			$path = str_ireplace($this->zajlib->basepath, '', $path);
		// Now make sure it is not still absolute url
			if($opath == $path && strpos($path, '/') === 0){
				if($fatal_error) return $this->zajlib->error($custom_error.' '.$opath);
				else return false;
			}
		// Now make sure no relative path stuff is tried
			if(strstr($path, '..') !== false){
				if($fatal_error) return $this->zajlib->error($custom_error.' '.$opath);
				else return false;
			}
		// If basepath was trimmed, now readd
			if($opath !== $path) $path = $this->zajlib->basepath.$path;
		// All is ok, so let's fix it up with single trailing slash!
			if($add_trailing_slash) $path = rtrim($path, '/').'/';
		return $path;
	}

	/**
	 * Same as {@link folder_check} except that this does not add a trailing slash by default (since it is for files).
	 * @param string $path The path of the file either relative to the basepath, or a full path within the basepath.
	 * @param string $custom_error The error message to fail on if an incorrect path is found.
	 * @param boolean $fatal_error If this is a true, fatal error will stop execution on failure. Defaults to true.
	 * @return string Returns the sanitized file path relative to the basepath if successful. Fatal error if not.
	 **/
	public function file_check($path, $custom_error = "Invalid file path given.", $fatal_error = true){
		return $this->folder_check($path, $custom_error, false, $fatal_error);
	}

	/**
	 * Sanitize a file name by removing all special characters.
	 * @param string $filename The original file name.
	 * @return string The sanitized file name.
	 **/
	public function sanitize($filename){
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		$filename = str_replace($special_chars, '', $filename);
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = $this->zajlib->lang->convert_eng($filename);
		$filename = trim($filename, '.-_');
		return $filename;
	}

	/**
	 * Given an absolute path it will return the relative path.
	 * @param string $absolute_path The absolute path to a file or folder.
	 * @return string|boolean The relative path to the file or folder or false if failed.
	 **/
	public function get_relative_path($absolute_path){
		// Check if exists
			if(!file_exists($absolute_path)) return $this->zajlib->warning("Tried to get relative path of non-existant file/folder.");
		// Check if dir
			$is_dir = is_dir($absolute_path);
		// Replace the basepath, strip slashes
			$relative_path = trim(str_ireplace($this->zajlib->basepath, '', $absolute_path), '/');
		// Add trailing slash if dir
			if($is_dir) return $relative_path.'/';
			else return $relative_path;
	}

	/**
	 * Returns an array of files found in this folder.
	 * @param string $absolute_path The absolute path to check for files.
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param string $mode Can be 'files' or 'folders'. This should not be used. If you want to check for folders, use {@link get_folders_in_dir()} instead.	 
	 * @param boolean $hidden_files_and_folders If set to true, hidden files and folders (beginning with .) will also be included. False by default.
	 * @param boolean|string $return_relative_to_input_path If set to true, the returned path will relative to the input path you set. You can also pass a string which will be prepended to the relative path.
	 * @return array An array of file paths within the directory.
	 **/
	public function get_files_in_dir($absolute_path, $recursive = false, $mode = "files", $hidden_files_and_folders = false, $return_relative_to_input_path = false){
		// Defaults
		$files = $folders = [];
		if($return_relative_to_input_path && is_string($return_relative_to_input_path)){
            $prepend_path = $return_relative_to_input_path;
		}
		else $prepend_path = "";

		// validate path
        $path = $this->folder_check($absolute_path, "Invalid path requested for get_files_in_dir.");

		// check if folder
        if(!is_dir($path)) return [];

		// else, fetch files in folder
			$dir = @opendir($path);
			while (false !== ($file = @readdir($dir))) { 
				if($file != "." && $file != ".." && ($hidden_files_and_folders || substr($file, 0, 1) != '.')){
					// if it is a file
					if(is_file($path."/".$file)){
						if($return_relative_to_input_path){
						    // Also set return relative to input path, because it is passed recursively and needs to be prepended in next round
						    $files[] = $return_relative_to_input_path = $prepend_path.trim($file, '/');
                        }
						else $files[] = rtrim($path, '/')."/".$file;
					}
					// if it is a dir
					elseif(is_dir($path."/".$file)){
						if($return_relative_to_input_path){
						    // Also set return relative to input path, because it is passed recursively and needs to be prepended in next round
						    $folders[] = $return_relative_to_input_path = $prepend_path.trim($file, '/').'/';
                        }
						else $folders[] = rtrim($path, '/')."/".$file.'/';
						// is recursive?
						if($recursive){
							$newfiles = $this->get_files_in_dir($path."/".$file, true, $mode, $hidden_files_and_folders, $return_relative_to_input_path);
							// add to files or folders
							if($mode == "files") $files = array_merge($files, $newfiles);
							else $folders = array_merge($folders, $newfiles);
						}
					}
				}
			}
		// decide what to return
			if($mode == "files") return $files;
			else return $folders;
	}

	/**
	 * Returns an array of files found in this folder. Same as get_files_in_dir, but path is relative to basepath.
	 * @param string $relative_path The path to check for files relative to the apps basepath.
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param string $mode Can be 'files' or 'folders'. This should not be used. If you want to check for folders, use {@link get_folders_in_dir()} instead.
	 * @param boolean $hidden_files_and_folders If set to true, hidden files and folders (beginning with .) will also be included. False by default.
	 * @param boolean $return_relative_to_input_path If set to true, the returned path will relative to the input path you set.
	 * @return array An array of absolute file paths within the directory.
	 */
	public function get_files($relative_path, $recursive = false, $mode = "files", $hidden_files_and_folders = false, $return_relative_to_input_path = false){
		// jail my path
		$this->folder_check($this->zajlib->basepath.$relative_path);
		return $this->get_files_in_dir($this->zajlib->basepath.$relative_path, $recursive, $mode, $hidden_files_and_folders, $return_relative_to_input_path);
	}

    /**
	 * Returns an array of files found in any app or plugin or system folder. Your path needs to be relative.
	 * @param string $relative_path The path to check for files relative to the app folders (not to basepath!).
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param string $scope Can be "full" (all folders - default) or any of the zajLibLoader::$app_path keys.
	 * @return array An array of file paths relative to basepath.
	 */
	public function get_all_files_in_app_folders($relative_path, $recursive = false, $scope = 'full'){
		// jail my path
		$this->folder_check($this->zajlib->basepath.$relative_path);

        // Get app paths
        $files = [];
        $app_paths = $this->zajlib->load->get_app_folder_paths($scope);
        foreach($app_paths as $app_path){
            $files = array_merge($files, $this->get_files($app_path.$relative_path, $recursive, "files", false, true));
        }
        return $files;
	}

    /**
	 * Returns an array of folders found in any app or plugin or system folder. Your path needs to be relative.
	 * @param string $relative_path The path to check for folders relative to the app folders (not to basepath!).
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param string $scope Can be "full" (all folders - default) or any of the zajLibLoader::$app_path keys.
	 * @return array An array of file paths relative to basepath.
	 */
	public function get_all_folders_in_app_folders($relative_path, $recursive = false, $scope = 'full'){
		// jail my path
		$this->folder_check($this->zajlib->basepath.$relative_path);

        // Get app paths
        $folders = [];
        $app_paths = $this->zajlib->load->get_app_folder_paths($scope);
        foreach($app_paths as $app_path){
            $folders = array_merge($folders, $this->get_folders($app_path.$relative_path, $recursive, false, true));
        }
        return $folders;
	}

    /**
	 * Returns an array of versions found for a specific file in any app or plugin or system folder. Your path needs to be relative.
	 * @param string $relative_path The path to check for files relative to the app folders (not to basepath!).
	 * @param string $scope Can be "full" (all folders - default) or any of the zajLibLoader::$app_path keys.
	 * @return array An array of file paths relative to basepath.
	 */
    public function get_all_versions_of_file_in_app_folders($relative_path, $scope = 'full'){
		// jail my path
		$this->folder_check($this->zajlib->basepath.$relative_path);

        // Get app paths
        $files = [];
        $app_paths = $this->zajlib->load->get_app_folder_paths($scope);
        foreach($app_paths as $app_path){
            if(file_exists($this->zajlib->basepath.$app_path.$relative_path)){
                $files[] = $app_path.$relative_path;
            }
        }
        return $files;
    }

	/**
	 * Returns an array of folders found in this folder. If set to recursive, the folder paths will be returned relative to the specified path.
	 * @param string $absolute_path The path to check for folders.
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param boolean $hidden_files_and_folders If set to true, hidden files and folders (beginning with .) will also be included. False by default.
	 * @param boolean $return_relative_to_input_path If set to true, the returned path will relative to the input path you set.
	 * @return array An array of folder paths within the directory.
	 **/
	public function get_folders_in_dir($absolute_path, $recursive = false, $hidden_files_and_folders=false, $return_relative_to_input_path = false){
		$folders = $this->get_files_in_dir($absolute_path, $recursive, "folders", $hidden_files_and_folders, $return_relative_to_input_path);
		return $folders;
	}

	/**
	 * Returns an array of folders found in this folder. If set to recursive, the folder paths will be returned relative to the specified path. Same as get_folders_in_dir, but relative to basepath.
	 * @param string $relative_path The path to check for folders.
	 * @param boolean $recursive If set to true, subfolders will also be checked. False by default.
	 * @param boolean $hidden_files_and_folders If set to true, hidden files and folders (beginning with .) will also be included. False by default.
	 * @param boolean $return_relative_to_input_path If set to true, the returned path will relative to the input path you set.
	 * @return array An array of absolute folder paths within the directory.
	 **/
	public function get_folders($relative_path, $recursive = false, $hidden_files_and_folders = false, $return_relative_to_input_path = false){
		// jail my path
		$this->folder_check($this->zajlib->basepath.$relative_path);
		return $this->get_folders_in_dir($this->zajlib->basepath.$relative_path, $recursive, $hidden_files_and_folders, $return_relative_to_input_path);
	}
	
	/**
	 * Returns the extension section of the file.
	 * @param string $filename The full filename, including extension.
	 * @return string The file's extension
	 **/
	public function get_extension($filename){
		$path_parts = pathinfo($filename);
		$path_parts['extension'] = mb_strtolower($path_parts['extension']);
		return $path_parts['extension'];
	}
	
	/**
	 * Creates folders and subfolders for the specified file name.
	 * @param string $filename The full filename, including extension.
	 * @return bool
	 */
	public function create_path_for($filename){
		// get folder
			$path = dirname($filename);
		// validate path
			$path = $this->folder_check($path, "Invalid path requested for create_path_for.");
		// check if exists
            if (!file_exists($path)) {
                // all ok, create
                mkdir($path, 0777, true);
                return true;
            } else {
                return false;
            }
	}

	/**
	 * Checks if the extension is valid.
	 * @param string $filename The full filename, including extension.
	 * @param array|string $extORextarray A single extension (string) or an array of extensions (array of strings). Defaults to an array of image extensions (jpg, jpeg, png, gif)
	 * @return boolean True if the file extension is valid according to the specified list.
	 */
	public function is_correct_extension($filename, $extORextarray = ""){
		// set default (for images)
			if($extORextarray == "") $extORextarray = array("jpg", "jpeg", "png", "gif");
		// now check to see if not array
			if(!is_array($extORextarray)) $extORextarray = array($extORextarray);
		// get file extension
			$ext = $this->get_extension($filename);
		// is it in the array?
			return in_array($ext, $extORextarray);
	}

	/**
	 * Generate a hierarchy of subfolders based on the timestamp. So for example: 2010/Jan/3/example.txt could be created.
	 * @param string $basepath The base path of the file (this will not use the global base path!)
	 * @param string $filename The full filename, including extension.
	 * @param integer $timestamp The UNIX time stamp to use for generating the folders. The current timestamp will be used by default.
	 * @param boolean $create_folders_if_they_dont_exist If set to true, the folders will not only be calculated, but also created.
	 * @param bool $include_day Whether to include the day level as well.
	 * @return string The new full path of the file.
	 */
	public function get_time_path($basepath, $filename, $timestamp = 0, $create_folders_if_they_dont_exist = true, $include_day=true){
		// Validate path
			$basepath = $this->folder_check($basepath, "Invalid path requested for get_time_path.");
		// Validate file
			$filename = $this->file_check($filename, "Invalid file requested for get_time_path.");
		// defaults and error checks
			if($basepath == "") return false;
			if($timestamp == 0) $timestamp = time();
		// Get timestamp based subfolder: /year/month/
			$timedata = localtime($timestamp, true);
			$sub1 = $timedata["tm_year"]+1900;
			$sub2 = date("m", $timestamp);
			if($include_day) $sub3 = date("d", $timestamp);
		// Generate full string and return
			$fullpath = $basepath."/".$sub1."/".$sub2."/".$sub3."/".$filename;
		// Make sure folders exist...if not, create...(unless not needed)
			if($create_folders_if_they_dont_exist){
				if(!$filename) {
                    if(!file_exists($fullpath)) {
                        mkdir($fullpath, 0777, true);
                    }
                }
				else $this->create_path_for($fullpath);
			}		
		// Return the full path	
		return $fullpath;
	}

	/**
	 * Generate a hierarchy of subfolders based on the file name. So example.txt at $level 3 will generate a path of e/x/a/example.txt
	 * @param string $basepath The base path of the file (this will not use the global base path!)
	 * @param string $filename The full filename, including extension.
	 * @param boolean $create_folders_if_they_dont_exist If set to true, the folders will not only be calculated, but also created.
	 * @param integer $level The number of levels of subfolders to calculate with.
	 * @return string The new full path of the file.
	 **/
	public function get_id_path($basepath, $filename, $create_folders_if_they_dont_exist = true, $level = 10){
		// Validate path
			$basepath = $this->folder_check($basepath, "Invalid path requested for get_id_path.");
		// Validate file
			$filename = $this->file_check($filename, "Invalid file requested for get_id_path.");
		// defaults and error checks
			if($basepath == "") return false;		
		// get filename and parts
			$pathdata = pathinfo($filename);
			$parts = str_split($pathdata['filename']);
		// now generate folder structure
			// remove the ending (it will get the full filename)
				$parts = array_slice($parts,0,$level);
			// join into path
				$folder_structure = implode("/",$parts);
		// generate new path
			$new_folder = $basepath."/".$folder_structure."/";
			$new_full_path = $new_folder.$filename;
		// create folders?
			// TODO: review permissions!
			if($create_folders_if_they_dont_exist){
				if(!$filename && !file_exists($new_folder)) {
                    mkdir($new_folder, 0777, true);
                }
				else $this->create_path_for($new_full_path);
			}
		// done.
		return $new_full_path;
	}
		
	/**
	 * Get mime-type of file based on the extension. This is not too reliable, since it takes the file name and not file content as the key.
	 * @param string $filename The full filename, including extension.
	 * @param string|boolean $file_path The relative file path to the project base path. This is optional. It will be used to check the actual file as well if the mime based on extension fails.
	 * @return string The mime type of the file
	 **/
	public function get_mime_type($filename, $file_path = false){
		// Validate path
			$filename = $this->file_check($filename, "Invalid file requested for get_mime_type.", false);
		// Define mime types
	        $mime_types = array(
	            'txt' => 'text/plain',
	            'htm' => 'text/html',
	            'html' => 'text/html',
	            'php' => 'text/html',
	            'css' => 'text/css',
	            'js' => 'application/javascript',
	            'json' => 'application/json',
	            'xml' => 'application/xml',
	            'swf' => 'application/x-shockwave-flash',
	            'flv' => 'video/x-flv',
	
	            // images
	            'png' => 'image/png',
	            'jpe' => 'image/jpeg',
	            'jpeg' => 'image/jpeg',
	            'jpg' => 'image/jpeg',
	            'gif' => 'image/gif',
	            'bmp' => 'image/bmp',
	            'ico' => 'image/vnd.microsoft.icon',
	            'tiff' => 'image/tiff',
	            'tif' => 'image/tiff',
	            'svg' => 'image/svg+xml',
	            'svgz' => 'image/svg+xml',
	
	            // archives
	            'zip' => 'application/zip',
	            'rar' => 'application/x-rar-compressed',
	            'exe' => 'application/x-msdownload',
	            'msi' => 'application/x-msdownload',
	            'cab' => 'application/vnd.ms-cab-compressed',
	
	            // audio/video
	            'mp3' => 'audio/mpeg',
	            'qt' => 'video/quicktime',
	            'mov' => 'video/quicktime',
	
	            // adobe
	            'pdf' => 'application/pdf',
	            'psd' => 'image/vnd.adobe.photoshop',
	            'ai' => 'application/postscript',
	            'eps' => 'application/postscript',
	            'ps' => 'application/postscript',
	
	            // ms office
	            'doc' => 'application/msword',
	            'rtf' => 'application/rtf',
	            'xls' => 'application/vnd.ms-excel',
	            'ppt' => 'application/vnd.ms-powerpoint',
	
	            // open office
	            'odt' => 'application/vnd.oasis.opendocument.text',
	            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	        );
	
			$ext = $this->get_extension($filename);

	        if(array_key_exists($ext, $mime_types)) {
	            return $mime_types[$ext];
	        }
	        elseif(function_exists('finfo_open') && !empty($file_path)) {
	            $finfo = finfo_open(FILEINFO_MIME);
	            $mimetype = finfo_file($finfo, $this->zajlib->basepath.$file_path);
	            finfo_close($finfo);
	            return $mimetype;
	        }
	        else{
	            return 'application/octet-stream';
	        }
	}

	/**
	 * Download a file specified by relative path. There are some checks here, but be careful to sanitize the input as this is a potentially dangerous function.
	 * @param string $file_path The relative file path to the project base path.
	 * @param string|boolean $download_name If specified, the file will download with this name.
	 * @param string|boolean $mime_type The mime type for the file. If not given, it will try to detect it automatically.
	 * @return boolean|array There is no return value and execution stop here because the file is returned. Tests return an array.
	 */
	public function download($file_path, $download_name = false, $mime_type = false){
		// check file
			$this->file_check($file_path);
		// disable some specific files
			$ext = $this->get_extension($file_path);
			if(in_array($ext, explode(',', OFW_DOWNLOAD_DISABLED_EXTENSIONS))){
				return $this->zajlib->error('Tried to download disabled extension '.$ext);
			}
		// add basepath
			$file_path = $this->zajlib->basepath.$file_path;
		// detect mime type if not given
			if($mime_type === false) $mime_type = $this->get_mime_type($file_path);
		// use file name as download name if not given
			if($download_name === false) $download_name = basename($file_path);
		// return my data for testing
			if($this->zajlib->test->is_running()) return array($file_path, $download_name, $mime_type);
		// pass file thru to user via download
			header('Content-Type: '.$mime_type);
			header('Content-Length: '.filesize($file_path));
			header('Content-Disposition: attachment; filename="'.addslashes($download_name).'"');
			ob_clean();
			flush();
			readfile($file_path);
		exit();
	}

	/**
	 * Calculate download time (in seconds)
	 * @param integer $bytes The file size in bytes.
	 * @param integer $kbps The connection speed in Kbps (kiloBITS per second!)
	 * @return integer The time in seconds
	 **/
	public function download_time($bytes, $kbps=512)	{
		// convert kbps to Bytes Per Second
		$speed = ($kbps/8)*1024;
		// by seconds
		$time	= ceil($bytes / $speed);
		return (int)$time;
	}

	/**
	 * Format the value like a 'human-readable' file size (i.e. '13 KB', '4.1 MB', '102 bytes', etc).
	 * @param integer $bytes The number of bytes.
	 * @return string A human-readable string of file size.
	 **/
	public function file_size_format($bytes){
		if($bytes < 950) return $bytes.' bytes';
		elseif($bytes < 1024*1024) return number_format($bytes/1024, 1, '.', ' ').' KB';
		elseif($bytes < 1024*1024*1024) return number_format($bytes/1024/1024, 1, '.', ' ').' MB';
		elseif($bytes < 1024*1024*1024*1024) return number_format($bytes/1024/1024, 1, '.', ' ').' GB';
		elseif($bytes < 1024*1024*1024*1024*1024) return number_format($bytes/1024/1024, 1, '.', ' ').' TB';
		else return number_format($bytes/1024/1024, 0, '.', ' ').' TB';
	}

}