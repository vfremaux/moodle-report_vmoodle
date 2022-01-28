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
 * @package    report_vmoodle
 * @category   report
 * @copyright  2012 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../../config.php');
require_once($CFG->dirroot.'/report/vmoodle/locallib.php');

$url = new moodle_url('/report/vmoodle/ajax/service.php', array('action' => 'noreplay'));

$systemcontext = context_system::instance();
require_login();
require_capability('report/vmoodle:view', $systemcontext);

$PAGE->set_url($url);
$PAGE->set_context($systemcontext);

$action = required_param('what', PARAM_TEXT);

switch ($action) {
    case 'getfragment': {
        include_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');
        $fragment = required_param('fragment', PARAM_TEXT);
        $hostroot = required_param('wwwroot', PARAM_TEXT);
        $fragmentloader = report_vmoodle_get_fragment($fragment, $hostroot);

        $response = new StdClass();
        $response->html = $fragmentloader->get_fragment($dataresults);
        $response->data = $dataresults;
        $response->source = urlencode($hostroot);
        $jsonresponse = json_encode($response);
        echo $jsonresponse;
        die();
    }

    case 'noreplay': {
        print_error('The service cannot be replayed');
    }
}