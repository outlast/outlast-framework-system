<?php

/**
 * A block in a particular source.
 *
 * @package Template
 * @subpackage CompilingBackend
 *
 * @property string $name
 * @property zajCompileBlock $parent
 * @property zajCompileBlock $child
 * @property integer $level
 * @property zajCompileSource $source
 * @property boolean $overridden
 */
class zajCompileBlock{

	/**
	 * @var string The name of the block.
	 */
	private $name;

	/**
	 * @var zajCompileSource The source file that contains this block. This is a pointer.
	 */
	private $source;

	/**
	 * @var zajCompileBlock The parent is the block that is higher up in template inheritance (and is thus overwritten by me).
	 */
	private $parent;

	/**
	 * @var zajCompileBlock The child is the block that is lower in the template inheritance (and therefore overwrites me).
	 */
	private $child;

	/**
	 * @var integer The block level where 0 means top-level block.
	 */
	private $level = 0;

	/**
	 * @var array All the destinations.
	 */
	private $destinations = [];

	/**
	 * @var boolean This is true if this block is overridden by another.
	 */
	private $overridden = false;
	/**
	 * zajCompileBlock constructor.
	 * @param string $name The name of the block.
	 * @param zajCompileSource $source
	 * @param zajCompileBlock $parent A parent block.
	 * @param integer $level The block level where 0 means top-level block.
	 */
	public function __construct($name, &$source, &$parent, $level){

		// Validate block name (only a-z) (because the whole stucture is involved, this is a fatal error!)
		if(preg_match('/[a-z]{2,25}/', $name) <= 0) $source->error("Invalid block name given!");

		// Set the name
		$this->name = $name;

		// Set source and parent block
		$this->source = $source;
		if($parent){
			$this->parent = $parent;
			$parent->set_child($this);
			if($level == 0) $source->error("Tried to open block $name with a parent ({$this->parent->name}) at top level. This is a system error and should never happen.");
		}

		// Do any child sources define this block?
		if($this->source->child_source && $this->source->child_source->has_block($name, true)) $this->overridden = true;
		else $this->overridden = false;

		// Set block level
		$this->level = $level;

		return true;
	}

    /**
     * Get the file path of the block relative to the view folder.
	 * @param zajCompileSource|boolean $source The source object to use to generate the file name. Defaults to my own source.
	 * @return string The file name of the block cache.
     */
    public function get_cache_file_path($source = false){

		// Defaults to current source
		if($source === false) $source = $this->source;

        // Generate file name for permanent block store
		return '__block/'.$source->get_requested_path().'-'.$this->name.'.html';

    }

	/**
	 * Add a destination for this block.
	 * @param zajCompileSource|boolean $source The source object to use to generate the file name. Defaults to my own source.
	 * @return zajCompileDestination Return the destination object.
	 */
	public function add_destination($source = false){

		// Defaults to current source
		if($source === false) $source = $this->source;

		// Generate file name for permanent block store
		$file_name = $this->get_cache_file_path($source);

		// Add destination for my block cache file
		zajCompileSession::verbose("<ul><li>Starting new block cache for <code>{$this->name}</code> as {$file_name}.");
		$destination = $this->source->get_session()->add_destination($file_name);

		// Add to array and return
		$this->destinations[$file_name] = $destination;
		return $destination;
	}

	/**
	 * Pause all block cache destinations.
	 */
	public function pause_destinations(){
		zajCompileSession::verbose("Pausing block cache destinations for <code>{$this->name}</code>.</li></ul>");

		foreach($this->destinations as $file_name=>$dest){
			/** @var zajCompileDestination $dest */
			$dest->pause();
		}
	}

	/**
	 * Resume all block cache destinations.
	 */
	public function resume_destinations(){
		zajCompileSession::verbose("Resuming block cache destinations for <code>{$this->name}</code>.</li></ul>");

		foreach($this->destinations as $file_name=>$dest){
			/** @var zajCompileDestination $dest */
			$dest->resume();
		}
	}

	/**
	 * Remove destination. If destinations were added recursively, it will remove them recursively.
	 */
	public function remove_destinations(){
		zajCompileSession::verbose("Removing block cache for <code>{$this->name}</code>.</li></ul>");

		// Remove all (will only exist if added recursively)
		foreach($this->destinations as $file_name=>$dest){
			$this->source->get_session()->remove_destination($file_name);
		}
		$this->destinations = [];
	}

	/**
	 * Is this tag overridden?
	 * @param boolean $recursive If set to true, it will also be marked as overridden if any of my parents are overridden.
	 * @return boolean Returns true if overridden, false if not.
	 */
	public function is_overridden($recursive = false){
		// If overridden is true at any point, then it is true recursively
		if($this->overridden) return true;

		// Now return based on value
		if(!$recursive) return $this->overridden;
		else{
			// If no more parents, then we are top-level, return
			if(!$this->parent) return $this->overridden;
			else return $this->parent->is_overridden(true);
		}
	}

	/**
	 * Insert the block file in currently active destinations.
	 */
	public function insert(){

		// Generate file name for permanent block store
		$file_name = $this->get_cache_file_path($this->source);

		zajCompileSession::verbose("Inserting the block <code>{$this->name}</code> from ".$file_name);
		zajLib::me()->compile->insert_file($file_name.'.php');

	}

	/**
	 * Set the child.
	 * @param zajCompileBlock $child
	 */
	public function set_child($child){
		$this->child = $child;
	}

	/**
	 * Get private properties.
	 * @param string $name The name of the property.
	 * @return mixed Returns the value of the property.
	 */
	public function __get($name){
		return $this->$name;
	}

}