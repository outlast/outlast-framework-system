<?php

    /**
     * A string value that is safe for output and no longer requires purifying XSS.
     */
	class OfwSafeString {

	    /**
         * The underlying string value
         * @var string
         */
	    public $value = "";

        /**
         * OfwSafeString constructor.
         * @param string $value
         */
	    public function __construct($value) {
	        $this->value = $value;
        }

        /**
         * Alternative to constructor.
         * @param $value
         * @return OfwSafeString
         */
        static function set($value) {
	        return new OfwSafeString($value);
        }

    }