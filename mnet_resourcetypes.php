<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

	require_once $CFG->dirroot.'/mod/resource/lib.php';

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
	$str .= " <input type=\"hidden\" name=\"view\" value=\"resourcetypes\" />";
	$str .= " <input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= $OUTPUT->heading(get_string('resourcetypes', 'report_vmoodle'), 2);

	if(is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_resourcetypes', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
	}
	
	$str .= "<table width=\"100%\"><tr>";

	$col = 0;
	$overall = 0 ;
	$totalstr = get_string('totalresourcetypes', 'report_vmoodle');		
	$allnodesstr = get_string('allnodes', 'report_vmoodle');
	$networktotalstr = get_string('networktotal', 'report_vmoodle');
	
	$yearclause = '';
	if (!empty($year)){
		$yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) = $year ";
	}

	$allnodes = array();
	$stdresultarr = array();
	
	foreach($vhosts as $vhost){
		$totresourcetypes = 0;
		$str .= "<td valign=\"top\">";
		$sql = "
			SELECT 
				m.name as typename,				
				COUNT(*) as rtcount
			FROM 
				`{$vhost->vdbname}`.{$vhost->vdbprefix}course_modules cm,
				`{$vhost->vdbname}`.{$vhost->vdbprefix}modules m
			WHERE 
				cm.module = m.id AND
				m.name IN ('resource', 'url', 'foler', 'sharedresource')
				$yearclause
			GROUP 
				BY typename
			ORDER BY
				typename
		";
	
		$str .= "<table width=\"100%\" class=\"generaltable\">";
		$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";
		
		$r = 0;
		if ($resourcetypes = $DB->get_records_sql($sql)){
			foreach($resourcetypes as $rt){
				$typename = get_string('pluginname', $rt->typename);
				$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$rt->rtcount}</td></tr>";
				$totresourcetypes = 0 + $rt->rtcount + @$totresourcetypes;
				$allnodes[$rt->typename] = 0 + $rt->rtcount + @$allnodes[$rt->typename];
				$r = ($r + 1) % 2;
				$stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $typename, $rt->rtcount);
			}
		}
		$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totresourcetypes}</td></tr>";
		$str .= "</table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

	$str .= $OUTPUT->heading(get_string('totalresourcetypesuses', 'report_vmoodle'), 2);

	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

	$r = 0;
	$nettotal = 0;
	foreach($allnodes as $typename => $rtcount){
		$typename = get_string('pluginname', $rt->typename);
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$rtcount}</td></tr>";
		$nettotal = 0 + $rtcount + @$nettotal;
		$r = ($r + 1) % 2;
	}
	$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
	$str .= "</table></td>";
	