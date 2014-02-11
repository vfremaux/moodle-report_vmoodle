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
	$str .= " <input type=\"hidden\" name=\"view\" value=\"blocks\" />";
	$str .= " <input type=\"submit\" value=\"$gostr\" />";
	$str .= '</form>';

	$str .= $OUTPUT->heading(get_string('blocks', 'report_vmoodle'), 2);
	
	if (is_dir($CFG->dirroot.'/local/staticguitexts')){
		$str .= local_print_static_text('static_vmoodle_report_blocks', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
	}
	
	$str .= "<table width=\"100%\"><tr>";

	$col = 0;
	$overall = 0 ;
	$totalstr = get_string('totalblocks', 'report_vmoodle');		
	$allnodesstr = get_string('allnodes', 'report_vmoodle');
	$networktotalstr = get_string('networktotal', 'report_vmoodle');
	
	$yearclause = '';
	if (!empty($year)){
		$yearclause = " AND YEAR( FROM_UNIXTIME(c.timecreated)) = $year ";
	}
	
	
	foreach($vhosts as $vhost){
		$totblocks = 0;
		$str .= "<td valign=\"top\">";
		$sql = "
			SELECT 
				bi.blockname,
				COUNT(*) as blockcount
			FROM 
				`{$vhost->vdbname}`.{$vhost->vdbprefix}block_instances bi,
				`{$vhost->vdbname}`.{$vhost->vdbprefix}context co,
				`{$vhost->vdbname}`.{$vhost->vdbprefix}course c
			WHERE 
			    bi.parentcontextid = co.id AND
			    co.contextlevel = 50 AND
			    co.instanceid = c.id
				$yearclause
			GROUP 
				BY blockname
			ORDER BY
				blockname
		";
	
		$str .= "<table width=\"100%\" class=\"generaltable\">";
		$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";

		$stdresultarr = array();
	
		$r = 0;
		if ($blockss = $DB->get_records_sql($sql)){
			foreach($blockss as $b){
				$blockname = get_string('pluginname', 'block_'.$b->blockname);
				if(strpos($blockname, '[[') !== false){
					$blockname = get_string($b->blockname, 'block_'.$blockname);
				}
				if(strpos($blockname, '[[') !== false){
					$blockname = $b->blockname;
				}
				$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$blockname</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$b->blockcount}</td></tr>";
				$totblocks = 0 + $b->blockcount + @$totblocks;
				$allnodes[$b->blockname] = 0 + $b->blockcount + @$allnodes[$b->blockname];
				$r = ($r + 1) % 2;
				$stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $blockname, $b->blockcount);
			}
		}
		$str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totblocks}</td></tr>";
		$str .= "</table></td>";
		
		$col++;
		if ($col >= 4){
			$str .= '</tr><tr>';
			$col = 0;
		}
	}
	
	$str .= '</tr></table>';

	$str .= $OUTPUT->heading(get_string('totalblocksuses', 'report_vmoodle'), 2);

	$str .= "<table width=\"250\" class=\"generaltable\">";
	$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

	$r = 0;
	$nettotal = 0;
	foreach($allnodes as $bname => $blockcount){
		$blockname = get_string('pluginname', 'block_'.$bname);
		if(strpos($blockname, '[[') !== false){
			$blockname = get_string($bname, 'block_'.$bname);
		}
		if(strpos($blockname, '[[') !== false){
			$blockname = $bname;
		}
		$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$blockname</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$blockcount}</td></tr>";
		$nettotal = 0 + $blockcount + @$nettotal;
		$r = ($r + 1) % 2;
	}
	$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
	$str .= "</table></td>";
	
