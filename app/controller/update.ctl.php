<?php
/**
 * Update controller is a special system controller which handles installation, upgrade, database update, cache, and template cache related tasks.
 * @package Controller
 * @subpackage BuiltinControllers
 **/
 	define('ZAJ_INSTALL_DONTCHECK', 'dont_check_install');

	class zajapp_update extends zajController{

		var $update_user_query = "";

		/**
		 * Authenticate the request if not in debug mode
		 **/
		function __load($request, $optional_params = []){
			// is update disabled?
				if(!$this->ofw->zajconf['update_enabled']) return exit("Update disabled.");

			// check for recommended updates
				if(defined('MOZAJIK_RECOMMENDED_HTACCESS_VERSION') && MOZAJIK_RECOMMENDED_HTACCESS_VERSION > $this->ofw->htver) $this->ofw->variable->htver_upgrade = MOZAJIK_RECOMMENDED_HTACCESS_VERSION;
				if(defined('MOZAJIK_RECOMMENDED_CONFIG_VERSION') && MOZAJIK_RECOMMENDED_CONFIG_VERSION > $this->ofw->zajconf['config_file_version']) $this->ofw->variable->conf_upgrade = MOZAJIK_RECOMMENDED_CONFIG_VERSION;
			// check for other stuff
				$this->ofw->variable->mysql_setting_enabled = $this->ofw->zajconf['mysql_enabled'];

			// add update user query string
				if(!empty($_GET['update_user'])){
					if(!$this->ofw->security->has_xss($_GET['update_user']) && !$this->ofw->security->has_xss($_GET['update_password'])){
						$this->update_user_query = '?update_user='.$_GET['update_user'].'&update_password='.$_GET['update_password'];
					}
				}

			// am i not in debug mode?
				if(!$this->ofw->debug_mode){
					$denied_message = "ACCESS DENIED. If regular http pw auth is not working you can use the update_user/update_password query string as well.";
					// is my password defined?
						if(!$this->ofw->zajconf['update_user'] || !$this->ofw->zajconf['update_password']) return $this->install();
					// realm
						if(!empty($this->ofw->zajconf['update_realm'])) $realm = $this->ofw->zajconf['update_realm'];
						else $realm = "Outlast Framework Update";
					// all is good, so authenticate. you can authenticate with http pass or via get request
						if(!empty($_REQUEST['update_user'])){
							// Verify!
								if($_REQUEST['update_user'] != $this->ofw->zajconf['update_user'] || $_REQUEST['update_password'] != $this->ofw->zajconf['update_password']){
									header('HTTP/1.0 401 Unauthorized');
									return exit($denied_message);
								}
								else return true;
						}
						else return $this->ofw->security->protect($this->ofw->zajconf['update_user'], $this->ofw->zajconf['update_password'], $realm, $denied_message);
				}
			return true;
		}
		
		/**
		 * Display main menu for update
		 **/
		function main(){
			// load menu
				$this->ofw->template->show("update/base-update.html");
		}

		/**
		 * Run the deployment script
		 * @todo Change the order of the db check and test runs
		 **/
		function deploy(){
			// Run template update
				$this->ofw->variable->count = $this->template(false);
			// Run unit tests
				$this->test(false);
			// Get test results
				$this->ofw->variable->testresults = $this->ofw->template->block("update/update-test.html", "testresults", false, false, true);

				$this->ofw->variable->db_enabled = $this->ofw->zajconf['mysql_enabled'];

			// Run dry run and throw 500 error if changes needed
			if ($this->ofw->zajconf['mysql_enabled']) {
				$this->ofw->variable->dbresults = (object)$this->ofw->model->update(true);
				if ($this->ofw->variable->dbresults->num_of_changes > 0) header('HTTP/1.1 500 Internal Server Error');
			}

			// all is okay, continue with update
				$this->ofw->template->show("update/update-deploy.html");
		}



		/**
		 * Display the database update menu
		 **/
		function database(){
			// is this a dry run?
				if(!empty($_GET['liverun'])){
					$this->ofw->variable->updateframeurl = "database/run/?liverun=yes";
					$this->ofw->variable->updatename = "Database model update";
				}
				else{
					$this->ofw->variable->updateframeurl = "database/run/";
					$this->ofw->variable->updatename = "Database model check";
				}
			// all is okay, continue with update
				$this->ofw->variable->title = "app update | database";
			return $this->ofw->template->show("update/update-process.html");
		}
		function database_liverun(){
			$_GET['liverun'] = true;
			return $this->database();
		}

		/**
		 * Display the database update log
		 **/
		function database_run(){
			// first let's show the update log template
				$this->ofw->template->show("update/update-log.html");
			// is this a dry run?
				if(!empty($_GET['liverun'])) $dryrun = false;
				else $dryrun = true;
			// now let's start the db update
				$db_update_result = $this->ofw->model->update($dryrun);
			// start output
				print "<div class='updatelog-results'>";
			// now check if any errors
				if($db_update_result['num_of_changes'] >= 0) print $db_update_result['log'];
				else exit("<input class='mozajik-update' type='hidden' id='update_result' value='".$db_update_result['num_of_changes']."'><br>error: stopping update</div></body></html>");
			// now print the update_result
				print "<input class='mozajik-update' type='hidden' id='update_result' value='".$db_update_result['num_of_changes']."'><input class='mozajik-update' type='hidden' id='update_todo' value='".$db_update_result['num_of_todo']."'></div></body></html>";
			exit;
		}

		/**
		 * Get the number of required automatic updates.
		 */
		function database_get_updates(){
			// get the updates needed
				$db_update_needed = $this->ofw->model->update(true);
			$this->ofw->json($db_update_needed);
		}

		/**
		 * Run all the unit tests.
		 * @param boolean $show_result If set to true, it will display results.
		 * @return boolean
		 **/
		function test($show_result = true){

			// Prepare the tests
			$this->ofw->test->prepare_all();

			// Run all
			$result = $this->ofw->test->run();

			// Now return error if any error found
            if(count($this->ofw->variable->test->Errors) > 0){
                header('HTTP/1.1 500 Internal Server Error');
            }
            else header('HTTP/1.1 200 Ok');

			// Display!
            if($show_result) return $this->ofw->template->show("update/update-test.html");
            else return $result;

		}

		/**
		 * Display the object cache reset menu
		 **/
		function cache(){
			// count all the files
				if(empty($_GET['force'])){
					$this->ofw->variable->folder = $this->ofw->basepath."cache/object/";
					return $this->ofw->template->show("update/update-cache.html");
				}
			// load variables
				$this->ofw->variable->title = "object cache update | reset";
				$this->ofw->variable->folder = $this->ofw->cache->clear_objects();
				return $this->ofw->template->show("update/update-cache.html");
		}

		/**
		 * Display the template cache reset menu
		 * @param boolean $show_result If set to true (the default), a message will be displayed once done. Otherwise the count will be returned.
		 * @return integer The count is returned if $show_result is set to false.
		 **/
		function template($show_result = true){
			// enable update mode
				file_put_contents($this->ofw->basepath."cache/progress.dat", time());
			// get all the files in the template cache folder
				$this->ofw->load->library("file");
				$my_files = $this->ofw->file->get_files_in_dir($this->ofw->basepath."cache/view/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count = count($my_files);

			// get all the files in the conf folder
				$my_files = $this->ofw->file->get_files_in_dir($this->ofw->basepath."cache/conf/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// get all the files in the conf folder
				$my_files = $this->ofw->file->get_files_in_dir($this->ofw->basepath."cache/lang/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// get all the files in the temp folder
				$my_files = $this->ofw->file->get_files_in_dir($this->ofw->basepath."cache/temp/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// disable update mode
				unlink($this->ofw->basepath."cache/progress.dat");
			// print them
				if($show_result){
					$this->ofw->variable->title = "template cache update | reset";
					$this->ofw->variable->count = $total_count;
					return $this->ofw->template->show("update/update-template.html");
				}
				else return $total_count;
		}

		
		/**
		 * Install a new version of Mozajik
		 * @todo Add plugin install check for dynamically loaded plugins (so check the folder instead of plugin_apps)
		 **/
		function install(){
			// Load my version information
				$this->ofw->load->model('MozajikVersion');
			// Define my statuses
				$done = '<span class="label label-success">Done</span>';
				$todo = '<span class="label label-important">Not done</span>';
				$optional = '<span class="label label-info">Optional</span>';
				$na = '<span class="label label-inverse">Not enabled</span>';
				$ready_to_activate = true;
				$ready_to_dbupdate = true;
			// Check install status of plugins
				// 1. Calls __install() method on each plugin
				// 2. Checks return value: if it is ZAJ_INSTALL_DONTCHECK, then the installation check is not continued (USE ONLY WHEN OTHER INSTALL PROCEDURES NEEDED. Ex: Wordpress).
				// 3. Checks return value: if it is a string, then it is an error and it is displayed.
				foreach(array_reverse($this->ofw->zajconf['plugin_apps']) as $plugin){
					// first load up the plugin without __plugin execution
						$this->ofw->plugin->load($plugin, false);
					// only do this if either default controller exists in the plugin folder
						if(file_exists($this->ofw->basepath.'plugins/'.$plugin.'/controller/'.$plugin.'.ctl.php') || file_exists($this->ofw->basepath.'plugins/'.$plugin.'/controller/'.$plugin.'/default.ctl.php')){
							// reroute but if no __install method, just skip without an error message (TODO: maybe remove the false here?)!
								$result = $this->ofw->reroute($plugin.'/__install/', array(), false, false);
							// __install should return a string if it fails, otherwise it is considered to pass
								$plugin_text = "";
								if(is_string($result) && $result == ZAJ_INSTALL_DONTCHECK) return true;
								elseif(is_string($result)){ $plugin_text .= "<li>Checking plugin $plugin. <span class='label label-important'>Failed</span><pre class='well' style='font-family: monospace; padding: 10px; overflow: auto; background-color: #f5f5f5; margin-top: 10px;'>$result</pre></li>"; $ready_to_activate = false; }
								else $plugin_text .= "<li>Checking plugin $plugin... <span class='label label-success'>Done</span></li>";
						}
				}				
			// Check status for each step
				// 1. Check writable
					if(!is_writable($this->ofw->basepath."cache/") || !is_writable($this->ofw->basepath."data/")){ $status_write  = $todo; $ready_to_dbupdate = false; $ready_to_activate = false; }
					else $status_write  = $done;
				// 2. Check database permissions
					if(!$this->ofw->zajconf['mysql_enabled']){ $status_db  = $na; $ready_to_dbupdate = false; }
					else{
						if($this->ofw->db->connect($this->ofw->zajconf['mysql_server'], $this->ofw->zajconf['mysql_user'], $this->ofw->zajconf['mysql_password'], $this->ofw->zajconf['mysql_db'], false)) $status_db = $done;
						else{ $status_db  = $todo; $ready_to_dbupdate = false; $ready_to_activate = false; }
					}
				// 3. Check user/pass for update
					if(empty($this->ofw->zajconf['update_user']) || empty($this->ofw->zajconf['update_password'])){
						if($this->ofw->debug_mode) $status_updatepass = $optional;
						else{ $status_updatepass = $todo; $ready_to_activate = false; }
					}
					else $status_updatepass = $done;
				// 4. Check database update (photo table should always exist)
					if(!$this->ofw->zajconf['mysql_enabled']) $status_dbupdate  = $na;
					elseif($status_db == $todo){ $status_dbupdate  = $todo; $ready_to_activate = false; }
					else{				
						$result = $this->ofw->db->query("SELECT count(*) as c FROM information_schema.tables WHERE table_schema = '".addslashes($this->ofw->zajconf['mysql_db'])."' AND table_name = 'photo'")->next();
						if($result->c <= 0){ $status_dbupdate = $todo; $ready_to_activate = false; }
						else $status_dbupdate = $done;
					}
				// 5. Check activation
					if(!is_object($this->ofw->mozajik) || !MozajikVersion::check()) $status_activate = $todo;
					else $status_activate = $done;
			?>
<head>
	<meta charset="utf-8">
	<title>Outlast Framework Installation</title>
	<meta name="description" content="">
	<meta name="author" content="">
	<!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<link rel="stylesheet" href="<?php echo $this->ofw->baseurl; ?>system/css/bootstrap/css/bootstrap.min.css">

	<link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300" rel="stylesheet" type="text/css">
	<link rel="shortcut icon" type="image/png" href="//localhost/wlp/system/img/outlast-favicon.png">
	<link rel="stylesheet" type="text/css" href="//localhost/wlp/system/css/outlast-update.css?v3" media="all">

	<script language="JavaScript" src="<?php echo $this->ofw->baseurl; ?>system/js/jquery/jquery-1.8.0.min.js" type="text/javascript"></script>
	<script language="JavaScript" src="<?php echo $this->ofw->baseurl; ?>system/js/mozajik-base-jquery.js" type="text/javascript"></script>

</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span12">
			<br/><br/>
			<h1>Outlast Framework Installation</h1>
			<h3>Welcome to the Outlast Framework installation for version <?php echo MozajikVersion::$major; ?>.<?php echo MozajikVersion::$minor; ?> <?php if(MozajikVersion::$beta) echo "beta"; ?></h3>
			<?php if($this->ofw->debug_mode){ ?><h5><span style="color: red;">Attention!</span> This installation will be running in <strong>debug mode</strong>. This is not recommended for production sites!</h5><?php } ?>
			<hr/>
			<ul>
				<?php if(empty($plugin_text)) echo "<li>Checking plugins... No plugins activated.</li>"; else echo $plugin_text ?>
			</ul>
			<hr/>
			<ol>
				<li>Make /cache and /data folders writable by webserver. - <?php echo $status_write; ?></li>
				<li>Create read/write permissions for database (if mysql is enabled). - <?php echo $status_db; ?></li>
				<li>Create an update user and password in /site/index.php (required if in production mode). - <?php echo $status_updatepass; ?></li>
				<li>Update the database (if mysql is enabled). - <?php echo $status_dbupdate; ?></li>
				<li>Activate this installation. - <?php echo $status_activate; ?></li>
				<?php if($ready_to_activate && $status_activate == $done){ ?><br/><div class="alert alert-success center">Your installation is currently <span style="color:green;">activated</span>. Go to the <a href="<?php echo $this->ofw->baseurl; ?>">home page</a>.</div><?php } ?>
			</ol>
			<hr/>
			</div>
		</div>
		<div class="row">
			<div class="span4 center">
				<input class="btn" type="button" onclick="ofw.reload();" value="Recheck install status">
			</div>
			<div class="span4 center">
				<input class="btn btn-primary" type="button" onclick="ofw.open('<?php echo $this->ofw->baseurl; ?>update/database/<?php echo $this->update_user_query; ?>', 1000, 500);" <?php if(!$ready_to_dbupdate){ ?>disabled="disabled"<?php } ?> value="Update the database">
			</div>
			<div class="span4 center">
				<input class="btn btn-success" type="submit" onclick="window.location = '<?php echo $this->ofw->baseurl; ?>update/install/go/<?php echo $this->update_user_query; ?>';" <?php if(!$ready_to_activate || $status_activate == $done){ ?>disabled="disabled"<?php } ?> value="Activate this installation">
			</div>
		</div>
		<div class="row">
			<div class="span12 center">
				<br/>
				<a href="<?php echo $this->ofw->baseurl; ?>update/<?php echo $this->update_user_query; ?>">Back to the update page</a>
			</div>
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
				$this->ofw->load->model('MozajikVersion');
			// install
				MozajikVersion::install();
			// now redirect to check
				if($redirect_me) return $this->ofw->redirect("update/install/".$this->update_user_query);
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
	<link rel="stylesheet" href="<?php echo $this->ofw->baseurl; ?>system/css/skeleton/base.css">
	<link rel="stylesheet" href="<?php echo $this->ofw->baseurl; ?>system/css/skeleton/skeleton.css">
	<link rel="stylesheet" href="<?php echo $this->ofw->baseurl; ?>system/css/skeleton/layout.css">
	<link rel="stylesheet" href="<?php echo $this->ofw->baseurl; ?>system/css/mozajik.css">


	<script language="JavaScript" src="<?php echo $this->ofw->baseurl; ?>system/js/mootools/mootools-core-1.3.js" type="text/javascript"></script>
	<script language="JavaScript" src="<?php echo $this->ofw->baseurl; ?>system/js/mootools/mootools-more-1.3.js" type="text/javascript"></script>
	<script language="JavaScript" src="<?php echo $this->ofw->baseurl; ?>system/js/mozajik-base-1.3.js" type="text/javascript"></script>

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
			<input type="button" onclick="ofw.reload();" value="Reload page now">
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
	
	}