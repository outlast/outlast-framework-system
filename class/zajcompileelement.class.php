<?php

/**
 * A general element in the source (tag or variable).
 *
 * This is a parent class for tags and variables in the source. It provides various methods that may be needed by these elements.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileElement{

    /**
     * @var string The name of the variable or tag.
     */
	protected $element_name;
	
    /**
     * @var zajCompileSource The parent source.
     */
	protected $parent;

    /**
     * zajCompileElement constructor.
     * @param string $element_name
     * @param zajCompileSource $parent
     */
	protected function __construct($element_name, &$parent){
		// set parent and element
			/** @var zajCompileSource $parent */
			$this->parent =& $parent;
			$this->element_name = $element_name;
		return true;
	}

    /**
     * Converts a variable to php output. Used in both tags and variables.
     * @param string $variable The variable as passed from the template source.
     * @param bool $check_xss Whether to check the variable contents for XSS. Defaults to true.
     * @param bool $add_null_protection If set to true, the variable will be null-protected. This is only supported when *reading* the variable. Defaults to true.
     * @return string Returns the valid PHP format, ready for writing.
     */
	protected function convert_variable($variable, $check_xss = true, $add_null_protection = true){
		// leaves 'asdf' as is but converts asdf.qwer to $this->zajlib->variable->asdf->qwer
		// config variables #asdf# now supported!
		// and so are filters...
			if(substr($variable, 0, 1) == '"' || substr($variable, 0, 1) == "'") return $variable;
			elseif(substr($variable, 0, 1) == '#'){
				$var_element = trim($variable, '#');
				return '($this->zajlib->config->variable->'.$var_element.' ?? null)';
			}
			else{
				$var_elements = explode('.',$variable);
				$new_var = '$this->zajlib->variable';
                $is_variable = false;
				// Run through each variable. Variables are valid in three ways: (1) actual variable, (2) numerical, or (3) operator in if tag
				foreach($var_elements as $element){
					// (1) Is it an actual variable?
						if(preg_match(regexp_zaj_variable, $element) <= 0){
							// (2) Is it an operator in an if tag
							if(preg_match(regexp_zaj_operator, $element) <= 0){
								// (3) Is it a numerical value
								if(is_numeric($variable)) $new_var = $element;
								else{
									// Nothing worked, this is just invalid...STOP!
									$this->parent->error("invalid variable/operator found: $variable!");
								}
							}
							else{
								// This is an operator! So now let's make sure this is an if tag
								if($this->parent->get_current_tag() != 'if' && $this->parent->get_current_tag() != 'elseif' && $this->parent->get_current_tag() != 'with'){
									$this->parent->warning("operator $variable is only supported for 'if' and 'with' tags!");
									return '$empty';
								}
								else $new_var = $element;
							}
						}
						else {
                            // Add null protection
                            if ($add_null_protection) {
                                $new_var .= '?->' . $element;
                            } else {
                                $new_var .= '->' . $element;
                            }
                            $is_variable = true;
                        }
				}
                // Add null protection for variables (to avoid warnings)
                    if ($is_variable && $add_null_protection) {
                        $new_var = '('.$new_var.' ?? null)';
                    }
				// Add xss protection
					if($check_xss && !empty(zajLib::me()->ofwconf->feature_xss_protection_enabled)) $new_var = '$this->ofw->security->purify('.$new_var.')';
				return $new_var;
			}
	}


}
