<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

	$year = optional_param('year', 0, PARAM_INT); 
	$context = optional_param('context', CONTEXT_COURSE, PARAM_INT); 

	$str = '';
		
	$str .= $OUTPUT->heading(get_string('roles', 'report_vmoodle'), 2);

	if (is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_roles', $CFG->wwwroot.'/dmin/report/vmoodle/view.php?view=roles', '', true);
	}
	
	$str .= "<form name=\"chooseyearform\">";
	$str .= "<input type=\"hidden\" name=\"view\" value=\"$view\" />";

	$years[0] = 'Sans filtrage';
	for ($i = 0 ; $i < 15 ; $i++){
		$years[2009 + $i] = 2009 + $i;
	}
	$str .= html_writer::select($years, 'year', $year, array());

	$contexts = array(CONTEXT_COURSE => get_string('course'), CONTEXT_COURSECAT => get_string('category'), 100 => get_string('site'), CONTEXT_SYSTEM => get_string('system', 'report_vmoodle'));
	$str .= html_writer::select($contexts, 'context', $context, array());
	$gostr = get_string('apply', 'report_vmoodle');
	$str .= "<input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= "<table width=\"100%\" cellspacing=\"10\"><tr>";
	
	$timeassignclause = '';
	if ($year){
		$timeassignclause = " AND YEAR(FROM_UNIXTIME(ra.timestart)) <= $year ";
	}
	
	$contextclause = '';
	switch($context){
		case CONTEXT_COURSE :
			$contextclause = ' AND c.contextlevel = 50 ';
			break;
		case CONTEXT_COURSECAT :
			$contextclause = ' AND c.contextlevel = 30 ';
			break;
		case 100 :
			$contextclause = ' AND c.contextlevel = 50 AND c.id = 1 ';
			break;
		case CONTEXT_SYSTEM :
			$contextclause = ' AND c.contextlevel = 10 ';
			break;
	}
	
	$col = 0;
	foreach($vhosts as $vhost){

		$vdbprefix = $vhost->vdbprefix;
		$vdbname = $vhost->vdbname;
		
		$str .= "<td valign=\"top\" align=\"center\">";
	
		$sql = "
			SELECT 
				r.name,
				COUNT(DISTINCT u.id) as users
			FROM 
				`{$vdbname}`.{$vdbprefix}user as u,
				`{$vdbname}`.{$vdbprefix}role_assignments as ra,
				`{$vdbname}`.{$vdbprefix}context as c,
				`{$vdbname}`.{$vdbprefix}role as r
			WHERE 
				u.id = ra.userid AND
				u.deleted = 0 AND
				ra.contextid = c.id AND
				ra.roleid = r.id
				$timeassignclause
				$contextclause
			GROUP BY
				r.name
			ORDER BY
				r.sortorder
		";
	
		$str .= "<table width=\"100%\" class=\"generaltable\"><tr>";
		$str .= "<th colspan=\"2\" class=\"header c0\"  style=\"line-height:20px;\" ><b>$vhost->name</b></th></tr>";
	
		if ($users = $DB->get_records_sql($sql)){
			$r = 0;
			foreach($users as $user){
				$usercount = 0 + $user->users;
				$str .= "<tr class=\"row r$r\"><td width=\"80%\"  class=\"cell c0\" style=\"border:1px solid #808080\">$user->name</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$usercount}</td></tr>";
				$r = ($r + 1) % 2;
			}
		} else {
			$str .= '<tr><td>'.get_string('nodata', 'report_vmoodle').'</td></tr>';
		}
		
		$str .= "</td></tr></table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

?>