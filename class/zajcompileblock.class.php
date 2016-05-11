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
	 * @var string The file name of the block cache.
	 */
	private $file_name;

	/**
	 * @var array All the destinations.
	 */
	private $destinations = [];

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

		// Set block level
		$this->level = $level;

		return true;
	}

	/**
	 * Add a destination for this block.
	 * @param boolean $recursive Destination was added recursively.
	 * @return string Return the file name for my destination.
	 */
	public function add_destination($recursive = false){
		// Generate file name for permanent block store
		$this->file_name = '__block/'.$this->source->get_requested_path().'-'.$this->name.'.html';

		// Add destination for my block cache file
		zajCompileSession::verbose("<ul><li>Starting new block cache for <code>{$this->name}</code>.");
		zajLib::me()->compile->add_destination($this->file_name);

		if($recursive){
			// Add to my child source if it does not have its own block already
			$child_source = $this->source->child_source;
			if($child_source && !$child_source->has_block($this->name)){
				// Add the block and destination
				/** @var zajCompileBlock $child_block */
				$child_block = $child_source->add_block($this->name);
				$this->destinations[] = $child_block->add_destination(true);
			}
		}

		return $this->file_name;
	}

	/**
	 * Remove destination. If destinations were added recursively, it will remove them recursively.
	 */
	public function remove_destinations(){
		zajLib::me()->compile->remove_destination($this->file_name);

		// Remove all (will only exist if added recursively)
		foreach($this->destinations as $dest){
			zajLib::me()->compile->remove_destination($dest);
		}
		$this->destinations = [];
	}

	/**
	 * Insert the block in currently active destinations.
	 */
	public function insert(){
		zajLib::me()->compile->insert_file($this->file_name.'.php');
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