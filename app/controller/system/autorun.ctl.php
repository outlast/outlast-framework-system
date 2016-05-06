<?php
/**
 * You can set this to run automatically via cron job for system tasks.
 **/

	class zajapp_system_autorun extends zajController{
		/**
		 * The __load() magic method is run each time this particular controller is used to process the request. You should place code here which is general for all
		 *  related requests. For example, an admin.ctl.php file's __load() method will likely contain an authentication process, so that anyone requesting
		 *  any admin pages will need to login first...
		 **/
		public function __load(){
			// Don't load up anything
		}

		/**
		 * The main() method is the default for any controller.
		 **/
		public function main(){
			// Nothing yet!
		}
	}