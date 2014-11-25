<?php

// recupere toutes les infos expo sous forme de tableau json

include('../inc/config.php');

// va récupérer la liste des box dispo dans les cases localisation des fiches de stocks
	$sql = <<<EOT
select
	S.LOCAL,S.LOCA2,S.LOCA3
from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 A
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 S
		on  A.NOART=S.NOART and S.DEPOT='EXP'
group by LOCAL,LOCA2,LOCA3
EOT;

	$locals = array();
	
	// pour le debug
	echo "Connecting to Rubis...";
	$loginor  	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	echo "OK\n";
	
	echo "Requesting Rubis...";
	$res 		= odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");
	while($row = odbc_fetch_array($res)) {
		foreach (array('LOCAL','LOCA2','LOCA3') as $field) {
			foreach (split('/',$row[$field]) as $local) {
				$box = substr(trim(strtoupper($local)),0,3);
				if (substr($box,0,1) == 'X')
					$locals[$box] = 1;
			}
		}
	}
	echo "OK\n";

	//$locals = array('X14'=>1,'X08'=>1);
	ksort($locals);

	//header('Content-type: text/json');
	//header('Content-type: application/json');
	$global_infos = array();
	$articles = array();
	$boxs = array();
	$articles = array();
	$titles = array();
	foreach($locals as $box => $val) {
		if (strlen($box) < 3) continue;
		
		echo "Get information for box $box\n";
		$box_infos = join('',file("http://10.211.14.6/intranet/devis2/ajax_etiquette_expo.php?what=get_detail_box&box=$box"));
		$boxs[$box] = json_decode($box_infos,true);
		
		$box_titles = join('',file("http://10.211.14.6/intranet/devis2/ajax_etiquette_expo.php?what=get_box_titles&box=$box"));
		$titles[$box] = json_decode($box_titles,true);

		//var_dump($boxs);
		//var_dump($titles);

		$global_infos['box'][$box] = $boxs[$box]['sousboxs'];
		$global_infos['title'][$box] = $titles[$box];
		$articles = array_merge($articles,$boxs[$box]['articles']);

	}

	$global_infos['article'] = $articles;

	$JSON = fopen('box_expo.json','w+');
	fwrite($JSON,json_encode($global_infos));
	fclose($JSON);
?>