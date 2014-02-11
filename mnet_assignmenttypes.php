<?php

	if (!defined('MOODLE_INTERNAL')) die('You cannot access this script directly');

	require_once $CFG->dirroot.'/mod/assignment/lib.php';

	$year = optional_param('year', 0, PARAM_INT); 

	$str = '';	
	$str .= "<form name=\"chooseyearform\">";
	$years[] = get_string('whenever', 'report_vmoodle');
	for ($i = 0 ; $i < 15 ; $i++){
		$years[2009 + $i] = 2009 + $i;
	}

	$str .= get_string('addeddate', 'report_vmoodle');		
	$str .= choose_from_menu($years, 'year', $year, '', false, false, true);
	$gostr = get_string('apply', 'report_vmoodle');
	$str .= " <input type=\"hidden\" name=\"view\" value=\"assignmenttypes\" />";
	$str .= " <input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= $OUTPUT->heading(get_string('assignmenttypes', 'report_vmoodle'), 2);
	
	if (is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_assignmenttypes', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
	}
	
	$str .= "<table width=\"100%\"><tr>";

	$col = 0;
	$overall = 0 ;
	$totalstr = get_string('totalassignmenttypes', 'report_vmoodle');		
	$allnodesstr = get_string('allnodes', 'report_vmoodle');
	$networktotalstr = get_string('networktotal', 'report_vmoodle');
	
	$alltypes = mnet_report_assignment_get_types();
	
	$yearclause = '';
	if (!empty($year)){
		$yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) = $year ";
	}
	
	$stdresultarr = array();
	
	foreach($vhosts as $vhost){
		$totassignmenttypes = 0;
		$str .= "<td valign=\"top\">";
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
	
		$str .= "<table width=\"100%\" class=\"generaltable\">";
		$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";
			
		$r = 0;
		if ($assignmenttypes = $DB->get_records_sql($sql)){
			foreach($assignmenttypes as $at){
				$typename = $alltypes[$at->assignmenttype]->typestr;
				$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$at->atcount}</td></tr>";
				$totassignmenttypes = 0 + $at->atcount + @$totassignmenttypes;
				$allnodes[$at->assignmenttype] = 0 + $at->atcount + @$allnodes[$at->atcount];
				$r = ($r + 1) % 2;
				$stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $typename, $at->atcount);
			}
		}
		$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totassignmenttypes}</td></tr>";
		$str .= "</table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

	$str .= $OUTPUT->heading(get_string('totalassignmenttypesuses', 'report_vmoodle'), 2);

	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

	$r = 0;
	$nettotal = 0;
	foreach($allnodes as $typename => $assigntypecount){
		$typename = $alltypes[$at->assignmenttype]->typestr;
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$assigntypecount}</td></tr>";
		$nettotal = 0 + $assigntypecount + @$nettotal;
		$r = ($r + 1) % 2;
	}
	$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
	$str .= "</table></td>";

function mnet_report_assignment_get_types(){
    $standardassignments = array('upload','online','uploadsingle','offline');
    foreach ($standardassignments as $assignmenttype) {
        $type = new object();
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->type = "assignment&amp;type=$assignmenttype";
        $type->typestr = get_string("type$assignmenttype", 'assignment');
        $types[$assignmenttype] = $type;
    }

    /// Drop-in extra assignment types
    $assignmenttypes = get_list_of_plugins('mod/assignment/type');
    foreach ($assignmenttypes as $assignmenttype) {
        if (!empty($CFG->{'assignment_hide_'.$assignmenttype})) {  // Not wanted
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
	