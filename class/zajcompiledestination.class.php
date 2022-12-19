<?php

/**
 * Handles a compile destination file.
 *
 * This handles writing to the file and deleting temporary files after use.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileDestination {
	private $zajlib;				// object - pointer to global zajlib object

	// instance variables
		private $file;					// file pointer - source file
		private $line_number = 0;		// int - number of the current line in this file
		private $file_path;				// file pointer - source file

	// controls
		private $exists = false;		// boolean - true if file exists, writing to this file will not occur
		private $paused = false;		// boolean - if paused, writing to this file will not occur
		private $temporary = false;		// boolean - if true, then this is a temporary file, delete on unset

	public function __construct($dest_file, &$zajlib, $temporary = false){
		// set zajlib & debug stats
		$this->zajlib =& $zajlib;

		// jail the destination path
		$dest_file = trim($dest_file, '/');
		$this->zajlib->file->file_check($dest_file);

		// tmp or not?
		$this->temporary = $temporary;
		if($this->temporary) $subfolder = "temp";
		else $subfolder = "view";

		// check path
		$this->file_path = $this->zajlib->basepath.'cache/'.$subfolder.'/'.$dest_file.'.php';

		zajCompileSession::verbose("Adding <code>$this->file_path</code> to compile destinations.");

		// does it exist...temporary files are not recreated
		if(file_exists($this->file_path)){
			$this->exists = true;
			if($this->temporary) return false;
		}

		// open the cache file, create folders (if needed)
        $directory = dirname($this->file_path);
        if(!file_exists($directory)){
            mkdir($directory, 0777, true);
        }
		$this->file = fopen($this->file_path, 'w');

		// did it fail?
		if(!$this->file) return $this->zajlib->error("could not open ($dest_file) for writing. does cache folder have write permissions?");
		return true;
	}

	public function write($content){
		// if paused, just return OR if exists&temp
			if(($this->exists && $this->temporary) || $this->paused) return true;
		// write this to file
			if(OFW_COMPILE_VERBOSE && trim($content)) zajCompileSession::verbose("Writing <pre>$content</pre> to compile destination $this->file_path.");
			return fputs($this->file, $content);
	}

	public function pause(){
		zajCompileSession::verbose("Pausing <code>$this->file_path</code> compile destination.");
		$this->paused = true;
		return true;
	}

	public function resume(){
		zajCompileSession::verbose("Resuming <code>$this->file_path</code> compile destination.");
		$this->paused = false;
		return true;
	}

	public function unlink(){
		// delete this file
			return @unlink($this->file_path);
	}

	public function __destruct(){
		zajCompileSession::verbose("Stopping <code>$this->file_path</code> compile destination.");
		// close the file
			if($this->file) fclose($this->file);
		// if this is temporary, delete
			if($this->temporary) $this->zajlib->compile->unlink($this);
	}

	// Read-only access to variables!
	public function __get($name){
		return $this->$name;
	}

}