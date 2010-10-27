<?

//require_once('../inc/constant.php');
require_once('../inc/fpdf/fpdf.php');

define('PAGE_WIDTH',297);
define('PAGE_HEIGHT',195);
define('LEFT_MARGIN',149);
define('RIGHT_MARGIN',10);
define('TOP_MARGIN',85);

class PDF extends FPDF
{
	//EN-TÊTE
	function Header() {
	}

	//PIED DE PAGE
	function Footer() {	
	}
}
?>