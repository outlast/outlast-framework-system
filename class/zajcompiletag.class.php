<?php
/**
 * A tag compiling object.
 *
 * This represents a single tag in the source. The class is responsible for parsing the tag, and sending control to the
 * appropriate tag/filter methods.
 *
 * @package Template
 * @subpackage CompilingBackend
 */
class zajCompileTag extends zajCompileElement {
    /**
     * @var array Array of parameters. Each item is a zajCompileVariable in parameter mode.
     */
	private array $parameters = [];

    /**
     * @var string This is the parameters as passed directly.
     */
	private string $paramtext;

    /**
     * @var string The name of the tag.
     */
	private string $tag;

    /**
     * @var int The number of parameters.
     */
	public int $param_count = 0;

    /**
     * zajCompileTag constructor.
     * @param string $element_name The element section of the tag.
     * @param string $parameters The parameters section of the tag.
     * @param zajCompileSource $parent The parent source.
     */
	protected function __construct(string $element_name, string $parameters, zajCompileSource &$parent){
		// call parent
			parent::__construct($element_name, $parent);
		// set paramtext & tag
			$this->paramtext = $parameters;
			$this->tag = $element_name;
			if(!empty($parameters)){
				// process parameters
					// now match all the parameters
						preg_match_all('/'.regexp_zaj_oneparam.'/', $parameters, $param_matches, PREG_PATTERN_ORDER);//PREG_SET_ORDER
					// grab parameter plus filters (all are at odd keys (why?))
						foreach($param_matches[0] as $param){
							if(trim($param) != ''){
								// create a compile variable
									$pobj = new zajCompileVariable($param, $this->parent, false);
								// set to parameter mode
									$pobj->set_parameter_mode(true);
								// set as a parameter
									$this->parameters[$this->param_count++] = $pobj;
							}
						}
			}
	}

    /**
     * Write the code for a particular tag to the destination.
     */
	public function write() : void {
		// prepare all filtered parameters
			$filter_prepare = '';
			foreach($this->parameters as $pkey=>$param){
				// does it have any filters?
				if($param->filter_count){
					// prepare the filtered variable
						$param->prepare();	// set to $filter_var
					// now reset parameter to filtered variable
						$random_var = 'tmp_'.uniqid("");
						$this->parent->write('<?php $this->zajlib->variable->'.$random_var.' = $'.$random_var.' = $filter_var; ?>');
					// reset parameter in array of objects (TODO: there may be a more memory-efficient way of doing this?)
						$this->parameters[$pkey] = new zajCompileVariable($random_var, $this->parent, false);
				}
			}
		// now call me
			$element_name = $this->element_name;
			zajLib::me()->compile->tags->$element_name($this->parameters, $this->parent);
	}

	public static function compile(string $element_name, string $parameters, zajCompileSource &$parent){
		// create new
			$tag = new zajCompileTag($element_name, $parameters, $parent);
		// write
			$tag->write();
	}

	public function __get(string $name) : string|bool {
		switch($name){
			case 'tag': return $this->tag;
			case 'paramtext': return $this->paramtext;
			default: return zajLib::me()->warning("Tried to access inaccessible parameter $name of zajCompileTag.");
		}
	}

}
