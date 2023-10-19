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

    /**
     * File pointer resource
     * @var mixed|false|resource
     */
    private mixed $file;

    /**
     * Path of the destination
     * @var string
     */
    private string $file_path;

    /**
     * true if file exists, writing to this file will not occur
     * @var bool
     */
    private bool $exists = false;

    /**
     * if paused, writing to this file will not occur
     * @var bool
     */
    private bool $paused = false;

    /**
     * if true, then this is a temporary file, delete on unset
     * @var bool|mixed
     */
    private bool $temporary;

	public function __construct(string $dest_file, bool $temporary = false){

		// jail the destination path
		$dest_file = trim($dest_file, '/');
		zajLib::me()->file->file_check($dest_file);

		// tmp or not?
		$this->temporary = $temporary;
		if($this->temporary) $subfolder = "temp";
		else $subfolder = "view";

		// check path
		$this->file_path = zajLib::me()->basepath.'cache/'.$subfolder.'/'.$dest_file.'.php';

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
		if(!$this->file) return zajLib::me()->error("could not open ($dest_file) for writing. does cache folder have write permissions?");
		return true;
	}

	public function write(string $content) : false|int {
		// if paused, just return OR if exists&temp
			if(($this->exists && $this->temporary) || $this->paused) return true;
		// write this to file
			if(OFW_COMPILE_VERBOSE && trim($content)) zajCompileSession::verbose("Writing <pre>$content</pre> to compile destination $this->file_path.");
			return fputs($this->file, $content);
	}

	public function pause() : bool {
		zajCompileSession::verbose("Pausing <code>$this->file_path</code> compile destination.");
		$this->paused = true;
		return true;
	}

	public function resume() : bool {
		zajCompileSession::verbose("Resuming <code>$this->file_path</code> compile destination.");
		$this->paused = false;
		return true;
	}

	public function unlink() : bool {
		// delete this file
			return unlink($this->file_path);
	}

	public function __destruct(){
		zajCompileSession::verbose("Stopping <code>$this->file_path</code> compile destination.");
		// close the file
			if($this->file) fclose($this->file);
		// if this is temporary, delete
			if($this->temporary) zajLib::me()->compile->unlink($this);
	}

	// Read-only access to variables!
	public function __get(string $name) : mixed {
		return $this->$name;
	}

}