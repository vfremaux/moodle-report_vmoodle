<?php

defined('MOODLE_INTERNAL') || die;

if (empty($ADMIN->fulltree)){
	if (preg_match('#'.$CFG->mainhostprefix.'#', $CFG->wwwroot)){
		$ADMIN->add('reports', new admin_externalpage('reportvmoodleext', get_string('pluginname', 'report_vmoodle'), "$CFG->wwwroot/report/vmoodle/view.php", 'moodle/site:config'));
	}
}
