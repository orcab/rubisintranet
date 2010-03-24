<?php
class iCal { 

	function iCalFileDecoder($file) {
		$stream = file_get_contents($file);
		return iCalStreamDecoder($stream);
	}

	function iCalStreamDecoder($stream) {
		preg_match_all('/(BEGIN:VEVENT.*?END:VEVENT)/si', $stream, $result, PREG_PATTERN_ORDER);
		for ($i = 0; $i < count($result[0]); $i++) {
			$tmpbyline = explode("\r\n", $result[0][$i]);
			$last_element = '';
			foreach ($tmpbyline as $item) {
				if (preg_match('/^\s*(.+?):(.*)/',$item,$regs)) {
					//print_r($regs);
					$majorarray[$regs[1]] = $regs[2];
					$last_element = $regs[1];
				} else {
					//print "DEBUG '$item' element non reconnu\n";
					$majorarray[$last_element] .= "\n$item"; // on rajoute cette ligne au precedent element
				}
			}
			/*
				lets just finish what we started..
			*/
			if (preg_match('/DESCRIPTION:(.*)END:VEVENT/si', $result[0][$i], $regs)) {
				$majorarray['DESCRIPTION'] = str_replace("  ", " ", str_replace("\r\n", "", $regs[1]));
			} 
			$icalarray[] = $majorarray;
			unset($majorarray);
		}
		return $icalarray;
	}
	
}

//$ical = new iCal();
//print_r( $ical->iCalReader() );
?>