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

defined('MOODLE_INTERNAL') || die;

/**
 * Version info
 *
 * @package    report_vmoodle
 * @cateogry   report
 * @copyright  2012 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$hasconfig = false;
$hassiteconfig = false;
if (is_dir($CFG->dirroot.'/local/adminsettings')) {
    // Integration driven code 
    if (has_capability('local/adminsettings:nobody', context_system::instance())) {
        $hasconfig = true;
        $hassiteconfig = true;
    } elseif (has_capability('moodle/site:config', context_system::instance())) {
        $hasconfig = true;
        $hassiteconfig = false;
    }
} else {
    // Standard Moodle code
    $hassiteconfig = true;
    $hasconfig = true;
}

// Liberalize the access.
// Each local instance should see only his own data.
if ($hasconfig) {
    $ADMIN->add('reports', new admin_externalpage('reportvmoodleext', get_string('pluginname', 'report_vmoodle'), "$CFG->wwwroot/report/vmoodle/view.php", 'moodle/site:config'));
}

if ($hassiteconfig) {
    $yearopts = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);

    $key = 'report_vmoodle/backexploredepth';
    $label = get_string('configbackexploredepth', 'report_vmoodle');
    $desc = get_string('configbackexploredepth_desc', 'report_vmoodle');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yearopts));

    $key = 'report_vmoodle/profilefields';
    $label = get_string('configprofilefields', 'report_vmoodle');
    $desc = get_string('configprofilefields_desc', 'report_vmoodle');
    $settings->add(new admin_setting_configtext($key, $label, $desc, ''));
}
