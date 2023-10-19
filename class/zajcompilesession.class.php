<?php
/**
 * Backend compile-related classes.
 * 
 * This file contains classes related to the template-compiling backend. You do not need to access these classes and methods directly;
 * you should use the compile() library instead. These classes ensure that tags, variables, parameters, and filters are processed,
 * the appropriate plugin files (tags, filters) are loaded, and the necessary functions are called.
 *
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Template
 * @subpackage CompilingBackend
 */

/**
 * Verbose mode - use only for debugging!
 */
define('OFW_COMPILE_VERBOSE', false);

/**
 * Regular expression to find a django-style tag.
 */
define('regexp_zaj_tag', "(\\{[%{][ ]*([[:alnum:]\'_#\\.]+)(.*?)([\\%}]}|\\n))");
/**
 * Regular expression to one filter.
 */
define('regexp_zaj_onefilter', '\|(([A-z]*)([ ]*(:)[ ]*)?)?(\'(.*?)[^\\\']\'|\"(.*?)[^\\\\\"]\"|[A-z.\-_0-9#]*)');
/**
 * Regular expression to one tag parameter (including filter support).
 */
define('regexp_zaj_oneparam', '(\'(.*?)\'|\"(.*?)\"|(<=|>=|!==|!=|===|==|=|>|<)|[A-z.\-_0-9#]*)('.regexp_zaj_onefilter.")*");
/**
 * Regular expression to one tag variable.
 */
define('regexp_zaj_variable', '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/');
/**
 * Regular expression to tag operator.
 */
define('regexp_zaj_operator', '/(<=|>=|!==|!=|===|==|=|>|<)/');

/**
 * Require my other files.
 */
require('zajcompileblock.class.php');
require('zajcompiledestination.class.php');
require('zajcompilesource.class.php');
require('zajcompileelement.class.php');
require('zajcompiletag.class.php');
require('zajcompilevariable.class.php');

/**
 * One compile session, which may include several source and destination files.
 * 
 * A compile session is the compilation of an entire tree of inherited, extended, included files. Individual blocks and insert tags will compile to their
 * own temporary files. The entire session is (in the end) combined into a single generated php file, which is stored in the cache folder.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileSession {
     // private
		/**
		 * @var array An array of zajCompileSource objects.
		 */
		private array $sources = [];

		/**
		 * @var array An array of zajCompileDestination objects representing destination files.
		 */
		private array $destinations = [];

		/**
		 * @var array An array of zajCompileDestination objects representing destination files to be unlinked (deleted) on completion.
		 */
		private array $unlinks = [];

		/**
		 * @var zajCompileSource The source which was requested originally.
		 */
		private zajCompileSource $main_source;

     // public
     	/**
		 * A unique id generated to identify this session.
		 * @var string
		 */
		public string $id;

		/**
		 * A list of blocks processed. Blocks are stored as paths relative to cache in the array keys.
		 * @var array
		 */
		private array $blocks_processed = [];
	
	/**
	 * Constructor for compile session. You should not create this object directly, but instead use the compile library.
	 *
	 * @param string $source_file Relative path of source file.
	 * @param string|boolean $destination_file Relative path of destination file. If not specified, the destination will be the same as the source, which is the preferred way of doing things. You should only specify this if you are customizing the template compilation process.
	 */
	public function __construct(string $source_file, string|bool $destination_file = false){
		// create id
			$this->id = uniqid("");
		// start a new destination
			if(!$destination_file) $this->add_destination($source_file);
			else $this->add_destination($destination_file);
		// start a new source
			$this->main_source = $this->add_source($source_file);
	}


	/**
	 * Starts the compilation session. You should not call methods of this object directly, but instead use the compile library.
	 *
	 * @return boolean True on success, false on failure.
	 */
	public function compile() : bool {
		// go!		
			$success = $this->go();
		// if not success, return false
			if(!$success) return false;
		// do i have any unlinks?
			foreach($this->unlinks as $uobj) $uobj->unlink();
		return true;
	}
	
	/**
	 * Compiles the currently selected source file.
	 *
	 * @access private
	 * @return boolean True on success, false on failure.
	 */
	private function go() : bool {
		// get current source
			$current_source = reset($this->sources);
		// unpause destination
			if($current_source->line_number == 0) $this->main_dest_paused(false);		
			else return false;
		// compile while i don't reach its eof
			zajCompileSession::verbose("Now compiling source $current_source->file_path");
			while(!$current_source->eof()) $current_source->compile();
			$current_source->close();
		// remove the source
			array_shift($this->sources);
		// now recursive if more sources left
			if(count($this->sources) > 0) return $this->go();
		return true;
	}

	/**
	 * Writes one line to each destination file.
	 *
	 * @return boolean Always returns true.
	 */
	public function write(string $content) : bool {
		foreach($this->destinations as $dest){
			$dest->write($content);
		}
		return true;
	}

	/**
	 * Inserts a file at current destination file location. The file will not be parsed.
	 * @param string $source_path Relative path of source file.
	 * @return boolean Always returns true.
	 */
	public function insert_file(string $source_path) : bool {
		// open file as source
			$source = new zajCompileSource($source_path, $this);
		// set not to parse
			$source->set_parse(false);
		// now compile
			while(!$source->eof()) $source->compile();
		    $source->close();
		return true;
	}


	/**
	 * Add a source to this compile session. You should not call methods of this object directly, but instead use the compile library.
	 * @param string $source_path Relative path of source file.
	 * @param string|bool $ignore_app_level The name of app up to which all path levels should be ignored. Setting this to false will ignore nothing.
	 * @param zajCompileSource|bool $child_source The zajCompileSource object of a child template, if one exists.
	 * @return boolean|zajCompileSource Returns the source object if the source was added, false if it was added earlier.
	 */
	public function add_source(string $source_path, string|bool $ignore_app_level = false, zajCompileSource|bool $child_source = false) : zajCompileSource|bool {
		if(!$this->is_source_added($ignore_app_level.$source_path)){
			$source = new zajCompileSource($source_path, $this, $ignore_app_level, $child_source, $this);
			$this->sources[$ignore_app_level.$source_path] = $source;
			return $source;
		}
		else return false;
	}

	/**
	 * Is the source path already
	 * @param string $source_path Relative path of source file.
	 * @return boolean Return true or false depending on if the file is already being compiled.
	 */
	public function is_source_added(string $source_path) : bool {
		return array_key_exists($source_path, $this->sources);
	}

	/**
	 * The number of sources added.
	 * @return integer Returns the number of sources.
	 */
	public function get_source_count() : int {
		return count($this->sources);
	}

	/**
	 * Gets the currently selected source file object.
	 *
	 * @return zajCompileSource
	 */
	public function get_current_source() : zajCompileSource {
		return reset($this->sources);
	}

	/**
	 * Gets the main destination object.
	 *
	 * @return zajCompileDestination
	 */
	public function get_destination() : zajCompileDestination {
		return reset($this->destinations);
	}

	/**
	 * Add a destination file to this compile session. You should not call methods of this object directly, but instead use the compile library.
	 *
	 * @param string $dest_path Relative path of destination file.
	 * @param boolean $temporary OPTIONAL. If true file will be deleted at the end of this session. Defaults to false.
	 * @return zajCompileDestination Returns the destination object.
	 */
	public function add_destination(string $dest_path, bool $temporary = false) : zajCompileDestination {
		if(!array_key_exists($dest_path, $this->destinations)){
			$this->destinations[$dest_path] = new zajCompileDestination($dest_path, $temporary);
		}
		return $this->destinations[$dest_path];
	}

	/**
	 * Remove a destination file to this compile session. You should not call methods of this object directly, but instead use the compile library.
	 *
	 * @param string $dest_path Relative path of destination file.
	 * @return boolean Always returns true.
	 */
	public function remove_destination(string $dest_path) : bool {
		unset($this->destinations[$dest_path]);
		return true;
	}

	/**
	 * Get destination by path and block.
	 *
	 * @param string $dest_path Relative path of destination file.
	 * @return zajCompileDestination|false Return false if not found or the object if found.
	 */
	public function get_destination_by_path(string $dest_path) : zajCompileDestination|bool {
		if(!array_key_exists($dest_path, $this->destinations)) return false;
		else return $this->destinations[$dest_path];
	}

	/**
	 * Pause all destination files. All writing will be ignored while pause is active.
	 *
	 * @return boolean Always returns true.
	 */
	public function pause_destinations() : bool {
		/** @var zajCompileDestination $dest */
		foreach($this->destinations as $dest) $dest->pause();
		return true;
	}

	/**
	 * Resume all destination files. All writing will be resumed.
	 *
	 * @return boolean Always returns true.
	 */
	public function resume_destinations() : bool {
		/** @var zajCompileDestination $dest */
		foreach($this->destinations as $dest) $dest->resume();
		return true;
	}

	/**
	 * Gets all of the current destinations.
	 *
	 * @return array
	 */
	public function get_destinations() : array {
		return $this->destinations;
	}

	/**
	 * Returns true if destinations are paused, false otherwise.
	 *
	 * @return boolean True or false, depending on whether destinations are currently paused.
	 */
	public function are_destinations_paused() : bool {
		return end($this->destinations)->paused;
	}

	/**
	 * Sets the pause status of the main destination. The main destination is the primary destination file which will contain the full php code in the end.
	 *
	 * As with other methods in this class, this is used internally by the system and should not be called directly.
	 *
	 * @param boolean $bool True if you want nothing to be written to the main file, false otherwise.
	 * @return void
	 */
	public function main_dest_paused(bool $bool) : void {
		if($bool) reset($this->destinations)->pause();
		else reset($this->destinations)->resume();
	}

	/**
	 * Returns true if main destination is paused, false otherwise.
	 *
	 * @return boolean True or false, depending on whether main destination is currently paused.
	 */
	public function is_main_dest_paused() : bool {
		return reset($this->destinations)->paused;
	}

	/**
	 * Sets a destination object to be deleted upon completion.
	 * @param zajCompileDestination $object Add to array of files to delete.
	 * @todo Shouldn't this be private?
	 * @return void
	 */
	public function unlink(zajCompileDestination $object) : void {
		// add to array of unlinks
			$this->unlinks[] = $object;
	}

	/**
	 * Add a processed block.
	 * @param zajCompileBlock $block The block object.
	 * @return boolean Will return true if the block was not yet processed, false if it was already.
	 */
	public function add_processed_block(zajCompileBlock $block) : bool {
		if(!$this->was_block_processed($block->name)){
			$this->blocks_processed[$block->name] = $block;
			return true;
		}
		return false;
	}

	/**
	 * Get a processed block.
	 * @param string $block_name The name of the block;
	 * @return zajCompileBlock The block object.
	 */
	public function get_processed_block(string $block_name) : zajCompileBlock {
		return $this->blocks_processed[$block_name];
	}

	/**
	 * Was block processed?
	 * @param string $block_name The name of the block.
	 * @return boolean True if processed, false if not.
	 */
	public function was_block_processed(string $block_name) : bool {
		return array_key_exists($block_name, $this->blocks_processed);
	}

	/**
	 * Get the main source, which is the source that was originally requested in the session.
	 * @return zajCompileSource The main source.
	 */
	public function get_main_source() : zajCompileSource {
		return $this->main_source;
	}

	/**
	 * Prints an on screen verbose message when verbosity is set to true.
	 * @param string $message The message to print on screen.
	 */
	public static function verbose(string $message) : void {
		if(OFW_COMPILE_VERBOSE) echo $message."<br/>";
	}

}
