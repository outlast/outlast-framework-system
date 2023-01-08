<?php
/**
 * OFW filter collection includes filters which are not in Django by default, but are part of the Outlast Framework.
 * @package Template
 * @subpackage Filters
 **/

////////////////////////////////////////////////////////////////////////////////////////////////
// The methods below will take the following parameters and generate the appropriate php code
//	using the write method.
//		- parameter - the parsed parameter variable/string
//		- source - the source file object
//		- $counter_for_all_filters (optional) - a 1-based counter specifying which filter is currently processing (among all)
//		- $counter_for_same_filter (optional) - a 1-based counter specifying which filter is currently processing (among the filters of the same name)
////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * OFW filter collection includes filters which are not in Django by default, but are part of the Outlast Framework.
 * @todo Make sure that filters correctly support the use of variables as filter parameters.
 **/
class zajlib_filter_ofw extends zajElementCollection{
	
	/**
	 * Filter: photo - Returns the url of the photo object. If it is a fetcher (many photos), then the first one will be returned.
	 *
	 *  <b>{{ user.data.photos|photo:'4' }}</b> The url of the photo will be displayed, without the baseurl.
	 **/
	public function filter_photo($parameter, &$source){
		// normal is default size
			if(!$parameter) $parameter = "'normal'";
		// write to file
			$content = <<<EOF
if(is_object(\$filter_var) && is_a(\$filter_var, "Photo")){
	\$filter_var = \$filter_var->get_image($parameter);
}
elseif(is_object(\$filter_var) && is_a(\$filter_var, "zajFetcher") && \$obj = \$filter_var->rewind()){
	\$filter_var=\$obj->get_image($parameter);
}
elseif(is_object(\$filter_var) && !empty(\$filter_var->photo_filter_supported)){
	\$filter_var = \$filter_var->{{$parameter}};
}
elseif(is_array(\$filter_var)){
	\$f = reset(\$filter_var);
	if(empty(\$f->photo_filter_supported)){
		\$filter_var = false;
	}
	else{
		\$filter_var = \$f->{{$parameter}};
	}
}
else{
	\$filter_var=false;
}
EOF;
			$this->zajlib->compile->write($content);
		return true;
	}

	/**
	 * Filter: srcset - Returns the HTML5 compatible srcset attribute value of an image
	 *
	 *  <b>{{ user.data.photos|srcset }}</b> The srcset="[value comes here]" of the <img> will be displayed.
	 **/
	public function filter_srcset($parameter, &$source){
		$content = <<<EOF
if(is_object(\$filter_var) && is_a(\$filter_var, "Photo")){
	\$filter_var = \$filter_var->get_srcset();
}
elseif(is_object(\$filter_var) && is_a(\$filter_var, "zajFetcher") && \$obj = \$filter_var->rewind()){
	\$filter_var=\$obj->get_srcset();
}
else{
	\$filter_var=false;
}
EOF;
		$this->zajlib->compile->write($content);
		return true;
	}

	/**
	 * Filter: count - Return the LIMITed count of a fetcher object. (This will be the number of rows returned taking into account LIMITs). This also works on arrays (where the number of items are returned) or any other data type (where 1 will be returned).
	 *
	 *  <b>{{fetcher|count}}</b> See {@link zajFetcher->count} for more details.
	 **/
	public function filter_count($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('if(is_object($filter_var) && class_implements("Countable") || is_array($filter_var)) $filter_var = count($filter_var); else $filter_var=count((array) $filter_var);');
		return true;
	}
	/**
	 * Filter: total - Return the total number of object in this fetcher. (This will be the number of rows returned independent of any LIMIT clause or pagination)
	 *
	 *  <b>{{fetcher|total}}</b> See {@link zajFetcher->total} for more details.
	 **/
	public function filter_total($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var->total;');
		return true;
	}
	/**
	 * Filter: truncate - Truncates the variable to the number specified by parameter.
	 *
	 *  <b>{{variable|truncate:'5'}}</b> Truncates the length of variable string to 5 characters. So 'Superdooper' will be 'Super...'
	 **/
	public function filter_truncate($parameter, &$source){
        // required
        if(!$parameter) return $source->warning('truncate filter parameter required!');
		// write to file
			$this->zajlib->compile->write('if(strlen($filter_var) > '.$parameter.') $filter_var=mb_substr($filter_var, 0, '.$parameter.')."...";');
		return true;
	}
	/**
	 * Filter: paginate - Paginates the fetcher object with the number per page set by the argument. By default, 10 per page.
	 *
	 *  <b>{{fetcher|paginate:'50'}}</b> Will list 50 items on this page. See {@link zajFetcher->paginate()} for more details.
	 **/
	public function filter_paginate($parameter, &$source){
		// default for parameter
			if(empty($parameter)) $parameter = 10;
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var->paginate('.$parameter.');');
		return true;
	}

	/**
	 * Filter: pagination - Displays the pagination widget.
	 *
	 *  <b>{{fetcher|pagination:'items'}}</b> You can optionally pass a parameter which will be the descriptor of the items. Defaults to 'items'.
	 **/
	public function filter_pagination($parameter, &$source){
		// default for parameter
		if(empty($parameter)) $parameter = '"items"';
		// write to file
		$this->zajlib->compile->write('$prevurl="<a href=\'{$filter_var->pagination->prevurl}\'>«</a>";  $nexturl="<a href=\'{$filter_var->pagination->nexturl}\'>»</a>"; if(!$filter_var->pagination->prevpage) { $prevdisabled = "disabled"; $prevurl = "<span>«</span>"; } if(!$filter_var->pagination->nextpage) {$nextdisabled = "disabled"; $nexturl = "<span>»</span>"; } $filter_var="<div class=\'pagination pagination-small pagination-centered text-center\'><ul class=\'pagination pagination-centered\'><li class=\'$prevdisabled\'>$prevurl</li><li class=\'disabled\'><a href=\'#\'>{$filter_var->pagination->page} / {$filter_var->pagination->pagecount} ({$filter_var->total} ".'.$parameter.'.")</a></li><li class=\'$nextdisabled\'>{$nexturl}</li></ul></div>";');
		return true;
	}

    /**
     * Filter: sort - Same as {@link zajlib_filter_base->filter_dictsort()
     * @param $parameter
     * @param $source
     * @return true
     **/
	public function filter_sort($parameter, &$source){
        // param required
        if (!$parameter) return $source->warning('sort filter parameter required!');
        // write to file
        $this->zajlib->compile->write('if(is_object($filter_var) && is_a($filter_var, "zajFetcher")) $filter_var->sort(' . $parameter . ', "ASC");');
        return true;
    }

	/**
	 * Filter: rsort - Same as {@link zajlib_filter_base->filter_dictsortreversed()
     * @param $parameter
     * @param $source
     * @return true
	 **/
   public function filter_rsort($parameter, &$source){
		// param required!
        if(!$parameter) return $source->warning('dictsort filter parameter required!');
		// write to file
        $this->zajlib->compile->write('if(is_object($filter_var) && is_a($filter_var, "zajFetcher")) $filter_var->sort('.$parameter.', "DESC");');
		return true;
	}

	/**
	 * Filter: strftime -  Format a local time/date according to locale settings
	 * @link http://www.php.net/manual/en/function.strftime.php
	 *
	 *  <b>{{user.data.time_create|strftime:'%V,%G,%Y'}}</b> = 1/3/2005
	 *  1. <b>format</b> - Uses the format of PHP's {@link http://www.php.net/manual/en/function.strftime.php strftime function}.
	 **/
	public function filter_strftime($parameter, &$source){
		// default parameter
			if(empty($parameter)) $parameter = "'%F'";
		// write to file
			$this->zajlib->compile->write('if(is_numeric($filter_var)) $filter_var=utf8_encode(strftime('.$parameter.', $filter_var)); else $filter_var=false;');
		return true;
	}

	/**
	 * Filter: print_r - Returns the value of PHP's print_r() function. Useful for debugging.
	 *
	 *  <b>{{variable|print_r}}</b> This is like running print_r(variable); in php.
	 **/
	public function filter_print_r($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = print_r($filter_var, true);');
		return true;
	}

	/**
	 * Filter: round - Round to the number of decimals specified by parameter (2 by default).
	 *
	 *  <b>{{variable|round:'2'}}</b> Assuming variable is 3.12355, the returned value will be 3.12.
	 **/
	public function filter_round($parameter, &$source){
			if(!$parameter && !is_numeric($parameter)) $parameter = 2;
		// write to file
			$this->zajlib->compile->write('if(is_numeric($filter_var)) $filter_var=number_format($filter_var, '.$parameter.', ".", "");');
		return true;
	}

	/**
	 * Filter: floor - Round an float down.
	 *
	 * <b>{{variable|floor}}</b> Assuming variable is 3.8, returned value will be 3.
	 *
	 **/
	public function filter_floor($parameter, &$source){
		// write to file
		$this->zajlib->compile->write('if(is_numeric($filter_var)) $filter_var=floor($filter_var);');
		return true;
	}

	/**
	 * Filter: ceil - Round an float up.
	 *
	 * <b>{{variable|ceil}}</b> Assuming variable is 3.2, returned value will be 4.
	 *
	 **/
	public function filter_ceil($parameter, &$source){
		// write to file
		$this->zajlib->compile->write('if(is_numeric($filter_var)) $filter_var=ceil($filter_var);');
		return true;
	}

	/**
	 * Filter: remainder - The variable is divided by the filter paramter and the remainder is returned.
	 *
	 *  <b>{{variable|remainder:'3'}}</b> Assuming variable is 8, the returned value will be 2.
	 **/
	public function filter_remainder($parameter, &$source){
		// param required!
			if(!$parameter) return $source->warning('remainder filter parameter required!');
		// write to file
			$this->zajlib->compile->write('$filter_var= $filter_var % '.$parameter.';');
		return true;
	}

	/**
	 * Filter: subtract - Subtract the amount specified by parameter from the variable.
	 *
	 *  <b>{{variable|subtract:'1'}}</b> Assuming variable is 3, the returned value will be 2.
	 **/
	public function filter_subtract($parameter, &$source){
		// validate parameter
			$parameter = (trim($parameter,"'\""));
			if(!str_starts_with($parameter, '($') && !is_numeric($parameter)) return $source->warning('subtract filter parameter not a variable or an integer!');
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var-'.$parameter.';');
		return true;
	}
	
	/**
	 * Filter: multiply - Multiply by the amount specified by parameter from the variable.
	 *
	 *  <b>{{variable|multiply:'2'}}</b> Assuming variable is 2, the returned value will be 4.
	 **/
	public function filter_multiply($parameter, &$source){
		// validate parameter
			$parameter = (trim($parameter,"'\""));
			if(!str_starts_with($parameter, '($') && !is_numeric($parameter)) return $source->warning('multiply filter parameter not a variable or an integer!');
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var*'.$parameter.';');
		return true;
	}

	/**
	 * Filter: divide - Divide by the amount specified by parameter from the variable.
	 *
	 *  <b>{{variable|divide:'2'}}</b> Assuming variable is 4, the returned value will be 2.
	 **/
	public function filter_divide($parameter, &$source){
		// validate parameter
			$parameter = (trim($parameter,"'\""));
			if(!str_starts_with($parameter, '($') && !is_numeric($parameter)) return $source->warning('divide filter parameter not a variable or an integer!');
		// write to file
			$this->zajlib->compile->write('$filter_var=$filter_var/'.$parameter.';');
		return true;
	}	
	
	/**
	 * Filter: toquerystring - Converts an array to a query string.
	 *
	 *  <b>{{variable|toquerystring:'name'}}</b> Assuming variable is an array ['red', 'white', 'blue'], the returned value will be name[0]=red&name[1]=white&name[2]=blue& .
	 **/
	public function filter_toquerystring($parameter, &$source){
		// validate parameter
			if(empty($parameter)) return $source->warning('toquerystring filter parameter is required!');
			$parameter = (trim($parameter,"'\""));
		// write to file
			$this->zajlib->compile->write('$new_str = ""; if(is_array($filter_var)) foreach($filter_var as $key=>$value){ $new_str .= "'.$parameter.'[$key]=$value&"; } $filter_var = $new_str;');
		return true;
	}

	/**
	 * Filter: json_encode - Converts a variable or object to its JSON value.
	 *
	 *  <b>{{variable|json_encode}}</b> Assuming variable is an array ['red', 'white', 'blue'], the returned value will be .
	 **/
	public function filter_json_encode($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = json_encode($filter_var);');
		return true;
	}
	/** @deprecated **/
	public function filter_tojson($parameter, &$source){ return $this->filter_json_encode($parameter, $source); }

	/**
	 * Filter: serialize - Converts a variable or object to its PHP-serialized value.
	 *
	 *  <b>{{variable|serialize}}</b> Assuming variable is an array ['red', 'white', 'blue'], the returned value will be its PHP-serialized value. This is the same as using serialize() function in native PHP.
	 **/
	public function filter_serialize($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = serialize($filter_var);');
		return true;
	}
	/** @deprecated **/
	public function filter_toserialized($parameter, &$source){ return $this->filter_serialize($parameter, $source); }

	/**
	 * Filter: unserialize - Unserializes a PHP-serialized value.
	 *
	 *  <b>{{variable|unserialize}}</b> Assuming variable is a serialized string it will unserialize the value and return the actual native PHP data.
	 **/
	public function filter_unserialize($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = unserialize($filter_var);');
		return true;
	}

	/**
	 * Filter: json_decode - Decodes a JSON-encoded value.
	 *
	 *  <b>{{variable|json_decode}}</b> Assuming variable is an json-encoded string containing ['red', 'white', 'blue'], the returned value will be the actual array.
	 **/
	public function filter_json_decode($parameter, &$source){
		// write to file
			$this->zajlib->compile->write('$filter_var = json_decode($filter_var);');
		return true;
	}

	/**
	 * Filter: safe - Disable automatic XSS filtering on the variable.
	 *
	 *  <b>{{variable|safe}}</b> Will allow <script> within the variable.
	 **/
	public function filter_safe($parameter, &$source){
			// Actually does nothing, just here as a placeholder. Action is performed during variable compilation.
		return true;
	}


	/**
	 * Filter: substr - Cuts a string at the given value. See also truncate.
	 *
	 *  <b>{{variable|substr:'5'}}</b> Truncates the length of variable string to 5 characters. So 'Superdooper' will be 'Super'
	 **/
	public function filter_substr($parameter, &$source){
			if(!$parameter) return $source->warning('substr filter parameter required!');
		// write to file
			$this->zajlib->compile->write('$filter_var=mb_substr($filter_var, 0, '.$parameter.');');
		return true;
	}
	
	/**
	 * Filter: querymode - Adds a ? or & to the end of the URL...whichever is needed.
	 *
	 *  <b>{{url|querymode}}</b> Assuming url is http://www.example.com/?q=1, it will return http://www.example.com/?q=1& and assuming URL is http://www.example.com/ it will return http://www.example.com/?
	 *  <b>{{url|querymode:'some=more&parameters=gohere'}}</b> Assuming url is http://www.example.com/?q=1, it will return http://www.example.com/?q=1&some=more&parameters=gohere and assuming URL is http://www.example.com/ it will return http://www.example.com/?some=more&parameters=gohere
	 **/
	public function filter_querymode($parameter, &$source){
		// parameter defaults to empty string
			if(!$parameter) $parameter = '""';
		// write to file
			$this->zajlib->compile->write('if(strstr($filter_var, "?") === false) $filter_var.="?".'.$parameter.'; else $filter_var.="&".'.$parameter.';');
		return true;
	}
	
	/**
	 * Filter: printf - Allows substitutions in a string value. This is especially useful for localization.
	 *
	 *  <b>{{#translated_string#|printf:'16'}}</b> Assuming translated_string is 'There are %s registered' it will return 'There are 16 registered users'. Of course, '16' can be replaced with a variable as such: {{#translated_string#|printf:users.total}}
	 **/
	
	public function filter_printf($parameter, &$source, $counter_for_all_filters, $counter_for_same_filter){
		// write to file
			$this->zajlib->compile->write('$filter_var = str_ireplace(\'%'.$counter_for_same_filter.'\', '.$parameter.', $filter_var);');
		return true;
	}

	/**
	 * Filter: is_dict - Returns true if dictionary/list. Dictionaries/lists are arrays and/or objects.
	 *
	 *  <b>{{myvar|is_dict}}</b> Returns true if myvar is an array or object.
	 **/

	public function filter_is_dict($parameter, &$source, $counter_for_all_filters, $counter_for_same_filter){
		// write to file
			$this->zajlib->compile->write('$filter_var = (is_object($filter_var) || is_array($filter_var));');
		return true;
	}


	/**
	 * Filter: translate - Translate a string to another locale.
	 *
	 * You must use a translation field as the input such as {{product.translation.name|translate:'hu_HU'}}. A warning will be generated if you try to use product.data.name instead.
	 *
	 * <b>{{product.translation.name|translate:'sk_SK'}}</b> Will return the localized version of the product name. Without the filter, the current locale's value is shown.
	 *
	 **/
	public function filter_translate($parameter, &$source, $counter_for_all_filters, $counter_for_same_filter){
		// If parameter is not defined, then the parameter is the current locale
			if(empty($parameter)) return $source->warning('You must specify which locale you want to translate to for filter "translate".');
		// write to file
			$this->zajlib->compile->write('$paramval = '.$parameter.'; $filter_var = $filter_var->get_by_locale($paramval);');
		return true;
	}

	/**
	 * Filter: explode - Opposite of join, explodes a string by a character. Same as PHP's explode().
	 *
	 * <b>{{'comma,separated,value'|explode:','}}</b> Returns a list separated by comma.
	 *
	 **/
	public function filter_explode($parameter, &$source, $counter_for_all_filters, $counter_for_same_filter){
		// If parameter is not defined, then the parameter is the current locale
		if(empty($parameter)) return $source->warning('You must specify a split character for filter "explode".');
		// write to file
		$this->zajlib->compile->write('$paramval = '.$parameter.'; $filter_var = explode($paramval, $filter_var);');
		return true;
	}

	/**
	 * Filter: keyvalue - Returns value of an item in a list or array.
	 *
	 * <b>{{list|keyvalue:itemkey}}</b> Useful if you want to fetch a key value by a variable. If itemkey is 'somekey' then this will return {{list.somekey}}.
	 *
	 **/
	public function filter_keyvalue($parameter, &$source){
		// If parameter is not defined, then the parameter is the current locale
		if(empty($parameter) && $parameter != 0) return $source->warning('You must specify a variable name to get the value of for filter "keyvalue".');
		// Write to file.
		$contents = <<<EOF
        if(empty(\$filter_var)) \$filter_var="";
        // When used numerically on zajFetcher, it will return the nth item
        elseif(is_object(\$filter_var) && is_a(\$filter_var, "zajFetcher") && is_numeric($parameter)){
            \$new_fetcher = clone \$filter_var;
            \$filter_var = \$new_fetcher->limit($parameter-1, 1)->next();
        }
        // Standard array or object
        elseif(is_array(\$filter_var) || is_object(\$filter_var)){
            // Note: we need to use ArrayObject because \$obj->{0} notation not working always.
            if(is_object(\$filter_var)){ \$filter_var = new ArrayObject(\$filter_var); }
            \$filter_var = \$filter_var[$parameter] ?? null;
        }
        else \$this->zajlib->warning("You tried to use the keyvalue filter on something other than an object or an array. A common mistake is to use {{zaj.lang}} instead of {{zaj.config}} for variable variables. <a href='http://framework.outlast.hu/advanced/internationalization/using-language-files/#docs-using-variable-language-variables'>See docs</a>.");
EOF;
		$this->zajlib->compile->write($contents);
		return true;
	}

    /**
     * @deprecated
     */
	public function filter_key($parameter, &$source){
	    // @todo add this warning: $source->warning('|key is deprecated and you should use |keyvalue instead.');
        return $this->filter_keyvalue($parameter, $source);
    }

	/**
	 * Filter: trim - Trims characters (space by default) from left and right side of string.
	 *
	 * <b>{{' this has whitespace '|trim}}</b> Will return 'this has whitespace'.
	 * <b>{{'/url/with/trailing/slash/'|trim:'/'}}</b> Will return without trailing or preceding slash, 'url/with/trailing/slash'
	 *
	 **/
	public function filter_trim($parameter, &$source){
		// If parameter is not defined, then the parameter is space
		if(empty($parameter)) $parameter = '" \t\n\r\0\x0B"';
		// Write to file.
		$this->zajlib->compile->write('$filter_var=trim($filter_var, '.$parameter.');');
		return true;
	}

	/**
	 * Filter: rtrim - Trims characters (space by default) from right side of string.
	 *
	 * <b>{{' this has whitespace '|rtrim}}</b> Will return ' this has whitespace'.
	 * <b>{{'/url/with/trailing/slash/'|rtrim:'/'}}</b> Will return without trailing slash, '/url/with/trailing/slash'
	 *
	 **/
	public function filter_rtrim($parameter, &$source){
		// If parameter is not defined, then the parameter is the current locale
		if(empty($parameter) && $parameter != 0) $parameter = "' '";
		// Write to file.
		$this->zajlib->compile->write('$filter_var=rtrim($filter_var, '.$parameter.');');
		return true;
	}

	/**
	 * Filter: ltrim - Trims characters (space by default) from left side of string.
	 *
	 * <b>{{' this has whitespace '|ltrim}}</b> Will return 'this has whitespace '.
	 * <b>{{'/url/with/trailing/slash/'|ltrim:'/'}}</b> Will return without preceding slash, 'url/with/trailing/slash/'
	 *
	 **/
	public function filter_ltrim($parameter, &$source){
		// If parameter is not defined, then the parameter is the current locale
		if(empty($parameter) && $parameter != 0) $parameter = "' '";
		// Write to file.
		$this->zajlib->compile->write('$filter_var=ltrim($filter_var, '.$parameter.');');
		return true;
	}


	/**
	 * Filter: in - You can check if an item is contained within another. This is especially useful for lists.
	 *
	 * If you check in a list, it will look for an object within that list. If you check in a string, it will check if the string is in the other. This is very similar to django's in operator {@link https://docs.djangoproject.com/en/1.2/ref/templates/builtins/#in-operator}
	 *
	 * <b>{{product|in:topproducts}}</b> Will return true if the object product is found within the list topproducts. Also works for arrays.
	 * <b>{{'something'|in:'A sentence about something.'}}</b> Will return true if the string is located within the string.
	 *
	 **/
	public function filter_in($parameter, &$source, $counter_for_all_filters, $counter_for_same_filter){
		// If parameter is not defined, then the parameter is the current locale
			if(empty($parameter)) return $source->warning('You must specify which variable you want to search in.');
		// Generate some code
			$content = <<<EOF
// if search in zajfetcher list
if(is_object($parameter) && is_a($parameter, "zajFetcher")){
	\$copy = clone {$parameter};
	\$filter_var = \$copy->filter('id', \$filter_var)->total;
}
elseif(is_object($parameter)){
	\$filter_var = in_array(\$filter_var, (array) $parameter);
}
elseif(is_array($parameter)){
	\$filter_var = in_array(\$filter_var, $parameter);
}
else{
	if(strstr($parameter, \$filter_var) !== false) \$filter_var = true;
	else \$filter_var = false;
}
EOF;
		$this->zajlib->compile->write($content);
		return true;
	}

	/**
	 * Filter: gravatar - Returns the gravatar
	 *
	 * <b>{{list|gravatar:size}}</b> Returns the full url of the user’s Gravatar image with a pixel width size of 'size'. Size will default to 50.
	 *
	 **/
	public function filter_gravatar($parameter, &$source){
		// If parameter is not defined, then the parameter is the current locale
		if(empty($parameter)) $parameter = 50;

		// Figure out gravatar url to use
		$this->zajlib->config->load('filters.conf.ini', 'gravatar');

		if($this->zajlib->url->valid($this->zajlib->config->variable->default_image_url)){
			$default_image_url = 'd='.urlencode($this->zajlib->config->variable->default_image_url);
		}
		elseif($this->zajlib->config->variable->default_image_url){
			$default_image_url = 'd='.urlencode($this->zajlib->baseurl . $this->zajlib->config->variable->default_image_url);
		}
		else{
			$default_image_url = '';
		}

		// Write to file
		$this->zajlib->compile->write('$filter_var = "//www.gravatar.com/avatar/" . md5( strtolower( trim( $filter_var ) ) ) . "?'.$default_image_url.'&s=" . '.$parameter.';');
		return true;
	}

	/**
	 * Filter: strip_attribute - Removes a given attribute from any html tags in the variable.
	 *
	 * <b>{{ product.data.description|strip_attribute:'style' }}</b> Will remove styling.
	 */
	public function filter_strip_attribute($parameter, &$source){
		// return error
			if(!$parameter) $this->zajlib->compile->error("Parameter is required for filter strip_attribute.");
		$this->zajlib->compile->write('$filter_var = preg_replace(\'/(<[^>]+) \'.'.$parameter.'.\'=".*?"/i\', \'$1\', $filter_var);');
		return true;
	}

}
