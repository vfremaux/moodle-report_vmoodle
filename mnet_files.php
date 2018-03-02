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

defined('MOODLE_INTERNAL') || die();

$year = optional_param('year', 0, PARAM_INT);
$context = optional_param('context', CONTEXT_COURSE, PARAM_INT); 

$str = '';

$str .= $OUTPUT->heading(get_string('files', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $return = new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'files'));
    $str .= local_print_static_text('static_vmoodle_report_files', $return, '', true);
}

$table = new html_table();

$strhostname = get_string('hostname', 'report_vmoodle');
$totalstr = get_string('totalfiles', 'report_vmoodle');
$draftstr = get_string('fixvsdraftfiles', 'report_vmoodle');
$videostr = get_string('videofiles', 'report_vmoodle');
$imagetr = get_string('imagefiles', 'report_vmoodle');
$appstr = get_string('appfiles', 'report_vmoodle');
$bigstr = get_string('bigfiles', 'report_vmoodle');
$bigstoragestr = get_string('bigfilessize', 'report_vmoodle');
$table->head = array($strhostname, $totalstr, $draftstr, $videostr, $imagetr, $appstr, $bigstr, $bigstoragestr);
$table->size = array('20%', '5%', '5%', '5%', '5%', '5%', '5%', '5%');
$table->align = array('left', 'center', 'center', 'center', 'center', 'center', 'center', 'center');

$col = 0;
foreach ($vhosts as $vhost) {

    $vdbprefix = $vhost->vdbprefix;
    $vdbname = $vhost->vdbname;

    $sql = "
        SELECT
            SUM( CASE WHEN f.filearea != 'draft' THEN 1 ELSE 0 END) as storagecount,
            SUM( CASE WHEN f.filearea != 'draft' THEN f.filesize ELSE 0 END) as storage,
            SUM( CASE WHEN f.filearea = 'draft' THEN f.filesize ELSE 0 END) as draftstorage,
            SUM( CASE WHEN f.mimetype LIKE 'video%' THEN f.filesize ELSE 0 END) as videostorage,
            SUM( CASE WHEN f.mimetype LIKE 'image%' THEN f.filesize ELSE 0 END) as imagestorage,
            SUM( CASE WHEN f.mimetype LIKE 'x-application%' THEN f.filesize ELSE 0 END) as appstorage,
            SUM( CASE WHEN f.filesize > 1000000 THEN 1 ELSE 0 END) as bigfiles,
            SUM( CASE WHEN f.filesize > 1000000 THEN f.filesize ELSE 0 END) as bigfilesstorage
        FROM
            `{$vdbname}`.{$vdbprefix}files as f
    ";

    if ($filestats = $DB->get_records_sql($sql)) {
        foreach ($filestats as $hoststat) {

            $totalstorage = $hoststat->storage + $hoststat->draftstorage;

            $row = array();
            $row[] = $renderer->host_full_name($vhost);
            $row[] = $renderer->format_size($totalstorage).' '.$renderer->size_bar($totalstorage);
            $row[] = $renderer->format_size($hoststat->storage).' / '.$renderer->format_size($hoststat->draftstorage);
            $row[] = $renderer->format_size($hoststat->videostorage).' '.$renderer->size_bar($hoststat->videostorage);
            $row[] = $renderer->format_size($hoststat->imagestorage).' '.$renderer->size_bar($hoststat->imagestorage);
            $row[] = $renderer->format_size($hoststat->appstorage).' '.$renderer->size_bar($hoststat->appstorage);

            if ($hoststat->storagecount > 0) {
                $bigfilesratio = sprintf("%.1f", $hoststat->bigfiles / $hoststat->storagecount * 100);
            } else {
                $bigfilesratio = 0;
            }
            $row[] = $hoststat->bigfiles.' ('.$bigfilesratio.'%)';
            if ($totalstorage > 0) {
                $bigfilesstorageratio = sprintf("%.1f", $hoststat->bigfilesstorage / $totalstorage * 100);
            } else {
                $bigfilesstorageratio = 0;
            }
            $row[] = $renderer->format_size($hoststat->bigfilesstorage).' '.$renderer->size_bar($bigfilesstorage).' ('.$bigfilesstorageratio.'%)';

            $table->data[] = $row;
        }
    }

}

if (!empty($table->data)) {
    $str = html_writer::table($table);
} else {
    $str = $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
}



