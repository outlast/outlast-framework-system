<?php

    /**
     * A string value that is safe for output and no longer requires purifying XSS.
     */
	class OfwSafeString {

	    /**
         * The underlying string value
         * @var string
         */
	    public string $value = "";

        /**
         * OfwSafeString constructor.
         * @param string $value
         */
	    public function __construct(string $value) {
	        $this->value = $value;
        }

        /**
         * Alternative to constructor.
         * @param string $value
         * @return OfwSafeString
         */
        static function set(string $value): OfwSafeString {
	        return new OfwSafeString($value);
        }

    }