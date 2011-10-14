<?php
define('IMAGE_PATH','C:/easyphp/www/intranet/tarif2/miniatures/');

$IMAGES = rscandir(IMAGE_PATH);

$f = fopen('images_data.php','w+');
fwrite($f,'<?php $IMAGES = ' . var_export($IMAGES,true) . '; ?>');
fclose($f);

// cherche les photos dans les répertoires
function rscandir($base='', &$data=array()) {
  $array = array_diff(scandir($base), array('.', '..')); # remove ' and .. from the array */
  foreach($array as $value) { /* loop through the array at the level of the supplied $base */
 
    if (is_dir($base.$value)) { /* if this is a directory */
		//$data[] = $base.$value.'/'; /* add it to the $data array */
		$data = rscandir($base.$value.'/', $data); /* then make a recursive call with the current $value as the $base supplying the $data array to carry into the recursion */
    }  elseif (is_file($base.$value) && (preg_match("/(.+?)\.(?:jpe?g|png)$/i",$value,$regs))) { /* else if the current $value is a file */
		$data[$regs[1]][] = $base.$value; /* just add the current $value to the $data array */
    }
  }
  return $data; // return the $data array
}

?>
