<?php

/**
 * Handles the compilation of single source file.
 *
 * The compilation process includes syntax check, parsing, and writing to any potential destinations via the compile session object.
 *
 * @package Template
 * @subpackage CompilingBackend
 *
 * Typically used read-only properties:
 * @property zajCompileSource|boolean $child_source
 * @property string $file_path
 * @property string $requested_path
 * @property integer $line_number
 * @property integer $block_level
  */
class zajCompileSource {
	/** @var zajLib */
	public $zajlib;				// object - pointer to global zajlib object

	// instance variables
		private $file;					// file pointer - source file
		private $current_line = '';		// string - contains the current line's string (or part of it)
		private $current_tag = '';		// string - contains the current tag being processed
		private $line_number = 0;		// int - number of the current line in this file
		private $file_path;				// string - full path to the source file
		private $requested_path;		// string - the relative path to the source file

	/**
	 * @var array An array of all zajCompileBlock objects in this source.
	 */
	private $blocks = [];			// array - an array of all zajCompileBlock objects in this source

	/**
	 * @var int The current block level.
	 */
	private $block_level = 0;		// int - current level of block hierarchy

	/**
	 * @var zajCompileBlock The current block
	 */
	private $current_block;			// zajCompileBlock - the current block

	/**
	 * @var zajCompileSession
	 */
	private $compile_session;


		private $hierarchy = [];		// array - stores info about open/close tags
		private $level = 0;				// int - current level of tag hierarchy
		private $app_level;				// string - the app level (plugin) at which this source is located
		private $child_source = false;	// zajCompileSource|boolean - child source object

	// these are set by the template tags @todo move to methods!
		public $is_extension = false;	// boolean - true if this source has an extends tag and thus is an extension of something else in this session
		public $extended = false;		// boolean - true if this source is extended in this session
		public $parent_requested = false;// string|boolean - relative path of my parent source. false if none.
		public $parent_path = false;	// string|boolean - full path of my parent template. false if none.
		public $parent_level = false;	// string|boolean - plugin level of my parent template. false if none.

	// settings
		private $paused = false;		// boolean - if paused, reading from this file will not occur
		private $parse = true;			// boolean - if parse is true, the line will be parsed before writing
		private $resume_at = '';		// string - when parse is turned off, you can set to resume at a certain tag
		private static $paths = array(			// array - array of paths to search, in order of preference
			'local'=>'app/view/',
			'plugin_apps'=>true,				// boolean - set this to false if you don't want to check for app plugin views
			'system'=>'system/app/view/',
			'system_apps'=>true,				// boolean - when true, system apps will be loaded (don't change this unless you know what you're doing!)
			'temp_block'=>'cache/temp/',
			'compiled'=>'cache/view/',
		);

	/**
	 * @param string $source_file A relative path to the source.
	 * @param zajCompileSession $session The current session.
	 * @param string|bool $ignore_app_level The name of app up to which all path levels should be ignored. Setting this to false will ignore nothing.
	 * @param zajCompileSource|bool $child_source The zajCompileSource object of a child template, if one exists.
	 */
	public function __construct($source_file, &$session, $ignore_app_level = false, $child_source = false){
		// set zajlib & debug stats
		$this->compile_session = $session;
		$this->zajlib =& zajLib::me();

		// jail the source path
		$source_file = trim($source_file, '/');
		$this->zajlib->file->file_check($source_file);

		// does it exist?
		$app_level_and_path = $this->check_app_levels($source_file, $ignore_app_level);
		if($app_level_and_path === false){
			if($ignore_app_level === false) return $this->zajlib->error("Template file $source_file could not be found anywhere.");
			else return $this->zajlib->error("Template file $source_file could not be found in app hierarchy levels below $ignore_app_level.");
		}

		// set my child. also if i have one, then I am extended.
		$this->child_source = $child_source;
		if($child_source !== false) $this->extended = true;

		// open file
		$this->app_level = $app_level_and_path[1];
		$this->requested_path = $source_file;
		$this->file_path = $app_level_and_path[0];

		zajCompileSession::verbose("Adding <code>$this->file_path</code> to compile sources.");

		return $this->file = fopen($this->file_path, 'r');
	}

	/**
	 * Read and parse
	 **/
	public function compile(){
		// while not end of file
			if($this->eof()) return $this->zajlib->error("tried reading at eof. please use eof method!");
		// pause
			if($this->paused) return '';
		// read a line from the file if current_line is empty
			if($this->current_line == ''){
				$this->current_line = fgets($this->file);
				$this->line_number++;
			}
		// check for php related stuff (but only if parsing is on)
			if($this->parse){
				// disable PHP tags
					if(preg_match("/<[\?%](php| |\\n)+/", $this->current_line) > 0) return $this->zajlib->error("cannot use PHP or ASP tags in template file ($this->file_path): &lt;?, &lt;?php, or &lt;% are all forbidden.");
				// now replace any other codes in line (<?xml for example)
					$this->current_line = preg_replace("/(<[\?%][A-z]*)/", '<?php print "${1}"; ?>', $this->current_line);
			}
		// try to match a tag
			$currentmatches = '';
			if(
				// if tag matched and parseing is on
					(preg_match(regexp_zaj_tag, $this->current_line, $currentmatches, PREG_OFFSET_CAPTURE) && $this->parse)
				// OR if parseing is off but match is equal to resume_at
					|| (!$this->parse && !empty($this->resume_at) && $currentmatches[3][0] == '%}' && $currentmatches[1][0] == $this->resume_at)
			){
				// check for syntax error
					if($currentmatches[3][0] != '%}' && $currentmatches[3][0] != '}}') $this->warning('line terminated before end of tag/variable!');
				// set my basics
					$full = trim($currentmatches[0][0], '{} ');
					$element_name = $currentmatches[1][0];
					$parameters = $currentmatches[2][0];
					$this->current_tag = $element_name;
				// calculate new offset
					$my_offset = $currentmatches[3][1] + 2;
				// write everything up to this tag to file
					$this->write(substr($this->current_line, 0, $my_offset - strlen($currentmatches[0][0])));
				// seek back to end of tag
					$new_offset = (strlen($this->current_line) - $my_offset)*-1;
				// if end of line
					if($new_offset >= 0) $this->current_line = '';
				// else, still some chars left
					else $this->current_line = substr($this->current_line, $new_offset);
				// is this a tag or variable? write either
					if($currentmatches[3][0] == '%}') zajCompileTag::compile($element_name, $parameters, $this);
					else zajCompileVariable::compile($full, $this);
			}
		// not tags/variables on this line, so just write it plain to the file
			else{
				// write current line
					$this->write($this->current_line);
				// reset current line
					$this->current_line = '';
			}
			return true;
	}
	/**
	 * Write a single line of content to each destination.
	 * @param string $content The content to be written to each file.
	 * @return boolean Always returns true.
	 **/
	public function write($content){
		return $this->zajlib->compile->write($content);
	}

	////////////////////////////////////////////////////////
	// Settings and parameters
	////////////////////////////////////////////////////////
	public function eof(){
		return (!$this->current_line && feof($this->file));
	}
	public function pause(){
		zajCompileSession::verbose("Pausing <code>$this->file_path</code> compile source.");
		$this->paused = true;
		return true;
	}
	public function resume(){
		zajCompileSession::verbose("Resuming <code>$this->file_path</code> compile source.");
		$this->paused = false;
		return true;
	}
	public function set_parse($new_setting, $resume_at=''){
		$this->parse = $new_setting;
		$this->resume_at = $resume_at;
	}

	/**
	 * Returns the requested source path which is relative to the plugin/view/etc. folder.
	 **/
	public function get_requested_path(){ return $this->requested_path; }

	////////////////////////////////////////////////////////
	// Blocks
	////////////////////////////////////////////////////////

	/**
	 * Add a block to this source.
	 * @param string $block_name The name of the block.
	 * @param zajCompileBlock $parent The parent block.
	 * @todo Convert warning to error instead of warning. Non-fatal for now because of backwards compatibility.
	 * @return zajCompileBlock The new block object.
	 */
	public function add_block($block_name){
		if($this->has_block($block_name)) $this->warning("The block $block_name was found more than once.");

		// Increment block level
		$this->current_block = $this->blocks[$block_name] = new zajCompileBlock($block_name, $this, $this->current_block, $this->block_level);
		$this->block_level++;

		return $this->current_block;
	}

	/**
	 * Check for an existing block
	 * @param string $block_name The name of the block.
	 * @param boolean $recursive Check all children recursively.
	 * @return boolean Returns true if the block exists for this source.
	 */
	public function has_block($block_name, $recursive = false){
		// Check me
		$i_have_block = array_key_exists($block_name, $this->blocks);
		if($i_have_block) return true;

		// Check children
		if($recursive && $this->child_source){
			return $this->child_source->has_block($block_name, true);
		}

		return false;
	}

	/**
	 * Returns an array of blocks used in this source.
	 * @return array An array of blocks used in this source.
	 */
	public function get_blocks(){
		return $this->blocks;
	}

	/**
	 * Returns the current block.
	 * @param string $name Return a block object by name. If left empty the current block will be returned.
	 * @param boolean $recursive If set to true, it will get the lowest-level block it can find. Defaults to false, in which case it returns the block object for the current source.
	 * @return zajCompileBlock|false The current block.
	 */
	public function get_block($name = "", $recursive = false){
		// If no name set, return current
		if(empty($name)) return $this->current_block;

		// If name set, get by name or return false if not exists
		if(!$this->has_block($name, true)) return false;
		else{
			// Set block object
			$block = $this->blocks[$name];

			// Recursively get (if needed)
			if($recursive && $this->child_source){
				$child_block = $this->child_source->get_block($name, true);
				if($child_block === false) return $block;
				else return $child_block;
			}
			else return $block;
		}

	}

	/**
	 * End a block in this source.
	 * @return zajCompileBlock The new current block (so the ended block's parent).
	 */
	public function end_block(){
		// Add the processed block
		$this->compile_session->add_processed_block($this->current_block);

		// Set the current block to my parent
		$this->current_block = $this->current_block->parent;
		$this->block_level--;

		return $this->current_block;
	}

	/**
	 * Get session of current source.
	 * @return zajCompileSession Returns the current source's session.
	 */
	public function get_session(){
		return $this->compile_session;
	}

	/**
	 * Am I the main source?
	 * @return boolean Returns true if I am the main source, false otherwise.
	 */
	public function am_i_the_main_source(){
		if(!$this->child_source) return true;
		else return false;
	}


	////////////////////////////////////////////////////////
	// Levels of hierarchy
	////////////////////////////////////////////////////////
	public function add_level($tag, $data){
		// add a level of hierarchy
			array_push($this->hierarchy, array(
				'tag'=>$tag,
				'data'=>$data,
			));
		// add one to level counter
			$this->level++;
		return true;
	}
	public function remove_level($tag){
		// remove a level of hierarchy
			list($start_tag, $data) = array_values(array_pop($this->hierarchy));
		// if tag mismatch
			if($tag != $start_tag) $this->error("Expecting $start_tag and found $tag end tag.");
		// remove one from level counter
			$this->level--;
		return $data;
	}
	public function get_level_data($tag){
		// get the last level of hierarchy
			list($start_tag, $data) = array_values(end($this->hierarchy));
		// if tag mismatch
			if($tag != $start_tag) $this->error("The tag $tag has to be nested within $start_tag tags.");
		return $data;
	}
	public function get_level_tag(){
		// get the last level of hierarchy
			list($start_tag, $data) = array_values(end($this->hierarchy));
		return $start_tag;
	}
	public function get_level(){
		return $this->level;
	}
	public function get_current_tag(){
		// returns the current tag being processed
		return $this->current_tag;
	}

	// Read-only access to variables!
	public function __get($name){
		return $this->$name;
	}

	////////////////////////////////////////////////////////
	// Error methods
	////////////////////////////////////////////////////////
	public function warning($message){
		$this->zajlib->warning("Template compile warning: $message (file: $this->file_path / line: $this->line_number)");
	}
	public function error($message){
		$this->zajlib->error("Template compile error: $message (file: $this->file_path / line: $this->line_number)", true);
		exit;
	}

	/**
	 * Check if template file exists in any of the paths. Returns path if yes, false if no.
	 * @param string $source_file The path to the source file.
	 * @param string|bool $ignore_app_level The name of app up to which all path levels should be ignored. Setting this to false will ignore nothing.
	 * @return array|boolean Returns an array with full path and app level or false if it does not exist.
	 **/
	public static function check_app_levels($source_file, $ignore_app_level = false){
		// run through all the paths
		foreach(zajCompileSource::$paths as $level=>$path){
			// if type is plugin_apps, then it is special!
				if($level == 'plugin_apps' && $path){
					// run through all of my registered plugin apps' views and return if one found!
						foreach(zajLib::me()->loaded_plugins as $plugin_app){
							$path = zajLib::me()->basepath.'plugins/'.$plugin_app.'/view/'.$source_file;
							// Return path if not ignoring and exists
								if(!$ignore_app_level && file_exists($path)) return [$path, $plugin_app];
							// Stop ignoring?
								if($ignore_app_level !== false && $ignore_app_level == $plugin_app) $ignore_app_level = false;
						}
				}
				elseif($level == 'system_apps' && $path){
					// run through all of my registered system apps' views and return if one found!
						foreach(zajLib::me()->zajconf['system_apps'] as $system_app){
							$path = zajLib::me()->basepath.'system/plugins/'.$system_app.'/view/'.$source_file;
							// Return path if not ignoring and exists
								if(!$ignore_app_level && file_exists($path)) return array($path, $system_app);
							// Stop ignoring?
								if($ignore_app_level !== false && $ignore_app_level == $system_app) $ignore_app_level = false;
						}
				}
				else{
					$path = zajLib::me()->basepath.$path.$source_file;
					// Return path if not ignoring and exists
						if(!$ignore_app_level && file_exists($path)) return array($path, $level);
					// Stop ignoring?
						if($ignore_app_level !== false && $ignore_app_level == $level) $ignore_app_level = false;
				}
		}
		// no existing files found
		return false;
	}

}