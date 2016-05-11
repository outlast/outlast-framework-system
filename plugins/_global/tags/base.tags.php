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
			if($param_array[0]) $this->zajlib->compile->write("<?php\n// $param_array[0]\n?>");
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
		echo \$this->zajlib->template->strip_xss({$var_name}{$which_one_var}, 'Found in {% cycle %} tag.');
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
	 * Tag: filter - Applies a filter to all text within tag.
	 *
	 *  <b>{% filter lowercase|escapejs %}</b>
	 *  1. <b>filters</b> - A list of filters to apply to the text.
	 * @todo Implement this, but this may have to work differently!
	 **/
	public function tag_filter($param_array, &$source){

		// TODO: do this with capture output
		
		
		// write to file
			//$this->zajlib->compile->write($contents);
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
			if($el->variable) echo $this->zajlib->template->strip_xss($el->variable, 'Found in {% firstof %} tag.');
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
		// which parameter goes where?
			// django compatible
			if($param_array[1]->variable == '$this->zajlib->variable->in'){
				$fetcher = $param_array[2]->variable;
				$fetchervar = $param_array[2]->vartext;
				$item = $param_array[0]->variable;
			}
			// php compatible
			elseif($param_array[1]->variable == '$this->zajlib->variable->as'){
				$fetcher = $param_array[0]->variable;
				$fetchervar = $param_array[0]->vartext;
				$item = $param_array[2]->variable;
			}
			else $source->warning('Invalid foreach tag syntax.');
		// add a level to hierarchy
			$local_var = '$foreach_item_'.uniqid("");
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
			if(is_object(\$this->zajlib->variable->forloop)) \$parent_forloop = clone \$this->zajlib->variable->forloop;
			else \$parent_forloop = false;
		// create for loop variables
			\$this->zajlib->variable->forloop = new stdClass();
			\$this->zajlib->variable->forloop->counter0 = -1;
			// If not countable object, then typecast to array first (todo: can we do this in lib->array_to_object?)
			if(is_object({$fetcher}) && !is_a({$fetcher}, 'Countable')) \$this->zajlib->variable->forloop->length = count((array) {$fetcher});
			else \$this->zajlib->variable->forloop->length = count({$fetcher});
 			\$this->zajlib->variable->forloop->counter = 0;
			\$this->zajlib->variable->forloop->revcounter = \$this->zajlib->variable->forloop->length+1;
			\$this->zajlib->variable->forloop->revcounter0 = \$this->zajlib->variable->forloop->length;
			\$this->zajlib->variable->forloop->value = false;
			if(is_object(\$parent_forloop)){
				\$this->zajlib->variable->forloop->parentloop = \$parent_forloop;
				\$this->zajlib->variable->forloop->totalcounter = \$parent_forloop->totalcounter;
				\$this->zajlib->variable->forloop->totalcounter0 = \$parent_forloop->totalcounter0;
				\$this->zajlib->variable->forloop->depth = \$this->zajlib->variable->forloop->parentloop->depth + 1;
			}
			else{
				\$this->zajlib->variable->forloop->totalcounter = 0;
				\$this->zajlib->variable->forloop->totalcounter0 = -1;
				\$this->zajlib->variable->forloop->depth = 1;
			}

			foreach({$fetcher} as \$key=>{$item}){
				\$this->zajlib->variable->forloop->counter++;
				\$this->zajlib->variable->forloop->counter0++;
				\$this->zajlib->variable->forloop->revcounter--;
				\$this->zajlib->variable->forloop->revcounter0--;
				\$this->zajlib->variable->forloop->totalcounter++;
				\$this->zajlib->variable->forloop->totalcounter0++;
				\$this->zajlib->variable->forloop->odd = (\$this->zajlib->variable->forloop->counter % 2);
				\$this->zajlib->variable->forloop->even = !(\$this->zajlib->variable->forloop->odd);
				\$this->zajlib->variable->forloop->first = !\$this->zajlib->variable->forloop->counter0;
				\$this->zajlib->variable->forloop->last = !\$this->zajlib->variable->forloop->revcounter0;
				\$this->zajlib->variable->forloop->key = \$key;
				\$this->zajlib->variable->forloop->previous = \$this->zajlib->variable->forloop->value;
				\$this->zajlib->variable->forloop->value = {$item};
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
	if(\$this->zajlib->variable->forloop->length == 0){
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
	if(@defined($data[local])){
		$data[item] = $data[local];
		unset(\$foreach_item);
	}
	// if I had a parent, set me
	if(is_object(\$this->zajlib->variable->forloop->parentloop)){
		// Set my total counters
		\$parent_forloop->totalcounter = \$this->zajlib->variable->forloop->totalcounter;
		\$parent_forloop->totalcounter0 = \$this->zajlib->variable->forloop->totalcounter0;
		// Unset me and reset me
		\$this->zajlib->variable->forloop = \$this->zajlib->variable->forloop->parentloop;
	}
	else{
		// unset stuff
			\$parent_forloop = null;
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
			foreach($param_array as $param){
				switch($param->variable){
					case '$this->zajlib->variable->not':	$string .= "!";
															break;
					case '$this->zajlib->variable->and':	$string .= "&& ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->or':		$string .= "|| ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->gt':
					case '>':
															$string .= "> ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->lt':
					case '<':
															$string .= "< ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->eq':
					case '=':
					case '==':

															$string .= "== ";
															$param_ok = true;
															break;
					case '===':

															$string .= "=== ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->lteq':
					case '<=':
															$string .= "<= ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->gteq':
					case '>=':
															$string .= ">= ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->neq':
					case '!=':
															$string .= "!= ";
															$param_ok = true;
															break;
					case '!==':
															$string .= "!== ";
															$param_ok = true;
															break;
					case '$this->zajlib->variable->in':
															$source->error("Use the |in filter instead!"); // fatal error
															$param_ok = false;
															break;
					default:	if(!$param_ok) $source->error("Proper operator expected instead of $param!"); // fatal error
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
	\$this->zajlib->load->library('url');
	\$this->zajlib->url->redirect($var1, array($var2));
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
			switch($param_array[0]->variable){
				case '$this->zajlib->variable->openblock':
				case "'openblock'":
																$this->zajlib->compile->write('{%');
																break;
				case '$this->zajlib->variable->closeblock':
				case "'closeblock'":
																$this->zajlib->compile->write('%}');
																break;
				case '$this->zajlib->variable->openvariable':
				case "'openvariable'":
																$this->zajlib->compile->write('{{');
																break;
				case '$this->zajlib->variable->closevariable':
				case "'closevariable'":
																$this->zajlib->compile->write('}}');
																break;
				case '$this->zajlib->variable->openbrace':
				case "'openbrace'":
																$this->zajlib->compile->write('{');
																break;
				case '$this->zajlib->variable->closebrace':
				case "'closebrace'":
																$this->zajlib->compile->write('}');
																break;
				case '$this->zajlib->variable->opencomment':
				case "'opencomment'":
																$this->zajlib->compile->write('{#');
																break;
				case '$this->zajlib->variable->closecomment':
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
				$temporary_variable = '$before_with_'.uniqid();
				$source->add_level('with', array([$param_array[2]->variable], [$temporary_variable]));
			// generate with
				$contents = <<<EOF
<?php
// save previous value for restore
	{$temporary_variable} = {$param_array[2]->variable};
// start with
	{$param_array[2]->variable} = {$param_array[0]->variable};
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

				// The second item should be an = sign
				$equal_param = $param_array[$i+1];
				if(trim($equal_param->vartext, ' ') != '=') return $source->error("Something is not right in your {% with %} tag! Make sure to use the old 'as' or the new x=y syntax. Check documentation!");

				// The third item can be pretty much anything
				$to_me_param = $param_array[$i+2];
				$to_this_value = $to_me_param->variable;

				// Store old value in temporary variable and store var name for endwith reference
				$temporary_variable = '$before_with_'.uniqid();
				$temporary_variables[] = $temporary_variable;
				$set_variables[] = $set_me;

				// Generate php
				$contents .= <<<EOF
<?php
// save previous value for restore
	{$temporary_variable} = {$set_me};
// start with
	{$set_me} = {$to_this_value};
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
	$localvar = $restorevar;
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

		/** @var zajCompileSource $source */

		// Prepare unparsed parameter string
		$block_name = strtolower(trim($param_array[0]->vartext, "'\" "));

		//zajCompileSession::$blocks_processed[$permanent_name] = $permanent_name;

		// Add the block to the source
		$my_block = $source->add_block($block_name);

		// If this is an extended session, and we are an extension (so not top-level)
		if($source->is_extension){
			// Pause main destination
			$this->zajlib->compile->main_dest_paused(true);

			// Add destination for my block, recursively
			$my_block->add_destination(true);
		}
		// If this is an extended session, but I am a top-level source
		elseif($source->extended){

			// If the block is unique to me (no children have it), then simply write!
			if(!$source->child_source->has_block($block_name, true)){
				// Unpause main destination
				$this->zajlib->compile->main_dest_paused(false);
			}
			else{
				// Unpause main destination
				$this->zajlib->compile->main_dest_paused(false);

				// Insert the lowest-level block directly
				$block = $source->get_block($block_name, true);
				$block->insert();

				// Pause the main destination
				$this->zajlib->compile->main_dest_paused(true);
			}

			// Add destination for my block, recursively
			$my_block->add_destination(true);
		}
		// If I am a single-level source
		else{
			// Unpause main destination
			$this->zajlib->compile->main_dest_paused(false);
			
			// Add block destination
			$my_block->add_destination();
		}


		// Main destination is currently paused if extended. Unpause if the block we are processing is the lowest
		//print "processing block $block_name in $source->file_path<br/>";


		// Now write block files for all my children (unless they already exist)

		$unpause_on_endblock = [];
		$child_blocks_processed = [];

		/**
			// Blocks processed
			// Define a function for recursive addition
			$add_child_destinations = function($my_source, $permanent_name) use ($block_name, &$child_blocks_processed, &$add_child_destinations, &$unpause_on_endblock, $source){
				// @var zajCompileSource $my_source
				if($my_source->child_source !== false){

					// Generate permanent name
					$my_permanent_name = '__block/'.$my_source->child_source->get_requested_path().'-'.$block_name.'.html';

					// Add destination if not already added previously
					if(!array_key_exists($my_permanent_name, zajCompileSession::$blocks_processed)){
						$this->zajlib->compile->add_destination($my_permanent_name);
						$child_blocks_processed[$my_permanent_name] = $my_permanent_name;
						zajCompileSession::$blocks_processed[$my_permanent_name] = $my_permanent_name;
					}
					else{
						// If I have a parent block...
						if($this->block_name){

							// Let's pause all destinations that
							$this->zajlib->compile->pause_destinations();
							
							// Insert parent block contents into me

							// Get the block files to resume
							$parent_block = $this->block_name;
							$current_child_block_file = '__block/'.$my_source->get_requested_path().'-'.$parent_block.'.html';
							// Unpause only me, then repause and add to unpause on endblock
							$dest_parent_block = $this->zajlib->compile->get_destination_by_path($current_child_block_file);
							if($dest_parent_block) $dest_parent_block->resume();
							zajCompileSession::verbose("Inserting file <code>$current_child_block_file</code> into $parent_block of all destinations.");

							// Now if we have the block on the child level, insert there as well @todo recursive has_block??
							if($my_source->child_source->has_block($block_name)){
								$current_parent_block_file = '__block/'.$my_source->child_source->get_requested_path().'-'.$block_name.'.html';
								// Unpause only me, then repause and add to unpause on endblock
								$dest_block = $this->zajlib->compile->get_destination_by_path($current_parent_block_file);
								if($dest_block){
									$dest_block->resume();
									zajCompileSession::verbose("Inserting file <code>$current_parent_block_file</code> into $block_name of all destinations.");
								}
									zajCompileSession::verbose("Inserting file <code>$current_parent_block_file</code> into $block_name of all destinations.");
							}
							else $dest_block = false;

							// Insert the file
							$this->zajlib->compile->insert_file($my_permanent_name.'.php');
							$this->zajlib->compile->resume_destinations();

							// Pause again
							if($dest_parent_block){
								$dest_parent_block->pause();
								$unpause_on_endblock[] = $dest_parent_block;
							}
							if($dest_block){
								$dest_block->pause();
								$unpause_on_endblock[] = $dest_block;
							}


						}

						// Add block to child if it doesnt exist in the interim files
						if(!$my_source->child_source->has_block($block_name)){
							$my_source->child_source->add_block($block_name);
						}



					}

					// Recursive!
					$add_child_destinations($my_source->child_source, $my_permanent_name);
				}
				else{
					// Write contents of $permanent_name to main destination
					// @var zajCompileDestination $destination
					$destination = $this->zajlib->compile->get_destination();

					// If the plugin level is not set @todo why do we need this?
					if(!$source->parent_level){
						// If the block exists in my child
						if($source->child_source && $source->child_source->has_block($block_name)){
							// Validate the file path
							$relative_path = 'cache/view/'.$permanent_name.'.php';
							$this->zajlib->file->file_check($relative_path);
							// @todo Why can't this work with compile->insert_file()?
							$this->zajlib->compile->main_dest_paused(false);
							zajCompileSession::verbose("Inserting <code>$relative_path</code> into current destination $destination->file_path.");
							$data = file_get_contents($this->zajlib->basepath.$relative_path);
							$destination->write($data);
						}
					}

					// Pause extended destinations
					if($source->extended && $source->child_source->has_block($block_name)){
						// Pause my extended sources
						$destination->pause();
						// Add to array of destinations to unpause
						$unpause_on_endblock[] = $destination;
					}

				}
			};

			// Start recursive function with current source
			$add_child_destinations($source, $permanent_name);
		 **/

		// Add the level with block parent as last param
		$source->add_level('block', [$my_block, $child_blocks_processed, $this->block_name, $unpause_on_endblock]);

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
		list($my_block, $child_blocks_processed, $parent_block, $unpause_on_endblock) = $source->remove_level('block');

		// end the block
		$new_current_block = $source->end_block();

		// remove permanent block file (if exists)
		/** @var zajCompileBlock $my_block */
		$my_block->remove_destinations();
		$block_name = $my_block->name;

		// If our source is top level or if our child still has even the parent block
			/** THIS IS NOT QUITE CORRECT */
			if(
				// If we are an extension then just pause it
				($source->is_extension) ||
				// Or our child has this block
				0 //($source->child_source && $source->child_source->has_block($parent_block))
			  ){
				zajCompileSession::verbose("We are at top level of <code>$source->file_path</code> which has extends tag, so keep main destination paused.");
				$this->zajlib->compile->main_dest_paused(true);
			}
			else{
				zajCompileSession::verbose("We are going from $block_name to $parent_block in <code>$source->file_path</code>, so unpausing main destination.");
				$this->zajlib->compile->main_dest_paused(false);
			}

		// reset current block to parent block
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
				$source->error("Cannot {% insert %} the same file that you used in {% extend %}! You can try to move that content to a seperate template file.");
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
				$source->error("Cannot {% insert %} the same file that you used in {% extend %}! You can try to move that content to a seperate template file.");
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