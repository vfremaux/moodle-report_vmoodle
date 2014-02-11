<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

	$year = optional_param('year', date('Y'), PARAM_INT); 
	$mode = optional_param('mode', 0, PARAM_INT); 
	
	$str = '';
	$str .= "<form name=\"chooseyearform\">";
	$years[] = get_string('whenever', 'report_vmoodle');
	for ($i = 0 ; $i < 15 ; $i++){
		$years[2009 + $i] = 2009 + $i;
	}

	$str .= get_string('addeddate', 'report_vmoodle');		
	$str .= html_writer::select($years, 'year', $year, array());

	$modecreatedsel = ($mode == 0) ? 'checked="checked"' : '' ;
	$modecreatedbeforesel = ($mode == 1) ? 'checked="checked"' : '' ;
	
	$str .= " <input type=\"radio\" name=\"mode\" value=\"0\" $modecreatedsel />";
	$str .= get_string('created', 'report_vmoodle');

	$str .= " <input type=\"radio\" name=\"mode\" value=\"1\" $modecreatedbeforesel />";
	$str .= get_string('createdbefore', 'report_vmoodle');

	$gostr = get_string('apply', 'report_vmoodle');
	$str .= " <input type=\"hidden\" name=\"view\" value=\"courses\" />";
	$str .= " <input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= $OUTPUT->heading(get_string('courses'), 2);

	if (is_dir($CFG->dirroot.'/local/staticguitexts')){	
		$str .= local_print_static_text('static_vmoodle_report_modules', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
	}
	
	$str .= "<table width=\"100%\"><tr>";

	$col = 0;
	$overall = 0 ;
	$totalstr = get_string('totalcourses', 'report_vmoodle');		
	$allnodesstr = get_string('allnodes', 'report_vmoodle');
	$networktotalstr = get_string('networktotal', 'report_vmoodle');
	$yearlytotalstr = get_string('totalyearly', 'report_vmoodle');		
	$shortyearlytotalstr = get_string('totalyearlyshort', 'report_vmoodle');		
	
	$yearclause = '';
		
	$stdresultarr = array();
	
	foreach($vhosts as $vhost){
		$totcourses = 0;
		$str .= "<td valign=\"top\">";
		if ($mode == 0){
			if ($year) $yearclause = " AND YEAR(FROM_UNIXTIME(timecreated)) = $year ";
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
			if ($year) $yearclause = " AND YEAR(FROM_UNIXTIME(timecreated)) <= $year";
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
			foreach($courses as $c){
				for($m = 1 ; $m <= 12 ; $m++){
					if ($c->year < $year || $c->month < $m){
						$courses[$m]->coursecount = 0 + @$courses[$m]->coursecount + 1;
					}
				}
			}
		}
	
		$str .= "<table width=\"100%\" class=\"generaltable\">";
		$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";
	
		$r = 0;
		$yearly = 0;
		if ($courses){
			for($m = 1 ; $m <= 12 ; $m++){
				$count = 0 + @$courses[$m]->coursecount;
				$yearly = $yearly + $count;
				$overalmonthly[$m] = @$overalmonthly[$m] + $count;
				$overall = $overall + $count;
				$totcourses += $count;
				if ($mode == 0){
					$coursesperhost[$vhost->name] = @$coursesperhost[$vhost->name] + $count;
				} else {
					$coursesperhost[$vhost->name] = $count;
				}
				$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$m</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$count}</td></tr>";
				$r = ($r + 1) % 2;
				$stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $m, $count);
			}
		}
		if ($mode == 0){
			$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totcourses}</td></tr>";
		}
		$str .= "</table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

	$str .= $OUTPUT->heading(get_string('coursetotals', 'report_vmoodle'), 2);

	$str .= '<table width="100%"><tr valign="top"><td>';
	$overalmonthlystr = get_string('totalmonthly', 'report_vmoodle');
	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$overalmonthlystr</th></tr>";

	$r = 0;
	for($m = 1 ; $m <= 12 ; $m++){
		$om = 0 + @$overalmonthly[$m];
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$m</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$om}</td></tr>";
		$r = ($r + 1) % 2;
	}
	if ($mode == 0){
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$yearlytotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$overall}</td></tr>";
	}
	$str .= "</table>";
	
	$str .= '</td><td>';

	$overalperhoststr = get_string('coursesperhost', 'report_vmoodle');
	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$overalperhoststr</th></tr>";
	foreach($vhosts as $vhost){
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$vhost->name</td><td class=\"cell c1\" style=\"border:1px solid #808080\">".@$coursesperhost[$vhost->name]."</td></tr>";
	}
	$str .= "</table>";

	$str .= '</td></tr></table>';
