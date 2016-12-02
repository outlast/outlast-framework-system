<?php
/**
 * Library helps you export data from Outlast Framework models, database queries, or an array of standard PHP objects. Using PHPExcel will provide more features and better export results.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 **/

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

define("OFW_EXPORT_ENCODING_DEFAULT", false);
define("OFW_EXPORT_ENCODING_EXCEL", true);


class zajlib_export extends zajLibExtension {

		/**
		 * Export model data as JSON.
		 * @param zajModel|array|zajFetcher $fetcher A zajFetcher list of zajModel objects which need to be exported. It can also be an array of objects (such as a zajDb query result) or a multi-dimensional array.
		 * @param array|bool $fields A list of fields from the model which should be included in the export. NOT YET IMPLEMENTED!
		 * @param string $file The name of the file which will be used during download.
		 * @return string|boolean Print the json or return it in test mode.
		 */
		public function json($fetcher, $fields = false, $file='export.json'){
			$array_data = array();
			// If this is a model
				if(is_a($fetcher, 'zajModel') || is_a($fetcher, 'zajModelExtender')){
					$array_data = $fetcher->to_array();
				}
			// If this is a fetcher or array
				elseif(is_a($fetcher, 'zajFetcher') || is_array($fetcher)){
					foreach($fetcher as $object){
						if(is_a($object, 'zajModel') || is_a($object, 'zajModelExtender')){
							/** @var zajModel $object */
							$array_data[] = $object->to_array();
						}
						else $this->zajlib->error("JSON exported list must contain model objects only!");
					}
				}
			// Now convert to JSON data and write to file output
				header('Content-Disposition: attachment; filename="'.$file);
				header('Content-Type: application/json;');
				$out = fopen("php://output", 'w');
				fputs($out, json_encode($array_data));
				fclose($out);
				exit;
		}

		/**
		 * Export model data as csv.
		 * @param array|zajlib_db_session|zajlib_mssql_resultset|zajFetcher $fetcher A zajFetcher list of zajModel objects which need to be exported. It can also be an array of objects (such as a zajDb query result) or a multi-dimensional array.
		 * @param array|bool $fields A list of fields from the model which should be included in the export.
		 * @param string|File $file The name of the file which will be used during download or the File object if writing to a file.
		 * @param boolean|string $encoding The value can be OFW_EXPORT_ENCODING_DEFAULT (utf8), OFW_EXPORT_ENCODING_EXCEL (Excel-compatible UTF-16LE), or any custom-defined encoding string.
		 * @param bool|string $delimiter The separator for the CSV data. Defaults to comma, unless you set excel_encoding...then it defaults to semi-colon.
         * @param int $rowcount_resume Set this to the row count at which you wish to resume the export. This only makes sense if writing to file. Header row not included!
		 * @return integer|boolean Will return false if error, the csv data if downloading, or the row count if writing to file.
		 */
		public function csv($fetcher, $fields = false, $file='export.csv', $encoding = false, $delimiter = false, $rowcount_resume = 1){
            // Get writer
            $writer = $this->get_writer($file, 'csv');

            $encoding = 'application/vnd.ms-excel';
            $encoding = 'text/csv';

            // Save file data
            if(File::is_instance_of_me($file)){
                // Set as uploaded
                /** @var File $file */
                $file->set('status', 'saved');
                $file->set('mime', $encoding);
                $file->save();
            }

            return $this->send_data($writer, $fetcher, $fields);






			// Show template
			$this->zajlib->config->load('export.conf.ini');

			// If encoding is boolean true, it is excel-encoding
			//	if($encoding === OFW_EXPORT_ENCODING_EXCEL) $encoding = "UTF-16LE";


            // Output path
            if(!File::is_instance_of_me($file)){
                $output_path = 'php://output';
                $write_mode = "w";
            }
            else{
                $output_path = $this->zajlib->basepath.$file->get_file_path(false, true);
                if(file_exists($output_path)) $write_mode = "a";
                else $write_mode = "w";
            }

			// No more autoloading for OFW
            zajLib::me()->model_autoloading = false;

            // Inlcude

			// Get my PhpExcel path
            $phpexcel_path = $this->zajlib->config->variable->php_excel_path;
            if(substr($phpexcel_path, 0, 1) != '/') $phpexcel_path = $this->zajlib->basepath.$phpexcel_path;

			// Try using PHPExcel if available
            @include_once($phpexcel_path);
            if(!class_exists('PHPExcel', false) || $encoding){
                // Standard CSV export
                zajLib::me()->model_autoloading = true;

                // Standard CSV or Excel header?
                if($encoding){
                    // Use excel encoding or custom encoding
                    if(!File::is_instance_of_me($file)){
                        if($encoding === OFW_EXPORT_ENCODING_EXCEL) header("Content-Type: application/vnd.ms-excel; charset=UTF-16LE");
                        else  header("Content-Type: application/vnd.ms-excel; charset=".$encoding);
                        header("Content-Disposition: attachment; filename=\"$file\"");
                    }
                    else{
                        /** @var File $file */
                        $file->set('status', 'saved');
                        $file->set('mime', 'application/vnd.ms-excel');
                        $file->save();
                    }
                    if(!$delimiter) $delimiter = ';';
                }
                else{
                    if(!File::is_instance_of_me($file)){
                        header("Content-Type: text/csv; charset=UTF-8");
                        header("Content-Disposition: attachment; filename=\"$file\"");
                    }
                    else{
                        /** @var File $file */
                        $file->set('status', 'saved');
                        $file->set('mime', 'text/csv');
                        $file->save();
                    }
                    if(!$delimiter) $delimiter = ',';
                }

                // Write output
                $output = fopen($output_path, $write_mode);
                $rows_written = $this->send_data($output, $fetcher, $fields, $encoding, $delimiter, $rowcount_resume);
                fclose($output);
            }
            else{
                // Create the csv file with PHPExcel
                if(!$delimiter) $delimiter = ',';

                // Create or resume the excel file
                if(!File::is_instance_of_me($file) || !file_exists($output_path)){
                    $workbook = new PHPExcel();
                    if(File::is_instance_of_me($file)){
                        // Set as uploaded
                        /** @var File $file */
                        $file->set('status', 'saved');
                        $file->set('mime', 'text/csv');
                        $file->save();
                    }
                }
                else{
                    $ioworkbook = PHPExcel_IOFactory::createReader('Excel2007');
                    $workbook = $ioworkbook->load($output_path);
                }
                $workbook->setActiveSheetIndex(0);

                zajLib::me()->model_autoloading = true;
                $rows_written = $this->send_data($workbook, $fetcher, $fields, false, false, $rowcount_resume);
                zajLib::me()->model_autoloading = false;
                if(File::is_instance_of_me($file)){
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment;filename="'.$file.'"');
                    header('Cache-Control: max-age=0');
                }

                // Write output
                $writer = PHPExcel_IOFactory::createWriter($workbook, 'CSV');
                $writer->setDelimiter($delimiter);
                //$writer->setEnclosure('\'.$delimiter);
                $writer->setLineEnding("\r\n");
                $writer->setSheetIndex(0);
                $writer->save($output_path);
            }

            // Autoload back on, exit or return
            zajLib::me()->model_autoloading = true;
            if(File::is_instance_of_me($file)) exit();
            else return $rows_written;

		}

		/**
		 * Export model data as excel. It should be noted that CSV export is much less memory and processor intensive, so for large exports we recommend that.
		 * @param array|zajlib_db_session|zajlib_mssql_resultset|zajFetcher $fetcher A zajFetcher list of zajModel objects which need to be exported. It can also be an array of objects (such as a zajDb query result) or a multi-dimensional array.
		 * @param array|bool $fields A list of fields from the model which should be included in the export.
		 * @param string|File $file The name of the file which will be used during download or the File object if writing to a file.
         * @param int $rowcount_resume Set this to the row count at which you wish to resume the export. This only makes sense if writing to file. Header row not included! @todo implement in future if it can optimize things!
		 * @require Requires the Spreadsheet_Excel_Writer PEAR module.
		 * @return integer|boolean Will return false if error, the xls data if downloading, or the row count if writing to file.
		 */
		public function xls($fetcher, $fields = false, $file='export.xlsx', $rowcount_resume = 1){
            // Get writer
            $writer = $this->get_writer($file, 'xls');

            // Save file data
            if(File::is_instance_of_me($file)){
                // Set as uploaded
                /** @var File $file */
                $file->set('status', 'saved');
                $file->set('mime', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $file->save();
            }

            // Send the data
            return $this->send_data($writer, $fetcher, $fields, false, false, $rowcount_resume);
		}

        /**
         * Get the writer object
         * @param File|string $file If it is a File, we check to see if it exists and will resume if it does. If it is a string, we will output to browser.
         * @param $format
		 * @param boolean|string $encoding The value can be OFW_EXPORT_ENCODING_DEFAULT (utf8), OFW_EXPORT_ENCODING_EXCEL (Excel-compatible UTF-16LE), or any custom-defined encoding string.
		 * @param bool|string $delimiter The separator for the CSV data. Defaults to comma, unless you set excel_encoding...then it defaults to semi-colon.
         * @return \Box\Spout\Writer\XLSX\Writer|\Box\Spout\Writer\CSV\Writer|\Box\Spout\Writer\ODS\Writer Returns a new writer.
         */
		private function get_writer($file, $format='csv', $encoding=false, $delimiter=false){
            // Are we in file or browser mode
            if(File::is_instance_of_me($file)) $is_a_file = true;
            else $is_a_file = false;

            // Load up writer
            zajLib::me()->model_autoloading = false;
            require_once $this->zajlib->basepath.'system/ext/spout/Autoloader/autoload.php';

            // Define type
            switch($format){
                case 'xlsx':
                case 'xls':
                    $type = Type::XLSX;
                    break;
                case 'ods':
                    $type = Type::ODS;
                    break;
                case 'csv':
                default:
                    $type = Type::CSV;
                    break;
            }

            // @todo add file path verification! should be in data folder.

            // Do we have an existing file?
            if($is_a_file && $file->file_exists()){
                // Move to a temporary location
                $temporary_file_path = $this->zajlib->basepath.$file->get_file_path().'.copying.tmp';
                rename($this->zajlib->basepath.$file->get_file_path(), $temporary_file_path);
                $reader = ReaderFactory::create($type);
                $reader->open($temporary_file_path);
                $reader->setShouldFormatDates(true); // this is to be able to copy dates
            }
            else $reader = false;

            // Create and open writer
            $writer = WriterFactory::create($type); // for XLSX files
            if($is_a_file) $writer->openToFile($this->zajlib->basepath.$file->get_file_path());
            else $writer->openToBrowser($file);

            // Copy old existing data
            if($is_a_file && $reader !== false){
                // let's read and write the entire spreadsheet...
                foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
                    // Add sheets in the new file, as we read new sheets in the existing one
                    if ($sheetIndex !== 1) {
                        $writer->addNewSheetAndMakeItCurrent();
                    }

                    foreach ($sheet->getRowIterator() as $row) {
                        // ... and copy each row into the new spreadsheet
                        $writer->addRow($row);
                    }
                }

                // now remove the temporary file and close reader
                $reader->close();
                if(!empty($temporary_file_path)) unlink($temporary_file_path);
            }

            zajLib::me()->model_autoloading = true;
            return $writer;
		}

		/**
		 * Write data to an output.
		 * @param resource|\Box\Spout\Writer\XLSX\Writer|\Box\Spout\Writer\CSV\Writer|\Box\Spout\Writer\ODS\Writer $output The output object or handle.
		 * @param zajFetcher|zajlib_db_session|zajlib_mssql_resultset|array $fetcher A zajFetcher list of zajModel objects which need to be exported. It can also be an array of objects (such as a zajDb query result) or a multi-dimensional array.
		 * @param array $fields A list of fields from the model which should be included in the export.
		 * @param boolean|string $encoding The value can be OFW_EXPORT_ENCODING_DEFAULT (utf8), OFW_EXPORT_ENCODING_EXCEL (Excel-compatible UTF-16LE), or any custom-defined encoding string.
		 * @param bool|string $delimiter The separator for the CSV data. Defaults to comma, unless you set excel_encoding...then it defaults to semi-colon.
         * @param int $rowcount_resume Set this to the row count at which you wish to resume the export. Header row not included!
		 * @return integer Returns the number of rows exported (excluding the header row).
		 */
		private function send_data(&$output, $fetcher, $fields, $encoding=false, $delimiter=false, $rowcount_resume = 1){
			// Set my time limit and memory limit
            $this->zajlib->config->load('export');
            if($this->zajlib->config->variable->export_max_memory) ini_set('memory_limit', $this->zajlib->config->variable->export_max_memory);
            if($this->zajlib->config->variable->export_max_execution_time) set_time_limit($this->zajlib->config->variable->export_max_execution_time);

            // Figure out where to start
            if($rowcount_resume <= 1) $rowcount = 1;
            else $rowcount = $rowcount_resume + 1; // Add the header row to it!
			$rowswritten = 0;

			// Initialize zajdbs
				$field_objects = [];
			// Get fields of fetcher class if fields not passed
				if(is_a($fetcher, 'zajFetcher') && (!$fields && !is_array($fields))){
					/** @var zajModel $class_name */
					$class_name = $fetcher->class_name;
					$my_fields = $class_name::__model();
					$fields = [];
					/** @var zajDb $val */
					foreach($my_fields as $field=>$val){
						if(!$val->disable_export){
							// Add fields to export
								$fields[$field] = $field;
							// Set the zajDb object if export formatting requiesd
								if($val->use_export) $field_objects[$field] = $class_name::__field($field);
								else $field_objects[$field] = false;
						}

					}
				}
			// Get fields of db object if fields not passed (the property names of the object)
				if(!is_a($fetcher, 'zajFetcher') && (!$fields && !is_array($fields))){
					// Get the first row and create $fields[] array from it
					// @todo Check to see if 0 rows in result set for each of these!
						if(is_a($fetcher, 'Iterator')) $my_fields = $fetcher->rewind();
						else $my_fields = reset($fetcher);
					// Make sure that it is an object or array
						if(!is_array($my_fields) && !is_object($my_fields)) return $this->zajlib->error("Tried exporting data but failed. Input data must be an array of objects or an object.");
					foreach($my_fields as $field=>$val) $fields[] = $field;
				}
			
			// Run through all of my rows
				$column_order = array();
				foreach($fetcher as $s){
					// Create row data
						$data = array();
					// Is this a model or an array?
						if(is_a($s, 'zajModel')) $model_mode = true;
						else $model_mode = false;
					// Add first default value (only if model_mode)
						if($model_mode){
							// Set as name
								$data['name'] = $s->name;
							// Convert encoding if it is set
								if($encoding) $data['name'] = mb_convert_encoding($data['name'], $encoding, 'UTF-8');
						}

					// Add my values for each field
						foreach($fields as $field_key => $field){
							// Figure out my string value
								if($model_mode){
									$field_value = $s->data->$field;
									// Do we need export formatting?
									if($field_objects[$field] !== false){
										/** @var zajDb|zajField $zajdb_obj */
										$zajdb_obj = $field_objects[$field];
										$field_value = $zajdb_obj->export($field_value, $s);
									}
								}
								else{
									// Either an array or an object
									if(is_array($s)) $field_value = $s[$field];
									else $field_value = $s->$field;
								}

							// If field value is an array or object then split into key/value columns but remove my column
								if(is_array($field_value) || (is_object($field_value) && is_a($field_value, 'stdClass'))){
									foreach($field_value as $key=>$value) $data[$field.'_'.$key] = $value;
								}
								else{
									// Set to array
									$data[$field] = $field_value;
								}

							// Convert encoding if excel mode selected
								if($encoding) $data[$field] = mb_convert_encoding($data[$field], $encoding, 'UTF-8');
						}

                    // Get the column order
                    $column_order = array_keys($data);

					// If firstline, display fields
                    if($rowcount == 1){
                        // Write via Writer or CSV
                        if(is_object($output)){
                            zajLib::me()->model_autoloading = false;
                            $output->addRow($column_order);
                            zajLib::me()->model_autoloading = true;
                        }
                        else fputcsv($output, $column_order, $delimiter);
                        $rowcount++;
                    }
						
                    // Write with Writer
                    if(is_object($output)){
                        // Write values
                        foreach($data as $field_key => $field_val){
                            /**
                            // Get which column this is
                            $r = array_keys($column_order, $field_key);
                            $col = $r[0];

                            // Check if col is defined, if not now is the time to create the new column
                            if(count($r) == 0){
                                $col = count($column_order);
                                $column_order[] = $field_key;
                                $output->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $field_key);
                            }
                            // If field value is an object
                                if(is_object($field_val) || is_array($field_val)) $field_val = json_encode($field_val);
                            // Replace new lines
                                //$field_val = str_ireplace("\n", " ", $field_val);
                            // Now output
                                $output->getActiveSheet()->setCellValueByColumnAndRow($col, $rowcount, $field_val);
                            **/
                            // @todo implement this stuff!
                        }
                        zajLib::me()->model_autoloading = false;
                        $output->addRow($data);
                        zajLib::me()->model_autoloading = true;
                    }
                    // Write standard CSV
                    else fputcsv($output, $data, $delimiter);

                    // Add to linecount
                    $rowswritten++;
                    $rowcount++;
				}

            // Close file
            if(is_object($output)){
                zajLib::me()->model_autoloading = false;
                $output->close();
                zajLib::me()->model_autoloading = true;
            }
            else fclose($output);

			return $rowswritten;
		}
	
		/**
		 * Send an array of form responses to a Google Docs spreadsheet form. You must make the sheet publicly visible and create a form from it.
		 * @param string $formkey The form key. You can get this data from the form's public URL query string.
		 * @param array $responses An key/value array of responses which to record. You must use the same key as the name of the field in the form (use 'inspect element' feature in Chrome on the Google Docs form to check).
		 * @param string $ok_text This text is searched for when we try to determine if the save was successful. You only need to change this if a custom confirmation message is set in Google Docs.
		 * @return boolean Returns true if successful and false if fails. It also throws a warning if it failed.
		 **/
		public function gdocs_form($formkey, $responses, $ok_text = "Your response has been recorded."){
			// Build entries list
				$query = '';
				foreach($responses as $key=>$val) $query .= '&'.urlencode($key).'='.urlencode($val);
			// Send the data
				$response = file_get_contents("https://docs.google.com/a/zajmedia.com/spreadsheet/formResponse?formkey=".$formkey."&embedded=true&ifq&pageNumber=0&submit=Submit".$query);
			// If $ok_text is contained in the response then all is ok.
				if(strstr($response, $ok_text) !== false) return true;
				else{
					$this->zajlib->warning('Unable to save responses to Google Forms: '.$query);
					return false;
				}
		}

}