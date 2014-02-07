<?php

class Category extends zajModel {
	
	public static $fetch_order = 'ASC';
	public static $fetch_order_field = 'abc';


	public $hierarchy;
	
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$fields->name = zajDb::name();
			$fields->abc = zajDb::text();
			$fields->description = zajDb::text();
			$fields->url = zajDb::text();
			$fields->parentcategory = zajDb::manytoone('Category');
			$fields->friendlyurl = zajDb::text(255);

		// do not modify the line below!
			$fields = parent::__model(__CLASS__, $fields); return $fields;
	}
	/**
	 * Construction and required methods
	 */
	public function __construct($id = ""){ parent::__construct($id, __CLASS__); return true; }
	public static function __callStatic($name, $arguments){ array_unshift($arguments, __CLASS__); return call_user_func_array(array('parent', $name), $arguments); }

	///////////////////////////////////////////////////////////////
	// !Custom methods
	///////////////////////////////////////////////////////////////

	public function __afterFetch(){
		// Friendly url cache
			$this->friendlyurl = $this->data->friendlyurl;	
		// Generate hierarchy
			$this->hierarchy = array();
			$me = $this;
			while($me = $me->data->parentcategory) $this->hierarchy[] = (object) array('name'=>$me->name, 'friendlyurl'=>$me->friendlyurl, 'id'=>$me->id);
		// Cache my parent if exists!
			if($this->data->parentcategory){
				$this->parentcategoryid = $this->data->parentcategory->id;
				$this->parentcategoryname = $this->data->parentcategory->name;
			}
		// Recalculate all category counters
			$this->recalcCounters();
	}	
	public function __afterDelete(){
		$this->recalcCounters();	
	}
	
	/**
	 * Calculates the number of children I and all of my ascendent parents have. Sets $this->count.
	 * @return integer The count of children.
	 **/
	public function recalcCounters(){
		// Recalculate my children
			$this->count = Category::fetch()->filter('parentcategory', $this->id)->total;
		// Recalculate for my parent (recursive)
			if(is_object($this->data->parentcategory)){
				$this->data->parentcategory->recalcCounters();
				$this->data->parentcategory->cache();
			}
		return $this->count;
	}	

	/**
	 * Create a product object by friendly url.
	 **/
	public static function fetch_by_friendlyurl($friendlyurl){
		return Category::fetch()->filter('friendlyurl', $friendlyurl)->next();
	}


	public function __onSearch($fetcher){ return $fetcher; }
}