<?php
class iCal { 

//	var $folders;
//	
//	function iCal() {
//		$this->folders = $_SERVER['DOCUMENT_ROOT'].'/cals';
//	}
//	
//	function iCalList() {
//		if ( $handle = opendir( $this->folders ) ) {
//			while ( false !== ( $file = readdir( $handle ) ) ) {
//				$files[] = $file;
//			}
//			return array_filter($files, array($this,"iCalClean"));
//		}
//	}
//	
//	function iCalClean($file) {
//			return strpos($file, '.ics');
//	}
//	
//	function iCalReader() {
//		$array = $this->iCalList();
//		foreach ($array as $icalfile) {
//			$iCaltoArray[$icalfile] = $this->iCalDecoder($icalfile);
//		}
//		return $iCaltoArray;
//	}
	
	function iCalDecoder($file) {
		$ical = file_get_contents($file);
		preg_match_all('/(BEGIN:VEVENT.*?END:VEVENT)/si', $ical, $result, PREG_PATTERN_ORDER);
		for ($i = 0; $i < count($result[0]); $i++) {
			$tmpbyline = explode("\r\n", $result[0][$i]);
			
			foreach ($tmpbyline as $item) {
				$tmpholderarray = explode(":",$item);
				if (count($tmpholderarray) >1) { 
					$majorarray[$tmpholderarray[0]] = $tmpholderarray[1];
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