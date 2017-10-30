<?php
/**
 * The basic model class.
 *
 * This is an abstract model class from which all model classes are derived.
 *
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Model
 */

define('MAX_EVENT_STACK', 50);
define('CACHE_DIR_LEVEL', 4);

/**
 * This is the abstract model class from which all model classes are derived.
 *
 * All model classes need to extend zajModel which provides the basic set of methods and variables to the object.
 *
 * @author Aron Budinszky <aron@outlast.hu>
 * @package Model
 * @subpackage DefaultModel
 * @abstract Model files extend this base class.
 * @method boolean __beforeCreateSave() EVENT. Executed before the object is first created in the database. If returns false, the object is not saved!
 * @method boolean __beforeSave() EVENT. Executed before the object is saved to the database. If returns false, the object is not saved!
 * @method boolean __beforeCache() EVENT. Executed before the object is saved to a cache file. If returns false, the object is not cached!
 * @method boolean __beforeUncache() EVENT. Executed before the object cache is removed. If it returns false, the object cache will not be removed! Note: This may not be called in every situation!
 * @method boolean __beforeDelete() EVENT. Executed before the object is deleted. If returns false, the object is not deleted!
 * @method __afterCreateSave() EVENT. Executed after the object is created in the database.
 * @method __afterCreate() EVENT. Executed after the object is created in memory.
 * @method __afterSave() EVENT. Executed after the object is saved to the database.
 * @method __afterFetch() EVENT. Executed after the object is fetched from the database (and NOT from cache). Also fired after save.
 * @method __afterFetchCache() EVENT. Executed after the object is fetched from a cache file. Note that this is also fired after a database fetch.
 * @method __afterCache() EVENT. Executed after the object is saved to a cache file.
 * @method __afterUncache() EVENT. Executed after the object cache is removed (but only if the remove was successful) Note: This may not be called in every situation!
 * @method __afterDelete() EVENT. Executed after the object is deleted.
 * @method __onFetch() EVENT. Executed when a fetch method is requested.
 * @method __onCreate() EVENT. Executed when a create method is requested.
 * @method static zajFetcher __onSearch() __onSearch(zajFetcher $fetcher, string $type) EVENT. Executed when the client side search API is requested. The API is disabled by default.
 * @method array __toSearchApiJson() __toSearchApiJson() EVENT. Executed when an item is being returned as part of the search API. You can override this to send more or different info about the object to the json response.
 * @method static boolean __onSearchFetcher() __onSearchFetcher(zajFetcher &$fetcher, string $query, boolean $similarity_search = false, string $type = 'AND') EVENT. Executed when search() is run on the model's zajFetcher object. If it returns boolean false (default) it is ignored and the default search is applied.
 * @method static boolean __onFilterQueryFetcher() __onFilterQueryFetcher(zajFetcher &$fetcher, string $query, boolean $similarity_search = false, string $type = 'AND') EVENT. Executed when filter_query() is run on the model's zajFetcher object. If it returns boolean false (default) it is ignored and the default filter query is applied.
 *
 * Properties...
 * @property zajLib $zajlib A pointer to the global object.
 * @property string $name The name of the object.
 * @property boolean $exists
 * @property stdClass $translation
 * @property zajData $data
 */
abstract class zajModel implements JsonSerializable {
	// Instance variables
	/**
	 * Stores the unique id of this object
	 * @var string
	 **/
	public $id;
	/**
	 * Stores the value of the name field.
	 * @var string
	 **/
	private $name;
	/**
	 * Stores the name (or key) of the name field.
	 * @var string
	 **/
	public $name_key;

	// Model structure
	/**
	 * Stores the field types as an associative array. See {@see zajDb}.
	 * @var array
	 **/
	protected $model;
	/**
	 * True if the object exists in the database, false otherwise.
	 * @var boolean
	 **/
	protected $exists = false;

	// Model settings
	/**
	 * Set to true if this object should be stored in the database.
	 * @var boolean
	 **/
	public static $in_database = true;
	/**
	 * Set to true if this object should have translations associated with it.
	 * @var boolean
	 **/
	public static $has_translations = true;
	/**
	 * Set to DESC or ASC depending on the default fetch sort order.
	 * @var string
	 **/
	public static $fetch_order = 'DESC';
	/**
	 * Set to the field which should be the default fetch sort field.
	 * @var string
	 **/
	public static $fetch_order_field = 'ordernum';
	/**
	 * Set the pagination default or leave as unlimited (which is the default value of 0)
	 * @var integer
	 **/
	public static $fetch_paginate = 0;
    /**
     * Connection type, used when object is fetched in connection to another. Empty if not a connected object.
     * @var string
     */
    public $connection_type = '';

	// Mysql database and child details / settings
	/**
	 * My class (or model) name.
	 * @var string
	 **/
	public $class_name = "zajModel";
	/**
	 * My table name (typically a lower-case form of class_name)
	 * @var string
	 **/
	public $table_name = "models";
	/**
	 * My id column key/name ('id' by default)
	 * @var string
	 **/
	public $id_column = "id";

	// Objects used by this class
	/**
	 * Access to the database-stored data through the object's own {@link zajData} object.
	 * @var zajData
	 **/
	private $data;

	/**
	 * Access to the database-stored data that is retrieved as custom query via the {@link zajFetcher} object's add_field_source method.
	 * @var stdClass|boolean
	 **/
	public $fetchdata = false;

	/**
	 * Access to the database-stored translation data through the object's own {@link zajModelLocalizer} object.
	 * @var zajModelLocalizer
	 **/
	protected $translations;

	// Object event stack
	/**
	 * The event stack, which is basically an array of events currently running.
	 * @var array
	 **/
	protected $event_stack = [];

	/**
	 * This is an object-specific private variable which registers if any extension of $this has had its event fired. This is used to prevent infinite loops.
	 * @var boolean
	 **/
	public $event_child_fired = false;


	// Model extension
	/**
	 * A key/value pair array of all extended models
	 * @var array
	 * @todo If it is possible to store this on a per-class basis, it would be better than this 'global' way!
	 **/
	public static $extensions = [];

	/**
	 * Constructor for model object. You should never directly call this. Use {@link: create()} instead.
	 *
	 * @param string $id The id of the object.
	 * @param string $class_name The name of child class (model class). This is deprecated and overridden anyway.
	 * @return zajModel
	 */
	public function __construct($id, $class_name = ''){
		$class_name = get_called_class();
		// check for errors
			if($id && !is_string($id) && !is_integer($id) && zajLib::me()->security->is_valid_id($id)) zajLib::me()->error("Invalid ID ($id) value given as parameter for model constructor! You probably tried to use an object instead of a string or integer!");
		// set class and table names
			$this->table_name = strtolower($class_name);
			$this->class_name = $class_name;
		// set id if its empty
			if($id == false) $this->id = uniqid("");
			else $this->id = $id;

		// everything else is loaded on request!
		return $this;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// !Static Methods
	/////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Defines and returns the model structure.
	 * @param bool|stdClass $fields The field's object generated by the child class.
 	 * @return stdClass Returns an object containing the field settings as parameters.
	 */
	public static function __model($fields = false){

		// Check fields
		if($fields === false) zajLib::me()->error("Tried to get model without specifying fields. You must at least pass an empty array of fields.");

		// Get my class_name
        /* @var string|zajModel $class_name */
        $class_name = get_called_class();
        if(!$class_name::$in_database) return new stdClass(); 	// disable for non-database objects

		// do I have an extension? if so, merge fields
        /** @var zajModel $ext */
        $ext = $class_name::extension();
        if($ext) $fields = $ext::__model($fields);

        // Check for errors
        if(!is_object($fields)) zajLib::me()->error("The __model() method of $fields is not yet upgraded to the new PHP 7 standard. Please review its other methods as well to avoid warnings and errors.");

		// now set defaults (if not already set)
        if(!isset($fields->unit_test)) $fields->unit_test = zajDb::unittest();
        if(!isset($fields->time_create)) $fields->time_create = zajDb::time();
        if(!isset($fields->time_edit)) $fields->time_edit = zajDb::time();
        if(!isset($fields->ordernum)) $fields->ordernum = zajDb::ordernum();
        if(!isset($fields->status)) $fields->status = zajDb::select(array("new","deleted"),"new");
        if(!isset($fields->id)) $fields->id = zajDb::id();

		// if i am not in static mode, then i can save it as $this->fields
		return $fields;
	}
	/**
	 * Get the field object for a specific field in the model.
	 * @param string $field_name The name of the field in the model.
	 * @return zajField|boolean Returns a zajField object or false if error.
	 */
	public static function __field($field_name){
		// Get my class_name
			/* @var string|zajModel $class_name */
			$class_name = get_called_class();
		// make sure $field is chrooted
			if(strpos($field_name, '.')) return zajLib::me()->error('Invalid field name "'.$field_name.'" used in model "'.$class_name.'".');
		// TODO: can I create a version where $this is set?
		// get model
			$field_def = $class_name::__model()->$field_name;
			if(empty($field_def)) return zajLib::me()->error('Undefined field name "'.$field_name.'" used in model "'.$class_name.'".');
		// create my field object
			$field_object = zajField::create($field_name, $field_def, $class_name);
		return $field_object;
	}

	/**
	 * Fetch a single or multiple existing object(s) of this class.
	 * @param bool|string|zajModel $id OPTIONAL. The id of the object. Leave empty if you want to fetch multiple objects. You can also pass an existing zajModel object in which case it will simply pass through the function without change - this is useful so you can easily support both id's and existing objects in a function.
	 * @return zajFetcher|zajModel|boolean Returns a zajFetcher object (for multiple objects) or a zajModel object (for single objects) or false if failed to fetch.
	 */
	public static function fetch($id=null){
		// Get my class_name
			/* @var string|zajModel $class_name */
			$class_name = get_called_class();
        // Arguments passed?
            $args = func_get_args();
            if(count($args) > 0) $has_args = true;
            else $has_args = false;
        // if id is specifically null or empty, then return false
			if($has_args && (is_null($id) || $id === false || (is_string($id) && $id == ''))) return false;
		// call event
			$class_name::fire_static('onFetch', array($class_name, $id));
		// disable for non-database objects if id not given!
			if(!$has_args && !$class_name::$in_database) return false;
		// if id is not given, then this is a multi-row fetch
			if(!$has_args) return new zajFetcher($class_name);
		// let's see if i can resume it!
			else{
				// first, is it already resumed? in this case let's make sure its the proper kind of object and just return it
				if(is_object($id)){
					// is it the proper kind of object? if not, warning, if so, return it
						if($class_name != $id->class_name) return zajLib::me()->warning("You passed an object to $class_name::fetch(), but it was not a(n) $class_name object. It is a $id->class_name instead.");
						else return $id;
				}
				// not resumed, so let's assume its a string and return the cache
				else return $class_name::get_cache($id);
			}
	}

	/**
	 * Create a new object in this model.
	 * @param bool|string $id Id's are automatically generated by default, but you can force one here. Once created, id's cannot be changed.
	 * @return zajModel Returns a brand new zajModel object.
	 */
	public static function create($id = false){
		// Get my class_name
			/* @var zajModel $class_name */
			$class_name = get_called_class();
		// call event
			$class_name::fire_static('onCreate', array($class_name));
		// create the new object
			$new_object = new $class_name(false, $class_name);
		// if id specified
			if($id){
				// Make sure ID is valid
					if(!zajLib::me()->security->is_valid_id($id)) return zajLib::me()->warning("Tried to create object with invalid id: $id");
				// All ok, set it!
					$new_object->id = $id;
			}
		// do i have any extenders?
			/* @var zajModelExtender $ext */
			$ext = $class_name::extension();
			/* @var zajModel $new_object */
			if($ext) $new_object = $ext::create($id, $new_object);
		// call the callback function
			$new_object->fire('afterCreate');
		// and return
			return $new_object;
	}

	/**
	 * Set the value of a field for this object.
	 * @param string $field_name The name of model field.
	 * @param mixed $value The new value of the field.
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set($field_name, $value){
		// disable for non-database objects
			if(!$this::$in_database) return false;
		// only allow unit_test when tests are running
			if($field_name == 'unit_test' && !$this->zajlib->test->is_running()) return $this->zajlib->error("Cannot set field unit_test while not running a test!");
		// init the data object if not done already
			if(!$this->data) $this->data = new zajData($this);
		// set it in the data object
			$this->data->__set($field_name, $value);
		return $this;
	}

	/**
	 * Sets all the fields specified by the list of parameters. It uses GET or POST requests, and ignores fields where no value was sent (that is, not even an empty value). In cases where you need more control, use {@link set()} for each individual field.
	 * @note string $field_name1 The first parameter to set.
	 * @note string $field_name2 The second parameter to set.
	 * @note string $field_name3 The third parameter to set...etc...
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set_these(){
		// Use _GET or _POST
		$_POST = array_merge($_GET, $_POST);
		// Run through each argument
		foreach(func_get_args() as $field_name){
			$this->set($field_name, $_POST[$field_name]);
		}
		return $this;
	}

	/**
	 * Sets all fields of model data based on a passed associative array or object.
	 * @param array|stdClass $data The data to create from. This can be a standard class or associative array.
	 * @param array $fields_allowed If this array is specified, only the fields in the array will be updated.
	 * @param array $fields_ignored If this array is specified, the fields in the array will be ignored. You should use either this or allowed.
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set_with_data($data, $fields_allowed = [], $fields_ignored = []){
	    // Fields to ignore @todo move this to field settings somehow
	    $fields_ignored = array_merge($fields_ignored, ['unit_test', 'id', 'time_create', 'time_edit', 'ordernum', 'translation']);

		// Verify data
        $data = (object) $data;
        if(!is_object($data)){
            $this->zajlib->warning("Called set_with_data() with invalid data. Must be an object or array.");
            return $this;
        }

		// Set data fields
        foreach($data as $field_name => $field_value){
            if(
                // Fields allowed is empty or the field is in it
                (count($fields_allowed) == 0 || in_array($field_name, $fields_allowed))
                // Field is not in ignored fields
                && !in_array($field_name, $fields_ignored)
            ){
                $this->set($field_name, $field_value);
            }
        }

        // Also set translations if applicable
        $this->set_translations_with_data($data, $fields_allowed, $fields_ignored);

		return $this;
	}

	/**
	 * Set the translation value of a field for this object.
	 * @param string $field_name The name of model field.
	 * @param mixed $value The new value of the field.
	 * @param string $locale The locale for which to set this translation.
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set_translation($field_name, $value, $locale){
		// disable for non-database objects
			if(!$this::$in_database) return false;
		// if default locale, use set
			if($locale == $this->zajlib->lang->get_default_locale()) return $this->set($field_name, $value);
		// init the data object if not done already
			$tobj = Translation::create_by_properties($this->class_name, $this->id, $field_name, $locale);
			$tobj->set('value', $value);
			$tobj->save();
		return $this;
	}

	/**
	 * Sets the translation of all the locales of all the fields specified by the list of parameters. It uses GET or POST requests, and ignores fields where no value was sent (that is, not even an empty value). In cases where you need more control, use {@link set_translation()} for each individual field.
	 * @note string $field_name1 The first parameter to set.
	 * @note string $field_name2 The second parameter to set.
	 * @note string $field_name3 The third parameter to set...etc...
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set_translations(){
        // If only one locale, then return
        if(count($this->zajlib->lang->get_locales()) <= 1) return $this;

		// Use _GET or _POST
		$_POST = array_merge($_GET, $_POST);
		// Run through each argument
		foreach(func_get_args() as $field_name){
			foreach($_POST['translation'][$field_name] as $locale=>$value){
				$this->set_translation($field_name, $value, $locale);
			}
		}
		return $this;
	}

	/**
	 * Sets the translation of all the locales with data much like set_with_data(). set_with_data() will call this if translation keys exist
	 * @param array|stdClass $data The data to create from. This can be a standard class or associative array.
	 * @param array $fields_allowed If this array is specified, only the fields in the array will be updated.
	 * @param array $fields_ignored If this array is specified, the fields in the array will be ignored. You should use either this or allowed.
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set_translations_with_data($data, $fields_allowed = [], $fields_ignored = []){
        // If only one locale, then return
        if(count($this->zajlib->lang->get_locales()) <= 1) return $this;

        // Validate data. Unlike set_with_data this is optional so will not fail if empty
        $data = (object) $data;
        if(!is_object($data)) return $this;

	    // Fields to ignore @todo move this to field settings somehow
	    $fields_ignored = array_merge($fields_ignored, ['unit_test', 'id', 'time_create', 'time_edit', 'ordernum', 'translation']);

        // Check to see if this is the root data or the translations data
        if(is_object($data->translation) || is_array($data->translation)){
            $data = (object) $data->translation;
        }
        else return $this;

        // Loop through each field
        foreach($data as $field_name=>$locale_values){
            if(
                // Fields allowed is empty or the field is in it
                (count($fields_allowed) == 0 || in_array($field_name, $fields_allowed))
                // Field is not in ignored fields
                && !in_array($field_name, $fields_ignored)
            ){
                foreach($locale_values as $locale=>$value){
                    $this->set_translation($field_name, $value, $locale);
                }
            }
        }

        return $this;
	}

	/**
	 * Save the values set by {@link: set()} to the database.
	 * @param boolean $events_and_cache If set to true, the before and after events and cacheing will be fired. If false, they will be ignored. This is useful when you are trying to speed things up (during import of data for example).
	 * @return zajModel|boolean Returns the chainable object if successful, false if beforeSave prevented it.
	 */
	public function save($events_and_cache = true){
		// same as cache() for non-database objects
		if(!$this::$in_database) return $this->cache();
		// init the data object if not done already
		if(!$this->data) $this->data = new zajData($this);
		// call beforeCreateSave event (only if this does not exist yet)
		$exists_before_save = $this->data->exists();
		if($events_and_cache && !$exists_before_save) $this->fire('beforeCreateSave');
		// call beforeSave event
		if($events_and_cache && $this->fire('beforeSave') === false) return false;
		// if unit test is running, set the unit_test field
		if(zajLib::me()->test->is_running()) $this->set('unit_test', true);
		// set it in the data object
		$this->data->save();
		// i now exist
		$this->exists = true;
		// call afterSave events 
		if($events_and_cache && !$exists_before_save) $this->fire('afterCreateSave');
		if($events_and_cache) $this->fire('afterSave');
		if($events_and_cache) $this->fire('afterFetch');
		// IMPORANT: these same events also called after adding/removing connections if that is done by another object.
		// cache the new values
		if($events_and_cache) $this->cache();
		return $this;
	}

	/**
	 * Creates a duplicate copy of the object. Connections are not duplicated by default and the new object is not saved to the db, just returned.
	 * @param bool|string $id Id's are automatically generated by default, but you can force one here. Once created, id's cannot be changed.
	 * @return zajModel Returns an object whose fields are set to the same data values.
	 */
	public function duplicate($id = false){
		// Get my class name
			/* @var zajModel $class_name */
			$class_name = $this->class_name;
		// create the same object type
			$new = $class_name::create($id);
		// load up data object if not already done
			if(!$this->data) $this->data = new zajData($this);
		// run through all of my fields
			$model = $class_name::__model();
			foreach($model as $name=>$fielddata){
				/* @var zajDb $fielddata */
				if($fielddata->use_duplicate){
					$fobj = $class_name::__field($name);
					$data = $fobj->duplicate($this->data->$name, $this);
					$new->set($name, $data);
				}
			}
		return $new;
	}

	/**
	 * Convert model data to a standard single-dimensional array format.
	 * @todo Move these conversions to field definition files.
	 * @todo Add support for model extensions.
	 * @param boolean $localized Set to true if you want the localized version.
	 * @return array Return a single-dimensional array.
	 */
	public function to_array($localized = true){
		// Get my class name
			/* @var zajModel $class_name */
			$class_name = $this->class_name;
		// Get my model data
			$mymodel = $class_name::__model();
		// Load up data if not yet loaded
			if($localized){
				if(!$this->translations) $this->translations = new zajModelLocalizer($this);
				$data = $this->translations;
			}
			else{
				if(!$this->data) $this->data = new zajData($this);
				$data = $this->data;
			}
		// Now fetch array data
			$array_data = array();
			foreach($mymodel as $field_name => $field_type){
				switch($field_type->type){
					case 'manytoone':
						$array_data[$field_name] = $data->$field_name->id;
						break;
					case 'categories':
					case 'files':
					case 'photos':
					case 'onetomany':
					case 'manytomany':
						// Just skip these
						break;
					default:
						$array_data[$field_name] = $data->$field_name;
						break;
				}
			}
		return $array_data;
	}

	/**
	 * Implement json serialize method.
	 */
	public function jsonSerialize(){
		return $this->to_array();
	}

	/**
	 * Set the object status to deleted or remove from the database.
	 *
	 * @param boolean $permanent OPTIONAL. If set to true, object is permanently removed from db. Defaults to false.
	 * @return boolean Returns true if the item was deleted, false if beforeDelete prevented it.
	 */
	public function delete($permanent = false){
		// fire __beforeDelete event
		if($this->fire('beforeDelete') === false) return false;
		// same as cache removal for non-database objects
		if(!$this::$in_database) return $this->uncache();
		// init the data object if not done already
		if(!$this->data) $this->data = new zajData($this);
		// set it in the data object
		$this->data->delete($permanent);
		// now fire __afterDelete
		$this->fire('afterDelete');
		return true;
	}

	/**
	 * A static method which deletes all objects that were created during unit testing.
	 * @param boolean|integer $max_to_delete The maximum number of objects to remove. Defaults to false which means it is unlimited. Fatal error if more than this amount found.
	 * @param boolean $permanent If set to true, object is permanently removed from db. Defaults to true.
	 * @return integer Returns the number of objects deleted.
	 */
	public static function delete_tests($max_to_delete = false, $permanent = true){
		// Fetch the test objects
			$test_objects = self::fetch()->filter('unit_test', true);
			if($max_to_delete !== false && $test_objects->total > $max_to_delete) return zajLib::me()->error("Reached maximum number of test object deletes during unit test for ".get_called_class().". Allowance is ".$max_to_delete." but found ".$test_objects->total." objects. Delete manually or raise limit!", true);
		// Now remove!
			$test_objects_deleted = 0;
			/** @var zajModel $obj */
			foreach($test_objects as $obj){
				$obj->delete($permanent);
				$test_objects_deleted++;
			}
		return $test_objects_deleted;
	}

	/**
	 * Fire an event.
	 * @param string $event Event name.
	 * @param array|bool $arguments Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 * @todo Somehow disable event methods from being declared public. They should be private or protected!
	 * @todo Make this static-compatible (though you cannot do event stack in that case! or can you?)
	 * @todo Optimize this!
	 **/
	public function fire($event, $arguments = false){
		// Do I even need to fire a child?
		if(!$this->event_child_fired){
			// Do I have an extension? If so, go down one level and start from there...
			$ext = self::extension();
			if($ext){
				// Create my child object, set event fired to true, and fire it!
				$child = $ext::create($this->id, $this);
				$this->event_child_fired = true;
				return $child->fire($event, $arguments);
			}
		}
		// We are back here now, so set my child event fired to false
		$this->event_child_fired = false;
		// Add event to stack
		$stack_size = array_push($this->event_stack, $event);
		$this->zajlib->event_stack++;
		// Check stack size
		if($stack_size > MAX_EVENT_STACK) $this->zajlib->error("Exceeded maximum event stack size of ".MAX_EVENT_STACK." for object ".$this->class_name.". Possible infinite loop?");
		if($this->zajlib->event_stack > MAX_GLOBAL_EVENT_STACK) $this->zajlib->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments === false) $arguments = array();
		// Call event function
		$return_value = call_user_func_array(array($this, '__'.$event), $arguments);
		// Remove from stack
		array_pop($this->event_stack);
		$this->zajlib->event_stack--;
		// Return value
		return $return_value;
	}

	/**
	 * Fire a static event.
	 * @param string $event The name of the event.
	 * @param array|bool $arguments Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 */
	public static function fire_static($event, $arguments = false){
		// Make sure propagation is enabled
		zajModelExtender::$event_stop_propagation = false;
		// Get my class_name
		$class_name = get_called_class();
		// Add event to stack
		zajLib::me()->event_stack++;
		// Check stack size
		if(zajLib::me()->event_stack > MAX_GLOBAL_EVENT_STACK) zajLib::me()->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments === false) $arguments = array();
		// Do I have an extension? If so, go down one level...
		$ext = self::extension();
		if($ext) $return_value = $ext::fire_static($event, $arguments);
		// Check to see if stop propagation, if so, return the return_value
		if(zajModelExtender::$event_stop_propagation){
			zajModelExtender::$event_stop_propagation = false;
			zajLib::me()->event_stack--;
			return $return_value;
		}
		// Call my version
		if(method_exists($class_name, '__'.$event)) $return_value = call_user_func_array("$class_name::__".$event, $arguments);
		else $return_value = false;
		// Remove from stack
		zajLib::me()->event_stack--;
		// Return value
		return $return_value;
	}


	/**
	 * This method returns the class name of the class which extends me.
	 * @return string The name of my extension class.
	 **/
	public static function extension(){
		$class_name = get_called_class();
		if(!empty(zajModel::${'extensions'}[$class_name])) return zajModel::${'extensions'}[$class_name];
		return false;
	}

	/**
	 * Checks if the passed object is a type of me.
	 * @param mixed $object Checks if the passed variable is instance of me.
	 * @return boolean True if yes, false if not.
	 */
	public static function is_instance_of_me($object){
		// Get my class name
		$class_name = get_called_class();
		return is_a($object, $class_name) || is_a($object, 'zajModelExtender');
	}


	/**
	 * This method looks for methods in extends children and creates "virtual" menthods to events and actions.
	 *
	 * @ignore
	 */
	public function __call($name, $arguments){
		// Get my class name
		$class_name = get_called_class();
		// zajModel events
			// @todo We need to check if these magic methods are available in any of our extensions
		switch($name){
			case '__beforeCreateSave':
			case '__beforeSave':
			case '__beforeCache':
			case '__beforeUncache':
			case '__beforeDelete':
				return true;
			case '__afterCreateSave':
			case '__afterCreate':
			case '__afterSave':
			case '__afterDelete':
			case '__afterFetch':
			case '__afterFetchCache':
			case '__afterCache':
			case '__afterUncache':
				return true;
            case '__toSearchApiJson':
                return ['id'=>$this->id, 'name'=>$this->__get('name')];
			default:		break;
		}
		// Search for the method in any of my parents

		// Search for the method in any of my children
		$child_class_name = $class_name;
		// Set my extension and repeat while it exists
		$my_extension = $child_class_name::extension();

		while($my_extension){
			// Let's check to see if the method exists here
			//print "Trying in $child_class_name / $my_extension";
			// @todo ADD SUPPORT FOR OVERRIDDEN OBJECT METHODS. NEED TO CREATE A MOCK OBJECT HERE THAT EXTENDS ME. See user extention in project.
			//if(method_exists($my_extension, $name)) return call_user_func_array("$my_extension->$name", $arguments);
            if(method_exists($child_class_name, $name)) return call_user_func_array("$child_class_name->$name", $arguments);
            // Not found, now go up one level
			else $child_class_name = $my_extension;
			// Set my extension
			$my_extension = $child_class_name::extension();
		}
		// Not found anywhere, return error!
		$this->zajlib->warning("Method $name not found in model '$class_name' or any of it's child models.");
	}

	/**
	 * Shortcuts to static events and actions.
	 *
	 * @ignore
	 */
	public static function __callStatic($name, $arguments){
		// get current class
		$class_name = get_called_class();
		// any specific static?
		switch($name){
			// Validation
			case 'validate':			return zajLib::me()->form->validate($class_name, $arguments);
			case 'check':				return zajLib::me()->form->check($class_name, $arguments);
			case 'filled':				return zajLib::me()->form->filled($arguments);
			// Extending
			case 'extend':
			case 'extension_of':
				zajLib::me()->error("The class $arguments[0] is not a child of zajModelExtender. Check the valid syntax for extending classes!");
				return false;
		}
		// do I have an extension? if so, these override my own settings but only if method is not __model() as that is special!
		$extended_but_does_not_exist = false;
		$ext = $class_name::extension();
		if($ext && $name != '__model' && $name != 'create'){
			// now, check if method exists on extension
			if(method_exists($ext, $name)){
				return call_user_func_array("$ext::$name", $arguments);
			}
			else  $extended_but_does_not_exist = true;
		}
		// check for events
		switch($name){
			case '__onSearch':
				if(!method_exists($arguments[0], $name)) return zajLib::me()->warning("You are trying to access the client-side search API for ".$class_name." and this is not enabled for this model. <a href='http://framework.outlast.hu/advanced/client-side-search-api/' target='_blank'>See docs</a>.");
			case '__onSearchFetcher':
			    return false;
			case '__onFilterQueryFetcher':
			    return false;
		}
		// redirect static method calls to local private ones
		if(!method_exists($arguments[0], $name)) zajLib::me()->error("called undefined method '$name'!"); return call_user_func_array("$arguments[0]::$name", $arguments);
	}
	/**
	 * Shortcuts to private variables (lazy loading)
	 *
	 * @ignore
	 */
	public function __get($name){
		// the zajlib
		switch($name){
			case "zajlib": 		return zajLib::me();
			case "data":
				if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!$this->data) return $this->data = new zajData($this);
				return $this->data;
			case "translation":
			case "translations":if(!$this::$has_translations) return false; 	// disable where no translations available
				if(!$this->translations || $this->translations->get_locale() != zajLib::me()->lang->get()) return $this->translations = new zajModelLocalizer($this);
				return $this->translations;
			case "autosave":	if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!$this->data) $this->data = new zajData($this);
				// turn on autosave
				$this->data->__autosave = true;
				$returned = $this->data;
				return $returned;
			case "model":		if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!$this->model) return $this->model = $this->__model();
				else return $this->model;
			case "exists":		if(!$this::$in_database) return true; 	// always return true for non-database objects
				if(!$this->data) $this->data = new zajData($this);
				return $this->data->exists();
			case "name":		if(!$this::$in_database || $this->name) return $this->name;
				// load model if not yet loaded
				if(!$this->model) $this->model = $this->__model();
				// load data
				if(!$this->data) $this->data = new zajData($this);
				// look for name and return if found
				foreach($this->model as $field=>$fdata){
					if($fdata->type == 'name'){					// actual name field
						$this->name_key = $field;
						return $this->name = $this->data->$field;
					}
					if(!$this->name && $fdata->type == 'text'){ 	// first text field
						$this->name_key = $field;
						$this->name = $this->data->$field;
					}
				}
				return $this->name;
			case "name_key":	if(!$this::$in_database) return false; 	// disable for non-database objects
				if($this->name_key) return $this->name_key;
				// load name
				$this->__get("name");
				// now return it
				return $this->name_key;
		}

		// Enable extended __get()
		$class_name = get_called_class();
		// Am I extended?
		$ext = $class_name::extension();
		if(method_exists($ext, '__get')){
			$extobj = $ext::create($this->id, $this);
			return $extobj->__get($name);
		}
	}
	/**
	 * @ignore
	 */
	public function __toString(){
		return $this->__get('name');
	}

	/**
	 * Gets the cached version of an object.
	 * @param string $id. The id of the object.
	 * @return zajModel|bool Returns the object or false if failed.
	 * @ignore
	 * @todo Disable get_cache from being called outside. Events should be used instead of overriding...
	 */
	public static function get_cache($id){
		// get current class
		$class_name = get_called_class();
		$zajlib = zajLib::me();
		// sanitize id just to be safe
		if(!$zajlib->security->is_valid_id($id)) return false;

		// return the resumed class
		$filename = zajLib::me()->file->get_id_path(zajLib::me()->basepath."cache/object/".$class_name, $id.".cache", false, CACHE_DIR_LEVEL);
		// try opening the file and unserializing
		$item_cached = false;
		if(!file_exists($filename)){
			// create object
			$new_object = new $class_name($id, $class_name);
			// get my name (this will grab the db)
			if($new_object::$in_database) $new_object->__get('name');
		}
		else{
			$new_object = unserialize(file_get_contents($filename));
			if(is_object($new_object)){
				$new_object->zajlib = zajLib::me();
				$item_cached = true;
			}
		}
		if(!method_exists($new_object, 'fire') || $new_object->class_name != $class_name){
			// @todo Remove this logging once the problem has been solved!
			//zajLib::me()->warning("Class mismatch for cache ($item_cached): ".$class_name." / ".$new_object->class_name." / $id / ".$new_object->id);
			copy($filename, zajLib::me()->basepath.'cache/_mismatched/'.$class_name.'-'.$id.'.cache');
			file_put_contents(zajLib::me()->basepath.'cache/_mismatched/printr_'.$class_name.'-'.$id.'.cache', print_r($new_object, true));

			// Refetch from db
			// create object
			$new_object = new $class_name($id, $class_name);
			// get my name (this will grab the db)
			if($new_object::$in_database) $new_object->__get('name');
			$item_cached = false;
		}


		// this is resumed from the db, so load the data
		if(!$new_object->exists && !$item_cached){
			// if in database load data
			if($new_object::$in_database){
				$new_object->data = new zajData($new_object);
				$new_object->exists = $new_object->data->exists();
			}
			// if does not exist, send back with false (only for ones with database)
			if($new_object::$in_database && !$new_object->exists) return false;
			// else, send to callback
			$new_object->fire('afterFetch');
			// now save to cache
			$new_object->cache();
		}
		// end of db fetch

		// one more callback, before finishing and returning
		$new_object->fire('afterFetchCache');
		return $new_object;
	}

	/**
	 * Remove a cached version of an object.
	 *
	 * @return zajModel|boolean Returns the chainable object if successful, false if beforeUncache prevented it.
	 */
	public function uncache(){
		// call __beforeUncache event
		if($this->fire('beforeUncache') === false) return false;
		// sanitize id just to be safe
		if(!zajLib::me()->security->is_valid_id($this->id)) return zajLib::me()->warning("Tried to uncache with invalid id: $this->id");
		// return the resumed class
		$filename = $this->zajlib->file->get_id_path($this->zajlib->basepath."cache/object/".$this->class_name, $this->id.".cache", false, CACHE_DIR_LEVEL);
		// if remove is successful, call __afterUncache event and return true. false otherwise
		if(!@unlink($filename)) return false;
		else{
			$this->fire('afterUncache');
			return $this;
		}
	}
	/**
	 * Create a cached version of the object.
	 *
	 * @return zajModel|boolean Returns the chainable object if successful, false if beforeCache prevented it.
	 */
	public function cache(){
		// call __beforeCache event
		if($this->fire('beforeCache') === false) return false;
		// if not in_database, then this is creating it, so exists will equal to true
		if(!$this::$in_database) $this->exists = true;
		// sanitize id just to be safe
		if(!zajLib::me()->security->is_valid_id($this->id)) return zajLib::me()->warning("Tried to save cache with invalid id: $this->id");
		// get filename
		$filename = $this->zajlib->file->get_id_path($this->zajlib->basepath."cache/object/".$this->class_name,$this->id.".cache", true, CACHE_DIR_LEVEL);

		// model, data do not need to be saved!
		$data = $this->data;
		$model = $this->model;
		$event_stack = $this->event_stack;
		$event_child_fired = $this->event_child_fired;
		unset($this->zajlib, $this->data, $this->model, $this->event_stack, $this->event_child_fired);

		if($this->fetchdata){
		    $fetchdata = $this->fetchdata;
		    unset($this->fetchdata);
        }
		if($this->translations){
		    $translations = $this->translations;
		    unset($this->translations);
        }

		// check for objects
		foreach($this as $varname=>$varval){
            if(is_a($varval, 'zajModel') || is_a($varval, 'zajFetcher')){
                zajLib::me()->warning("You cannot cache an zajModel or zajFetcher object! Stick to simple data types. This will be a fatal error in the future. Found at variable $this->class_name / $varname.");
            }
        }

		// now serialize and save to file
		file_put_contents($filename, serialize($this));

		// now bring back data
		$this->data = $data;
		$this->model = $model;
		$this->event_stack = $event_stack;
		$this->event_child_fired = $event_child_fired;
		if(!empty($translations)) $this->translations = $translations;
		if(!empty($fetchdata)) $this->fetchdata = $fetchdata;
		$this->zajlib = zajLib::me();
		// call the callback function
		$this->fire('afterCache');
		return $this;
	}

	/**
	 * Reorder the item's ordernum based on the order of ids in the passed array.
	 * @param array $reorder_array An array of object ids.
	 * @param bool $reverse_order If set to true, the reverse order will be taken into account.
	 * @return bool Always returns true.
	 */
	public static function reorder($reorder_array, $reverse_order = false){
		// get current class
		$class_name = get_called_class();
		// this supports JSON input from a Sortables.serialize method of mootools, always returns true
		if(is_string($reorder_array)) $reorder_data = json_decode($reorder_array);
		else $reorder_data = $reorder_array;
		// continue processing the array of ids
		if((is_object($reorder_data) || is_array($reorder_data)) && count($reorder_data) > 0){
			// get the order num of each
			foreach($reorder_data as $oneid){
				/** @var zajModel $class_name */
				$obj = $class_name::fetch($oneid);
				// if failed to find, issue warning
				if(!is_object($obj) || !is_a($obj, 'zajModel')) zajLib::me()->warning("Tried to reorder non-existant object!");
				// all is okay
				else{
					// TODO: fix, but for now explicitly load data class, because autoload won't work in current scope
					$myobj[$oneid] = $obj;
					$myobj[$oneid]->data = new zajData($myobj[$oneid]);
					$array_of_ordernums[] = $myobj[$oneid]->data->ordernum;
				}
			}
			// Only proceed if actual array done
			if(is_array($array_of_ordernums)){
				// place them in order of descendence
				if($class_name::$fetch_order=="DESC" && !$reverse_order || $class_name::$fetch_order=="ASC" && $reverse_order) rsort($array_of_ordernums);
				else sort($array_of_ordernums);
				// now start with the first
				$current_ordernum = reset($array_of_ordernums);
				// now set their order id
				/** @var zajModel $oneobj **/
				foreach($myobj as $oneobj){
					$oneobj->set('ordernum',$current_ordernum);
					$oneobj->save();
					$current_ordernum = next($array_of_ordernums);
				}
			}
		}
		return true;
	}

}

/**
 * This is the abstract extender model class from which all extended model classes are derived.
 *
 * @author Aron Budinszky <aron@outlast.hu>
 * @package Model
 * @subpackage DefaultModel
 * @abstract Model files which extend other models should extend this base class.
 * @extends zajModel
 */
abstract class zajModelExtender {
	// Instance variables
	/**
	 * A pointer to my parent object.
	 * @var zajModel
	 **/
	public $parent;

	/**
	 * The name of my own class.
	 * @var string
	 **/
	private $class_name;

	/**
	 * A key/value pair array of all parent models
	 * @var array
	 * @todo If it is possible to store this on a per-class basis, it would be better than this 'global' way!
	 **/
	private static $parents = array();

	/**
	 * The number of extender events currently running.
	 * @var integer
	 **/
	private static $event_stack_size = 0;

	/**
	 * Set to true if the current event propagation needs to be stopped (done by the static method STOP_PROPAGATION!)
	 * @var boolean
	 **/
	public static $event_stop_propagation = false;

	/**
	 * Set to true if this object should be stored in the database.
	 * @todo This should be set somehow based on parent.
	 * @var boolean
	 **/
	public static $in_database = true;

	/**
	 * Constructor for extender objects.
	 * @param zajModel $parent My parent object.
	 * @return zajModelExtender
	 */
	private function __construct($parent){ $this->parent = $parent; }

	/**
	 * This method allows you to extend existing models in a customized fashion.
	 * @param string $parentmodel The name of the model to extend. By default, Mozajik will try to extend plugins. If you need to extend something else, use the $model_source_file parameter.
	 * @param bool|string $known_as The name which it will be known by to controllers trying to access this model. By default, it is known by the name of the model it extends.
	 * @param bool|string $parentmodel_source_file An optional parameter which specifies the relative path to the source file containing the model to extend.
	 * @return bool
	 * @todo Once there is a solution for non-explicitly declared static variables, use that! See http://stackoverflow.com/questions/5513484/php-static-variables-in-an-abstract-parent-class-question-is-in-the-sample-code
     * @todo Use load->get_app_folder_paths();
	 */
	public static function extend($parentmodel, $known_as = false, $parentmodel_source_file = false){
		// Check to see if already extended (this will never run because once it is extended the parent class will exist, and any additional iterations will not autoload the other model file! fix this somehow to warn the user!)
		// if(!empty(zajModel::${extensions}[$parentmodel])) return zajLib::me()->error("Could not extend $parentmodel with $childmodel because the class $parentmodel was already extended by ".zajModel::${extensions}[$parentmodel].".");
		// Determine where the user called from
		$childmodel = get_called_class();
		// If a specific parentmodel source file was specified, use that!
		if(!class_exists($parentmodel, false) && $parentmodel_source_file) zajLib::me()->load->file($parentmodel_source_file, true, true, "specific");
		// If the current class does not exist, try to load it from all files in the plugin app hierarchy
		if(!class_exists($parentmodel, false)){
			foreach(zajLib::me()->loaded_plugins as $plugin_app){
				// Attempt to load file
				$result = zajLib::me()->load->file('plugins/'.$plugin_app.'/model/'.strtolower($parentmodel).'.model.php', false, true, "specific");
				// If successful, break
				if($result && class_exists($parentmodel, false)) break;
			}
		}
		// If the current class does not exist, try to load it from all files in the system app hierarchy
		if(!class_exists($parentmodel, false)){
			foreach(zajLib::me()->zajconf['system_apps'] as $system_app){
				// Attempt to load file
				$result = zajLib::me()->load->file('system/plugins/'.$system_app.'/model/'.strtolower($parentmodel).'.model.php', false, true, "specific");
				// If successful, break
				if($result && class_exists($parentmodel, false)) break;
			}
		}
		// If the current class still does not exist, try the system itself
		if(!class_exists($parentmodel, false)){
			// Attempt to load file
			zajLib::me()->load->file('system/app/model/'.strtolower($parentmodel).'.model.php', false, true, "specific");
		}

		// See if successful
		if(class_exists($parentmodel, false)){
			// Add to my extensions
			zajModel::${'extensions'}[$parentmodel] = $childmodel;
			// Add to my parents
			zajModelExtender::${'parents'}[$childmodel] = $parentmodel;
			return true;
		}
		else return zajLib::me()->error("Could not extend $parentmodel with $childmodel because the class $parentmodel was not found in any plugin or system apps.");
	}

	/**
	 * This method returns the class name of my parent class.
	 * @return string The name of my extension class.
	 **/
	public static function extension_of(){
		$class_name = get_called_class();
		return zajModelExtender::${'parents'}[$class_name];
	}

	/**
	 * This method returns the class name of the class which extends me.
	 * @return string The name of my extension class.
	 **/
	public static function extension(){
		$class_name = get_called_class();
		return zajModel::${'extensions'}[$class_name];
	}

	/**
	 * Override model. Check to see if I have extensions and extend me.
	 * @param bool|stdClass $fields The field's object generated by the child class.
 	 * @return stdClass Returns an object containing the field settings as parameters.
	 **/
	public static function __model($fields = false){
		$class_name = get_called_class();
		// do I have an extension? if so, these override my own settings
		/* @var zajModelExtender $class_name */
		/* @var zajModel $ext */
		$ext = $class_name::extension();
		if($ext) $fields = $ext::__model($fields);
		return $fields;
	}

	/**
	 * This helps create a model-like object which is actually an extender object.
	 * @param bool|string $id Id's are automatically generated by default, but you can force one here. Once created, id's cannot be changed.
	 * @param bool|zajModel|zajModelExtender $parent_object An explicitly specified parent object.
	 * @return zajModelExtender The extended zajModel object in the form of a zajModelExtender object.
	 */
	public static function create($id = false, $parent_object = false){
		$class_name = get_called_class();
		// get my extension
			/* @var zajModelExtender $class_name */
			$ext_of = $class_name::extension_of();
		// get my parent object
			/* @var zajModel|zajModelExtender $ext_of */
			if($parent_object === false) $parent_object = $ext_of::create($id);
		// create a new class based on my parent_object
			$object = new $class_name($parent_object, $class_name);
			$object->class_name = $class_name;
		// do i have an extension? then create that too...
			/* @var zajModelExtender $ext */
			$ext = $class_name::extension();
			if($ext) $object = $ext::create($id, $object);
		return $object;
	}

	/**
	 * Redirect inaccessible static method calls to my parent.
	 **/
	public static function __callStatic($name, $arguments){
		$class_name = get_called_class();
		/* @var zajModelExtender $class_name */
		$parent_class = $class_name::extension_of();
		// redirect static method calls to local private ones
		return call_user_func_array("$parent_class::$name", $arguments);
	}

	/**
	 * Redirect inaccessible method calls to my parent.
	 **/
	public function __call($name, $arguments){
		// call the method whether-or-not it exists, parent should handle errors...
		return call_user_func_array(array($this->parent, $name), $arguments);
	}

	/**
	 * Redirect inaccessible property setters to my parent.
	 **/
	public function __set($name, $value){
		return $this->parent->$name = $value;
	}

	/**
	 * Redirect inaccessible property getters to my parent.
	 **/
	public function __get($name){
		return $this->parent->$name;
	}

	/**
	 * Fire event for me if it exists, then just send on to parent.
	 *
	 * Firing non-static event methods starts at the child and goes to parent.
	 * @parameter string $event The name of the event.
	 * @parameter array $arguments An optional array of arguments to be passed to the event handler.
	 * @todo Add support for static events.
	 * @todo Add support for event stack.
	 **/
	public function fire($event, $arguments = false){
		// Make sure propagation is enabled
		zajModelExtender::$event_stop_propagation = false;
		// Add to current event stack
		zajModelExtender::$event_stack_size++;
		// Check event stack size
		if(zajModelExtender::$event_stack_size > MAX_EVENT_STACK) return $this->zajlib->error("Maximum extender event stack size exceeded. You probably have an infinite loop somewhere!");
		// Check to see if event function exists
		if(!$arguments) $arguments = array();
		if(method_exists($this, '__'.$event)) $return_value = call_user_func_array(array($this, '__'.$event), $arguments);
		// Subtract from current event stack
		zajModelExtender::$event_stack_size--;
		// Check to see if stop propagation, if so, return the return_value
		if(zajModelExtender::$event_stop_propagation){
			zajModelExtender::$event_stop_propagation = false;
			return $return_value;
		}
		// Now call for parent but tell them the child has been fired
		else{
			$this->parent->event_child_fired = true;
			return $this->parent->fire($event, $arguments);
		}
	}

	/**
	 * Fire a static event.
	 *
	 * Firing static event methods starts at the parent, but goes to child before the parent is executed. Thus the result is the same as with non-static events.
	 * @param string $event Event name.
	 * @param array|bool Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 **/
	public static function fire_static($event, $arguments = false){
		// Make sure propagation is enabled
		zajModelExtender::$event_stop_propagation = false;
		// Get my class_name
		$class_name = get_called_class();
		// Add event to stack
		zajLib::me()->event_stack++;
		// Check stack size
		if(zajLib::me()->event_stack > MAX_GLOBAL_EVENT_STACK) zajLib::me()->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments === false) $arguments = array();
		// Do I have an extension? If so, go down one level...
		$ext = self::extension();
		if($ext) $return_value = $ext::fire_static($event, $arguments);
		// Check to see if stop propagation, if so, return the return_value
		if(zajModelExtender::$event_stop_propagation){
			zajLib::me()->event_stack--;
			return $return_value;
		}
		// Call my version
		if(method_exists($class_name, '__'.$event)) $return_value = call_user_func_array("$class_name::__".$event, $arguments);
		else $return_value = false;
		// Remove from stack
		zajLib::me()->event_stack--;
		// Return value
		return $return_value;
	}


	/**
	 * A static method used to set stop_propagation. This stops events from moving up my ancestors and forces the event to return the current value.
	 **/
	public static function stop_propagation(){
		zajModelExtender::$event_stop_propagation = true;
	}

}

/**
 * This class allows the model data translations to be fetched easily.
 *
 * @author Aron Budinszky <aron@outlast.hu>
 * @package Model
 * @subpackage DefaultModel
 */
class zajModelLocalizer {

    /** @var string */
    private $locale;

    /** @var zajModel */
    private $parent;

	/**
	 * Create a new localizer object.
     * @param zajModel $parent The parent object.
     * @param string|boolean $locale The locale (defaults to current).
	 **/
	public function __construct($parent, $locale = false){
		if($locale != false) $this->locale = $locale;
		else $this->locale = zajLib::me()->lang->get();
		$this->parent = $parent;
	}

    /**
     * Return the locale of the current item.
     */
    public function get_locale(){
        return $this->locale;
    }

	/**
	 * Return data using the __get() method.
     * @param string $name The name of the field to return.
     * @return zajModelLocalizerItem Returns the zajModelLocalizerItem object.
	 **/
	public function __get($name){
		return new zajModelLocalizerItem($this->parent, $name, $this->locale);
	}

}

/**
 * Helper class for a specific localization item. You can 'print' it (__toString) to get the translation
 * @todo Caching needs to be added to these!
 **/
class zajModelLocalizerItem implements JsonSerializable {

	/** Make all variables private **/
	private $parent;
	private $fieldname;
	private $locale;

	/**
	 * Create a new localizer item.
	 * @param zajModel $parent The parent object. This is not just the id, it's the object!
	 * @param string $fieldname The field name of the parent object.
	 * @param string $locale The locale of the translation.
	 **/
	public function __construct($parent, $fieldname, $locale){
		$this->parent = $parent;
		$this->fieldname = $fieldname;
		$this->locale = $locale;
	}

	/**
	 * Returns the translation for the object's set locale.
	 * @return mixed Return the translated value according to the object's locale.
	 **/
	public function get(){
		return $this->get_by_locale($this->locale);
	}

	/**
	 * Returns the translation for the given locale. It can be set to another locale if desired. If nothing set, the global default value will be returned (not a translation).
	 * @param string|boolean $locale The locale. If not set (or default locale set), the default value is returned.
	 * @return mixed Return the translated value.
	 **/
	public function get_by_locale($locale = false){
		// Locale is not set or is default, so return the default value
		if(empty($locale) || $locale == zajLib::me()->lang->get_default_locale()) return $this->parent->data->{$this->fieldname};
		// A translation is requested, so let's retrieve it
		$tobj = Translation::fetch_by_properties($this->parent->class_name, $this->parent->id, $this->fieldname, $locale);
		if($tobj !== false) $field_value = $tobj->value;
		else $field_value = "";
		// check if translation filter is to be used
		// TODO: ADD THIS!
		// if not, filter through the usual get
		if($this->parent->model->{$this->fieldname}->use_get){
			// load my field object
			$field_object = zajField::create($this->fieldname, $this->parent->model->{$this->fieldname});
			// if no value, set to null (avoids notices)
			if(empty($field_value)) $field_value = null;
			// process get
			return $field_object->get($field_value, $this->parent);
		}
		// otherwise, just return the unprocessed value
		return $field_value;
	}

	/**
	 * Invoked as an object so must return properties. Return the default value if no translation.
	 * @param string $name The name of the field to return.
	 * @return mixed Return the translated value or the default lang value if no translation available.
	 **/
	public function __get($name){
		// Get the property of the value
		$value = $this->get()->$name;
		if($value !== '') return $value;
		else return $this->parent->data->$name;
	}

	/**
	 * Simply printing this object will result in the translation being printed. Return the default value if no translation.
	 * @return mixed Return the translated value or the default lang value if no translation available.
	 **/
	public function __toString(){
		$value = $this->get();
		$fieldname = $this->fieldname;
		if($value !== '') return $value;
		else return $this->parent->data->$fieldname;
	}

	/**
	 * Implement json serialize method.
	 */
	public function jsonSerialize(){
		return $this->__toString();
	}

}
