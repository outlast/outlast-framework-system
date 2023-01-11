<?php

/**
 * A block in a particular source.
 *
 * @package Template
 * @subpackage CompilingBackend
 *
 * @property string $name
 * @property zajCompileBlock $parent
 * @property array $children
 * @property integer $level
 * @property zajCompileSource $source
 * @property boolean $overridden
 */
class zajCompileBlock {

	/**
	 * @var string The name of the block.
	 */
	private string $name;

	/**
	 * @var zajCompileSource The source file that contains this block. This is a pointer.
	 */
	private zajCompileSource $source;

	/**
	 * @var ?zajCompileBlock The parent is the block that contains me.
	 */
	private ?zajCompileBlock $parent;

	/**
	 * @var array Array of zajCompileBlock items with all my child blocks.
	 */
	private array $children = [];

	/**
	 * @var integer The block level where 0 means top-level block.
	 */
	private int $level = 0;

	/**
	 * @var array All the destinations.
	 */
	private array $destinations = [];

	/**
	 * @var boolean This is true if this block is overridden by another.
	 */
	private bool $overridden = false;
	/**
	 * zajCompileBlock constructor.
	 * @param string $name The name of the block.
	 * @param zajCompileSource $source
	 * @param ?zajCompileBlock $parent A parent block.
	 * @param integer $level The block level where 0 means top-level block.
	 */
	public function __construct(string $name, zajCompileSource &$source, ?zajCompileBlock &$parent, int $level){

		// Validate block name (only a-z) (because the whole stucture is involved, this is a fatal error!)
		if(preg_match('/[a-z]{2,25}/', $name) <= 0) $source->error("Invalid block name given!");

		// Set the name
		$this->name = $name;

		// Set source and parent block
		$this->source = $source;
        $this->parent = $parent;
		if($parent){
			$parent->add_child($this);
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
    public function get_cache_file_path(zajCompileSource|bool $source = false): string {

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
	public function add_destination(zajCompileSource|bool $source = false): zajCompileDestination {

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
	 * @param boolean $recursive If set to true, pause will be peformed for parents.
	 */
	public function pause_destinations(bool $recursive = false): void {
		zajCompileSession::verbose("Pausing block cache destinations for <code>{$this->name}</code>.</li></ul>");

        // Pause each
		foreach($this->destinations as $file_name=>$dest){
			/** @var zajCompileDestination $dest */
			$dest->pause();
		}

		// Recursive?
        if($recursive) $this->parent?->pause_destinations(true);
	}

	/**
	 * Resume all block cache destinations.
	 * @param boolean $recursive If set to true, resume will be peformed for parents.
	 */
	public function resume_destinations(bool $recursive = false): void {
		zajCompileSession::verbose("Resuming block cache destinations for <code>{$this->name}</code>.</li></ul>");

		foreach($this->destinations as $file_name=>$dest){
			/** @var zajCompileDestination $dest */
			$dest->resume();
		}

		// Recursive?
        if($recursive) $this->parent?->resume_destinations(true);
	}

	/**
	 * Remove destination. If destinations were added recursively, it will remove them recursively.
	 */
	public function remove_destinations() : void {
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
	public function is_overridden(bool $recursive = false) : bool {
		// If overridden is true at any point, then it is true recursively
		if($this->overridden) return true;

		// Now return based on value
		if(!$recursive) return $this->overridden;
		else{
			// If no more parents, then we are top-level, return
			return $this->parent?->is_overridden(true) ?? $this->overridden;
		}
	}

	/**
	 * Insert the block file in currently active destinations.
	 */
	public function insert() : void {

		// Generate file name for permanent block store
		$file_name = $this->get_cache_file_path($this->source);

		zajCompileSession::verbose("Inserting the block <code>{$this->name}</code> from ".$file_name);
		zajLib::me()->compile->insert_file($file_name.'.php');

	}

	/**
	 * Set the child.
	 * @param zajCompileBlock $child
	 */
	public function add_child(zajCompileBlock $child) : void {
		$this->children[] = $child;
	}

	/**
	 * Get private properties.
	 * @param string $name The name of the property.
	 * @return mixed Returns the value of the property.
	 */
	public function __get(string $name) : mixed {
        return $this->$name;
	}

}