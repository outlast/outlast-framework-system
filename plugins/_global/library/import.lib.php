<?php
/**
 * Library helps you import data into Mozajik models, arrays, or other objects.
 * @author Aron Budinszky <aron@mozajik.org>
 * @version 3.0
 * @package Library
 **/

define("OFW_IMPORT_PHPEXCEL_PATH", "/var/www/_scripts/PHPExcel/PHPExcel.php");
define("OFW_IMPORT_MAX_EXECUTION_TIME", 300);

class zajlib_import extends zajLibExtension {

	/**
	 * Reads a tab of a published Google Document in CSV format returns an array of objects. In order to use this you must use the File / Publish to the web... feature. Also, check Automatically republish changes to make sure it stays in sync.
	 * @param string $url A CSV-formatted url that is displayed in the Publish to the web... feature of Google docs.
	 * @param boolean $first_row_is_header If set to true, the values of the first row will be used as keys (converted to compatible chars).
	 * @param string $delimiter Set the field delimiter (one character only).
	 * @param string $enclosure Set the field enclosure character (one character only).
	 * @param string $escape Set the escape character (one character only). Defaults as a backslash (\).
	 * @return array An array of objects where the keys are either numbers or taken from the header row.
	 **/
	public function gdocs_spreadsheet($url, $first_row_is_header = true, $delimiter = ',', $enclosure = '"', $escape = '\\'){
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
						foreach($current_row as $key => $value){
							if($first_row_is_header) $current_data[$first_row[$key]] = $value;
							else $current_data[$key] = $value;
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

}