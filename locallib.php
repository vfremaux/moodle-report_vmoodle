<?php

function vmoodle_report_write_results_xls(&$worksheet, &$stdresultarr, $startrow = 0, $latinexport = false){
	global $CFG;
	
	$rownum = $startrow;
	
	if (empty($stdresultarr)) return $rownum;
	
	foreach($stdresultarr as $row){
		
		$cellnum = 0;
		foreach($row as $cell){
			
			if (is_numeric($cell)){
    			$worksheet->write_number($rownum, $cellnum, $cell);
			} else {
				if (empty($latinexport)){
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

function vmoodle_report_write_init_xls(&$workbook, $view, $latinexport = false){
	global $CFG;
	
	$sheettitle = get_string($view, 'report_vmoodle');

	if (!empty($latinexport)){
    	$sheettitle = mb_convert_encoding($sheettitle, 'ISO-8859-1', 'UTF-8');		
    }

    $worksheet = & $workbook->add_worksheet($sheettitle);
	$worksheet->set_column(0,0,30);
    $worksheet->set_column(1,1,30);
	$worksheet->set_column(2,2,30);
	$worksheet->set_column(3,3,15);

	return $worksheet;
}
