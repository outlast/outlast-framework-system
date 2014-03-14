<?php
/**
 * A class for storing hierarchical categories.
 * @property string $friendlyurl
 * @property array $hierarchy An array list of categories above me wher each element is an object with name, friendly url, id.
 * @property string $parentcategoryid
 * @property string $parentcategoryname
 * @property integer $child_count The number of children this category has.
 * @property boolean $featured
 * @property zajDataCategory $data
 */
class Category extends zajModel {

	// Change the default sorting behavior
	public static $fetch_order = 'ASC';
	public static $fetch_order_field = 'abc';

	public $hierarchy;
	
	///////////////////////////////////////////////////////////////
	// !Model design
	///////////////////////////////////////////////////////////////
	public static function __model(){	
		// define custom database fields
			$f = (object) array();
			$f->name = zajDb::name();
			$f->abc = zajDb::text();
			$f->photo = zajDb::photo();
			$f->description = zajDb::text();
			$f->featured = zajDb::boolean();
			$f->parentcategory = zajDb::manytoone('Category');
			$f->subcategories = zajDb::onetomany('Category', 'parentcategory');
			$f->friendlyurl = zajDb::text(255);

		// do not modify the line below!
			$f = parent::__model(__CLASS__, $f); return $f;
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
		// The count
			$this->child_count = $this->recalc_counters();
		// Other fields
			$this->featured = $this->data->featured;
	}
	public function __afterDelete(){
		$this->recalc_counters();
	}
	
	/**
	 * Calculates the number of children I and all of my ascendent parents have. Sets $this->count.
	 * @return integer The count of children.
	 **/
	public function recalc_counters(){
		// Recalculate my children
			$this->child_count = Category::fetch()->filter('parentcategory', $this->id)->total;
		// Recalculate for my parent (recursive)
			if(is_object($this->data->parentcategory)){
				$this->data->parentcategory->recalc_counters();
				$this->data->parentcategory->cache();
			}
		return $this->child_count;
	}	

	/**
	 * Create a product object by friendly url.
	 **/
	public static function fetch_by_friendlyurl($friendlyurl){
		return Category::fetch()->filter('friendlyurl', $friendlyurl)->next();
	}

	/**
	 * Categories are completely public by default.
	 * @param zajFetcher $fetcher
	 * @return zajFetcher
	 */
	public function __onSearch($fetcher){ return $fetcher; }
}