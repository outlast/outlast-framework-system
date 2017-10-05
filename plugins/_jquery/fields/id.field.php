<?php
/**
 * Field definition for the primary key ID. This is an internal field used by Mozajik as the basic ID field for individual objects.
 * @package Fields
 * @subpackage BuiltinFields
 **/
zajLib::me()->load->file('/fields/text.field.php');

class zajfield_id extends zajField {
	// name, options - these are passed to constructor and available here!
	const in_database = true;		// boolean - true if this field is stored in database		
	const use_validation = false;	// boolean - true if data should be validated before saving
	const use_get = false;			// boolean - true if preprocessing required before getting data
	const use_save = true;			// boolean - true if preprocessing required before saving data
	const use_duplicate = false;	// boolean - true if data should be duplicated when duplicate() is called
	const use_filter = true;		// boolean - true if fetch is modified
	const disable_export = false;	// boolean - true if you want this field to be excluded from exports
	const search_field = true;		// boolean - true if this field is used during search()
	const edit_template = 'field/id.field.html'; // string - the edit template, false if not used
	const show_template = false;	// string - used on displaying the data via the appropriate tag (n/a)
			
	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		// call parent constructor
			parent::__construct(__CLASS__, $name, $options, $class_name, $zajlib);
	}	

	/**
	 * Defines the structure and type of this field in the mysql database.
	 * @return array Returns in array with the database definition.
	 **/
	public function database(){
		if($this->options[0] == AUTO_INCREMENT){
			$type = 'int';
			$options = array(0 => 11);
			$extra = AUTO_INCREMENT;
		}
		else{
			$type = 'varchar';
			$options = array(0 => 13);
			$extra = '';
		}
		
		// define each field
			$fields[$this->name] = array(
					'field' => $this->name,
					'type' => $type,
					'option' => $options,
 					'key' => 'PRI',
					'default' => false,
					'extra' => $extra,
					'comment' => 'id',
			);
		return $fields;
	}
	
	/**
	 * This is called when a filter() or exclude() methods are run on this field. It is actually executed only when the query is being built.
	 * @param zajFetcher $fetcher A pointer to the "parent" fetcher which is being filtered.
	 * @param array $filter An array of values specifying what type of filter this is.
	 * @return bool|string
	 */
	public function filter(&$fetcher, $filter){
		// break up filter
		list($field, $value, $operator, $type) = $filter;

		// if it is a model
		if(zajModel::is_instance_of_me($value)) $value = $value->id;
		elseif(zajFetcher::is_instance_of_me($value)){
		    if($operator == "NOT LIKE" || $operator == "!=") $operator = "NOT IN";
		    else $operator = "IN";
            return "model.`$field` $operator (".$value->get_query().")";
        }

        // If it is an array of ids
        elseif(is_array($value)){
		    if($operator == "NOT LIKE" || $operator == "!=") $operator = "NOT IN";
		    else $operator = "IN";
            $in_array = "(";

            // Run through all the ids
            $i = 0;
            $length = count($value);
            foreach($value as $item){
                $in_array .= "'".zajLib::me()->db->escape($item)."'";
                if ($i != $length - 1) $in_array .= ', ';
                $i++;
            }
            $in_array .= ")";


            return "model.`$field` $operator $in_array";
        }

        // Return a standard query
        return "model.`$field` $operator '".zajLib::me()->db->escape($value)."'";
    }

    /**
     * Disable save as a fatal error for id fields.
     */
	public function save($data, &$object){
		return zajLib::me()->error("You tried modifying the id of an object. This is not allowed.");
	}

}