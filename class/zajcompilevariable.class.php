<?php

/**
 * A variable compiling object.
 *
 * This represents a single variable in the source. The class is responsible for parsing the variable, and sending control to the
 * appropriate filter methods (if needed).
 *
 * @package Template
 * @subpackage CompilingBackend
 *
 * A list of read-only properties.
 * @property string $vartext
 * @property string $variable A compiled, php-ready version of the variable; but should be used for reading the values.
 * @property string $variable_write A compiled, php-ready version of the variable; can be used for writing, less safe for reading.
 */
class zajCompileVariable extends zajCompileElement {

    /**
     * @var string The original variable text as in template source.
     */
	private string $vartext;

    /**
     * @var string The compiled, php-ready version of the variable used for consuming the variable.
     */
	private string $variable;

    /**
     * @var string The compiled, php-ready version of the variable used for writing to the variable.
     */
    private string $variable_write;

	/** @var array A list of filters to be applied */
	private array $filters = [];

    /**
     * @var bool Whether this is a parameter. If false, it means it is a stand-alone {{variable}}. If true, it is a param of a tag.
     */
	private bool $parameter_mode = false;

    /**
     * @var int Total number of filters
     */
	public int $filter_count = 0;

    /**
     * zajCompileVariable constructor.
     * @param string $element_name
     * @param zajCompileSource $parent
     * @param bool $check_xss
     */
	public function __construct(string $element_name, zajCompileSource &$parent, bool $check_xss = true){
		// call parent
			parent::__construct($element_name, $parent);
		// now match all the filters
			preg_match_all('/'.regexp_zaj_onefilter.'/', $element_name, $filter_matches, PREG_SET_ORDER);//PREG_PATTERN_ORDER
		// now run through all the filters
			$trim_from_end = 0;
			$same_filter_counter = [];
			foreach($filter_matches as $filter){
				if(!empty($filter[0])){
					// if this is safe, then disable check xss
					if($filter[2] == 'safe' || $filter[2] == 'escape' || $filter[2] == 'escapejs'){
						$check_xss = false;
					}
                    // count same filter type
                    if(!key_exists($filter[2], $same_filter_counter)) {
                        $same_filter_counter[$filter[2]] = 1;
                    } else {
                        $same_filter_counter[$filter[2]]++;
                    }
					// pass: full filter text, filter value, file pointer, debug stats
					if(!empty($filter[5])) $filter[5] = $this->convert_variable($filter[5], $check_xss);
					$this->filters[$this->filter_count++] = array(
						'filter'=>$filter[2],
						'parameter'=>$filter[5],
						'total_count'=>$this->filter_count,
						'same_filter_count'=>$same_filter_counter[$filter[2]],
					);
					$trim_from_end -= strlen($filter[0]);
				}
			}
		// trim filters from me
			if($trim_from_end < 0) $element_name = substr($element_name, 0, $trim_from_end);

		// If variable is empty
		    if($element_name == "" || $element_name == "##") {
		        $this->parent->error("Empty variable name found.");
                // exits
            }

            // all is well...continue.
			$this->vartext = $element_name;
		// convert me
			$this->variable = $this->convert_variable($element_name, $check_xss);
            $this->variable_write = $this->convert_variable($element_name, $check_xss, false);
			$this->element_name = $element_name;
		return true;
	}

	public function set_parameter_mode(bool $parameter_mode){
		$this->parameter_mode = $parameter_mode;
	}

	public function prepare() : bool {
		// start the filter var
			$this->parent->write("<?php \$filter_var = $this->variable; ");
		// now execute all the filters
			foreach($this->filters as $filter){
				// get filter
					list($filter, $parameter, $total_count, $same_filter_count) = array_values($filter);
				// now call the filter
					zajLib::me()->compile->filters->$filter($parameter, $this->parent, $total_count, $same_filter_count);
			}
			//$this->parent->write("\$filter_var = \$filter_var;");
		// now end it
			$this->parent->write(" ?>");
		return true;
	}

	public function write() : bool {
		// if filter count
			if($this->filter_count){
				// prepare the filter var
					$this->prepare();
				// now echo the filter var
					$this->parent->write("<?php echo \$filter_var ?? ''; ?>");
			}
		// no filter, just the var
			else $this->parent->write("<?php echo $this->variable ?? ''; ?>");
		return true;
	}

	public static function compile($element_name, &$parent, $check_xss = true){
		// create new
			$var = new zajCompileVariable($element_name, $parent, $check_xss);
		// write
			$var->write();
	}

    /**
     * Gives acces to the private variables.
     * @param $name
     * @return bool|string
     */
	public function __get($name){
		switch($name){
			case 'vartext': return $this->vartext;
			case 'variable': return $this->variable;
            case 'variable_write': return $this->variable_write;
			default: return zajLib::me()->warning("Tried to access inaccessible parameter $name of zajCompileVariable.");
		}
	}

}

