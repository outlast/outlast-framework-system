<?php

/**
 * A block in a particular source.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileBlock{

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
	 * @var boolean If no parent, then I am top level.
	 */
	private $top_level = false;

	/**
	 * zajCompileBlock constructor.
	 * @param zajCompileSource $source
	 * @param zajCompileBlock $parent
	 */
	public function __construct(&$source, &$parent){
		// set parent and element
			$this->source =& $source;
			if($parent){
				$this->parent =& $parent;
				$parent->set_child($this);
			}
			else $this->top_level = true;
		return true;
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