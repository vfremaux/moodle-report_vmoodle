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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Version info
 *
 * @package     report_vmoodle
 * @category    report
 * @copyright   2015 Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$renderer = $PAGE->get_renderer('report_vmoodle');

$str = '';

$str .= $OUTPUT->heading(get_string('slowpages', 'local_advancedperfs'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_usersync', new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'usersync')), '', true);
}

foreach($vhosts as $vhost) {

    $vhost->id = 0 + @$vhost->id;

    $sql = "
        SELECT
            COUNT(*) as slowps
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}local_advancedperfs_slowp
    ";
    $slowps[$vhost->id] = $DB->get_field_sql($sql, array());

    if ($slowps[$vhost->id]) {
        $sql = "
            SELECT
                MIN(timecreated) as rangestart
            FROM
                `{$vhost->vdbname}`.{$vhost->vdbprefix}local_advancedperfs_slowp
        ";
        $rangemin[$vhost->id] = $DB->get_field_sql($sql, array());

        $sql = "
            SELECT
                MAX(timecreated) as rangestart
            FROM
                `{$vhost->vdbname}`.{$vhost->vdbprefix}local_advancedperfs_slowp
        ";
        $rangemax[$vhost->id] = $DB->get_field_sql($sql, array());
    } else {
        $slowps[$vhost->id] = 0;
        $rangemin[$vhost->id] = 0;
        $rangemax[$vhost->id] = 0;
    }

}

$table = new html_table();
$table->head = array(get_string('hostname', 'report_vmoodle'),
                     get_string('slowpages', 'local_advancedperfs'),
                     get_string('mean', 'local_advancedperfs'),
                     get_string('range', 'local_advancedperfs'),
                     get_string('nothingsince', 'local_advancedperfs'),
                     );
$table->size = array('60%', '10%', '10%', '10%', '10%');
$table->width = '90%';

$now = time();

foreach ($vhosts as $vhost) {
    $vhost->id = 0 + $vhost->id;

    if ($rangemax[$vhost->id] == 0) {
        $slowprange = 0;
    } else {
        $slowprange = ceil(($rangemax[$vhost->id] - $rangemin[$vhost->id]) / DAYSECS);
    }

    $slowp = 0 + $slowps[$vhost->id];
    if (!empty($slowps[$vhost->id])) {
        $slowp = '<div class="vmoodle-report-notice">'.(0 + $slowps[$vhost->id]).'</div>';
    }

    if ($slowprange) {
        $mean = sprintf('%.3f', $slowps[$vhost->id] / $slowprange);
    } else {
        $mean = sprintf('%.3f', 0);
    }

    if ($rangemax[$vhost->id]) {
        $lastissue = date('d M Y H:i', $rangemax[$vhost->id]);
    } else {
        $lastissue = '--';
    }

    $range = '--';
    if ($slowprange) {
        $range = $slowprange.' j';
    }

    $table->data[] = array($vhosts[$vhost->id]->name, $slowp, $mean, $range, $lastissue);
}

$str .= html_writer::table($table);