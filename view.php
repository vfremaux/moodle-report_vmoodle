<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version info
 *
 * @package    report
 * @subpackage vmoodle
 * @copyright  2012 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

ob_start();
require_once($CFG->dirroot.'/report/vmoodle/locallib.php');
require_once($CFG->libdir.'/filelib.php');

// Security.

$systemcontext = context_system::instance();
require_login();
require_capability('report/vmoodle:view', $systemcontext);

$view = optional_param('view', 'cnxs', PARAM_TEXT);
$output = optional_param('output', 'html', PARAM_TEXT);

if ($output == 'html') {
    \core_php_time_limit::raise();
}

$url = new moodle_url('/report/vmoodle/view.php');
$PAGE->set_url($url, array('view' => $view));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('vmoodlereport', 'report_vmoodle'));
$PAGE->set_heading(get_string('vmoodlereport', 'report_vmoodle'));
$PAGE->requires->js_call_amd('report_vmoodle/graphcontrol', 'init');
$PAGE->requires->js_call_amd('report_vmoodle/fragments', 'init');

$renderer = $PAGE->get_renderer('report_vmoodle');

$thishost = new StdClass;
$thishost->id = 0;
$thishost->name = $SITE->fullname;
$thishost->vdbtype = $CFG->dbtype;
$thishost->vdbname = $CFG->dbname;
$thishost->vhostname = $CFG->wwwroot;
$thishost->vdblogin = $CFG->dbuser;
$thishost->vdbpass = $CFG->dbpass;
$thishost->vdbprefix = $CFG->prefix;

$vhosts[] = $thishost;

if (preg_match('#'.@$CFG->mainhostprefix.'#', $CFG->wwwroot)) {
    $vhosts = $vhosts + $DB->get_records('local_vmoodle', array('enabled' => '1'));
}

if ($view == 'online') {
    include($CFG->dirroot.'/report/vmoodle/mnet_online.php');
}

if ($view == 'cnxs') {
    include($CFG->dirroot.'/report/vmoodle/mnet_cnxs.php');
}

if ($view == 'users') {
    include($CFG->dirroot.'/report/vmoodle/mnet_users.php');
}

if (is_dir($CFG->dirroot.'/local/ent_installer')) {
    if ($view == 'usersync') {
        include($CFG->dirroot.'/report/vmoodle/mnet_usersync.php');
    }
}

if ($view == 'files') {
    include($CFG->dirroot.'/report/vmoodle/mnet_files.php');
}

if ($view == 'logs') {
    include($CFG->dirroot.'/report/vmoodle/mnet_logs.php');
}

if ($view == 'roles') {
    include($CFG->dirroot.'/report/vmoodle/mnet_roles.php');
}

if ($view == 'userclasses') {
    include($CFG->dirroot.'/report/vmoodle/mnet_userclasses.php');
}

if ($view == 'modules') {
    include($CFG->dirroot.'/report/vmoodle/mnet_modules.php');
}

if ($view == 'blocks') {
    include($CFG->dirroot.'/report/vmoodle/mnet_blocks.php');
}

if ($view == 'formats') {
    include($CFG->dirroot.'/report/vmoodle/mnet_formats.php');
}

if ($view == 'assignmenttypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_assignmenttypes.php');
}

if ($view == 'questiontypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_questiontypes.php');
}

if ($view == 'resourcetypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_resourcetypes.php');
}

if (is_dir($CFG->dirroot.'/local/sharedresources')) {
    if ($view == 'sharedresources') {
        include($CFG->dirroot.'/report/vmoodle/mnet_sharedresources.php');
    }
}

if ($view == 'forumtypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_forumtypes.php');
}

if (is_dir($CFG->dirroot.'/local/advancedperfs')) {
    if ($view == 'slowpages') {
        include($CFG->dirroot.'/report/vmoodle/mnet_slowpages.php');
    }
}

if ($view == 'courses') {
    include($CFG->dirroot.'/report/vmoodle/mnet_courses.php');
}

if ($output == 'html') {
    ob_end_clean();
    echo $OUTPUT->header();
    echo $renderer->tabs($view);
    echo $str;

    if (isset($stdresultarr)) {
        echo $renderer->xlsexport($view);
    }

    echo $OUTPUT->footer();
} else {
    // Print as xls the stdresultarr array.
    require_once($CFG->libdir.'/excellib.class.php');

    $latin = optional_param('latin', false, PARAM_BOOL);

    $filename = 'vmoodle_report_'.$view.'_'.$year.'_'.date('d-M-Y', time()).'.xls';
    $workbook = new MoodleExcelWorkbook("-");

    // Sending HTTP headers.
    ob_end_clean();

    $workbook->send($filename);
    $worksheet = vmoodle_report_write_init_xls($workbook, $view, $latin);

    if (!isset($headerarr)) {
        $headerarr[] = array(get_string('hostname', 'report_vmoodle'),
                             get_string('year', 'report_vmoodle'),
                             get_string('objecttype', 'report_vmoodle'),
                             get_string('objectcount', 'report_vmoodle'));
    }

    vmoodle_report_write_results_xls($worksheet, $headerarr, 0, $latin);
    vmoodle_report_write_results_xls($worksheet, $stdresultarr, 1, $latin);

    $workbook->close();
}
