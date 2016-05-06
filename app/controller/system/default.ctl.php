<?php
/**
 * This system controller handles various callbacks.
 * @package Controller
 * @subpackage BuiltinControllers
 * @todo Review the security issues for these methods.
 * @todo Disable direct access to data folder (only through PHP).
 * @todo Fix error messages to english and/or global via lang files.
 **/
	
	class zajapp_system extends zajController{
		
		/**
		 * Load method is called each time any system action is executed.
		 * @todo Allow a complete disabling of this controller. 
		 **/
		function __load(){
			// Add disable-check here!
		}

		/**
		 * Search for a relationship.
		 **/
		function search_relation(){		 	
		 	// strip all non-alphanumeric characters
		 		$class_name = ucfirst(strtolower(preg_replace('/\W/',"",$_REQUEST['class'])));
		 		$field_name = preg_replace('/\W/',"",$_REQUEST['field']);
		 		if(!empty($_REQUEST['type'])) $type = preg_replace('/\W/',"",$_REQUEST['type']);
		 		else $type = 'default';
			// limit defaults to 15
				if(empty($_REQUEST['limit']) || !is_numeric($_REQUEST['limit'])) $limit = 15;
				else $limit = $_REQUEST['limit'];
		 	// is it a valid model?
		 		if(!is_subclass_of($class_name, "zajModel")) return $this->zajlib->error("Cannot search model '$class_name': not a zajModel!");
		 	// now what is my field connected to?
		 		/** @var zajModel $class_name */
				$my_model = $class_name::__model();
		 		$other_model = reset($my_model->{$field_name}->options);
		 		if(empty($other_model)) $this->zajlib->error("Cannot connect to field '$field_name' because it is not defined as a relation or its relation model has not been defined!");
		 	// first fetch all
				/** @var zajFetcher $relations */
		 		$relations = $other_model::fetch();
		 	// filter by search query (if any)
				if(!empty($_REQUEST['query'])) $relations->search('%'.$_REQUEST['query'].'%', false);
			// limit
				$relations->limit($limit);
			// now send this to the magic method
				if(empty($this->zajlib->variable->user)) $this->zajlib->variable->user = User::fetch_by_session();
				$relations = $other_model::fire_static('onSearch', array($relations, $type));
			// error?
				if(!is_object($relations)) return zajLib::me()->error("You are trying to access the client-side search API for $other_model and access was denied by this model. <a href='http://framework.outlast.hu/advanced/client-side-search-api/' target='_blank'>See docs</a>.");
		 	// now output to relations json
				$my_relations = array();
				foreach($relations as $rel){
					$my_relations[] = (object) array('id'=>$rel->id, 'name'=>$rel->name);
				}
			// now return the json-encoded object	
				return $this->zajlib->json(array('query'=>$_REQUEST['query'], 'data'=>$my_relations));
		}	
		
		/**
		 * Logs javascript errors to a file (if enabled)
		 **/
		function javascript_error(){
			// Check if logging is enabled
				if(empty(zajLib::me()->zajconf['jserror_log_enabled']) || empty(zajLib::me()->zajconf['jserror_log_file'])) return $this->zajlib->ajax('not logged');
			// Defaults
				if(empty($_REQUEST['line'])) $_REQUEST['line'] = 0;
				if(empty($_SERVER['HTTP_USER_AGENT'])) $_SERVER['HTTP_USER_AGENT'] = "";
			// Intro
				$intro = 'Javascript error @ '.date('Y.m.d. H:i:s').' ('.zajLib::me()->request->client_ip().' | '.$_SERVER['HTTP_USER_AGENT'].')';
			// Now write to file
				$errordata = "\n".$_REQUEST['message'].' in file '.$_REQUEST['url'].' on line '.$_REQUEST['line'];
				$errordata .= "\nPage: ".$_REQUEST['location']."\n\n";
			// Now write to javascript error log
				file_put_contents(zajLib::me()->zajconf['jserror_log_file'], $intro.$errordata, FILE_APPEND);
			// Return ok
				return $this->zajlib->ajax('logged');
		}		

	}