<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

	$year = optional_param('year', 0, PARAM_INT); 
	$context = optional_param('context', CONTEXT_COURSE, PARAM_INT); 

	$str = '';
		
	$str .= $OUTPUT->heading(get_string('userclasses', 'report_vmoodle'), 2);

	if (is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_userstatus', $CFG->wwwroot.'/dmin/report/vmoodle/view.php?view=userstatus', '', true);
	}
	
	$str .= "<form name=\"chooseyearform\">";
	$str .= "<input type=\"hidden\" name=\"view\" value=\"$view\" />";

	$years[0] = 'Sans filtrage';
	for ($i = 0 ; $i < 15 ; $i++){
		$years[2009 + $i] = 2009 + $i;
	}
	$str .= html_writer::select($years, 'year', $year, array());

	$gostr = get_string('apply', 'report_vmoodle');
	$str .= "<input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= "<table width=\"100%\" cellspacing=\"10\"><tr>";
	
	$timeassignclause = '';
	if ($year){
		$timeassignclause = " AND YEAR(FROM_UNIXTIME(u.firstaccess)) <= $year ";
	}
		
	$userclasscount = array();

	$col = 0;
	foreach($vhosts as $vhost){

		$vdbprefix = $vhost->vdbprefix;
		$vdbname = $vhost->vdbname;
		
		$str .= "<td valign=\"top\" align=\"center\">";
		
		$localmnethostidsql = "
			SELECT
				value
			FROM 
				`{$vdbname}`.{$vdbprefix}config
			WHERE
				name = 'mnet_localhost_id'
		";
		$remote_local_mnethostid = $DB->get_field_sql($localmnethostidsql);

		// limit count to real local users	
		$sql = "
			SELECT
				uif.name as userclass,
				COUNT(DISTINCT(u.id)) as users
			FROM 
				`{$vdbname}`.{$vdbprefix}user as u,
				`{$vdbname}`.{$vdbprefix}user_info_data as uid,
				`{$vdbname}`.{$vdbprefix}user_info_field as uif
			WHERE 
				u.id = uid.userid AND
				uid.fieldid = uif.id AND
				u.mnethostid = {$remote_local_mnethostid} AND
				uid.data = 1 AND
				u.deleted = 0 AND
				uif.shortname IN ('parent', 'enseignant', 'eleve', 'administration', 'cdt')
				$timeassignclause
			GROUP BY
				uif.shortname
			ORDER BY
				uif.sortorder
		";
	
		$str .= "<table width=\"100%\" class=\"generaltable\"><tr>";
		$str .= "<th colspan=\"2\" class=\"header c0\"  style=\"line-height:20px;\" ><b>$vhost->name</b></th></tr>";
	
		if ($users = $DB->get_records_sql($sql)){
			$r = 0;
			foreach($users as $user){
				$usercount = 0 + $user->users;
				$userclasscount[$user->userclass] = @$userclasscount[$user->userclass] + $user->users; 
				$str .= "<tr class=\"row r$r\"><td width=\"80%\"  class=\"cell c0\" style=\"border:1px solid #808080\">$user->userclass</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$usercount}</td></tr>";
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

	if (!empty($userclasscount)){
		$str .= '<br/><center><table width="50%" class=\"generaltable\">';
		foreach($userclasscount as $userclass => $usercount){
			$str .= '<tr class="row"><td class="cell">'.$userclass.'</td><td class="cell">'.$usercount.'</td></tr>';
		}
		$str .= '</table></center>';
	}
