<?php

include('inc/config.php');

$tournee_chauffeur = array(
	'124' =>	array(	'1' => 'PH1',
						'2' => 'PH2',
						'4' => 'GIL'
				),
	'134' =>	array(	'1' => 'GIL',
						'3' => 'PH1',
						'4' => 'PH2'
				),
	'135' =>	array(	'1' => 'PH2',
						'3' => 'GIL',
						'5' => 'PH1'
				),
	'235' =>	array(	'2' => 'PH2',
						'3' => 'GIL',
						'5' => 'PH1'
				),
	'245' =>	array(	'2' => 'GIL',
						'4' => 'PH1',
						'5' => 'PH2'
				)
);



$sql = <<<EOT
AFAGESTCOM.ACLIENP1.NOMCL,AFAGESTCOM.ACLIENP1.TOUCL,NOBON
from AFAGESTCOM.AENTBOP1,AFAGESTCOM.ACLIENP1
where
CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB)))))='2008-07-31'
and TYVTE='LIV'
and FACAV='F'
and ETSEE=''
and AFAGESTCOM.AENTBOP1.NOCLI=AFAGESTCOM.ACLIENP1.NOCLI
EOT;

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res			= odbc_exec($loginor,$sql) ; 

echo "<pre>";
while($row = odbc_fetch_array($res)) {
	print_r($row) ;
}
echo "</pre>";
?>