<?php
/**
 * This controller allows tracking locally so that events are saved to UserEvent model (along with Analytics)
 * @package Controller
 * @subpackage BuiltinControllers
 **/

	// Set default configuration options

	class zajapp_system_track extends zajController{

		/**
		 * Track a user.
		 */
		public function main(){
			// Convert undefined to null
				if($_GET['label'] == 'undefined') $_GET['label'] = '';
				if($_GET['value'] == 'undefined') $_GET['value'] = '';
			// Check if local tracking enabled
				if($this->zajlib->zajconf['trackevents_local'] === true) UserEvent::track($_GET['category'], $_GET['action'], $_GET['label'], $_GET['value']);
			$this->zajlib->ajax('ok');
		}


	}
