<?php

	include_once '../../config.php';
	ob_start();
	require_once $CFG->dirroot.'/report/vmoodle/locallib.php';

	$systemcontext = context_system::instance();
	require_capability('moodle/site:config', $systemcontext);

	$view = optional_param('view', 'cnxs', PARAM_TEXT);
	$output = optional_param('output', 'html', PARAM_TEXT);
	
	$url = $CFG->wwwroot.'/report/vmoodle/view.php';
	$PAGE->set_url($url, array('view' => $view));
	$PAGE->set_context($systemcontext);
	$PAGE->set_pagelayout('admin');
	$PAGE->set_title(get_string('vmoodlereport', 'report_vmoodle'));
	$PAGE->set_heading(get_string('vmoodlereport', 'report_vmoodle'));

	$thishost = new StdClass;
	$thishost->name = $SITE->fullname;
	$thishost->vdbtype = $CFG->dbtype;
	$thishost->vdbname = $CFG->dbname;
	$thishost->vhostname = $CFG->wwwroot;
	$thishost->vdblogin = $CFG->dbuser;
	$thishost->vdbpass = $CFG->dbpass;
	$thishost->vdbprefix = $CFG->prefix;
	
	$vhosts[] = $thishost;
	$vhosts = $vhosts + $DB->get_records('block_vmoodle', array('enabled' => '1'));
	
	/// Print tabs with options for user
	
	if (!preg_match('/cnxs|roles|users|modules|blocks|courses|formats|assignmenttypes|questiontypes|resourcetypes|sharedresources|forumtypes|userclasses/', $view)) $view = 'cnxs';
	$rows[0][] = new tabobject('cnxs', "view.php?view=cnxs", get_string('cnxs', 'report_vmoodle'));
	$rows[0][] = new tabobject('users', "view.php?view=users", get_string('users','report_vmoodle'));
	$rows[0][] = new tabobject('roles', "view.php?view=roles", get_string('roles','report_vmoodle'));
	$rows[0][] = new tabobject('userclasses', "view.php?view=userclasses", get_string('userclasses','report_vmoodle'));
	$rows[0][] = new tabobject('courses', "view.php?view=courses", get_string('courses','report_vmoodle'));
	$rows[0][] = new tabobject('modules', "view.php?view=modules", get_string('modules','report_vmoodle'));
	$rows[0][] = new tabobject('blocks', "view.php?view=blocks", get_string('blocks','report_vmoodle'));
	$rows[0][] = new tabobject('formats', "view.php?view=formats", get_string('formats','report_vmoodle'));
	$rows[0][] = new tabobject('assignmenttypes', "view.php?view=assignmenttypes", get_string('assignmenttypes','report_vmoodle'));
	$rows[0][] = new tabobject('questiontypes', "view.php?view=questiontypes", get_string('questiontypes','report_vmoodle'));
	$rows[0][] = new tabobject('resourcetypes', "view.php?view=resourcetypes", get_string('resourcetypes','report_vmoodle'));

	if ($sharedinstalled = $DB->get_record('modules', array('name' => 'sharedresource'))){
		$rows[0][] = new tabobject('sharedresources', "view.php?view=sharedresources", get_string('sharedresources','report_vmoodle'));
	}
	$rows[0][] = new tabobject('forumtypes', "view.php?view=forumtypes", get_string('forumtypes','report_vmoodle'));

	$tabs = print_tabs($rows, $view, NULL, NULL, true);

	if ($view == 'cnxs'){
		include 'mnet_general.php';
	}

	if ($view == 'users'){
		include 'mnet_users.php';
	}

	if ($view == 'roles'){
		include 'mnet_roles.php';
	}

	if ($view == 'userclasses'){
		include 'mnet_userclasses.php';
	}

	if ($view == 'modules'){
		include 'mnet_modules.php';
	}

	if ($view == 'blocks'){
		include 'mnet_blocks.php';
	}

	if ($view == 'formats'){
		include 'mnet_formats.php';
	}

	if ($view == 'assignmenttypes'){
		include 'mnet_assignmenttypes.php';
	}

	if ($view == 'questiontypes'){
		include 'mnet_questiontypes.php';
	}

	if ($view == 'resourcetypes'){
		include 'mnet_resourcetypes.php';
	}

	if ($sharedinstalled){
		if ($view == 'sharedresources'){
			include 'mnet_sharedresources.php';
		}
	}

	if ($view == 'forumtypes'){
		include 'mnet_forumtypes.php';
	}

	if ($view == 'courses'){
		include 'mnet_courses.php';
	}

	if ($output == 'html'){		
		ob_end_clean();
		echo $OUTPUT->header();
		echo $tabs;
		echo $str;

		if (isset($stdresultarr)){
			echo '<form name="asxlsform" target="_blank" action="'.$CFG->wwwroot.'/report/vmoodle/view.php">';
			echo '<input type="hidden" name="view" value="'.$view.'">';
			echo '<input type="hidden" name="latin" value="">';
			echo '<input type="hidden" name="year" value="'.$year.'">';
			echo '<input type="hidden" name="output" value="asxls">';
			echo '<center><p><input type="button" name="asxls" value="'.get_string('asxls', 'report_vmoodle').'" onclick="document.forms[\'asxlsform\'].latin.value = 0; document.forms[\'asxlsform\'].submit()" /> <input type="button" name="asxls" value="'.get_string('asxlslatin', 'report_vmoodle').'" onclick="document.forms[\'asxlsform\'].latin.value = 1; document.forms[\'asxlsform\'].submit()" /></p></center>';
			echo '</form>';
		}

		echo $OUTPUT->footer();
	} else {
		// print as xls the stdresultarr array
    	require_once($CFG->libdir.'/excellib.class.php');
    	
    	$latin = optional_param('latin', false, PARAM_BOOL);
    	        
        $filename = 'vmoodle_report_'.$view.'_'.$year.'_'.date('d-M-Y', time()).'.xls';
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers
        ob_end_clean();
        
        $workbook->send($filename);
        $worksheet = vmoodle_report_write_init_xls($workbook, $view, $latin);
        
        $headerarr[] = array(get_string('hostname', 'report_vmoodle'), get_string('year', 'report_vmoodle'), get_string('objecttype', 'report_vmoodle'), get_string('objectcount', 'report_vmoodle'));
        
        vmoodle_report_write_results_xls($worksheet, $headerarr, 0, $latin);
        vmoodle_report_write_results_xls($worksheet, $stdresultarr, 1, $latin);

       	$workbook->close();
	}
