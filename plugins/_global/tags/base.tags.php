<?php
/**
 * The base tag collection includes tags which are part of the Django templates system.
 * @package Template
 * @subpackage Tags
 **/
 
////////////////////////////////////////////////////////////////////////////////////////////////////
// Each method should use the write method to send the generated php code to each applicable file.
// The methods take two parameters:
//		- $param_array - an array of parameter objects {@link zajCompileVariable}
//		- $source - the source file object {@link zajCompileSource}
////////////////////////////////////////////////////////////////////////////////////////////////////

class zajlib_tag_base extends zajElementCollection{

	/**
	 * Tag: comment - Comments are sections which are completely ignored during compilation.
	 *
	 *  <b>{% comment 'My comment' %}</b>
	 *  <br><b>{% comment %}My comment{% endcomment %}</b>	 
	 *  1. <b>comment</b> - The comment.
	 **/
	public function tag_comment($param_array, &$source){
		// convert to php comment
			if(array_key_exists(0, $param_array)) $this->zajlib->compile->write("<?php\n// $param_array[0]\n?>");
			else{
				// nested mode!
				$this->zajlib->compile->write("<?php\n/*");
				$source->add_level('comment', false);
			}
		// return true
			return true;
	}
	/**
	 * @ignore
	 **/
	public function tag_endcomment($param_array, &$source){
		// take out level
			$source->remove_level('comment');
		// write line
			$this->zajlib->compile->write("*/?>");
		// return
			return true;	
	}
	
	/**
	 * Tag: cycle - Cycle among the given strings or variables each time this tag is encountered.
	 *
	 *  <b>{% cycle var1 'text' var3 %}</b>
	 *  1. <b>var1</b> - Use this the first time through.
	 *  2. <b>var2</b> - Use this the second time through.
	 *  etc.
	 **/
	public function tag_cycle($param_array, &$source){
		// generate cycle array
			$var_name = '$cycle_array_'.uniqid("");
			$var_name_counter = '$cycle_counter_'.uniqid("");
			$my_array = 'if(empty('.$var_name.')) '.$var_name.' = array(';
			foreach($param_array as $el) $my_array .= "$el->variable, ";
			$my_array .= ');';
			$which_one_var = "[\$which_one]";
		// generate content
			$contents = <<<EOF
<?php
	// define my choices and my default
		$my_array
		if(!isset($var_name_counter)) $var_name_counter = 0;
		else $var_name_counter++;
	// choose which one to display now
		\$which_one = abs($var_name_counter % count($var_name));
	// choose
		echo \$this->ofw->security->purify({$var_name}{$which_one_var});
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}

	/**
	 * Tag: random - Randomly cycle among the given strings or variables each time this tag is encountered.
	 *
	 *  <b>{% random var1 'text' var3 %}</b>
	 *  1. <b>var1</b> - Choose this randomly.
	 *  2. <b>var2</b> - Choose this randomly.
	 *  etc.
	 **/
	public function tag_random($param_array, &$source){
		// generate random array
			$var_name = '$random_array_'.uniqid("");
			$my_array = 'if(empty('.$var_name.')) '.$var_name.' = array(';
			foreach($param_array as $el) $my_array .= "$el->variable, ";
			$my_array .= ');';
			$which_one_var = "[\$which_one]";
		// generate content
			$contents = <<<EOF
<?php
	// define my choices and my default
		$my_array
	// choose which one to display now
		\$which_one = rand(0, count($var_name));
	// choose
		echo {$var_name}{$which_one_var};
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}

	/**
	 * Tag: firstof - Prints the first in a list which evaluates to true.
	 *
	 *  <b>{% firstof var1 var2 var3 %}</b>
	 *  - <b>variables</b> - a list of variables
	 **/
	public function tag_firstof($param_array, &$source){
		// generate cycle array
			$var_name = '$firstof_array';
			$my_array = $var_name.' = array(';
			foreach($param_array as $el) $my_array .= "$el->variable, ";
			$my_array .= ');';
		// generate content
			$contents = "<?php\n";
			$contents .= <<<'EOF'
	// my array
		$my_array
	// first which is true
		foreach($firstof_array as $el->variable){
			if($el->variable) echo $this->ofw->security->purify($el->variable);
			break;
		}
EOF;
			$contents .= "\n?>";
		
		
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}

	/**
	 * Tag: for, foreach - Loops through a collection which can be a {@link zajFetcher} object or an array of values. It can accept django or php style syntax.
	 *
	 *  <b>{% for band in bands %}</b>
	 *  <br><b>{% foreach bands as band %}</b> 
	 *  1. <b>band</b> - The variable to use within the loop to refer to the current element.
	 *  2. <b>bands</b> - The fetcher or array to loop through.
	 *  Extra forloop.var helpers variables: key (the current key - if applicable), even (true if counter is even), odd (true if counter is odd)
	 **/
	public function tag_for($param_array, &$source){
		return $this->tag_foreach($param_array, $source);
	}	
	/**
	 * See for.
	 * 
	 * {@link tag_for()}
	 **/
	public function tag_foreach($param_array, &$source){
        if (count($param_array) < 3) {
            $source->error("Invalid for tag syntax!");
        }
        // which parameter goes where?
			// django compatible
			if($param_array[1]->vartext == 'in'){
				$fetcher = $param_array[2]->variable;
				$fetchervar = $param_array[2]->vartext;
				$item = $param_array[0]->variable_write;
			}
			// php compatible
			elseif($param_array[1]->vartext == 'as'){
				$fetcher = $param_array[0]->variable;
				$fetchervar = $param_array[0]->vartext;
				$item = $param_array[2]->variable_write;
			}
			else $source->warning('Invalid foreach tag syntax.');
		// add a level to hierarchy
			$local_var = '$this->zajlib->variable->ofw->tmp->foreach_item_'.uniqid("");
			$source->add_level('foreach', array('item'=>$item, 'local'=>$local_var));
		
		// generate code
			$contents = <<<EOF
<?php
// save the item if it exists
	if(!empty({$item})) $local_var = {$item};

// this is an array or a fetcher object
	if(!is_array({$fetcher}) && !is_object({$fetcher})) \$this->zajlib->warning("Cannot use for loop for parameter ({$fetchervar}) because it is not an an array, a fetcher, or an object!");
	else{
		// what is our forloop depth
			if(empty(\$forloop_depth)) \$forloop_depth = 1;
			else \$forloop_depth++;
		// does a parent forloop exist?
			if(isset(\$this->zajlib->variable->ofw->tmp->current_forloop)) \$this->zajlib->variable->ofw->tmp->parent_forloop = clone \$this->zajlib->variable->ofw->tmp->current_forloop;
			else \$this->zajlib->variable->ofw->tmp->parent_forloop = false;
		// create for loop variables
			\$this->zajlib->variable->ofw->tmp->current_forloop = new stdClass();
			\$this->zajlib->variable->ofw->tmp->current_forloop->counter0 = -1;
			// If not countable object, then typecast to array first (todo: can we do this in lib->array_to_object?)
			if(is_object({$fetcher}) && !is_a({$fetcher}, 'Countable')) \$this->zajlib->variable->ofw->tmp->current_forloop->length = count((array) {$fetcher});
			else \$this->zajlib->variable->ofw->tmp->current_forloop->length = count({$fetcher});

 			\$this->zajlib->variable->ofw->tmp->current_forloop->counter = 0;
			\$this->zajlib->variable->ofw->tmp->current_forloop->revcounter = \$this->zajlib->variable->ofw->tmp->current_forloop->length+1;
			\$this->zajlib->variable->ofw->tmp->current_forloop->revcounter0 = \$this->zajlib->variable->ofw->tmp->current_forloop->length;
			\$this->zajlib->variable->ofw->tmp->current_forloop->value = false;
			if(is_object(\$this->zajlib->variable->ofw->tmp->parent_forloop)){
				\$this->zajlib->variable->ofw->tmp->current_forloop->parentloop = \$this->zajlib->variable->ofw->tmp->parent_forloop;
				\$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter = \$this->zajlib->variable->ofw->tmp->parent_forloop->totalcounter;
				\$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter0 = \$this->zajlib->variable->ofw->tmp->parent_forloop->totalcounter0;
				\$this->zajlib->variable->ofw->tmp->current_forloop->depth = \$this->zajlib->variable->ofw->tmp->current_forloop->parentloop->depth + 1;
			}
			else{
				\$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter = 0;
				\$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter0 = -1;
				\$this->zajlib->variable->ofw->tmp->current_forloop->depth = 1;
			}

			foreach({$fetcher} as \$key=>{$item}){
				\$this->zajlib->variable->ofw->tmp->current_forloop->counter++;
				\$this->zajlib->variable->ofw->tmp->current_forloop->counter0++;
				\$this->zajlib->variable->ofw->tmp->current_forloop->revcounter--;
				\$this->zajlib->variable->ofw->tmp->current_forloop->revcounter0--;
				\$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter++;
				\$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter0++;
				\$this->zajlib->variable->ofw->tmp->current_forloop->odd = (\$this->zajlib->variable->ofw->tmp->current_forloop->counter % 2);
				\$this->zajlib->variable->ofw->tmp->current_forloop->even = !(\$this->zajlib->variable->ofw->tmp->current_forloop->odd);
				\$this->zajlib->variable->ofw->tmp->current_forloop->first = !\$this->zajlib->variable->ofw->tmp->current_forloop->counter0;
				\$this->zajlib->variable->ofw->tmp->current_forloop->last = !\$this->zajlib->variable->ofw->tmp->current_forloop->revcounter0;
				\$this->zajlib->variable->ofw->tmp->current_forloop->key = \$key;
				\$this->zajlib->variable->ofw->tmp->current_forloop->previous = \$this->zajlib->variable->ofw->tmp->current_forloop->value;
				\$this->zajlib->variable->ofw->tmp->current_forloop->value = {$item};
				\$this->zajlib->variable->forloop = \$this->zajlib->variable->ofw->tmp->current_forloop;
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}
	/**
	 * Tag: elsefor, elseforeach - This is shown if the for loop array or fetcher contains no elements.
	 *
	 * Please note that if the variable is neither a {@link zajFetcher} object or an array, then an error will be generated! Elsefor is only valid in case the count is zero.
	 *  <b>{% for band in bands %}</b>
	 *  <br><b>{% elsefor %}</b>
	 *  <br>Your content here.	 
	 *  <br><b>{% endfor %}</b> 
	 **/
	public function tag_elsefor($param_array, &$source){
		return $this->tag_empty($param_array, $source);
	}
	/**
	 * See elsefor.
	 * 
	 * {@link tag_elsefor()}
	 **/
	public function tag_elseforeach($param_array, &$source){
		return $this->tag_elsefor($param_array, $source);
	}
	/**
	 * See elsefor.
	 * 
	 * {@link tag_elsefor()}
	 **/
	public function tag_empty($param_array, &$source){
		// get level data
			$data = $source->get_level_data('foreach');
		// generate code
			$contents = <<<EOF
<?php
// end while
	}
//only print rest if 0
	if(\$this->zajlib->variable->ofw->tmp->current_forloop->length == 0){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}	
	/**
	 * See for.
	 * 
	 * {@link tag_for()}
	 **/
	public function tag_endfor($param_array, &$source){
		return $this->tag_endforeach($param_array, $source);
	}
	/**
	 * See for.
	 * 
	 * {@link tag_for()}
	 **/
	public function tag_endforeach($param_array, &$source){
		// get the data
			$data = $source->remove_level('foreach');
		// generate code
			$contents = <<<EOF
<?php
// end while and if
	}}
	
// reset foreach item
	if(isset($data[local])){
		$data[item] = $data[local];
		unset(\$foreach_item);
	}
	// if I had a parent, set me
	if(is_object(\$this->zajlib->variable->ofw->tmp->current_forloop->parentloop ?? null)){
		// Set my total counters
		\$this->zajlib->variable->ofw->tmp->parent_forloop->totalcounter = \$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter;
		\$this->zajlib->variable->ofw->tmp->parent_forloop->totalcounter0 = \$this->zajlib->variable->ofw->tmp->current_forloop->totalcounter0;
		// Unset me and reset me
		\$this->zajlib->variable->forloop = \$this->zajlib->variable->ofw->tmp->current_forloop = \$this->zajlib->variable->ofw->tmp->current_forloop->parentloop;
	}
	else{
		// unset stuff
			\$this->zajlib->variable->ofw->tmp->parent_forloop = null;
			\$this->zajlib->variable->ofw->tmp->current_forloop = null;
			\$this->zajlib->variable->forloop = null;
	}
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}

	
	/**
	 * Tag: if - If the condition evaluates to true, the contents will be printed.
	 *
	 *  <b>{% if condition %}</b>
	 *  <br><b>{% elseif %}{% endif %}</b>	 
	 *  - <b>condition</b> - A variable which evaluates to true or false.<br>
	 *
	 *  <b>{% if condition eq '10' %}</b>
	 *  - <b>condition</b> - In this case condition is tested against a value. You can use <b>not, and, or</b> for boolean logic and <b>eq, gt, lt, gteq, lteq</b> for operators.<br>	 
	 * 
	 *	<b>{% elseif %}</b>
	 *	Content if condition is false.
	 *	<b>{% endif %}</b>
	 **/
	public function tag_if($param_array, &$source){
		// add level
			$source->add_level('if', false);
		
		// get conditional string
			$string = $this->generate_conditional_string($param_array, $source);
		
		// generate if true
			$contents = <<<EOF
<?php
// start if
	if($string){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}
	/**
	 * @ignore
	 * @todo Remove this depricated tag.
	 **/
	public function tag_ifequal($param_array, &$source){
		// add level
			$source->add_level('if', false);
		// generate if true
			$contents = <<<EOF
<?php
// start if
	if({$param_array[0]->variable} == {$param_array[1]->variable}){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return
			return true;	
	}
	/**
	 * @ignore
	 * @todo Remove this depricated tag.
	 **/
	public function tag_ifnotequal($param_array, &$source){
		// add level
			$source->add_level('if', false);
		// generate if true
			$contents = <<<EOF
<?php
// start if
	if({$param_array[0]->variable} != {$param_array[1]->variable}){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return
			return true;	
	}
	/**
	 * See if.
	 * 
	 * {@link tag_if()}
	 **/
	public function tag_elseif($param_array, &$source){
		// generate lone else when no param given
		if(count($param_array) == 0){
		// standard, lone else
			$contents = <<<EOF
<?php
	}
	else{
?>
EOF;
		}
		// generate full elseif when param given
		else{
		// get conditional string
			$string = $this->generate_conditional_string($param_array, $source);
		// generate if true
			$contents = <<<EOF
<?php
	}
// another conditional
	elseif($string){
?>
EOF;

		}

		// write to file
			$this->zajlib->compile->write($contents);
		// return
			return true;	
	}
	/**
	 * See if. Though this can also be used by foreach.
	 * 
	 * {@link tag_if()}
	 * {@link tag_for()}
	 **/
	public function tag_else($param_array, &$source){
		// auto-detect the current tag
			$current_tag = $source->get_level_tag();
		// do a switch
			switch($current_tag){
				case 'if':
				case 'ifequal':
				case 'ifnotequal':		return $this->tag_elseif($param_array, $source);
				case 'ifchanged':		return $this->tag_elseifchanged($param_array, $source);
				case 'foreach':			return $this->tag_elsefor($param_array, $source);
				default:				$source->error('Unexpected else tag!');
			}
	}
	/**
	 * See if.
	 * 
	 * {@link tag_if()}
	 **/
	public function tag_endif($param_array, &$source){
		// remove level
			$source->remove_level('if');
		// write to file
			$this->zajlib->compile->write("<?php } ?>");
		// return true
			return true;
	}
	/**
	 * @ignore
	 **/
	public function tag_endifequal($param_array, &$source){
		return $this->tag_endif($param_array, $source);
	}
	/**
	 * @ignore
	 **/
	public function tag_endifnotequal($param_array, &$source){
		return $this->tag_endif($param_array, $source);
	}

	/**
	 * Tag: ifchanged - If the param var has changed, returns true, print contents.
	 *
	 *  <br><b>{% ifchanged whatever.item %}print this if changed{% endifchanged%}</b>	 
	 *  1. <b>variable</b> - The variable to test for change.
	 **/
	public function tag_ifchanged($param_array, &$source){
		// create a random variable name
			$varname = '$ifchanged_'.uniqid("");
		// add level
			$source->add_level('ifchanged', array('var'=>$varname,'param'=>$param_array[0]->variable));
		// generate if true
			$contents = <<<EOF
<?php
// start ifchanged
	if(!isset($varname) || $varname != {$param_array[0]->variable}){
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}
	/**
	 * See ifchanged.
	 * 
	 * {@link tag_ifchanged()}
	 **/
	public function tag_elseifchanged($param_array, &$source){
		// generate if true
			$contents = <<<EOF
<?php	
	}
	else{
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return
			return true;		
	}
	/**
	 * See ifchanged.
	 * 
	 * {@link tag_ifchanged()}
	 **/
	public function tag_endifchanged($param_array, &$source){
		// remove level, get var name
			$vars = $source->remove_level('ifchanged');
		// generate if true
			$contents = <<<EOF
<?php	
	}
	$vars[var] = $vars[param];
// end of ifchanged
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return
			return true;		
	}

	/**
	 * Helper function for creating a conditional string from parameter array
	 * @param array $param_array An array of parameters.
	 * @param zajCompileSource $source The source object passed here not as reference.
	 * @return string Returns the conditional string.
	 */
	private function generate_conditional_string($param_array, $source){
			$param_ok = true;	// needed to track that param cannot follow param
			$string = '';
            /** @var zajCompileVariable $param */
            foreach($param_array as $param){
				switch($param->vartext){
					case 'not':	                            $string .= "!";
															break;
					case 'and':	                            $string .= "&& ";
															$param_ok = true;
															break;
					case 'or':		                        $string .= "|| ";
															$param_ok = true;
															break;
					case 'gt':
					case '>':
															$string .= "> ";
															$param_ok = true;
															break;
					case 'lt':
					case '<':
															$string .= "< ";
															$param_ok = true;
															break;
					case 'eq':
					case '=':
					case '==':

															$string .= "== ";
															$param_ok = true;
															break;
					case '===':

															$string .= "=== ";
															$param_ok = true;
															break;
					case 'lteq':
					case '<=':
															$string .= "<= ";
															$param_ok = true;
															break;
					case 'gteq':
					case '>=':
															$string .= ">= ";
															$param_ok = true;
															break;
					case 'neq':
					case '!=':
															$string .= "!= ";
															$param_ok = true;
															break;
					case '!==':
															$string .= "!== ";
															$param_ok = true;
															break;
					case 'in':
															$source->error("Use the |in filter instead!"); // fatal error
															$param_ok = false;
															break;
					default:	if(!$param_ok) $source->error("Proper operator expected instead of $param->vartext!"); // fatal error
								$string .= $param->variable.' ';
								$param_ok = false;
								break;
				}
			}
		return $string;
	}


	/**
	 * Tag: include - Include an app controller by request url (relative to base url).
	 *
	 *  <br><b>{% include '/message/new/' parameter1 'parameter two' %}</b>	 
	 *  1. <b>request</b> - The request which will be routed as any other such URL request.
	 *  2. <b>optional parameters</b> - zero, one, or more optional parameters, passed as parameters to the controller method.

	 **/
	public function tag_include($param_array, &$source){
		// generate optional parameters
			$var1 = array_shift($param_array)->variable;
			$param_vars = array();
			foreach($param_array as $param) $param_vars[] = $param->variable;
			$var2 = join(', ', $param_vars);
		// generate content
			$contents = <<<EOF
<?php
    // start include
	\$this->zajlib->reroute($var1, array($var2));
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}	


	/**
	 * Tag: load - Load and register external tags and/or filters.
	 *
	 *  <br><b>{% load 'django' %}</b>	 
	 *  1. <b>name</b> - The name of the tag and/or filter collection to load.
	 **/
	public function tag_load($param_array, &$source){
		// generate content
			$contents = <<<EOF
<?php
// register tags and filters
	\$this->zajlib->compile->register_tags({$param_array[0]->variable});
	\$this->zajlib->compile->register_filters({$param_array[0]->variable});
?>
EOF;
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}	


	/**
	 * Tag: now - Generates the current time with PHP date as parameter
	 *
	 *  <br><b>{% now 'Y.m.d.' %}</b>	 
	 *  1. <b>format</b> - Uses the format of PHP's {@link http://php.net/manual/en/function.date.php date function}.
	 **/
	public function tag_now($param_array, &$source){
		// figure out content
			if(count($param_array)>0) $contents = "<?php if(!{$param_array[0]->variable}) echo date('Y.m.d.'); else echo date({$param_array[0]->variable}); ?>";
			else $contents = "<?php echo date('Y.m.d.');  ?>";
		// write to file
			$this->zajlib->compile->write($contents);
		// return true
			return true;
	}


	/**
	 * Tag: regroup - Not supported by mozajik.
	 **/
	public function tag_regroup($param_array, &$source){
		// write to file
			$source->error('Regroup tag not yet supported by Mozajik!');
		// return true
			return true;
	}


	/**
	 * Tag: spaceless - Not supported by mozajik.
	 **/
	public function tag_spaceless($param_array, &$source){
		// write to file
			$source->error('Spaceless tag not supported by Mozajik!');
		// return true
			return true;
	}


	/**
	 * Tag: ssi - Not supported by mozajik.
	 **/
	public function tag_ssi($param_array, &$source){
		// write to file
			$source->error('Ssi tag not supported by Mozajik!');
		// return true
			return true;
	}


	/**
	 * Tag: templatetag - Displays special template characters. You can also use {@link tag_literal()}.
	 *
	 *  <br><b>{% templatetag openblock %}</b>	 
	 *  1. <b>type</b> - Specifies the type of tag character to print. Possible values are <b>openblock, closeblock, openvariable, closevariable, openbrace, closebrace, opencomment, closecomment</b>
	 **/
	public function tag_templatetag($param_array, &$source){
		// write to file
			switch($param_array[0]->vartext){
				case 'openblock':
				case "'openblock'":
																$this->zajlib->compile->write('{%');
																break;
				case 'closeblock':
				case "'closeblock'":
																$this->zajlib->compile->write('%}');
																break;
				case 'openvariable':
				case "'openvariable'":
																$this->zajlib->compile->write('{{');
																break;
				case 'closevariable':
				case "'closevariable'":
																$this->zajlib->compile->write('}}');
																break;
				case 'openbrace':
				case "'openbrace'":
																$this->zajlib->compile->write('{');
																break;
				case 'closebrace':
				case "'closebrace'":
																$this->zajlib->compile->write('}');
																break;
				case 'opencomment':
				case "'opencomment'":
																$this->zajlib->compile->write('{#');
																break;
				case 'closecomment':
				case "'closecomment'":
																$this->zajlib->compile->write('#}');
																break;
			}
		
		// return true
			return true;
	}

	/**
	 * Tag: url - Not supported by mozajik. Use the {{baseurl}}path/to/controller/ format.
	 **/
	public function tag_url($param_array, &$source){
		// write to file
			$source->error('Not supported by Mozajik! Just use {{baseurl}}path/to/controller/.');
		// return true
			return true;
	}

	/**
	 * Tag: widthratio - Not supported by mozajik. Use CSS instead.
	 **/
	public function tag_widthratio($param_array, &$source){
		// write to file
			$source->error('Widthratio tag not supported by Mozajik! Please use CSS to replicate this.');
		// return true
			return true;
	}

	/**
	 * Tag: with - Caches a complex variable under a simpler name.
	 *
	 *  <br><b>{% with business.employees.count as total %} total is {{total}} {% endwith %}</b>	 
	 *  1. <b>complex</b> - The complex variable to catch.
	 *  2. <b>simplename</b> - The local variable name to use within the nested tag area.
	 **/
	public function tag_with($param_array, &$source){
		// Support old {% with business.employees.count as total %} syntax.
		if($param_array[1]->vartext == 'as'){
			// add level
				$temporary_variable = '$this->zajlib->variable->ofw->tmp->before_with_'.uniqid();
				$source->add_level('with', array([$param_array[2]->variable_write], [$temporary_variable]));
			// generate with
				$contents = <<<EOF
<?php
// save previous value for restore
	{$temporary_variable} = {$param_array[2]->variable};
// start with
	{$param_array[2]->variable_write} = {$param_array[0]->variable};
?>
EOF;
		}
		// Support new {% with business.employees.count = total %} syntax.
		else{
			// Check if we have the proper amount of entries (must be divisble by 3)
			if(count($param_array) % 3 != 0) return $source->error("Something is not right in your {% with %} tag! Make sure to use the old 'as' or the new x=y syntax. Check documentation!");

			// Now let's start counting up by 3's
			$temporary_variables = [];
			$set_variables = [];
			$contents = '';
			for($i = 0; $i < count($param_array); $i = $i+3){
				// The first element is the left side of the equals. It must be a variable.
				$set_me_param = $param_array[$i];
				if(is_numeric($set_me_param->vartext) || $set_me_param->vartext[0] == '"' || $set_me_param->vartext[0] == "'") return $source->error("You cannot set a string or number to a value in your {% with %} tag! Make sure to have a variable on the left of your x=y syntax.");
                $set_me = $set_me_param->variable;
				$set_me_write = $set_me_param->variable_write;

				// The second item should be an = sign
				$equal_param = $param_array[$i+1];
				if(trim($equal_param->vartext, ' ') != '=') return $source->error("Something is not right in your {% with %} tag! Make sure to use the old 'as' or the new x=y syntax. Check documentation!");

				// The third item can be pretty much anything
				$to_me_param = $param_array[$i+2];
				$to_this_value = $to_me_param->variable;

				// Store old value in temporary variable and store var name for endwith reference
				$temporary_variable = '$before_with_'.uniqid();
				$temporary_variables[] = $temporary_variable;
				$set_variables[] = $set_me_write;

				// Generate php
				$contents .= <<<EOF
<?php
// save previous value for restore
	{$temporary_variable} = {$set_me};
// start with
	{$set_me_write} = {$to_this_value};
?>
EOF;
			}

			// We are done looping, let's now increase level with temporary variables
			$source->add_level('with', array($set_variables, $temporary_variables));
		}

		// write to file
		$this->zajlib->compile->write($contents);
		// return true
		return true;
	}
	/**
	 * @ignore
	 **/
	public function tag_endwith($param_array, &$source){

		// get the data
		list($localvars, $restorevars) = $source->remove_level('with');

		// restore values for each item
		$contents = '';
		foreach($restorevars as $key=>$restorevar){
			$localvar = $localvars[$key];
			$contents .= <<<EOF
<?php
// restore it
	@$localvar = $restorevar;
?>
EOF;
		}

		// write to file
		$this->zajlib->compile->write($contents);

		// return true
		return true;
	}
		
	/**
	 * Tag: block - Creates a content block.
	 *
	 *  <br><b>{% block 'name_of_block' %}Content of block.{% endblock %}</b>	 
	 *  1. <b>block name</b> - A unique name used to identify this block. Blocks with the same names will override each other according to the rules of {@link http://docs.djangoproject.com/en/1.2/topics/templates/#template-inheritance Template inheritance}
	 **/
	private $block_name = "";
	public function tag_block($param_array, &$source){
		// Note: the block parameter is special because even if it is a variable it is not
		//		treated as such. So {% block content %} is same as {% block 'content' %}.
        if(is_string($source)) {
            exit("Woo: $source");
        }

		/** @var zajCompileSource $source */

		// Prepare unparsed parameter string
		$block_name = strtolower(trim($param_array[0]->vartext, "'\" "));
		zajCompileSession::verbose("{% block $block_name %} in <code>$source->file_path</code>.");

		// Add the block to the source
		$my_block = $source->add_block($block_name);

		// Get the main source for this session
		$main_source = $source->get_session()->get_main_source();

		// Was this block already processed in a lower level source?
		if($source->child_source && $source->child_source->has_block($block_name, true)){
			// Yes.

			// Write lower level block cache to all main source block caches
            if(is_string($source)) {
                exit("ooppaa: $source");
            }
			$child_block = $source->child_source->get_block($block_name, true);
			$child_block->insert();

			// Close all main destinations
			$source->get_session()->main_dest_paused(true);
			// @todo I think this is too strong here

			// Close all main source block caches
			foreach($main_source->get_blocks() as $block){
			    /** @var zajCompileBlock $block */
			    //print "<h1>I am $block->name, my child is ".$block->child->name." and my parent is ".$block->parent->name."</h1>";
			    //$block->pause_destinations(true);
			}

			// Close all parent block destinations
			$my_block->pause_destinations(true);

			// @todo somehow we have to pause all the blocks in the

		}
		else{
			// No.

			// Open block cache for main source
			$my_block->add_destination($main_source);

			// Is the current source not the main source?
			if(!$source->am_i_the_main_source()){
				// ...then open a destination for the current source as well
				$my_block->add_destination($source);
			}
		}

		// Add the level with block parent as last param
		$source->add_level('block', [$my_block, $this->block_name]);

		// Set as current global block (overwriting parent)
		$this->block_name = $block_name;
		zajCompileSession::verbose("Finished starting a new block <code>$block_name</code> in <code>$source->file_path</code>.</li></ul>");

		// Return true
		return true;
	}
	/**
	 * See block.
	 * 
	 * {@link tag_block()}
	 **/
	public function tag_endblock($param_array, &$source){
		/** @var zajCompileSource $source */
		// remove level
		list($my_block, $parent_block) = $source->remove_level('block');
		zajCompileSession::verbose("{% endblock $my_block->name %} in <code>$source->file_path</code>.");

		// end the block
		$new_current_block = $source->end_block();

		/** @var zajCompileBlock $my_block */
		// Remove my direct destinations (non-recursively)
		$my_block->remove_destinations();

        // If the parent is still overriddren, then do not resume
        if($my_block->parent && !$my_block->parent->is_overridden(true)) $my_block->resume_destinations(true);

        // If this source is the root file (not extended)
		if(!$source->is_extension){
            if(
                // If we are at 0 block level
                $source->block_level == 0 ||
                // If the currently ended block is not overridden (and nor are any of its parents)
                ($new_current_block && !$new_current_block->is_overridden(true))
            ){
                if ($new_current_block != null) {
                    zajCompileSession::verbose("We are back at $new_current_block->name in <code>$source->file_path</code>, so unpausing main destination.");
                } else {
                    zajCompileSession::verbose("We are back at root in <code>$source->file_path</code>, so unpausing main destination.");
                }
                $this->zajlib->compile->main_dest_paused(false);
            }

		}

		// reset current block to parent block @todo use $new_current_block instead
		$this->block_name = $parent_block;

		// return true
		return true;
	}

	/**
	 * Tag: parentblock - Inserts the block from the parent template (specified by extends tag) here. This can be used when you do not want to override the block but instead add to it.
	 *
	 *  <b>{% parentblock %}</b>
	 **/
	public function tag_parentblock($param_array, &$source){
		// check if valid
			if(empty($this->extended_path)) $source->error("No extends tag found. You cannot use parentblock unless this template extends another.");
		// set source to be extended and set actual file path
			$this->zajlib->compile->write("<?php  \$this->zajlib->template->block({$this->extended_path}, '{$this->block_name}', true); ?>");
		// return true
			return true;
	}
	
	/**
	 * Tag: extends - Extends a parent template. You can also use this programatically 
	 *
	 *  <b>{% extends '/my/template/path' %}</b>
	 *  1. <b>template_path</b> - The path to the parent template.
	 **/
	private $extended_path = "";
	public function tag_extends($param_array, &$source){
		// check if valid
			if(count($param_array) > 1) $source->error("Invalid extends parameter: must be a valid variable or string!");
		// save current extended path to var
			$this->extended_path = strtolower(trim($param_array[0]->vartext, " "));
		// prepare unparsed parameter string
			$source_path = trim($this->extended_path, "'\"");
		// is not in first line?
			if($source->line_number != 1) $source->error("Extends must be on first line before any other content!");
		// is the user jailed?
			if(strpos($source_path, '..') !== false) $source->error("Invalid extends path ($source_path) found during compilation! Path must be give relative to the 'view' folder.");

		// should we extend ourselves?
			if($source_path == 'self'){
				$ignore_app_level = $source->app_level;
				$source_path = $source->requested_path;
			}
			else $ignore_app_level = false;

		// get app level check
			$result = zajCompileSource::check_app_levels($source_path, $ignore_app_level);

		// check if it exists to provide friendly error message
			if(!$result){
				if($ignore_app_level) $source->error("Extends path ($source_path) not suitable for decoration! File does not exist in any app levels lower than $ignore_app_level.");
				else $source->error("Invalid extends path ($source_path) found during compilation! File does not exist anywhere.");
			}
			else{
				// set my source's parent
				$source->parent_path = $result[0];
				$source->parent_level = $result[1];
				$source->parent_requested = $source_path;
			}

		// set source to be extended and set actual file path
			$source->is_extension = true;

		// now pause main destination
			$this->zajlib->compile->main_dest_paused(true);

		// add me to the compile queue
			zajCompileSession::verbose("Extend detected with $source_path.");
			$this->zajlib->compile->add_source($source_path, $ignore_app_level, $source);
		// return true
			return true;
	}

	/**
	 * Tag: insert - Inserts another template at this location. The template is treated as if it were inline.
	 *
	 *  <b>{% insert '/admin/news_edit.html' 'block_name' %}</b>
	 *  1. <b>template_file</b> - The template file to insert.
	 *  2. <b>block_section</b> - If you only want to insert the block section from the file. (optional)
	 * @todo See comments below for optimization.
	 **/
	public function tag_insert($param_array, &$source){
		// get the first parameter...
			$var = $param_array[0]->variable;
			$tvar = trim($var, "'\"");
		// TODO: if it is a string, then its static, so compile and insert file here...
			/*if($var != $tvar){
				// compile contents
					$this->zajlib->compile->compile($tvar);
				// insert to current destination
					$this->zajlib->compile->insert_file($tvar.'.php');
			}*/
			// DO THIS FOR insertlocal as well

		// Check if extends is the same as insert
			// @todo This will not solve the issue if it is not a direct parent.
			if($this->tag_get_extend() == trim($param_array[0]->variable, "'\"")){
				//$source->error("Cannot {% insert %} the same file that you used in {% extend %}! You can try to move that content to a seperate template file.");
			}
		// if it is a single variable, then we need to do it with template->show
			else{
				if(count($param_array) <= 1) $contents = <<<EOF
<?php
// start insert
	\$this->zajlib->template->show({$param_array[0]->variable});
?>
EOF;
		// if it is two variables, then we need to do it with template->block
				else $contents = <<<EOF
<?php
// start insert block
	\$this->zajlib->template->block({$param_array[0]->variable}, {$param_array[1]->variable});
?>
EOF;


				// write to file
					$this->zajlib->compile->write($contents);
			}
		// return
			return true;
	}

	/**
	 * Tag: insertlocale - Same as {@link insert} except that this also checks for localized versions of the HTML file before including.
	 *
	 *  <b>{% insertlocale '/admin/news_edit.html' 'block_name' %}</b>
	 *  1. <b>template_file</b> - The template file to insert.
	 *  2. <b>block_section</b> - If you only want to insert the block section from the file. (optional)
	 **/
	public function tag_insertlocale($param_array, &$source){
		// get the first parameter...
			$var = $param_array[0]->variable;
			$tvar = trim($var, "'\"");
		// Check if extends is the same as insert
			if($this->tag_get_extend() == trim($param_array[0]->variable, "'\"")){
				//$source->error("Cannot {% insert %} the same file that you used in {% extend %}! You can try to move that content to a seperate template file.");
			}
		// if it is a single variable, then we need to do it with template->show
				if(count($param_array) <= 1) $contents = <<<EOF
<?php
// start insert for local file
	\$this->zajlib->lang->template({$param_array[0]->variable});
?>
EOF;
		// if it is two variables, then we need to do it with template->block
				else $contents = <<<EOF
<?php
// start insert block for local file
	\$this->zajlib->lang->block({$param_array[0]->variable}, {$param_array[1]->variable});
?>
EOF;


				// write to file
					$this->zajlib->compile->write($contents);
			//}
		// return
			return true;
	}
	public function tag_insertlocal($param_array, &$source){
		return $this->tag_insertlocale($param_array, $source);
	}


	/**
	 * These are special functions which return the current extend file path and block name in use. THESE ARE NOT TAGS!
	 **/
	public function tag_get_extend(){ return trim($this->extended_path, "'\""); }
	public function tag_get_block(){ return $this->block_name; }
}