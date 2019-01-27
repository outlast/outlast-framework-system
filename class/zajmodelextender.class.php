<?php

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
		if(zajModelExtender::$event_stack_size > MAX_EVENT_STACK) return $this->ofw->error("Maximum extender event stack size exceeded. You probably have an infinite loop somewhere!");
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