<?php
 include 'connexion.php'; 
// on se connecte à MySQL
connexion();

// FUNCTION data_artisan
//Vérification des paramètres demandés
if(isset($_GET['function']) && $_GET['function'] == 'data_artisan' && isset($_GET['numero_artisan']) && $_GET['numero_artisan']) 
{	
	
$sql = <<<EOT
SELECT 	artisan.numero,artisan.nom,artisan.email,artisan.tel1,artisan.tel3,artisan.adr1,artisan.cp,artisan.ville,artisan.activite,text_desc,photo,website
FROM 	artisan LEFT JOIN infoexpo
ON artisan.numero = infoexpo.numero
WHERE 	artisan.numero='$_GET[numero_artisan]'
LIMIT 	0,1
EOT;
	
	$res = mysql_query($sql) or die("Erreur dans la requette SQL (".mysql_error().")") ;
	
	//Tableau des informations
	$data = mysql_fetch_array($res);
	
	//Nettoie le champs desc des espaces
	$data['text_desc'] 	= 	trim($data['text_desc']); 
						 
	//Transforme les données en base64 pour eviter les probleme de caracteres				
	$data['nom'] = base64_encode ($data['nom']);
	$data['adr1'] = base64_encode ($data['adr1'] );
	$data['ville'] = base64_encode ($data['ville'] );
	if ($data['text_desc']){
		$data['text_desc'] = base64_encode ($data['text_desc'] );
	}
		
		echo <<<EOT
		{   "numero":"$data[numero]", 
			"nom":"$data[nom]", 
			"email":"$data[email]",
			"tel1":"$data[tel1]",
			"tel3":"$data[tel3]",
			"adr1":"$data[adr1]",
			"cp":"$data[cp]",
			"ville":"$data[ville]",
			"activite":"$data[activite]",
			"text_desc":"$data[text_desc]",
			"photo":"$data[photo]",
			"website":"$data[website]"
		}
EOT;

}
else {
	echo "erreur";
}
?> 