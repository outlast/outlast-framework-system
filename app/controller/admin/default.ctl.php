<?php
/**
 * This controller stops admin access by default.
 * @package Controller
 * @subpackage BuiltinControllers
 **/
	class zajapp_admin_default extends zajController{
		/**
		 * The __load() magic method is run each time this particular controller is used to process the request. You should place code here which is general for all
		 *  related requests. For example, an admin.ctl.php file's __load() method will likely contain an authentication process, so that anyone requesting
		 *  any admin pages will need to login first...
		 **/
		public function __load(){
			// your code here			
		}
		
		/**
		 * The main() method is the default for any controller.
		 **/
		public function main(){
			if($this->zajlib->debug_mode) $this->zajlib->warning("Admin access not available. Please create a local admin/default.ctl.php file with authentication. <a href='http://framework.outlast.hu/plugins/user-plugin/creating-admin-interfaces/' target='_blank'>See documentation.</a>");
			else exit("Admin access is disabled for this site. If you believe this is an error, please contact administrator.");
		}

	}