<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

	// preloads all QTYPES
	require_once $CFG->dirroot.'/lib/questionlib.php';

	$year = optional_param('year', 0, PARAM_INT); 
	
	$str = '';
	$str .= "<form name=\"chooseyearform\">";
	$years[] = get_string('whenever', 'report_vmoodle');
	for ($i = 0 ; $i < 15 ; $i++){
		$years[2009 + $i] = 2009 + $i;
	}

	$str .= get_string('addeddate', 'report_vmoodle');		
	$str .= html_writer::select($years, 'year', $year, array());
	$gostr = get_string('apply', 'report_vmoodle');
	$str .= " <input type=\"hidden\" name=\"view\" value=\"questiontypes\" />";
	$str .= " <input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= $OUTPUT->heading(get_string('questiontypes', 'report_vmoodle'), 2);

	if (is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_questiontypes', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
	}
	
	$str .= "<table width=\"100%\"><tr>";

	$col = 0;
	$overall = 0 ;
	$totalstr = get_string('totalquestiontypes', 'report_vmoodle');		
	$allnodesstr = get_string('allnodes', 'report_vmoodle');
	$networktotalstr = get_string('networktotal', 'report_vmoodle');
	
	$yearclause = '';
	if (!empty($year)){
		$yearclause = " AND YEAR( FROM_UNIXTIME(q.timecreated)) = $year ";
	}

	$allnodes = array();
	$stdresultarr = array();
	
	foreach($vhosts as $vhost){
		$totquestiontypes = 0;
		$str .= "<td valign=\"top\">";
		$sql = "
			SELECT 
				q.qtype as typename,
				COUNT(*) as qtcount
			FROM 
				`{$vhost->vdbname}`.{$vhost->vdbprefix}question q
			WHERE 
				1 = 1
				$yearclause
			GROUP 
				BY typename
			ORDER BY
				typename
		";
	
		$str .= "<table width=\"100%\" class=\"generaltable\">";
		$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";

		$r = 0;
		if ($questiontypes = $DB->get_records_sql($sql)){
			foreach($questiontypes as $qt){
				$typename = get_string('pluginname', 'qtype_'.$qt->typename);
				$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$qt->qtcount}</td></tr>";
				$totquestiontypes = 0 + $qt->qtcount + @$totquestiontypes;
				$allnodes[$qt->typename] = 0 + $qt->qtcount + @$allnodes[$qt->typename];
				$r = ($r + 1) % 2;
				$stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $typename, $qt->typename);
			}
		}
		$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totquestiontypes}</td></tr>";
		$str .= "</table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

	$str .= $OUTPUT->heading(get_string('totalquestiontypesuses', 'report_vmoodle'), 2);

	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

	$r = 0;
	$nettotal = 0;
	foreach($allnodes as $typename => $qtcount){
		$typename = get_string('pluginname', 'qtype_'.$typename);
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$qtcount}</td></tr>";
		$nettotal = 0 + $qtcount + @$nettotal;
		$r = ($r + 1) % 2;
	}
	$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
	$str .= "</table></td>";
	
