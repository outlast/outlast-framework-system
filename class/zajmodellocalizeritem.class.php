<?php

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
		if($tobj != null) $field_value = $tobj->value;
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
        if(is_string($value) && $value !== '') return $value;
        else return $this->parent->data->$fieldname ?? '';
    }

	/**
	 * Implement json serialize method.
	 */
	public function jsonSerialize(){
		return $this->__toString();
	}

}
