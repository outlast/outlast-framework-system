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

const MAX_EVENT_STACK = 50;
const CACHE_DIR_LEVEL = 4;

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
 * zajLib $zajlib A pointer to the global object. - deprecated
 * @property zajLib $ofw A pointer to the singleton OFW object.
 * @property string $name The name of the object.
 * @property ?string $name_key The key of the field where the name is stored. Will be null for non-db objects.
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
	public string $id;
	/**
	 * Stores the value of the name field.
	 * @var ?string
	 **/
	private ?string $name;
	/**
	 * Stores the name (or key) of the name field.
	 * @var ?string
	 **/
	private ?string $name_key;

	// Model structure
	/**
	 * Stores the field type definitions. See {@see zajDb}.
	 * @var stdClass
	 **/
	protected stdClass $model;
	/**
	 * True if the object exists in the database, false otherwise.
	 * @var boolean
	 **/
	protected bool $exists = false;

	// Model settings
	/**
	 * Set to true if this object should be stored in the database.
	 * @var boolean
	 **/
	public static bool $in_database = true;
	/**
	 * Set to true if this object should have translations associated with it.
	 * @var boolean
	 **/
	public static bool $has_translations = true;
	/**
	 * Set to DESC or ASC depending on the default fetch sort order.
	 * @var string
	 **/
	public static string $fetch_order = 'DESC';
	/**
	 * Set to the field which should be the default fetch sort field.
	 * @var string
	 **/
	public static string $fetch_order_field = 'ordernum';
	/**
	 * Set the pagination default or leave as unlimited (which is the default value of 0)
	 * @var integer
	 **/
	public static int $fetch_paginate = 0;
	/**
	 * Connection type, used when object is fetched in connection to another. Empty if not a connected object.
	 * @var string
	 */
	public string $connection_type = '';

	// Mysql database and child details / settings
	/**
	 * My class (or model) name.
	 * @var string
	 **/
	public string $class_name = "zajModel";
	/**
	 * My table name (typically a lower-case form of class_name)
	 * @var string
	 **/
	public string $table_name = "models";
	/**
	 * My id column key/name ('id' by default)
	 * @var string
	 **/
	public string $id_column = "id";

	// Objects used by this class
	/**
	 * Access to the database-stored data through the object's own {@link zajData} object.
	 * @var ?zajData
	 **/
	private ?zajData $data;

	/**
	 * Access to the database-stored data that is retrieved as custom query via the {@link zajFetcher} object's add_field_source method.
	 * @var ?stdClass
	 **/
	public ?stdClass $fetchdata;

	/**
	 * Access to the database-stored translation data through the object's own {@link zajModelLocalizer} object.
	 * @var ?zajModelLocalizer
	 **/
	protected ?zajModelLocalizer $translations;

	// Object event stack
	/**
	 * The event stack, which is basically an array of events currently running.
	 * @var array
	 **/
	protected array $event_stack = [];

	/**
	 * This is an object-specific private variable which registers if any extension of $this has had its event fired. This is used to prevent infinite loops.
	 * @var boolean
	 **/
	public bool $event_child_fired = false;


	// Model extension
	/**
	 * A key/value pair array of all extended models
	 * @var array
	 * @todo If it is possible to store this on a per-class basis, it would be better than this 'global' way!
	 **/
	public static array $extensions = [];

	/**
	 * Constructor for model object. You should never directly call this. Use {@link: create()} instead.
	 *
	 * @param string|int|null|bool $id The id of the object. Boolean false is allowed for backwards compatibility.
	 * @param string $class_name The name of child class (model class). This is deprecated and overridden anyway.
	 * @return zajModel
	 */
	public function __construct(string|int|null|bool $id, string $class_name = ''){
		$class_name = get_called_class();
		// check for errors
			if($id && !is_string($id) && !is_integer($id) && zajLib::me()->security->is_valid_id($id)) zajLib::me()->error("Invalid ID ($id) value given as parameter for model constructor! You probably tried to use an object instead of a string or integer!");
		// set class and table names
			$this->table_name = strtolower($class_name);
			$this->class_name = $class_name;
		// set id if its empty
			if($id == null) $this->id = uniqid("");
			else $this->id = $id;

		// everything else is loaded on request!
		return $this;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// !Static Methods
	/////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Defines and returns the model structure.
	 * @param stdClass $fields The field's object generated by the child class.
	  * @return stdClass Returns an object containing the field settings as parameters.
	 */
	public static function __model(stdClass $fields = new stdClass()) : stdClass {

		// Check fields
		if (count(get_object_vars($fields)) == 0) {
			zajLib::me()->error("Tried to get model without specifying fields. You must define at least a single field.");
		}

		// Get my class_name
		/* @var string|zajModel $class_name */
		$class_name = get_called_class();
		if(!$class_name::$in_database) return new stdClass(); 	// disable for non-database objects

		// do I have an extension? if so, merge fields
		/** @var zajModel $ext */
		$ext = $class_name::extension();
		if($ext) {
			$fields = $ext::__model($fields);
		}

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
	 * @return ?zajField Returns a zajField object or false if error.
	 */
	public static function __field(string $field_name) : ?zajField {
		// Get my class_name
		/* @var string|zajModel $class_name */
		$class_name = get_called_class();

		// make sure $field is chrooted
		if(strpos($field_name, '.')) {
			zajLib::me()->warning('Invalid field name "'.$field_name.'" used in model "'.$class_name.'".');
			return null;
		}

		// TODO: can I create a version where $this is set?

		// get model
		$model_def = $class_name::__model();
		if (!property_exists($model_def, $field_name)) {
			zajLib::me()->warning('Undefined field name "'.$field_name.'" used in model "'.$class_name.'".');
			return null;
		}
		$field_def = $model_def->$field_name;
		if(!isset($field_def)){
			zajLib::me()->warning('Incorrectly defined field name "'.$field_name.'" used in model "'.$class_name.'".');
			return null;
		}

		// create my field object
		return zajField::create($field_name, $field_def, $class_name);
	}

	/**
	 * Fetch a single or multiple existing object(s) of this class.
	 * @param string|zajModel|null $id OPTIONAL. The id of the object. Leave empty if you want to fetch multiple objects. You can also pass an existing zajModel object in which case it will simply pass through the function without change - this is useful so you can easily support both id's and existing objects in a function.
	 * @return zajFetcher|zajModel|null Returns a zajFetcher object (for multiple objects) or a zajModel object (for single objects) or null if failed to fetch.
	 */
	public static function fetch(string|zajModel|null $id = null) : zajFetcher|zajModel|null {
		// Get my class_name
			/* @var string|zajModel $class_name */
			$class_name = get_called_class();
		// Arguments passed?
			$args = func_get_args();
			if(count($args) > 0) $has_args = true;
			else $has_args = false;
		// if id is specifically null or empty, then return false
			if($has_args && (is_null($id) || $id === false || (is_string($id) && $id == ''))) return null;
		// call event
			$class_name::fire_static('onFetch', array($class_name, $id));
		// disable for non-database objects if id not given!
			if(!$has_args && !$class_name::$in_database) return null;
		// if id is not given, then this is a multi-row fetch
			if(!$has_args) return new zajFetcher($class_name);
		// let's see if i can resume it!
			else{
				// first, is it already resumed? in this case let's make sure its the proper kind of object and just return it
				if(is_object($id)){
					// is it the proper kind of object? if not, warning, if so, return it
						if($class_name != $id->class_name) {
							zajLib::me()->warning("You passed an object to $class_name::fetch(), but it was not a(n) $class_name object. It is a $id->class_name instead.");
							return null;
						}
						else return $id;
				}
				// not resumed, so let's assume its a string and return the cache
				else return $class_name::get_cache($id);
			}
	}

	/**
	 * Create a new object in this model.
	 * @param ?string $id Id's are automatically generated by default, but you can force one here. Once created, id's cannot be changed.
	 * @return zajModel|zajModelExtender|null Returns a brand new zajModel object. Null if an error occurred.
	 */
	public static function create(?string $id = null) : self|zajModelExtender|null {
		// Get my class_name
			/* @var zajModel $class_name */
			$class_name = get_called_class();
		// call event
			$class_name::fire_static('onCreate', array($class_name));
		// create the new object
			$new_object = new $class_name(false, $class_name);
		// if id specified
			if($id != null){
				// Make sure ID is valid
					if(!zajLib::me()->security->is_valid_id($id)) {
						zajLib::me()->warning("Tried to create object with invalid id: $id");
						return null;
					}
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
	 * Set a mock database for testing.
	 * @param mixed $db
	 */
	public function set_mock_database(mixed $db){
		// Only allow during testing
		if($this->ofw->test->is_running()){
			$this->data = new zajData($this, $db);
		}
	}

	/**
	 * Set the value of a field for this object.
	 * @param string $field_name The name of model field.
	 * @param mixed $value The new value of the field.
	 * @return zajModel Returns me to allow chaining.
	 */
	public function set(string $field_name, mixed $value) : self {
		// disable for non-database objects
			if(!$this::$in_database) {
				$this->ofw->error("Cannot set field when db not active!");
				return $this;
			}
		// only allow unit_test when tests are running
			if($field_name == 'unit_test' && !$this->ofw->test->is_running()) {
				$this->ofw->error("Cannot set field unit_test while not running a test!");
				return $this;
			}
		// init the data object if not done already
			if(!isset($this->data)) $this->data = new zajData($this);
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
	public function set_these() : self {
		// Use _GET or _POST
		$_POST = array_merge($_GET, $_POST);
		// Run through each argument
		foreach(func_get_args() as $field_name){
			$this->set($field_name, $_POST[$field_name] ?? null);
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
	public function set_with_data(array|stdClass $data, array $fields_allowed = [], array $fields_ignored = []) : self {
		// Fields to ignore @todo move this to field settings somehow
		$fields_ignored = array_merge($fields_ignored, ['unit_test', 'id', 'time_create', 'time_edit', 'ordernum', 'translation']);

		// Verify data
		$data = (object) $data;
		if(!is_object($data)){
			$this->ofw->warning("Called set_with_data() with invalid data. Must be an object or array.");
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
	public function set_translation(string $field_name, mixed $value, string $locale) : self {
		// disable for non-database objects
			if(!$this::$in_database || !$this::$has_translations) {
				zajLib::me()->error("Tried to set translation; but translations not enabled for model $this->class_name");
				return $this;
			}
		// if default locale, use set
			if($locale == $this->ofw->lang->get_default_locale()) return $this->set($field_name, $value);
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
	public function set_translations() : self {
		// If only one locale, then return
		if(count($this->ofw->lang->get_locales()) <= 1) return $this;

		// Use _GET or _POST
		$_POST = array_merge($_GET, $_POST);
		// Run through each argument
		foreach(func_get_args() as $field_name){
			foreach(($_POST['translation'][$field_name] ?? []) as $locale=>$value){
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
	public function set_translations_with_data(array|stdClass $data, array $fields_allowed = [], array $fields_ignored = []) : self {
		// If only one locale, then return
		if(count($this->ofw->lang->get_locales()) <= 1) return $this;

		// Validate data. Unlike set_with_data this is optional so will not fail if empty
		$data = (object) $data;
		if(!is_object($data)) return $this;

		// Fields to ignore @todo move this to field settings somehow
		$fields_ignored = array_merge($fields_ignored, ['unit_test', 'id', 'time_create', 'time_edit', 'ordernum', 'translation']);

		// Check to see if this is the root data or the translations data
		if(is_object($data->translation ?? null) || is_array($data->translation ?? null)){
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
	 * @return ?zajModel Returns the chainable object if successful, null if beforeSave prevented it.
	 */
	public function save(bool $events_and_cache = true) : ?zajModel {
		// same as cache() for non-database objects
		if(!$this::$in_database) return $this->cache();
		// init the data object if not done already
		if(!isset($this->data)) $this->data = new zajData($this);
		// call beforeCreateSave event (only if this does not exist yet)
		$exists_before_save = $this->data->exists();
		if($events_and_cache && !$exists_before_save) $this->fire('beforeCreateSave');
		// call beforeSave event
		if($events_and_cache && $this->fire('beforeSave') === false) return null;
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
	 * @param string|int|null $id Id's are automatically generated by default, but you can force one here. Once created, id's cannot be changed.
	 * @return zajModel Returns an object whose fields are set to the same data values.
	 */
	public function duplicate(string|int|null $id = null) : self {
		// Get my class name
			/* @var zajModel $class_name */
			$class_name = $this->class_name;
		// create the same object type
			$new = $class_name::create($id);
		// load up data object if not already done
			if(!isset($this->data)) $this->data = new zajData($this);
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
		// time create and edit should be now
		$new->set('time_create', time())->set('time_edit', time());

		return $new;
	}

	/**
	 * Convert model data to a standard single-dimensional array format.
	 * @todo Move these conversions to field definition files.
	 * @todo Add support for model extensions.
	 * @param boolean $localized Set to true if you want the localized version.
	 * @return array Return a single-dimensional array.
	 */
	public function to_array(bool $localized = true) : array {
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
				if(!isset($this->data)) $this->data = new zajData($this);
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
	public function jsonSerialize() : array {
		return $this->to_array();
	}

	/**
	 * Set the object status to deleted or remove from the database.
	 *
	 * @param boolean $permanent OPTIONAL. If set to true, object is permanently removed from db. Defaults to false.
	 * @return boolean Returns true if the item was deleted, false if beforeDelete prevented it.
	 */
	public function delete(bool $permanent = false) : bool {
		// fire __beforeDelete event
		if($this->fire('beforeDelete') === false) return false;
		// same as cache removal for non-database objects
		if(!$this::$in_database) {
			$this->uncache();
			return true;
		}
		// init the data object if not done already
		if(!isset($this->data)) $this->data = new zajData($this);
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
	public static function delete_tests(bool $max_to_delete = false, bool $permanent = true) : int {
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
	 * @param ?array $arguments Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 * @todo Somehow disable event methods from being declared public. They should be private or protected!
	 * @todo Make this static-compatible (though you cannot do event stack in that case! or can you?)
	 * @todo Optimize this!
	 **/
	public function fire(string $event, ?array $arguments = null) : mixed {
		// Do I even need to fire a child?
		if(!$this->event_child_fired){
			// Do I have an extension? If so, go down one level and start from there...
			/** @var zajModel $ext */
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
		$this->ofw->event_stack++;
		// Check stack size
		if($stack_size > MAX_EVENT_STACK) $this->ofw->error("Exceeded maximum event stack size of ".MAX_EVENT_STACK." for object ".$this->class_name.". Possible infinite loop?");
		if($this->ofw->event_stack > MAX_GLOBAL_EVENT_STACK) $this->ofw->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments == null) $arguments = array();
		// Call event function
		$return_value = call_user_func_array(array($this, '__'.$event), $arguments);
		// Remove from stack
		array_pop($this->event_stack);
		$this->ofw->event_stack--;
		// Return value
		return $return_value;
	}

	/**
	 * Fire a static event.
	 * @param string $event The name of the event.
	 * @param ?array $arguments Array of parameters. Leave empty if no params.
	 * @return mixed Returns the value returned by the event method.
	 */
	public static function fire_static(string $event, ?array $arguments = null) : mixed {
		// Make sure propagation is enabled
		zajModelExtender::$event_stop_propagation = false;
		// Get my class_name
		$class_name = get_called_class();
		// Add event to stack
		zajLib::me()->event_stack++;
		// Check stack size
		if(zajLib::me()->event_stack > MAX_GLOBAL_EVENT_STACK) zajLib::me()->error("Exceeded maximum global event stack size of ".MAX_GLOBAL_EVENT_STACK.". Possible infinite loop?");
		// If no arguments specified
		if($arguments == null) $arguments = array();
		// Do I have an extension? If so, go down one level...
		/** @var zajModel $ext */
		$ext = self::extension();
		if($ext) $return_value = $ext::fire_static($event, $arguments);
		else $return_value = null;
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
	 * @return ?string The name of my extension class.
	 **/
	public static function extension() : ?string {
		$class_name = get_called_class();
		if(!empty(zajModel::${'extensions'}[$class_name])) return zajModel::${'extensions'}[$class_name];
		return null;
	}

	/**
	 * Checks if the passed object is a type of me.
	 * @param mixed $object Checks if the passed variable is instance of me.
	 * @return boolean True if yes, false if not.
	 */
	public static function is_instance_of_me(mixed $object) : bool {
		// Get my class name
		$class_name = get_called_class();
		// @todo need a more sophisticated check than zajModelExtender...this is an error!
		// Solution: zajModel should also be true if zajModelExtender
		// ...but for any others it should be limited to me or an extension of me!
		// Additional: in connections, you should make sure incoming model instance is the same type as defined
		return is_object($object) && (is_a($object, $class_name) || is_a($object, 'zajModelExtender'));
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
			case '__afterUncache':
			case '__afterCache':
			case '__beforeDelete':
			case '__afterCreateSave':
			case '__afterCreate':
			case '__afterSave':
			case '__afterDelete':
			case '__afterFetch':
			case '__afterFetchCache':
				return true;
			case '__toSearchApiJson':
				return ['id'=>$this->id, 'name'=>$this->__get('name')];
			default:		break;
		}
		// Search for the method in any of my parents

		// Search for the method in any of my children
		/** @var zajModel $child_class_name */
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
		$this->ofw->warning("Method $name not found in model '$class_name' or any of it's child models.");
	}

	/**
	 * Shortcuts to static events and actions.
	 *
	 * @ignore
	 */
	public static function __callStatic($name, $arguments){
		// get current class
		/** @var zajModel $class_name */
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
				break;
			case '__onFilterQueryFetcher':
			case '__onSearchFetcher':
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
			case "ofw":
			case "zajlib": 		return zajLib::me();
			case "data":
				if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!isset($this->data)) return $this->data = new zajData($this);
				return $this->data;
			case "translation":
			case "translations":if(!$this::$has_translations) return false; 	// disable where no translations available
				if(!isset($this->translations) || $this->translations->get_locale() != zajLib::me()->lang->get()) return $this->translations = new zajModelLocalizer($this);
				return $this->translations;
			case "autosave":	if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!isset($this->data)) $this->data = new zajData($this);
				// turn on autosave
				$this->data->__autosave = true;
				return $this->data;
			case "model":		if(!$this::$in_database) return false; 	// disable for non-database objects
				if(!isset($this->model)) return $this->model = $this->__model();
				else return $this->model;
			case "exists":		if(!$this::$in_database) return true; 	// always return true for non-database objects
				if(!isset($this->data)) $this->data = new zajData($this);
				return $this->data->exists();
			case "name":		if(!$this::$in_database || isset($this->name)) return $this->name ?? "";
				// load model if not yet loaded
				if(!isset($this->model)) $this->model = $this->__model();
				// load data
				if(!isset($this->data)) $this->data = new zajData($this);
				// look for name and return if found
				foreach($this->model as $field=>$fdata){
					if($fdata->type == 'name'){					// actual name field
						$this->name_key = $field;
						return $this->name = $this->data->$field;
					}
					if(!isset($this->name) && $fdata->type == 'text'){ 	// first text field
						$this->name_key = $field;
						$this->name = $this->data->$field;
					}
				}
				return $this->name;
			case "name_key":	if(!$this::$in_database) return null; 	// disable for non-database objects
				if(isset($this->name_key)) return $this->name_key;
				// load name
				$this->__get("name");
				// now return it
				return $this->name_key;
		}

		// Enable extended __get()
		/** @var zajModel $class_name */
		$class_name = get_called_class();
		// Am I extended?
		/** @var zajModel $ext */
		$ext = $class_name::extension();
		if($ext && method_exists($ext, '__get')){
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
	 * @return ?zajModel Returns the object or null if failed.
	 * @ignore
	 * @todo Disable get_cache from being called outside. Events should be used instead of overriding...
	 */
	public static function get_cache(string $id) : ?zajModel {
		// get current class
		$class_name = get_called_class();
		// sanitize id just to be safe
		if(!zajLib::me()->security->is_valid_id($id)) return null;

		// return the resumed class
		$filename = zajLib::me()->file->get_id_path(zajLib::me()->basepath."cache/object/".$class_name, $id.".cache", false, CACHE_DIR_LEVEL);

		// try opening the file and unserializing
		$item_cached = false;
		if(file_exists($filename)) {
			$new_object = unserialize(file_get_contents($filename));
			if (is_object($new_object) && zajModel::is_instance_of_me($new_object)) {
				$item_cached = true;
			}
			else{
				zajLib::me()->warning("Failed to unserialize $class_name ($id) object from file '$filename'!");
			}
		}

		// If failed to uncache
		if(!$item_cached || empty($new_object)){
			// create object
			$new_object = new $class_name($id, $class_name);
			// get my name (this will grab the db)
			if($new_object::$in_database) $new_object->__get('name');
		}

		// this is resumed from the db, so load the data
		if(!$new_object->exists && !$item_cached){
			// if in database load data
			if($new_object::$in_database){
				$new_object->data = new zajData($new_object);
				$new_object->exists = $new_object->data->exists();
			}
			// if does not exist, send back with false (only for ones with database)
			if($new_object::$in_database && !$new_object->exists) return null;
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
	 * @return ?zajModel Returns the chainable object if successful, false if beforeUncache prevented it.
	 */
	public function uncache() : ?zajModel {
		// call __beforeUncache event
		if($this->fire('beforeUncache') === false) return null;
		// sanitize id just to be safe
		if(!zajLib::me()->security->is_valid_id($this->id)) {
			zajLib::me()->warning("Tried to uncache with invalid id: $this->id");
			return null;
		}
		// return the resumed class
		$filename = $this->ofw->file->get_id_path($this->ofw->basepath."cache/object/".$this->class_name, $this->id.".cache", false, CACHE_DIR_LEVEL);
		// if remove is successful, call __afterUncache event and return true. false otherwise
		if(!@unlink($filename)) return null;
		else{
			$this->fire('afterUncache');
			return $this;
		}
	}
	/**
	 * Create a cached version of the object.
	 *
	 * @return ?zajModel Returns the chainable object if successful, null if beforeCache prevented it.
	 */
	public function cache() : ?zajModel {
		// call __beforeCache event
		if($this->fire('beforeCache') === false) return null;
		// if not in_database, then this is creating it, so exists will equal to true
		if(!$this::$in_database) $this->exists = true;
		// sanitize id just to be safe
		if(!zajLib::me()->security->is_valid_id($this->id)){
			zajLib::me()->warning("Tried to save cache with invalid id: $this->id");
			return $this;
		}
		// get filename
		$filename = $this->ofw->file->get_id_path($this->ofw->basepath."cache/object/".$this->class_name,$this->id.".cache", true, CACHE_DIR_LEVEL);

		// model, data do not need to be saved!
		$data = $this->data;
		$model = $this->model;
		$event_stack = $this->event_stack;
		$event_child_fired = $this->event_child_fired;
		unset($this->ofw, $this->data, $this->model, $this->event_stack, $this->event_child_fired);

		if(isset($this->fetchdata)){
			$fetchdata = $this->fetchdata;
			unset($this->fetchdata);
		}
		if(isset($this->translations)){
			$translations = $this->translations;
			unset($this->translations);
		}

		// check for objects
		foreach($this as $varname=>$varval){
			if(zajModel::is_instance_of_me($varval) || zajFetcher::is_instance_of_me($varval)){
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
		$this->ofw = zajLib::me();
		// call the callback function
		$this->fire('afterCache');
		return $this;
	}

	/**
	 * Reorder the item's ordernum based on the order of ids in the passed array.
	 * @param array|string $reorder_array An array of object ids. Optionally can be a json-encoded array.
	 * @param bool $reverse_order If set to true, the reverse order will be taken into account.
	 * @return bool Always returns true.
	 */
	public static function reorder(array|string $reorder_array, bool $reverse_order = false) : bool {
		// get current class
		$class_name = get_called_class();
		// @todo remove this support for JSON input from a Sortables.serialize method of mootools, always returns true
		if(is_string($reorder_array)) $reorder_data = json_decode($reorder_array);
		else $reorder_data = $reorder_array;
		// continue processing the array of ids
		if((is_object($reorder_data) || is_array($reorder_data)) && count($reorder_data) > 0){
			// get the order num of each
			$array_of_ordernums = [];
			$myobj = [];
			foreach($reorder_data as $oneid){
				/** @var zajModel $class_name */
				$obj = $class_name::fetch($oneid);
				// if failed to find, issue warning
				if(!is_object($obj) || !zajModel::is_instance_of_me($obj)) zajLib::me()->warning("Tried to reorder non-existant object!");
				// all is okay
				else{
					// TODO: fix, but for now explicitly load data class, because autoload won't work in current scope
					$myobj[$oneid] = $obj;
					$myobj[$oneid]->data = new zajData($myobj[$oneid]);
					$array_of_ordernums[] = $myobj[$oneid]->data->ordernum;
				}
			}
			// Only proceed if actual array done
			if(count($array_of_ordernums) > 0){
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

