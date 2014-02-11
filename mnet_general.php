<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

	$year = optional_param('year', 2010, PARAM_INT); 
	$SESSION->vmoodle_stat_distinct_users = optional_param('distinctusers', @$SESSION->vmoodle_stat_distinct_users, PARAM_INT); 
	
	$str = '';
	$str .= "<form name=\"chooseyearform\">";
	for ($i = 0 ; $i < 15 ; $i++){
		$years[2009 + $i] = 2009 + $i;
	}
	
	$distinctchecked = ($SESSION->vmoodle_stat_distinct_users) ? 'checked="checked"' : '' ;
	$individualchecked = (!$SESSION->vmoodle_stat_distinct_users) ? 'checked="checked"' : '' ;
	
	$str.= html_writer::select($years, 'year', $year, array());
	$str .= " <input type=\"radio\" name=\"distinctusers\" value=\"1\" $distinctchecked />";
	$str .= get_string('distinctusers', 'report_vmoodle');
	$str .= " - <input type=\"radio\" name=\"distinctusers\" value=\"0\" $individualchecked />";
	$str .= get_string('individualconnections', 'report_vmoodle');
	$gostr = get_string('apply', 'report_vmoodle');
	$str .= " <input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= $OUTPUT->heading(get_string('cnxs', 'report_vmoodle'), 2);

	if (is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_general', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
	}
	
	$str .= "<table width=\"100%\"><tr>";

	$stdresultarr = array();
	$col = 0;
	$overall = 0 ;
	$yearlytotalstr = get_string('totalyearly', 'report_vmoodle');		
	$shortyearlytotalstr = get_string('totalyearlyshort', 'report_vmoodle');		
	foreach($vhosts as $vhost){
		$str .= "<td valign=\"top\">";
		if ($SESSION->vmoodle_stat_distinct_users){
			$sql = "
				SELECT 
					MONTH(FROM_UNIXTIME(time)) as month,
					COUNT(DISTINCT userid) as cnxs
				FROM 
					`{$vhost->vdbname}`.{$vhost->vdbprefix}log
				WHERE 
					ACTION = 'login' AND 
					YEAR( FROM_UNIXTIME(time)) = $year
				GROUP BY 
					MONTH( FROM_UNIXTIME(time))
				ORDER BY
					month
				";
		} else {
			$sql = "
				SELECT 
					MONTH(FROM_UNIXTIME(time)) as month,
					COUNT(*) as cnxs
				FROM 
					`{$vhost->vdbname}`.{$vhost->vdbprefix}log
				WHERE 
					ACTION = 'login' AND 
					YEAR( FROM_UNIXTIME(time)) = $year
				GROUP 
					BY MONTH( FROM_UNIXTIME(time))
				ORDER BY
					month
			";
		}
	
		$str .= "<table width=\"100%\" class=\"generaltable\">";
		$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";
	
		$yearly = 0;
		$r = 0;
		$overalhostname = array();
		if ($connections = $DB->get_records_sql($sql)){
			for($m = 1 ; $m <= 12 ; $m++){
				$cnxs = 0 + @$connections[$m]->cnxs;
				$yearly = $yearly + $cnxs;
				$overall += $cnxs;
				$overalmonthly[$m] = @$overalmonthly[$m] + $cnxs;
				$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$m</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$cnxs}</td></tr>";
				$r = ($r + 1) % 2;
				$stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $m, $cnxs);
			}
		}
		if ($SESSION->vmoodle_stat_distinct_users){
			$sql = "
				SELECT 
					COUNT(DISTINCT userid) as cnxs
				FROM 
					`{$vhost->vdbname}`.{$vhost->vdbprefix}log
				WHERE 
					ACTION = 'login' AND 
					YEAR( FROM_UNIXTIME(time)) = $year
				";
			$totaldistinct = $DB->count_records_sql($sql);
			$overalhostname[$vhost->name] = $totaldistinct;
			$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$shortyearlytotalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totaldistinct}</td></tr>";
		} else {
			$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$shortyearlytotalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$yearly}</td></tr>";
		}
		$str .= "</table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

	$str .= $OUTPUT->heading(get_string('totalcnxs', 'report_vmoodle'), 2);

	$overalmonthlystr = get_string('totalmonthly', 'report_vmoodle');
	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$overalmonthlystr</th></tr>";

	$r = 0;
	for($m = 1 ; $m <= 12 ; $m++){
		$om = 0 + @$overalmonthly[$m];
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$m</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$om}</td></tr>";
		$r = ($r + 1) % 2;
	}
	if ($SESSION->vmoodle_stat_distinct_users){
		$overall = array_sum(array_values($overalhostname));
	}
	$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$yearlytotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$overall}</td></tr>";
	$str .= "</table></td>";
	
