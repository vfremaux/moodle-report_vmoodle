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

function vmoodle_report_write_results_xls(&$worksheet, &$stdresultarr, $startrow = 0, $latinexport = false) {
    global $CFG;

    $rownum = $startrow;

    if (empty($stdresultarr)) {
        return $rownum;
    }

    foreach ($stdresultarr as $index => $row) {

        $cellnum = 0;
        foreach ($row as $cell) {

            if (is_numeric($cell)) {
                $worksheet->write_number($rownum, $cellnum, $cell);
            } else {
                if (empty($latinexport)) {
                    $cell = mb_convert_encoding($cell, 'ISO-8859-1', 'UTF-8');
                }
                $worksheet->write_string($rownum, $cellnum, $cell);

            }
            $cellnum++;
        }
        $rownum++;
    }

    return $rownum;
}

function vmoodle_report_write_init_xls(&$workbook, $view, $latinexport = false) {
    global $CFG;

    $sheettitle = get_string($view, 'report_vmoodle');

    if (!empty($latinexport)) {
        $sheettitle = mb_convert_encoding($sheettitle, 'ISO-8859-1', 'UTF-8');
    }

    $worksheet = $workbook->add_worksheet($sheettitle);
    $worksheet->set_column(0,0,30);
    $worksheet->set_column(1,1,30);
    $worksheet->set_column(2,2,30);
    $worksheet->set_column(3,3,15);

    return $worksheet;
}

function mnet_report_assignment_get_types() {
    global $CFG;

    $standardassignments = array('upload','online','uploadsingle','offline');
    foreach ($standardassignments as $assignmenttype) {
        $type = new object();
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->type = "assignment&amp;type=$assignmenttype";
        $type->typestr = get_string("type$assignmenttype", 'assignment');
        $types[$assignmenttype] = $type;
    }

    // Drop-in extra assignment types.
    $assignmenttypes = get_list_of_plugins('mod/assignment/type');
    foreach ($assignmenttypes as $assignmenttype) {
        if (!empty($CFG->{'assignment_hide_'.$assignmenttype})) {
            // Not wanted.
            continue;
        }

        if (!in_array($assignmenttype, $standardassignments)) {
            $type = new object();
            $type->modclass = MOD_CLASS_ACTIVITY;
            $type->type = "assignment&amp;type=$assignmenttype";
            $type->typestr = get_string("type$assignmenttype", 'assignment_'.$assignmenttype);
            $types[$assignmenttype] = $type;
        }
    }

    return $types;
}

function mnet_reports_get_assignmenttypes($vhost, $year) {
    global $DB;

    $yearclause = '';
    if (!empty($year)) {
        $yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) = $year ";
    }

    $sql = "
        SELECT
            a.assignmenttype as assignmenttype,
            COUNT(*) as atcount
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}assignment a,
            `{$vhost->vdbname}`.{$vhost->vdbprefix}course_modules cm,
            `{$vhost->vdbname}`.{$vhost->vdbprefix}modules m
        WHERE
            a.id = cm.instance AND
            cm.module = m.id AND
            m.name = 'assignment'
            $yearclause
        GROUP
            BY assignmenttype
        ORDER BY
            assignmenttype
    ";
    return $DB->get_records_sql($sql);
}

function mnet_report_get_courses($vhost, $mode, $year) {
    global $DB;

    $yearclause = '';

    if ($mode == 0) {
        if ($year) {
            $yearclause = " AND YEAR(FROM_UNIXTIME(timecreated)) = $year ";
        }
        $sql = "
            SELECT 
                MONTH(FROM_UNIXTIME(timecreated)) as month,
                COUNT(*) as coursecount
            FROM 
                `{$vhost->vdbname}`.{$vhost->vdbprefix}course c
            WHERE 
                c.id != 1
                $yearclause
            GROUP 
                BY MONTH(FROM_UNIXTIME(timecreated))
            ORDER BY
                month
        ";
        $courses = $DB->get_records_sql($sql);
    } else {
        if ($year) {
            $yearclause = " AND YEAR(FROM_UNIXTIME(timecreated)) <= $year";
        }
        $sql = "
            SELECT
                c.id,
                CONCAT(YEAR(FROM_UNIXTIME(timecreated)), '-', MONTH(FROM_UNIXTIME(timecreated))) as calmonth,
                YEAR(FROM_UNIXTIME(timecreated)) as year,
                MONTH(FROM_UNIXTIME(timecreated)) as month
            FROM 
                `{$vhost->vdbname}`.{$vhost->vdbprefix}course c
            WHERE 
                c.id != 1
                $yearclause
            ORDER BY
                calmonth ASC
        ";
        $courses = $DB->get_records_sql($sql);
        foreach ($courses as $c) {
            for ($m = 1 ; $m <= 12 ; $m++) {
                if ($c->year < $year || $c->month < $m) {
                    $courses[$m]->coursecount = 0 + @$courses[$m]->coursecount + 1;
                }
            }
        }
    }
    return $courses;
}

function report_vmoodle_prepare_graph_structure($title) {
    return array(
        'title' => array(
            'text' => $title,
            'fontSize' => '1.3em',
            'color' => '#000080',
            ),
        'legend' => array(
            'show' => false, 
        ),
        'axesDefaults' => array('labelRenderer' => '$.jqplot.CanvasAxisLabelRenderer'),
        'axes' => array(
            'xaxis' => array(
                'label' => get_string('month'),
                'renderer' => '$.jqplot.DateAxisRenderer',
                'tickOptions' => array(
                    'formatString' => '%m/%Y',
                ),
            ),
            'yaxis' => array(
                'autoscale' => true,
                'tickOptions' => array('formatString' => '%.2f'),
                'label' => 'Q',
                'labelRenderer' => '$.jqplot.CanvasAxisLabelRenderer',
                'labelOptions' => array('angle' => 0)
                )
            ),
        'series' => array(
            array('color' => '#C00000'),
        ),
    );
}

