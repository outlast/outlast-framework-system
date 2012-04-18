<?php
/**
 * Update controller is a special system controller which handles installation, upgrade, database update, cache, and template cache related tasks.
 * @package Controller
 * @subpackage BuiltinControllers
 **/

	class zajapp_update extends zajController{
		/**
		 * Authenticate the request if not in debug mode
		 **/
		function __load(){
			// is update disabled?
				if(!$GLOBALS['zaj_update_enabled']) return exit("Update disabled.");
			// am i not in debug mode?
				if(!$this->zajlib->debug_mode){
					// is my password defined?
						if(!$GLOBALS['zaj_update_user'] || !$GLOBALS['zaj_update_password']) return $this->install();
					// all is good, so authenticate
						return $this->zajlib->security->protect_me($GLOBALS['zaj_update_user'], $GLOBALS['zaj_update_password'], "Mozajik update");
				}
			return true;
		}
		
		/**
		 * Display main menu for update
		 **/
		function main(){
			// mysql enabled?
				$this->zajlib->variable->mysql_enabled = $GLOBALS['zaj_mysql_enabled'];
			// load menu
				$this->zajlib->variable->title = "app update";
				$this->zajlib->template->show("update/update-menu.html");						
		}

		/**
		 * Display the database update menu
		 **/
		function database(){
			// check to see if my current install is up to date
				$version_status = MozajikVersion::check();
			// if all is good, display that message
				if($version_status < 0) return $this->zajlib->template->show('update/update-version-toonew.html'); 
			// if database exists and it is too old, then update!
				if($version_status == 0 && is_object($this->zajlib->mozajik)) return $this->zajlib->template->show('update/update-version-needed.html'); 
			// all is okay, continue with update
				$this->zajlib->variable->title = "app update | database";
				$this->zajlib->variable->updatename = "database model";
				$this->zajlib->variable->updateframeurl = "database/go/";
				$this->zajlib->template->show("update/update-process.html");			
		}

		/**
		 * Display the database update log
		 **/
		function database_go(){			
			// first let's show the update log template
				$this->zajlib->template->show("update/update-log.html");
			// now let's start the db update
				$this->zajlib->load->library("model");
				$db_update_result = $this->zajlib->model->update();
				$db_update_todo = $this->zajlib->model->num_of_todo;
			// now check if any errors
				if($db_update_result[0] >= 0) print $db_update_result[1];
				else exit("<input class='mozajik-update' type='hidden' id='update_result' value='$db_update_result[0]'><br>error: stopping update</body></html>");
			// now print the update_result
				print "<input class='mozajik-update' type='hidden' id='update_result' value='$db_update_result[0]'><input class='mozajik-update' type='hidden' id='update_todo' value='$db_update_todo'></body></html>";
			exit;
		}

		/**
		 * Display the object cache reset menu
		 **/
		function cache(){
			// count all the files
				if(empty($_GET['force'])){
					$this->zajlib->variable->folder = $this->zajlib->basepath."cache/object/";
					return $this->zajlib->template->show("update/update-cache.html");
				}
			// load variables
				$this->zajlib->variable->title = "object cache update | reset";
				$this->zajlib->variable->folder = $this->zajlib->cache->clear_objects();
				return $this->zajlib->template->show("update/update-cache.html");
		}

		/**
		 * Display the template cache reset menu
		 * @param boolean $show_result If set to true (the default), a message will be displayed once done. Otherwise the count will be returned.
		 * @return integer The count is returned if $show_result is set to false.
		 **/
		function template($show_result = true){
			// enable update mode
				file_put_contents($this->zajlib->basepath."cache/progress.dat", time());
			// get all the files in the template cache folder
				$this->zajlib->load->library("file");
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/view/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count = count($my_files);

			// get all the files in the conf folder
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/conf/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// get all the files in the conf folder
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/lang/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// get all the files in the temp folder
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/temp/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// disable update mode
				unlink($this->zajlib->basepath."cache/progress.dat");
			// print them
				if($show_result){
					$this->zajlib->variable->title = "template cache update | reset";
					$this->zajlib->variable->count = $total_count;
					$this->zajlib->template->show("update/update-template.html");
				}
				else return $total_count;
		}

		
		/**
		 * Install a new version of Mozajik
		 **/
		function install(){
			// Load my version information
				$this->zajlib->load->model('MozajikVersion');
			// Define my statuses
				$done = "<span style='color: green;'>Done.</span>";	
				$todo = "<span style='color: red;'>Not done.</span>";
				$optional = "<span style='color: grey;'>Optional.</span>";
				$na = "<span style='color: grey;'>Not enabled.</span>";
				$ready_to_activate = true;
				$ready_to_dbupdate = true;
			// Check status for each step
				// 1. Check writable
					if(!is_writable($this->zajlib->basepath."cache/") || !is_writable($this->zajlib->basepath."data/")){ $status_write  = $todo; $ready_to_dbupdate = false; $ready_to_activate = false; }
					else $status_write  = $done;
				// 2. Check database permissions
					if(!$GLOBALS['zaj_mysql_enabled']){ $status_db  = $na; $ready_to_dbupdate = false; }
					else{
						if($this->zajlib->db->connect($GLOBALS['zaj_mysql_server'], $GLOBALS['zaj_mysql_user'], $GLOBALS['zaj_mysql_password'], $GLOBALS['zaj_mysql_db'], false)) $status_db = $done;
						else{ $status_db  = $todo; $ready_to_dbupdate = false; $ready_to_activate = false; }
					}
				// 3. Check user/pass for update
					if(empty($GLOBALS['zaj_update_user']) || empty($GLOBALS['zaj_update_password'])){
						if($this->zajlib->debug_mode) $status_updatepass = $optional;
						else{ $status_updatepass = $todo; $ready_to_activate = false; }
					}
					else $status_updatepass = $done;
				// 4. Check database update (photo table should always exist)
					if(!$GLOBALS['zaj_mysql_enabled']) $status_dbupdate  = $na;
					elseif($status_db == $todo){ $status_dbupdate  = $todo; $ready_to_activate = false; }
					else{				
						$result = $this->zajlib->db->query("SELECT count(*) as c FROM information_schema.tables WHERE table_schema = '".addslashes($GLOBALS['zaj_mysql_db'])."' AND table_name = 'photo'")->next();
						if($result->c <= 0){ $status_dbupdate = $todo; $ready_to_activate = false; }
						else $status_dbupdate = $done;
					}
				// 5. Check activation
					if(!is_object($this->zajlib->mozajik) || !MozajikVersion::check()) $status_activate = $todo;
					else $status_activate = $done;
			
			?>
<head>
	<meta charset="utf-8">
	<title>Mozajik Installation</title>
	<meta name="description" content="">
	<meta name="author" content="">
	<!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/base.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/skeleton.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/layout.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/mozajik.css">


	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mootools/mootools-core-1.3.js" type="text/javascript"></script>
	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mootools/mootools-more-1.3.js" type="text/javascript"></script>	
	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mozajik-base-1.3.js" type="text/javascript"></script>

</head>
<body>
	<div class="container">
		<div class="sixteen columns">
			<br/><br/>
			<h1>Mozajik Installation</h1>
			<h3>Welcome to the Mozajik Framework installation for version <?php echo MozajikVersion::$major; ?>.<?php echo MozajikVersion::$minor; ?>.<?php echo MozajikVersion::$build; ?> <?php if(MozajikVersion::$beta) echo "beta"; ?></h3>
			<?php if($this->zajlib->debug_mode){ ?><h5><span style="color: red;">Attention!</span> This installation will be running in <strong>debug mode</strong>. This is not recommended for production sites!</h5><?php } ?>
			<hr/>
			<ol>
				<li>Make /cache and /data folders writable by webserver. - <?php echo $status_write; ?></li>
				<li>Create read/write permissions for database (if mysql is enabled). - <?php echo $status_db; ?></li>
				<li>Create an update user and password in /site/index.php (required if in production mode). - <?php echo $status_updatepass; ?></li>
				<li>Update the database (if mysql is enabled). - <?php echo $status_dbupdate; ?></li>
				<li>Activate this installation. - <?php echo $status_activate; ?></li>
				<?php if($ready_to_activate && $status_activate == $done){ ?><p class="center">Your installation is currently <span style="color:green;">activated</span>. Go to the <a href="<?php echo $this->zajlib->baseurl; ?>">home page</a>.</p><?php } ?>
			</ol>
			<hr/>
		</div>
		<div class="five columns center">
			<input type="button" onclick="zaj.reload();" value="Recheck install status">
		</div>
		<div class="five columns center">
			<input type="button" onclick="zaj.open('<?php echo $this->zajlib->baseurl; ?>update/database/', 1000, 500);" <?php if(!$ready_to_dbupdate){ ?>disabled="disabled"<?php } ?> value="Update the database">
		</div>
		<div class="five columns center">
			<input type="submit" onclick="window.location = '<?php echo $this->zajlib->baseurl; ?>update/install/go/';" <?php if(!$ready_to_activate || $status_activate == $done){ ?>disabled="disabled"<?php } ?> value="Activate this installation">			
		</div>
	</div>
</body>
			
			
			
			
			<?php
			
			exit();
		}	

		/**
		 * Run the installation/activation.
		 **/
		function install_go($redirect_me = true){
			// manually load model (avoids issues when database disabled)
				$this->zajlib->load->model('MozajikVersion');
			// install
				MozajikVersion::install();
			// now redirect to check
				if($redirect_me) return $this->zajlib->redirect("update/install/");
				else return true;
		}


		/**
		 * Update in progress
		 **/
		function progress(){
			?>
<head>
	<meta charset="utf-8">
	<title>Site update in progress...</title>
	<meta name="description" content="">
	<meta name="author" content="">
	<!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/base.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/skeleton.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/layout.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/mozajik.css">


	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mootools/mootools-core-1.3.js" type="text/javascript"></script>
	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mootools/mootools-more-1.3.js" type="text/javascript"></script>	
	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mozajik-base-1.3.js" type="text/javascript"></script>

</head>
<body>
	<div class="container">
		<div class="sixteen columns">
			<br/><br/>
			<h1>Update in progress...</h1>
			<h3>This site is being updated. Please retry in a few minutes.</h3>
			<hr/>
			<p>If this message does not go away after a few minutes, please contact the site administrator.</p>
		</div>
		<div class="five columns left">
			<input type="button" onclick="zaj.reload();" value="Reload page now">
		</div>
		<div class="five columns center">

		</div>
		<div class="five columns center">

		</div>
	</div>
</body>
			
			
			
			
			<?php
			
			exit();
		}	

		/**
		 * Display error log
		 **/
		function errors(){
			// get my errors
				$this->zajlib->variable->errors = MozajikError::fetch()->paginate(50);
			// send to template
				return $this->zajlib->template->show('update/update-errors.html');
		}

		/**
		 * Display error details
		 * @param MozajikError $eobj An error object.
		 **/
		function error_details($eobj){
			$backtrace = unserialize($eobj->data->backtrace);
			if(is_array($backtrace)){
				foreach($backtrace as $key=>$data){
					$this->zajlib->variable->number = $key;
					$this->zajlib->variable->detail = (object) $data;
					$this->zajlib->template->show('update/update-error-detail.html');
				}
			}
			else{
				print "No backtrace available for this error.";
			}
		}
	
	}
	

?>