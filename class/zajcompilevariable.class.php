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
 * @property string $variable
 */
class zajCompileVariable extends zajCompileElement {

    /**
     * @var string The original variable text as in template source.
     */
	private $vartext;

    /** @var string The compiled, php-ready version of the variable */
	private $variable;

	/** @var array A list of filters to be applied */
	private $filters = [];

    /**
     * @var bool Whether or not this is a parameter. If false, it means it is a stand-alone {{variable}}. If true, it is a param of a tag.
     */
	private $parameter_mode = false;

    /**
     * @var int Total number of filters
     */
	public $filter_count = 0;

    /**
     * zajCompileVariable constructor.
     * @param string $element_name
     * @param zajCompileSource $parent
     * @param bool $check_xss
     */
	public function __construct($element_name, &$parent, $check_xss = true){
		// call parent
			parent::__construct($element_name, $parent);
		// now match all the filters
			preg_match_all('/'.regexp_zaj_onefilter.'/', $element_name, $filter_matches, PREG_SET_ORDER);//PREG_PATTERN_ORDER
		// now run through all the filters
			$trim_from_end = 0;
			foreach($filter_matches as $filter){
				if(!empty($filter[0])){
					// if this is safe, then disable check xss
					if($filter[2] == 'safe'){
						$check_xss = false;
					}
					// pass: full filter text, filter value, file pointer, debug stats
					if(!empty($filter[5])) $filter[5] = $this->convert_variable($filter[5]);
					$this->filters[$this->filter_count++] = array(
						'filter'=>$filter[2],
						'parameter'=>$filter[5],
					);
					$trim_from_end -= strlen($filter[0]);
				}
			}
		// temporarily allow ofw.js and zaj.js, error out in debug mode @todo Remove this eventually!
			if($check_xss && ($element_name == 'ofw.js' || $element_name == 'zaj.js')){
				if($this->parent->zajlib->debug_mode) $this->parent->zajlib->error("Deprecated: {{ofw.js}} or {{zaj.js}} is missing the |safe filter!");
				$check_xss = false;
			}
		// trim filters from me
			if($trim_from_end < 0) $element_name = substr($element_name, 0, $trim_from_end);
		// original text
			$this->vartext = $element_name;
		// convert me
			$this->variable = $this->convert_variable($element_name, $check_xss);
			$this->element_name = $element_name;
		return true;
	}

	public function set_parameter_mode($parameter_mode){
		$this->parameter_mode = $parameter_mode;
	}

	public function prepare(){
		// start the filter var
			$this->parent->write("<?php \$filter_var = $this->variable; ");
		// now execute all the filters
			$count = 1;
			foreach($this->filters as $filter){
				// get filter
					list($filter, $parameter) = array_values($filter);
				// now call the filter
					$this->parent->zajlib->compile->filters->$filter($parameter, $this->parent, $count);
					$count++;
			}
			//$this->parent->write("\$filter_var = \$filter_var;");
		// now end it
			$this->parent->write(" ?>");
		return true;
	}

	public function write(){
		// if filter count
			if($this->filter_count){
				// prepare the filter var
					$this->prepare();
				// now echo the filter var
					$this->parent->write("<?php echo \$filter_var; ?>");
			}
		// no filter, just the var
			else $this->parent->write("<?php echo $this->variable; ?>");
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
			default: return $this->parent->zajlib->warning("Tried to access inaccessible parameter $name of zajCompileVariable.");
		}
	}

}

