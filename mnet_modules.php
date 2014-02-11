<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

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
	$str .= " <input type=\"hidden\" name=\"view\" value=\"modules\" />";
	$str .= " <input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= $OUTPUT->heading(get_string('modules', 'report_vmoodle'), 2);
	
	if(is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_modules', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
	}
	
	$str .= "<table width=\"100%\"><tr>";

	$col = 0;
	$overall = 0 ;
	$totalstr = get_string('totalmodules', 'report_vmoodle');		
	$allnodesstr = get_string('allnodes', 'report_vmoodle');
	$networktotalstr = get_string('networktotal', 'report_vmoodle');
	
	$yearclause = '';
	if (!empty($year)){
		$yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) = $year ";
	}

	$stdresultarr = array();
		
	foreach($vhosts as $vhost){
		$totmodules = 0;
		$str .= "<td valign=\"top\">";
		$sql = "
			SELECT 
				m.name as modname,
				COUNT(*) as modcount
			FROM 
				`{$vhost->vdbname}`.{$vhost->vdbprefix}modules m,
				`{$vhost->vdbname}`.{$vhost->vdbprefix}course_modules cm
			WHERE 
				cm.module = m.id 
				$yearclause
			GROUP 
				BY modname
			ORDER BY
				modname
		";
	
		$str .= "<table width=\"100%\" class=\"generaltable\">";
		$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";
	
		$r = 0;
		if ($modules = $DB->get_records_sql($sql)){
			foreach($modules as $m){
				$modname = get_string('modulenameplural', $m->modname);
				$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$modname</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$m->modcount}</td></tr>";
				$totmodules = 0 + $m->modcount + @$totmodules;
				$allnodes[$m->modname] = 0 + $m->modcount + @$allnodes[$m->modname];
				$r = ($r + 1) % 2;
				$stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $modname, $m->modcount);
			}
		}
		$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totmodules}</td></tr>";
		$str .= "</table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

	$str .= $OUTPUT->heading(get_string('totalmodulesuses', 'report_vmoodle'), 2);

	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

	$r = 0;
	$nettotal = 0;
	foreach($allnodes as $modname => $modcount){
		$modname = get_string('modulenameplural', $modname);
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$modname</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$modcount}</td></tr>";
		$nettotal = 0 + $modcount + @$nettotal;
		$r = ($r + 1) % 2;
	}
	$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
	$str .= "</table></td>";
	
