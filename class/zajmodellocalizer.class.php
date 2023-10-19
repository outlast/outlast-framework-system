<?php

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