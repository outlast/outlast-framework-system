<?php
/**
 * Library helps you import data into Mozajik models, arrays, or other objects.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

define("OFW_IMPORT_PHPEXCEL_PATH", "/var/www/_scripts/PHPExcel/PHPExcel.php");
define("OFW_IMPORT_MAX_EXECUTION_TIME", 300);

class zajlib_import extends zajLibExtension {

	/**
	 * Reads a tab of a publicly shared Google Document in CSV format returns an array of objects.
	 * @param string $url A CSV-formatted url that is displayed in the Publish to the web... feature of Google docs.
	 * @param boolean $first_row_is_header If set to true, the values of the first row will be used as keys (converted to compatible chars).
	 * @param string $delimiter Set the field delimiter (one character only).
	 * @param string $enclosure Set the field enclosure character (one character only).
	 * @param string $escape Set the escape character (one character only). Defaults as a backslash (\).
	 * @return array An array of objects where the keys are either numbers or taken from the header row.
	 **/
	public function gdocs_spreadsheet($url, $first_row_is_header = true, $delimiter = ',', $enclosure = '"', $escape = '\\'){
		// Must be a valid url
			if(!$this->zajlib->url->valid($url)) return $this->zajlib->warning("Gdocs import must be a valid url.");
		// Check if we have access
			if($this->zajlib->url->response_code($url) != 200) return $this->zajlib->warning("No access to Gdocs document. Is the link publicly shared?");

		// If url is not an export url convert it now
			if(strstr($url, 'export') === false){
				// Get my document key
					preg_match("|spreadsheets/d/([A-z0-9]+)/edit|", $url, $matches);
				// Get my tab fragment
					$urldata = parse_url($url);
					if(substr($urldata['fragment'], 0, 4) == 'gid=') $tab = '&'.$urldata['fragment'];
					else $tab = '';
				// Now set my url
					$url = "https://docs.google.com/spreadsheets/d/".$matches[1]."/export?format=csv&key=".$matches[1].$tab;
			}
		return $this->csv($url, $first_row_is_header, $delimiter, $enclosure, $escape);
	}

	/**
	 * Reads a CSV document and returns an array of objects.
	 * @param string $urlORfile A CSV-formatted file (relative to basepath) or URL.
	 * @param boolean $first_row_is_header If set to true, the values of the first row will be used as keys (converted to compatible chars).
	 * @param string $delimiter Set the field delimiter (one character only).
	 * @param string $enclosure Set the field enclosure character (one character only).
	 * @param string $escape Set the escape character (one character only). Defaults as a backslash (\).
	 * @return array An array of objects where the keys are either numbers or taken from the header row.
	 */
	public function csv($urlORfile, $first_row_is_header = true, $delimiter = ',', $enclosure = '"', $escape = '\\'){
		// If it is not a url, then check sandbox
			if(!$this->zajlib->url->valid($urlORfile)){
				$this->zajlib->file->file_check($urlORfile);
				$urlORfile = $this->zajlib->basepath.$urlORfile;
			}
		// Open the url
			$return_data = array();
			if (($handle = fopen($urlORfile, "r")) !== FALSE) {
				// Use first row as header?
					if($first_row_is_header) $first_row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
					else $first_row = array();
				// Now while not feof add a row to object
					while(!feof($handle)){
						$current_data = array();
						$current_row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
						if(is_array($current_row)){
							foreach($current_row as $key => $value){
								if($first_row_is_header) $current_data[$first_row[$key]] = $value;
								else $current_data[$key] = $value;
							}
						}
						$return_data[] = (object) $current_data;
					}
			}
			else return $this->zajlib->warning("Could not open CSV for importing: $urlORfile");
		// Now return my data
			return $return_data;
	}

	/**
	 * Reads an Excel document and returns an array of objects.
	 * @param string $urlORfile A CSV-formatted file (relative to basepath) or URL.
	 * @param boolean $first_row_is_header If set to true, the values of the first row will be used as keys (converted to compatible chars).
	 * @return array An array of objects where the keys are either numbers or taken from the header row.
	 */
	public function xls($urlORfile, $first_row_is_header = true){
		// If it is not a url, then check sandbox
			if(!$this->zajlib->url->valid($urlORfile)){
				$this->zajlib->file->file_check($urlORfile);
				$urlORfile = $this->zajlib->basepath.$urlORfile;
			}
		// No more autoloading for OFW
			zajLib::me()->model_autoloading = false;
		// Create the PHPExcel input
			/** Load data into a PHPExcel Object  **/
			include_once(OFW_IMPORT_PHPEXCEL_PATH);
			/**  Identify the type of $inputFileName  **/
			$inputFileType = PHPExcel_IOFactory::identify($urlORfile);
			/**  Create a new Reader of the type that has been identified  **/
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			/**  Load $inputFileName to a PHPExcel Object  **/
			$objPHPExcelReader = $objReader->load($urlORfile);
		// Create the PHPExcel output
			$sheetData = $objPHPExcelReader->getActiveSheet()->toArray('',true,true,true);
		// Turn autoloading back on
			zajLib::me()->model_autoloading = true;
		// If no need to for keys just return
			if(!$first_row_is_header) return $sheetData;
			else{
				// Open the url
					$return_data = array();
				// Run through my results
					$key = array_shift($sheetData);
					foreach($sheetData as $data){
						$return_data[] = array_combine($key, $data);
					}
				return $return_data;
			}
	}

	/**
	 * Reads JSON data and tries to import as model data. Each item must have at least an id property to be imported.
	 * @param string $json_data The JSON data.
	 * @param string $model_name The name of the model
	 * @param boolean $create_if_not_exists If set to true, it will create the model object if it does not exist. Defaults to true.
	 * @param boolean $create_if_exists If set to false, existing items will be updated. If set to true, new ones will be created even for existing items. Defaults to false.
	 * @param boolean $return_created_objects If set to true, it will return the created model objects. Otherwise it returns the number. Defaults to false.
	 * @return integer|boolean|array The number of models updated or the created objects if that is requested.
	 */
	public function json($json_data, $model_name, $create_if_not_exists = true, $create_if_exists = false, $return_created_objects = false){
		/** @var zajModel $model_name */
		// Decode the data
			$data = json_decode($json_data);
			$updated = 0;
			$objects = array();
		// Validate data. It must be an array or an object.
			if(!is_array($data) && !is_object($data)){
				return $this->zajlib->warning("You tried to import invalid JSON data. JSON data must be an array or an object.");
			}
		// If this is not an array, then just create a single element array
			if(is_object($data)) $data = array($data);
		// Run through the list of model data objects
			foreach($data as $d){
				if(!empty($d->id)){
					// Try to fetch the item
					$object = $model_name::fetch($d->id);

					// Let's create it if it does not exist
					if($object === false && $create_if_not_exists) $object = $model_name::create($d->id);
					// Create a new one if it exists already, but with a new id
					if($object !== false && $create_if_exists) $object = $model_name::create();
					// Otherwise update if it already exists...

					// If found or created object then time to set with data
					if($object !== false){
						$object->set_with_data($d)->save();
						$updated++;
						if($return_created_objects) $objects[] = $object;
					}
				}
			}
		// Decide what to return
			if($return_created_objects) return $objects;
			else return $updated;
	}

}