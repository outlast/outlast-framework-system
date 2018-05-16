<?php
/**
 * The fetcher class.
 * 
 * The fetcher class is the API to fetch data from the database. This could be a single row (single object) or many rows
 * filtered by one or more parameters, sorted, paginated, etc.
 *
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Model
 * @subpackage DatabaseApi
 * @todo Make sure all methods can be chained! Parameters should be the only things that "stop" the chaining.
 */

/**
 * You can use this value to set the sort() method to random order using mysql's RAND() function.
 * @const string
 **/
define('RANDOM', 'RANDOM');
define('RAND', 'RANDOM');
define('CUSTOM_SORT', 'CUSTOM_SORT');

/**
 * This is the fetcher class for getting and traversing lists in Outlast Framework.
 * These are read-only properties available.
 * @property zajModel $first The first item in the list.
 * @property integer $total The total number of items on all pages.
 * @property integer $count The number of items in the current limit / page.
 * @property integer $affected The number affected items.
 * @property stdClass $pagination The pagination object.
 * @property string $wherestr The generated WHERE clause.
 * @property string $orderby The generated ORDER BY clause.
 * @property string $groupby The generated GROUP BY clause
 * @property string $limit The generated LIMIT clause.
 **/
class zajFetcher implements Iterator, Countable, JsonSerializable{
	// create a fetch class
		public $class_name;									// the class name
		public $table_name;									// the table name
	// public settings
		public $distinct = false;							// distinct is false by default
	// private vars
		private $db;										// my private db instance
		private $query_done = false;						// true if query has been run already
		private $total = false;								// the total count (limit not taken into account)
		private $count = false;								// the instance count (limit included)
		private $affected = false;							// the number returned (limit is taken into account)
	// instance variables needed for generating the sql
		private $select_what = array();						// what to select from db. the index is the 'as' value.
		private $select_from = array();						// the tables to select from
		private $limit = "";								// the limit parameter
		private $orderby = "ORDER BY model.ordernum";		// ordered by ordernum by default
		private $ordermode = "DESC";						// default order ASC or DESC (defined by model)
		private $groupby = "";								// not grouped by default
		private $filter_deleted = "model.status!='deleted'";	// this does not show deleted items by default
		private $filters = [];	    						// an array of filters to be applied
		private $filter_groups = [];                        // an array of filter groups to be applied (useful for OR logic)
		private $filterstr = "";							// the generated filter query
		private $wherestr = "";								// where is empty by default
	// connection related stuff
		private $connection_wherestr = "";					// part of the where string if there is a connection involved
		/** @var zajModel */
		public $connection_parent;							// connections have a parent object - this is a reference to that
		/** @var string */
		public $connection_field;							// field name of connection
		/** @var string */
		public $connection_other;							// connections sometimes have another field
		/** @var string */
		public $connection_type;							// string - manytomany, manytoone, etc.
	// full sql (utilized by connection-related methods)
		private $full_sql = false;							// full sql is when something wants to override the query
	// pagination variable
		private $pagination = false;						// pagination object (variable)
	// iterator variables
		private $current_object = false;					// object - the current object
		private $current_key = false;						// string - key of current object (id)

	/**
	 * Creates a new fetcher object for a specific {@link zajModel} class.
	 * @param string $classname A valid {@link zajModel} class name which will be used to retrieve the individual objects.
	 **/
	public function __construct($classname = ''){
		// Is this an extended class?
			if(get_parent_class($classname) == 'zajModelExtender') $classname = $classname::extension_of();
		// Table and class
			$this->class_name = addslashes($classname);
			$this->table_name = strtolower($this->class_name);
		// Generate query defaults
			$this->add_source('`'.$this->table_name.'`', "model");
			$this->add_field_source('model.id');
			$this->db = zajLib::me()->db->create_session();		// create my own database session
		// Default order and pagination (defined by model)
			if($classname::$fetch_paginate > 0) $this->paginate($classname::$fetch_paginate);
			$this->ordermode = $classname::$fetch_order;
			$this->orderby = "ORDER BY model.".$classname::$fetch_order_field;
		return $this;
	}

	/**
	 * Implement json serialize method.
	 */
	public function jsonSerialize(){
		return $this->to_array();
	}

    /**
     * A parameterized version of json conversion.
     * @param boolean $localized Set to true if you want the localized version.
     * @param string[] $only_fields A list of fields to include in the array.
     * @return string Return a json string.
     */
	public function to_json($localized = true, $only_fields = []){
	    return json_encode($this->to_array($localized, $only_fields));
	}

  	/**
     * Return the model data as an array.
     * @param boolean $localized Set to true if you want the localized version of element fields.
     * @param string[] $only_fields A list of fields to include in the array elements.
     * @return array The list as an array.
     **/
	public function to_array($localized = true, $only_fields = []){
		$array_data = array();
		foreach($this as $row){
			$array_data[] = $row->to_array($localized, $only_fields);
		}
		return $array_data;
	}

	
	/**
	 * Paginate results by a specific number.
	 * @param integer $perpage The number of items to list per page.
	 * @param integer|bool $page The current page number. This is normally controlled automatically via the created template variables. See docs for details.
	 * @return zajFetcher This method can be chained.
	 **/
	public function paginate($perpage=10, $page = false){
		// if perpage is 0 or turned off
			if(!$perpage){
				$this->limit(false);
				$this->pagination = false;
			}
		// if specific value set
			else{
			// if page is false, then automatically set!
				if(!empty($_GET['zajpagination']) && $page === false) $page = $_GET['zajpagination'][$this->class_name];
			// set to default page
				if(!$page || !is_numeric($page) || $page <= 0) $page = 1;
			// set the start point
				$startat = $perpage*($page - 1);
			// set the limit values
				$this->limit($startat, $perpage);
			// now set pagination variables
				$this->pagination = (object) array();
				$this->pagination->page = $page;
				$this->pagination->perpage = $perpage;
				$this->pagination->pagefirstitem0 = ($page-1)*$perpage;
				$this->pagination->pagefirstitem = $this->pagination->pagefirstitem0+1;
				$this->pagination->nextpage = $page+1;	// nextpage is reset to false if not enough object (done after query)
				$this->pagination->prevpage = $page-1;
				$this->pagination->prevurl = zajLib::me()->fullrequest."&zajpagination[{$this->class_name}]={$this->pagination->prevpage}";
				if($this->pagination->prevpage > 0) $this->pagination->prev = "<a href='".$this->pagination->prevurl."'>&lt;&lt;&lt;&lt;</a>";
				else $this->pagination->prev = '';
				$this->pagination->nexturl = zajLib::me()->fullrequest."&zajpagination[{$this->class_name}]={$this->pagination->nextpage}";
				$this->pagination->next = "<a href='".$this->pagination->nexturl."'>&gt;&gt;&gt;&gt;</a>";
				$this->pagination->pageurl = zajLib::me()->fullrequest."&zajpagination[{$this->class_name}]=";
				$this->pagination->pagecount = 1;			// pagecount is reset to actual number (after query)
				$this->pagination->autopagination = false;	// autopagination is set after query
			}
		// changes query, so reset me
			// done by limit
		return $this;
	}

	/**
	 * Sort by a specific field and by an order.
	 * @param string $by The field name to sort by. Or RANDOM if you want it randomly. Or CUSTOM_SORT if you want the second parameter to just be used directly.
	 * @param string $order ASC or DESC or RANDOM depending on what you want. If left empty, the default for this model will be used. If the first parameter is set to CUSTOM_SORT, you can provide a custom sort string here, including ORDER BY.
	 * @return zajFetcher This method can be chained.
	 **/
	public function sort($by, $order=''){
		// if order is not set
			if($order) $this->ordermode = $order;
			// else do not change
		// set the orderby
			if($by == RANDOM || $order == RANDOM) $this->orderby = "ORDER BY RAND()";
			elseif($by == 'CUSTOM_SORT'){
				$this->orderby = $order;
				$this->ordermode = '';
			}
			elseif($by) $this->orderby = "ORDER BY model.$by";
			else{
				$this->orderby = '';
				$this->ordermode = '';
			}
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Custom order by.
	 * @param string $orderby The ORDER BY clause in full. Nothing is checked or escaped here, so be careful!
	 * @return zajFetcher This method can be chained.
	 */
	public function orderby($orderby = "model.ordernum DESC"){
		$this->orderby = "ORDER BY ".$orderby;
		$this->ordermode = '';
		$this->reset();
	}

	/**
	 * This allows you to group items by whatever field you prefer. Only one field can be specified for now.
	 * @param string|bool $by The fetcher results will be grouped by this field.
	 * @return zajFetcher This method can be chained.
	 */
	public function group($by = false){
		// set the orderby
			if($by) $this->groupby = "GROUP BY model.$by";
			else $this->groupby = '';
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Use this method to specify a custom WHERE clause. Begin with either || or && to continue the query! This is different from {@link zajFetcher->full_query()} because it is appended to the existing query. You should however use this only when necessary as it may cause unexpected behavior.
	 * @param string $wherestr The customized portion of the WHERE clause. Since it is appended to the existing query, begin with || or && to produce a valid query.
	 * @param bool $append If set to true (the detault), the string will be appended to any existing custom WHERE clause.
	 * @return zajFetcher This method can be chained.
	 **/
	public function where($wherestr, $append=true){
		// append or no
			if($append) $this->wherestr .= $wherestr;
			else $this->wherestr = $wherestr;
		// changes query, so reset me
			$this->reset();
		return $this;
	}

    /**
     * Union of two items.
     * @param zajFetcher $fetcher1
     * @param zajFetcher $fetcher2
     * @return zajFetcher
     */
    public function union($fetcher1, $fetcher2){
        // Get my queries
        $query1 = $fetcher1->add_field_source('*', '', true)->limit(false)->get_query();
        $query2 = $fetcher2->add_field_source('*', '', true)->limit(false)->get_query();

        // Build and return new fetcher
        /** @var zajModel $class_name */
        $class_name = $this->class_name;
        return $class_name::fetch()->sql("($query1) UNION ($query2)");
    }

    /**
     * Set distinct from a method.
     * @param bool $distinct Set to true or false.
     * @return zajFetcher This method can be chained.
     */
    public function distinct($distinct){
        $this->distinct = $distinct;
        $this->reset();
        return $this;
    }

	/**
	 * Set filter deleted to 0, 1, or the default.
	 * @param string|integer $filter_deleted Takes 0, 1, or 'default'.
	 * @return string Returns the actual value it was set to.
	 */
	public function set_filter_deleted($filter_deleted = 'default'){
		switch($filter_deleted){
			case 1:
				$this->filter_deleted = "1";
				break;
			case 0:
				$this->filter_deleted = "0";
				break;
			default:
				$this->filter_deleted = "model.status!='deleted'";
				break;
		}
		return $this->filter_deleted;
	}

	/**
	 * This method adds a joined source to the query. This is mostly for internal use.
	 * @param string $db_table The name of the table to select from.
	 * @param string $as_name The name of the table as referenced within the sql query (SELECT .... FROM table_name AS as_name)
	 * @param boolean $replace If set to true, this will remove all other sources before adding this new one.
	 * @return zajFetcher This method can be chained.
	 **/
	public function add_source($db_table, $as_name, $replace = false){
		//
		if($replace) $this->reset_sources();
		$this->select_from[] = "$db_table AS $as_name";
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Remove all existing joined source from the query. This is mostly for internal use.
	 * @return zajFetcher This method can be chained.
	 **/
	public function reset_sources(){
		// reset the array
			$this->select_from = array();
		// changes query, so reset me
			$this->reset();
		return $this;
	}

    /**
     * Provide a full custom sql query. Any additional, non-model columns can be referenced as well. See documentation for full information.
     * @param string $query The full query. Custom SQL queries should query all the columns of the relevant table.
	 * @return zajFetcher This method can be chained.
     */
    public function sql($query){
        return $this->add_source("($query)", 'model', true);
    }

	/**
	 * This method adds a field to be selected from a joined source. This is mostly for internal use.
	 * @param string $source_field The name of the field to select.
	 * @param string|bool $as_name The name of the field as referenced within the sql query (SELECT field_name AS as_name)
	 * @param bool $replace If set to true, this will remove all other joined fields before adding this new one.
	 * @return zajFetcher This method can be chained.
	 **/
	public function add_field_source($source_field, $as_name=false, $replace = false){
		// if replace
			if($replace) $this->reset_field_sources();

		// Check if there is a function
		// @todo remove this eventually
		if(preg_match('/[a-zA-Z]+\([^\)]*\)(\.[^\)]*\))?/', $source_field)){
			zajLib::me()->warning("Mysql function detected as source field: $source_field. Use custom queries with named fields instead to get rid of this warning.");
		}
		// Set source field

			if (false === strpos($source_field, ".")) {
				// It's a *
				if($source_field === '*') $sfield = $source_field;
				// It's not in table.column format
				else $sfield = '`'.$source_field.'`';
			} else {
				// It's in table.column format
				list($table, $field) = explode(".", $source_field);
				if($field === '*') $sfield = $table.'.'.$field;
				else $sfield = $table.'.`'.$field.'`';
			}
		// if an as name was chosen
			if($as_name) $this->select_what[$as_name] = $sfield.' as "'.$as_name.'"';
			else $this->select_what[$source_field] = $sfield;
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Remove all existing joined fields from the query. This is mostly for internal use.
	 * @return zajFetcher This method can be chained.
	 **/
	public function reset_field_sources(){
		// reset the array
			$this->select_what = array();
		// changes query, so reset me
			$this->reset();
		return $this;
	}


	/**
	 * Create a fetcher object from an array of ids.
	 * @param array $id_array An array of ids to search for.
	 * @return zajFetcher This method can be chained.
	 **/
	public function from_array($id_array){
		$this->wherestr .= " && (0";
		foreach($id_array as $id) $this->wherestr .= " || `id`='".addslashes($id)."'";
		$this->wherestr .= ")";		
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Toggle whether or not to show deleted items. By default, Mozajik will not delete rows you remove, but simply put them in a 'deleted' status. However, {@link zajFetcher} will not show these unless you toggle this option.
	 * @param bool $default If set to true (the default), it will show deleted items for this query. If set to false, it will turn this feature off.
	 * @return zajFetcher This method can be chained.
	 **/
	public function show_deleted($default = true){
		// i want to hide them!
			if(!$default) $this->set_filter_deleted();
			else $this->set_filter_deleted(1);
		// changes query, so reset me
			$this->reset();		
		return $this;
	}

	/**
	 * Results are filtered according to $field and $value.
	 * @param string $field The name of the field to be filtered
	 * @param string $value The value by which to filter.
	 * @param string $operator The operator with which to filter. Can be any valid MySQL-compatible operator: LIKE, NOT LIKE, <, >, <=, =, REGEXP etc.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @return zajFetcher This method can be chained.
	 **/
	public function filter($field, $value, $operator='LIKE', $type='AND'){
		// add to filters array and reset
        $this->filters[] = [$field, $value, $operator, $type];
		$this->reset();
		return $this;
	}

	/**
	 * Results are filtered according to $field and $value.
	 * @param string $field The name of the field to be filtered
	 * @param array $values A group of values by which to filter, usually
	 * @param string $operator The operator with which to filter. Can be any valid MySQL-compatible operator: LIKE, NOT LIKE, <, >, <=, =, REGEXP etc.
	 * @param string $type AND or OR depending on how you want this filter to connect. Defaults to AND.
	 * @param string $group_type AND or OR depending on how you want this filter to connect. Defaults to OR.
	 * @return zajFetcher This method can be chained.
	 **/
	public function filter_group($field, $values, $operator='LIKE', $type='AND', $group_type = 'OR'){
		// add to filter_groups array
		$this->filter_groups[] = [$field, $values, $operator, $type, $group_type];
		$this->reset();
		return $this;
	}

	/**
	 * Remove all filters or a specific filter.
	 * @param string|boolean $field The name of the field who's filter should be reset. If omitted (or if false), all are removed.
	 * @return zajFetcher This method can be chained.
	 */
	public function remove_filters($field = false){
		if($field === false){
		    $this->filters = [];
		    $this->filter_groups = [];
        }
		else{
		    // Run through filters and filter groups
			foreach($this->filters as $key => $filter){
				if($filter[0] == $field) unset($this->filters[$key]);
			}
			foreach($this->filter_groups as $key => $filter_group){
				if($filter_group[0] == $field) unset($this->filter_groups[$key]);
			}
		}
		return $this;
	}

	/**
	 * Exclude/remove filter is just an alias of filter but with different defaults
	 * @param string $field The name of the field to be filtered
	 * @param string $value The value by which to filter.
	 * @param string $operator The operator with which to exclude. It defaults to NOT LIKE. Can be any valid MySQL-compatible operator: NOT LIKE, !=, <, >=, etc.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @return zajFetcher This method can be chained.
	 **/
	public function exclude($field, $value, $operator='NOT LIKE', $type='AND'){ return $this->filter($field, $value, $operator, $type); }
	public function exc($field, $value, $operator='NOT LIKE', $type='AND'){ return $this->filter($field, $value, $operator, $type); }

	/**
	 * Remove all results. This is good for reseting a fetch to zero results by default.
	 * @return zajFetcher This method can be chained.
	 **/
	public function exclude_all(){
		return $this->filter('id', '-nothing');
	}
	
	/**
	 * Include results in the result set.
	 **/
	public function inc($field, $value, $operator='LIKE', $type='OR'){ return $this->filter($field, $value, $operator, $type); }

	/**
	 * A special filter method to be used for time filtering. It will filter results into everything BEFORE the given time or object. It is important to note that if you use an object as a parameter, it will change the sort order to the opposite since you are "going backwards" from the selected object.
	 * @param string|zajModel $value The value by which to filter. Can also be a zajmodel of the same type - then field is checked by the model's default sort order.
	 * @param string $field The name of the field to be filtered. Defaults to the time_create field.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @todo Add support for any sort() situation.
	 * @return zajFetcher This method can be chained.
	 **/
	public function before($value, $field='time_create', $type='AND'){
		// default operator
			$operator = '<=';
		// is $value a zajmodel
			if(zajModel::is_instance_of_me($value)){
				// check error
					if($value->class_name != $this->class_name) return $this->zajlib->error("Fetcher's before() method only supports using the same model. You tried using '$value->class_name' while this fetcher is a '$this->class_name'.");
				// check my default sort order
					$class_name = $value->class_name;
					$field = $class_name::$fetch_order_field;
				// set my value
					$value = $value->data->$field;
				// am i desc or asc? select operator and reverse the sort order
					if($class_name::$fetch_order == 'ASC'){
						$operator = '<';
						$this->sort($field, 'DESC');
					}
					else{
						$operator = '>';
						$this->sort($field, 'ASC');
					}
			}
		// filter it now
			return $this->filter($field, $value, $operator, $type);
	}
	/**
	 * A special filter method to be used for time filtering. It will filter results into everything AFTER the given time.
	 * @param string|zajModel $value The value by which to filter. Can also be a zajmodel of the same type - then field is checked by the model's default sort order.
	 * @param string $field The name of the field to be filtered. Defaults to the time_create field. Ignored if first paramter is zajModel.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @todo Add support for any sort() situation.
	 * @return zajFetcher This method can be chained.
	 **/
	public function after($value, $field='time_create', $type='AND'){
		// default operator
			$operator = '>=';
		// is $value a zajmodel
			if(zajModel::is_instance_of_me($value)){
				// check error
					if($value->class_name != $this->class_name) return $this->zajlib->error("Fetcher's after() method only supports using the same model. You tried using '$value->class_name' while this fetcher is a '$this->class_name'.");
				// check my default sort order
					$class_name = $value->class_name;
					$field = $class_name::$fetch_order_field;
				// set my value
					$value = $value->data->$field;
				// am i desc or asc?
					if($class_name::$fetch_order == 'DESC') $operator = '<';
					else $operator = '>';
			}
		// filter it now
			return $this->filter($field, $value, $operator, $type);
	}

	/**
	 * Limits the results of the query using LIMIT in MySQL.
	 * @param integer $startat This can be either startat or it can be the limit itself.
	 * @param integer|bool $limitto The number of objects to take. Leave empty if the first parameter is used as the limit.
	 * @return zajFetcher This method can be chained.
	 **/
	public function limit($startat, $limitto=false){
		// turn off limit and make sure it is valid
			if($startat === false || !is_numeric($startat) || ($limitto !== false && !is_numeric($limitto))) $this->limit = "";
		// set limit to value
			else{
			// no $limitto specified
				if($limitto === false) $this->limit = "LIMIT $startat";
			// else, it is specified
				else $this->limit = "LIMIT $startat, $limitto";
			}
		// changes query, so reset me
			$this->reset();
		return $this;
	}

	/**
	 * Performs a search on all searchable fields. You can optionally use similarity search. This will use the wherestr parameter, 
	 * @param string $query The text to search for.
	 * @param boolean $similarity_search If set to true (false is the default), similar sounding results will be returned as well.
	 * @param string $type AND or OR depending on how you want this filter to connect
	 * @todo Add the option to specify fields.
	 * @return zajFetcher This method can be chained.
	 **/
	public function search($query, $similarity_search = false, $type = 'AND'){
		/** @var zajModel $class_name */
		$class_name = $this->class_name;

		// retrieve model
        $model = $class_name::__model();

        // try to call search method
        $result = $class_name::__onSearchFetcher($this, $query, $similarity_search, $type);

        // perform the default if result is false
        if($result === false){
            // similarity?
            if($similarity_search) $sim = "SOUNDS";
            else $sim = "";

            // type?
            if($type != 'AND') $type = 'OR';

            // figure out search fields (searchfield=true is usually the case for text and id fields)
            $this->wherestr .= " $type (0";
            foreach($model as $key=>$field){
                if($field->search_field) $this->wherestr .= " || model.$key $sim LIKE '".$this->db->escape($query)."' || model.$key LIKE '%".$this->db->escape($query)."%'";
            }
            $this->wherestr .= ")";

            // changes query, so reset me
            $this->reset();
        }

		return $this;
	}

    /**
     * Apply a filter query to the list.
     * @param array|boolean $query The filter query. See documentation for formatting. Defaults to $_GET.
	 * @param boolean $similarity_search If set to true (false is the default), similar sounding results will be returned as well.
	 * @param string $logic AND or OR depending on how you want this filter to connect
	 * @return zajFetcher This method can be chained.
     */
    public function filter_query($query = false, $similarity_search = false, $logic = 'AND'){
		// Default query
		if($query == false) $query = $_GET;

		/** @var zajModel $class_name */
		$class_name = $this->class_name;
        $result = $class_name::__onFilterQueryFetcher($this, $query, $similarity_search, $logic);

        // perform the default if result is false
        if($result === false){

            // Do we have a regular query
            if(!empty($query['query'])){
                $this->search($query['query']);
            }

            // Now apply field queries
            if(!empty($query['filter']) && is_array($query['filter'])){
                foreach($query['filter'] as $field => $values){
                    if(is_array($values)){
                        // Empty values mean no filter will be applied
                        foreach($values as $key=>$value){
                            if(empty($value)) unset($values[$key]);
                        }

                        // Run through all filters for the field
                        // @todo add custom group type param

                        //print_r($values);
                        //print "$field<br/>";

                        if(is_array($values) && count($values) > 0){
                            foreach($values as $value){
                                if(is_array($value) && array_key_exists('value', $value) && array_key_exists('operator', $value) && array_key_exists('logic', $value)){
                                    if(!empty($value['value'])) $this->filter($field, $value['value'], $value['operator'], $value['logic']);
                                }
                                else $this->filter($field, $value, 'LIKE', $logic);
                            }
                        }
                    }

                }
            }
        }

        return $this;
    }


	/**
	 * Execute a full, customized query. Any query must return a column 'id' with the IDs of corresponding {@link zajModel} objects. Otherwise it will not be a valid {@link zajFetcher} object and related methods will fail. A full query will override any other methods used, except for paginate and limit (the limit is appended to the end, if specified!).
	 * @param string $full_sql The full, customized query.
     * @deprecated You should use sql() instead nowadays.
	 * @return zajFetcher This method can be chained.
	 **/
	public function full_query($full_sql){
		// set the full_sql parameter
			$this->full_sql = $full_sql;
		// changes query, so reset me
			$this->reset();
		return $this;
	}


	/**
	 * This method returns the sql statement which will be used during the query.
	 * @return string
	 * @todo Solve the issue with $type: if combining AND and OR then the order matters!
	 * @todo Bug when get_query called twice in a row!
	 */
	public function get_query(){
		// if full_sql set, just return that
			if($this->full_sql) return $this->full_sql.' '.$this->limit;
		// get my class field types
			$classname = $this->class_name;
			$mymodel = $classname::__model();
		// distinct?
			if($this->distinct) $distinct = "DISTINCT";
			else $distinct = "";
		// generate filters
			// apply filters to WHERE clause
            $filters_sql = '';
            foreach($this->filters as $key=>$filter){
                $filters_sql .= $this->filter_to_sql($mymodel, $filter);
            }
			// apply group filters to WHERE clause
            foreach($this->filter_groups as $key=>$filter_group){
                list($field, $values, $operator, $type, $group_type) = $filter_group;

                // Now process group type to make sure it is valid (defaults to ||)
                if(strtoupper($group_type) == "AND" || $group_type == "&&"){
                    $group_type = "&&";
                    $group_starter = "1";
                }
                else{
                    $group_type = "||";
                    $group_starter = "0";
                }

                // Run through each value item
                $group_filter_sql = " $type (".$group_starter." ";
                foreach($values as $value){
                    $group_filter_sql .= $this->filter_to_sql($mymodel, [$field, $value, $operator, $group_type]);
                }
                $group_filter_sql .= ")";
                $filters_sql .= $group_filter_sql;
            }

        // generate from and what
			$from = join(', ', $this->select_from);
			$what = join(', ', $this->select_what);
		// save filter query
			$this->filterstr = $filters_sql;
		// generate full query
			return "SELECT $distinct $what FROM $from WHERE $this->filter_deleted $filters_sql $this->wherestr $this->connection_wherestr $this->groupby $this->orderby $this->ordermode $this->limit";
	}

    /**
     * Prepare a logical WHERE clause section based on a specific filter.
	 * @param stdClass $mymodel The model definition.
     * @param array $filter A filter array.
     * @return string The generated sql.
     */
    private function filter_to_sql($mymodel, $filter){

        // define my vars
		list($field, $value, $operator, $type) = $filter;
        $classname = $this->class_name;
        $filters_sql = "";

        // Validate the field name
        if(!zajLib::me()->db->verify_field($field)) return zajLib::me()->warning("Field '$classname.$field' contains invalid characters and did not pass safety inspection!");

        // Now process type
        if(strtoupper($type) == "OR" || $type == "||") $type = "||";
        else $type = "&&";

        // Verify logic param
        if($operator != 'IN' && $operator != 'NOT IN' && $operator != "SOUNDS LIKE" && $operator != "LIKE" && $operator != "NOT LIKE" && $operator != "REGEXP" && $operator != "NOT REGEXP" && $operator != "!=" && $operator != "==" && $operator != "=" && $operator != "<=>" && $operator != ">" && $operator != ">=" && $operator != "<" && $operator != "<=") return zajLib::me()->warning("Fetcher class could not generate query. The logic parameter ($operator) specified is not valid.");
        // if $value is a model object, use its id
        if(zajModel::is_instance_of_me($value)) $value = $value->id;

        // fix name if virtual field
        if($mymodel->{$field}->virtual){
            $field = $mymodel->{$field}->virtual;
            $filter = array($field, $value, $operator, $type);
        }

        // if use_filter is true, then not a standard field object
        if($mymodel->{$field}->use_filter){
            // create the model
            /** @var zajModel $classname */
            $fieldobject = $classname::__field($field);
            // call my filter generator
            $filters_sql .= " $type ".$fieldobject->filter($this, $filter);
        }
        else{
            // check if it is a string
            if(is_object($value)) zajLib::me()->error("Invalid filter/exclude value on fetcher object for $classname/$field! Value cannot be an object since this is not a special field!");
            // allow subquery
            if($operator != 'IN' && $operator != 'NOT IN') $filters_sql .= " $type model.`$field` $operator '".$this->db->escape($value)."'";
            else $filters_sql .= " $type model.`$field` $operator ($value)";
        }
        return $filters_sql;
    }

	/**
	 * Executes the fetcher query.
	 * @param bool $force By default, query will only execute once, so a second query() will be ignored. Set this to true if you want to force execution regardless of previous status.
	 * @return zajFetcher
	 */
	public function query($force = false){		
		// if query already done
			if($this->query_done === true && !$force) return $this;
		// get query and execute it
			$this->db->query($this->get_query());
		// count rows
			$this->total = (int) $this->db->get_total_rows();
			$this->count = (int) $this->db->get_num_rows();
		// set pagination stuff
			if(is_object($this->pagination)){
				$this->pagination->pagecount = ceil($this->total/$this->pagination->perpage);
				if($this->pagination->nextpage > $this->pagination->pagecount){
					$this->pagination->nextpage = false;
					$this->pagination->next = '';
				}
				// Set autopagination data
				$this->pagination->autopagination = htmlspecialchars(
					json_encode(array(
						'model'=>$this->class_name,
						'url'=>zajLib::me()->protocol.$this->pagination->pageurl,
						'startPage'=>$this->pagination->page,
						'pageCount'=>$this->pagination->pagecount,
					))
				);
			}
		// query is done
			$this->query_done = true;
		// return me, so as to enable chaining
			return $this;
	}

	/**
	 * Counts the total number of rows available in this query. Accessible via the {@link zajFetcher->total} parameter.
	 * @return integer
	 **/
	private function count_total(){
		// if already counted, just return
			if($this->total !== false) return $this->total;
		// execute the query
			if(!$this->query_done) $this->query();
		// count
			return $this->total;
	}

	/**
	 * Reset will force the fetcher to reload the next time it is accessed
	 * @todo This should return the actual object not the fetcher. If you fix this, you must fix filter_first().
	 **/
	public function reset(){
		// Set query_done to false
			$this->query_done = false;
		// Set counters to false
			$this->total = false;
			$this->affected = false;
			$this->count = false;
		// Reset iteration
			$this->current_object = false;
			$this->current_key = false;
		return $this;
	}

	/****************************************************************************************
	 *	!Iterator methods
	 *		- These are used by foreach
	 ***************************************************************************************/

	/**
	 * Returns the current object in the iteration.
	 **/
	public function current(){
		// if current is not an object, rewind
			if(!is_object($this->current_object)) $this->rewind();
		// else return the current
			return $this->current_object;
	}
	/**
	 * Returns the current key in the iteration.
	 **/
	public function key(){
		if(!is_object($this->current_object)) $this->rewind();
		return $this->current_key;
	}
	/**
	 * Returns the next object in the iteration.
	 **/
	public function next(){
		// Run query if not yet done
			if(!$this->query_done) $this->query();
		// Get the next row
			$result = $this->db->next();
		// Convert to an object and return
			return $this->row_to_current_object($result);
	}
	/**
	 * Rewinds the iterator.
	 **/
	public function rewind(){
		// reewind db pointer
			// if query not yet run, run now
			if(!$this->query_done){
				$this->query();
				return $this->next();
			}
			else{
				// Rewind my db
					$result = $this->db->rewind();
				// Now get result
					return $this->row_to_current_object($result);
			}
	}

	/**
	 * Returns true if the current object of the iterator is a valid object.
	 **/
	public function valid(){
		return is_object($this->current_object);
	}

	/**
	 * Converts the current database row to the current fetched object. Also sets current_key and current_object vars.
	 * @param stdClass $result The database result row object.
	 * @return zajModel Returns the currently selected zajModel object.
	 **/
	public function row_to_current_object($result){
		// First off, check to see if valid result
			if(!is_object($result) || empty($result->id)) $this->current_object = false;
			else{
				// Now fetch based on my id result
					/** @var zajModel $class_name */
					$class_name = $this->class_name;
					$this->current_object = $class_name::fetch($result->id);
				// Add fetcher data if it exists
					$this->current_object->fetchdata = new stdClass();
					foreach($this->select_what as $as_name => $val){
						if(property_exists($result, $as_name) && !is_null($result->$as_name)){
							$this->current_object->fetchdata->$as_name = $result->$as_name;
						}
					}
			}
		// Set current key, but only if current object is successful
			if(is_object($this->current_object)) $this->current_key = $this->current_object->id;
			else $this->current_key = false;
		return $this->current_object;
	}


	/****************************************************************************************
	 *	!Countable methods
	 *		- This is used by count()
	 *
	 ***************************************************************************************/

	/**
	 * Returns the total count of this fetcher object.
	 **/
	public function count(){
		return $this->count_total();
	}

	/****************************************************************************************
	 *	!Magic methods
	 *
	 ***************************************************************************************/

	/**
	 * Get object variables which are private or undefined by default.
	 **/
	public function __get($name){
		switch($name){
			case "first":	// if query not yet executed, do it now
							if(!$this->query_done) $this->query();
							return $this->reset()->next();
			case "total":	// if total not yet loaded, then retrieve and return it...otherwise just return it
							if($this->total === false) return $this->count_total();
							return $this->total;
			case "count":	// if count not yet loaded, then retrieve and return it...otherwise just return it
							if($this->count === false) $this->count_total();
							return $this->count;
			case "affected":// if total not yet loaded, then retrieve and return it...otherwise just return it
							if($this->total === false) $this->count_total();
							return $this->total;
			case "paginate":
			case "pagination":
							// if query not yet executed, do it now
							if(!$this->query_done) $this->query();
							return $this->pagination;
			case "wherestr":
							$this->get_query();
							return $this->filter_deleted.' '.$this->filterstr.' '.$this->wherestr.' '.$this->connection_wherestr;
			case "orderby":
							$this->get_query();
							return $this->orderby;
			case "groupby":
							$this->get_query();
							return $this->groupby;
			case "limit":
							$this->get_query();
							return $this->limit;

			default: 		zajLib::me()->warning("Attempted to access inaccessible variable ($name) for zajFetcher class!");
		}
	}

	/****************************************************************************************
	 *	!Multiple object connections
	 *		- Many-to-many & one-to-many both return a fetcher object and not a single one.
	 *		- Chaining is enabled, yes!
	 ***************************************************************************************/

	/**
	 * This method returns the connected fetcher object. This will be a collection of {@link zajModel} objects.
	 * @param string $field The field name.
	 * @param zajModel $object The object.
	 * @return zajFetcher Returns a list of objects.
	 **/
	public static function manytomany($field, &$object){
		// Fetch the other model and other field
			// get my
				$class_name = $object->class_name;
				$table_name = $object->table_name;
			// get other via field
				$field_model = $class_name::__field($field);
				$other_model = $field_model->options['model'];
				$other_field = $field_model->options['field'];		
				$other_table = strtolower($other_model);
		// Am I a primary manytomany connection	
			if(!$other_field){
				// Create a new zajFetcher object
				$my_fetcher = new zajFetcher($other_model);
				// I am a primary connection!
				$field_sql = addslashes($field);	// added for extra safety!
				$my_fetcher->add_source(strtolower("connection_{$class_name}_{$other_table}"), 'conn', true)->add_source('`'.$my_fetcher->table_name.'`', 'model');
				$my_fetcher->add_field_source("conn.id2", "id", true);
				$my_fetcher->connection_wherestr = "&& conn.field='$field_sql' && conn.id1='{$object->id}' && model.id=conn.id2 && conn.status!='deleted' ";
				$my_fetcher->orderby = "ORDER BY conn.order2";
				$my_fetcher->ordermode = "ASC";
			}
			else{
				// Create a new zajFetcher object
				$my_fetcher = new zajFetcher($class_name);
				// I am a reference to a primary connection!
				$my_fetcher->add_source(strtolower("connection_{$other_model}_{$table_name}"), 'conn', true)->add_source('`'.$other_table.'`', 'model');
				$my_fetcher->add_field_source("conn.id1", "id", true);
				$my_fetcher->connection_wherestr = "&& conn.field='$other_field' && conn.id2='{$object->id}' && model.id=conn.id1 && conn.status!='deleted' ";
				$my_fetcher->orderby = "ORDER BY conn.order1";
				$my_fetcher->ordermode = "ASC";
				$my_fetcher->class_name = $other_model;
				$my_fetcher->table_name = $other_table;
			}
		// Set my parent object
			$my_fetcher->connection_parent = $object;
			$my_fetcher->connection_field = $field;
			$my_fetcher->connection_other = $other_field;
			$my_fetcher->connection_type = 'manytomany';
			
		// Return my fetcher object
			return $my_fetcher;
	}

	/**
	 * This method returns the connected fetcher object. This will be a collection of {@link zajModel} objects.
	 * @param string $field The field name.
	 * @param zajModel $object The object.
	 * @return zajFetcher Returns a list of objects.
	 **/
	public static function onetomany($field, &$object){
		// Fetch the other model and other field		
			$class_name = $object->class_name;
			$field_model = $class_name::__field($field);
			$other_model = $field_model->options['model'];
			$other_field = $field_model->options['field'];		
		// Create a new zajFetcher object
			$my_fetcher = new zajFetcher($other_model);
		// Now filter to only ones where id matches me!
			$my_fetcher->filter($other_field, $object->id);
			$my_fetcher->sort($other_model::$fetch_order_field, $other_model::$fetch_order);
		// Set my parent object
			$my_fetcher->connection_parent = $object;			
			$my_fetcher->connection_field = $field;
			$my_fetcher->connection_other = $other_field;
			$my_fetcher->connection_type = 'onetomany';
		// Return my fetcher object
			return $my_fetcher;
	}


	/****************************************************************************************
	 *	!Single object connections
	 *		- Many-to-one & one-to-one both return single objects, so no fetch really.
	 *		- Chaining is enabled!
	 ***************************************************************************************/

	/**
	 * This method returns the connected fetcher object (which actually translates to a single zajModel object).
	 * @param string $class_name The class name.
	 * @param string $field The field name.
	 * @param string $id The id.
	 * @return zajModel|boolean Returns the connected object or boolean if no connection.
	 **/
	public static function manytoone($class_name, $field, $id){
		// if not id, then return false
        if(empty($id)) return false;

		// get the other model
        /** @var zajModel $class_name */
        $field_model = $class_name::__field($field);
        $other_model = $field_model->options['model'];

		// return the one object
        /** @var zajModel $other_model */
        /** @var zajModel $fetcher */
        $fetcher = $other_model::fetch($id);
        // if it exists, perform additional stuff!
        if(is_object($fetcher)){
            // set connection type
                $fetcher->connection_type = 'manytoone';
            // if it is deleted then do not return
                if($fetcher->data->status == 'deleted') $fetcher = false;
        }
        return $fetcher;
	}

	/**
	 * This method returns the connected fetcher object (which actually translates to a single zajModel object).
	 * @param string $class_name The class name.
	 * @param string $field The field name.
	 * @param string $id The id.
	 * @return zajModel
	 */
	public static function onetoone($class_name, $field, $id){
		// return the one object
			$fetcher = zajFetcher::manytoone($class_name, $field, $id);
			if(is_object($fetcher)) $fetcher->connection_type = 'onetoone';
			return $fetcher;
	}

	/****************************************************************************************
	 *	!Relationships editing
	 *		
	 ***************************************************************************************/

	/**
	 * This method adds $object to the manytomany relationship.
	 * @param zajModel $object
	 * @param string $mode Can be add or delete. This will add or remove the relationship. Defaults to add.
	 * @param bool|array $additional_fields  An assoc array with key/value pairs of additional columns to save to the relationship connection table.
	 * @param bool $delete_all Remove all connections not just a single one. This only works in 'delete' mode. Defaults to false.
	 * @return zajFetcher Returns the zajFetcher object, so it can be chained.
     * @todo This needs a better implementation so that object can accept fetchers and that the table name is not generated based on the param but based on field.
	 */
	public function add($object, $mode = 'add', $additional_fields = false, $delete_all = false){
		// if not an object
			if(!is_object($object)) zajLib::me()->error('tried to edit a relationship with something that is not a model or fetcher object.');
			//if(!$object->exists) zajLib::me()->error('tried to add a relationship that was not an object');
		// if parent does not exist
			if(!$this->connection_parent->exists) zajLib::me()->error('You tried to add a '.$object->class_name.' connection where the '.$this->connection_parent->class_name.' does not yet exist!');

		// if manytomany, write in separate table
			if($this->connection_type == 'manytomany'){
				$row = array('time_create'=>time());
				if(empty($this->connection_other)){
					$table_name = strtolower('connection_'.$this->connection_parent->class_name.'_'.$object->class_name);
					$row['id1'] = $this->connection_parent->id;
					$row['id2'] = $object->id;
					$row['field'] = $this->connection_field;
				}
				else{
					$table_name = strtolower('connection_'.$object->class_name.'_'.$this->connection_parent->class_name);
					$row['id1'] = $object->id;
					$row['id2'] = $this->connection_parent->id;
					$row['field'] = $this->connection_other;
				}
				// add additional fields
					if(!empty($additional_fields) && is_array($additional_fields)){
						foreach($additional_fields as $key=>$value){
							$row[$key] = $value;
						}
					}
				// create a row to add
					$row['id'] = uniqid("");
					$row['order1'] = MYSQL_MAX_PLUS;
					$row['order2'] = MYSQL_MAX_PLUS;
					$db = zajLib::me()->db->create_session();
					if($mode == 'add') $db->add($table_name, $row);
					if($mode == 'delete'){
						// Delete all connections or just one?
							if(!$delete_all) $limit = "LIMIT 1";
							else $limit = "";
						// Execute SQL
							$db->query("DELETE FROM `$table_name` WHERE `id1`='".$row['id1']."' && `id2`='".$row['id2']."' && `field`='".$row['field']."' ".$limit);
					}
			}
			elseif($this->connection_type == 'manytoone' || $this->connection_type == 'onetoone'){
				zajLib::me()->warning('Using add is only necessary on manytomany fields.');
			}
			elseif($this->connection_type == 'onetomany'){
				zajLib::me()->warning('Using add is only necessary on manytomany fields. On onetomany fields, you should try setting up the relationship from the manytoone direction.');
			}
		// Update other object (if needed)! Since the save() method is only called on $connection_parent and not on $object, the appropriate magic methods
		//			need to be called here....
			if(!empty($this->connection_other)){
				// same events called as after a save()
					$object->fire('afterSave');
					$object->fire('afterFetch');
				// cache the new values
					$object->cache();
			}
		// return me so i can chain more
			return $this;	
	}

	/**
	 * This method removes $object from the manytomany relationship.
	 * @param zajModel $object 
	 * @param bool $delete_all Remove all connections not just a single one. This only works in 'delete' mode. Defaults to false.
	 * @return zajFetcher Returns the zajFetcher object, so it can be chained.
	 **/
	public function remove($object, $delete_all = false){
		return $this->add($object, 'delete', false, $delete_all);
	}
	
	/**
	 * Returns true or false based on whether or not the current fetcher is connected to the object $object.
	 * @param zajModel|string $objectORid The object in question.
	 * @return boolean True if connected, false otherwise.
	 * @todo Change count_only!
	 **/
	public function is_connected($objectORid){
		// Check for errors
        if(!zajModel::is_instance_of_me($this->connection_parent)){
            return zajLib::me()->warning("The connection parent for is_connected() is not a zajModel object.");
        }

        // Get details
        $connection_details = $this->get_connection_table_details();
        $class_name = $this->class_name;

        // If it is a string
        if(is_string($objectORid)){
            $object = $class_name::fetch($objectORid);
        }
        else{
            // It is already an object, check if it is valid
            $object = $objectORid;
            if(!$class_name::is_instance_of_me($object)){
                return zajLib::me()->warning("You tried to check {$connection_details->primary_model}.{$connection_details->field}.is_connected() status with a parameter that is not a $class_name object.");
            }
        }

        // Not connected if does not exist or deleted
        if($object === false || $object->data->status == "deleted"){
            return false;
        }

        // Run SQL @todo convert to prepared statement
        $sql = <<<EOF
          SELECT
            COUNT(*) as c
          FROM
            {$connection_details->table}
          WHERE
            {$connection_details->primary_id_field}='{$this->connection_parent->id}' AND
            {$connection_details->secondary_id_field}='{$object->id}' AND
            field='{$connection_details->field}'
EOF;
        $result = $this->db->query($sql);
        return ($result->next()->c > 0);
	}


	/**
	 * Reorder the connected objects' ordernum based on the order of ids in the passed array.
	 * @param string[] $reorder_array An array of ids.
	 * @param bool $reverse_order If set to true, the reverse order will be taken into account.
	 * @return bool Always returns true.
	 */
	public function reorder($reorder_array, $reverse_order = false){

	    // If reversed
	    if($reverse_order){
    	    $reorder_array = array_reverse($reorder_array);
	    }

	    // Assemble a list of ids
	    $sqlIdList = "";
        foreach($reorder_array as $item){
            if($sqlIdList) $sqlIdList .= ", ";
            $sqlIdList .=  "'".addslashes($item)."'";
        }

        // Fetch all items by current order
        $connection_details = $this->get_connection_table_details();
        $sql = <<<EOF
        SELECT
          {$connection_details->secondary_id_field} as id, {$connection_details->order_field} as ordernum
        FROM
          {$connection_details->table}
        WHERE
          {$connection_details->primary_id_field}='{$this->connection_parent->id}' AND
          {$connection_details->secondary_id_field} IN ($sqlIdList)
        ORDER BY
          {$connection_details->order_field} ASC
EOF;
        $items = $this->db->query($sql);
        $current_order = [];
        foreach($items as $item){

            $current_order[] = $item->ordernum;

        }

        // Now run through ids and apply my ordering
        $i = 0;
        foreach($reorder_array as $reorder_item){
            // If we are past the index, then just break
            if($i >= count($current_order)) break;

            // Generate update sql
            $item_sql = addslashes($reorder_item);
            $update_sql = <<<EOF
            UPDATE
              {$connection_details->table}
            SET
              {$connection_details->order_field}={$current_order[$i]}
            WHERE
              {$connection_details->primary_id_field}='{$this->connection_parent->id}' AND
              {$connection_details->secondary_id_field}='$item_sql'
EOF;
            // Run and incement if any rows affected
            $this->db->query($update_sql);
            //if($this->db->affected > 0) @todo fix if an id which does not exist is in array!
            $i++;
        }

        return true;

    }


    /**
     * Gets the connection table details for manytomany connections.
     * @return zajFetcherConnectionDetails|stdClass|boolean
     */
	private function get_connection_table_details(){
	    // Only many to many have connection tables
	    if($this->connection_type != 'manytomany') return zajLib::me()->error("Tried to get connection table for something other than manytomany.");

        // Primary connection
	    if(!$this->connection_other){
	        return (object) [
	            'table'=>"connection_{$this->connection_parent->table_name}_{$this->table_name}",
	            'field'=>$this->connection_field,
	            'primary_model'=>$this->connection_parent->class_name,
	            'primary_id_field'=>'id1',
	            'secondary_model'=>$this->class_name,
	            'secondary_id_field'=>'id2',
	            'order_field'=>'order2'
            ];
	    }
	    // Secondary connection
	    else{
	        return (object) [
	            'table'=>"connection_{$this->table_name}_{$this->connection_parent->table_name}",
	            'field'=>$this->connection_other,
	            'primary_model'=>$this->class_name,
	            'primary_id_field'=>'id2',
	            'secondary_model'=>$this->connection_parent->class_name,
	            'secondary_id_field'=>'id1',
	            'order_field'=>'order1'
            ];
	    }
	}

    /**
     * Returns true if the object is a fetcher.
     * @param mixed $object
     * @return boolean True if yes, false if no.
     */
	public static function is_instance_of_me($object){
        return is_object($object) && is_a($object, 'zajFetcher');
    }

	/**
     * Set a mock database for testing.
     * @param $db
     */
    public function set_mock_database($db){
        // Only allow during testing
        if(zajLib::me()->test->is_running()){
            $this->db = $db;
        }
    }

}