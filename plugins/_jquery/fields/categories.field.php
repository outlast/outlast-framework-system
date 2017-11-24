<?php
/**
 * Field definition for a single category.
 * @package Fields
 * @subpackage BuiltinFields
 **/
 zajLib::me()->load->file('/fields/manytomany.field.php');

class zajfield_categories extends zajfield_manytomany {
	// similar to manytoone

	// only editor is different
	const edit_template = 'field/categories.field.html';  // string - the edit template, false if not used
	const filter_template = 'field/categories.filter.html';	// string - the filter template

	// Construct
	public function __construct($name, $options, $class_name, &$zajlib){
		$options = ['Category'];
		return parent::__construct($name, $options, $class_name, $zajlib);
	}

    /**
	 * Preprocess the data before saving to the database.
	 * @param mixed $data The first parameter is the input data.
	 * @param zajModel $object This parameter is a pointer to the actual object which is being modified here.
	 * @param array|bool $additional_fields Use this to save additional columns in the manytomany table. This parameter is really only useful if you override this method to create a custom field.
	 * @return array Returns an array where the first parameter is the database update, the second is the object update
	 * @todo Fix where second parameter is actually taken into account! Or just remove it...
	 **/
	public function save($data, &$object, $additional_fields = false){
	    // Run manytoone version
	    $return = parent::save($data, $object);

        // Now check if parent categories need to be added (recursive)
        $this->zajlib->config->load('category');
        /** @var Category $my_category */
        $my_categories = clone $object->data->{$this->name};
        if($this->zajlib->config->variable->category_auto_add_parents){
            foreach($my_categories as $my_category){
                $my_category->add_parent_categories_recursively($object, $this->name);
            }
        }

        // @todo Add remove subcats recursively (implemented in Category->remove_subcategories_recursively)

        return $return;
    }


	/**
	 * This method is called just before the input field is generated. Here you can set specific variables and such that are needed by the field's GUI control.
	 * @param array $param_array The array of parameters passed by the input field tag. This is the same as for tag definitions.
	 * @param zajCompileSource $source This is a pointer to the source file object which contains this tag.
	 * @return bool
	 */
	public function __onInputGeneration($param_array, &$source){
		// override to print all choices
			// use search method with all
				$class_name = $this->options['model'];
			// write to compile destination
				$this->zajlib->compile->write('<?php $this->zajlib->variable->field->choices = '.$class_name.'::__onSearch('.$class_name.'::fetch()); $this->zajlib->variable->field->choices_toplevel = '.$class_name.'::__onSearch('.$class_name.'::fetch_top_level()); if($this->zajlib->variable->field->choices === false) $this->zajlib->warning("__onSearch method required for '.$class_name.' for this input."); ?>');
		return true;
	}
}