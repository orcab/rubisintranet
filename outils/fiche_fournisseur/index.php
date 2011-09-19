<?
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message ='' ;

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Fiches Fournisseur</title>
<style>
body { font-family:verdana; font-size:0.9em; }
a img { border:none; }
input,textarea { border:solid 2px #AAA; }
fieldset { -moz-border-radius:6px; border:solid 1px grey; }

table#liste-fournisseur {
	border-spacing: 0px;
	border-collapse: collapse;
}

tr.fournisseur > td:first-child { /* premiere case qui contient le nom du fournisseur */
	border:solid 1px grey;
	border-top:none;
	font-weight:bold;
	padding:3px;
	padding-left:23px;
}

tr.fournisseur > td:nth-child(2) { /* deuxieme case qui contient l'ajout de com' */
	border:solid 1px grey;
	border-top:none;
	background:url(gfx/add-mini.png) no-repeat center -2px;
	width:30px;
}

tr.fournisseur			{	background-image:-moz-linear-gradient( top , #fdfdfd, #eee );	}
tr.fournisseur:hover	{
	color:white;
	text-shadow:grey 0px -1px;
	-moz-box-shadow: 0 0 9px #6a9dca;
	background-image:-moz-linear-gradient( top, #83b8e2, #5393c5 );
}

/* supprime la bordure entre le nom et le numero de l'artisan */
tr.fournisseur > td:first-child { border-right:none; }
tr.fournisseur > td:nth-child(2) { border-left:none; }

table#liste-fournisseur td:first-child, table#liste-fournisseur td:nth-child(2) { border-top:solid 1px grey; } /* premiere et 2eme case fournisseur */
table#liste-fournisseur { cursor:pointer; }


/* liste des derniers com' */
table.liste {
	width:200px;
	font-size:0.7em;
	border-spacing: 0px;
	border-collapse: collapse;
}

table.liste .date {
	color:grey;
	margin:0;
}

#liste-container {
	float:left;
	width:200px;	
}

table.liste  > caption {
	color:white;
	background-image:-moz-linear-gradient( top, #83b8e2, #5393c5 );
	font-weight:bold;
	text-transform:uppercase;
	padding:3px;
	text-shadow:grey 0px -1px;
}

table.liste td {
	border:solid 1px grey;
	text-align:left;
	padding-left:5px;
	background-image:-moz-linear-gradient( top , #fdfdfd, #eee );
	cursor:pointer;
}

table.liste td:hover {
	background-image:-moz-linear-gradient( top, #83b8e2, #5393c5 );
	color:white;
	text-shadow:grey 0px -1px;
	-moz-box-shadow: 0 0 9px #6a9dca;
}


.fournisseur-annule {
	text-decoration:strike-through;
	color:#999;
	font-weight:normal;
	background:url(gfx/hachure.gif);
}

div#intervention {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
	/* (inset ?)    x-offset  y-offset  blur-raduis  spread-radius   color  --> for opactiy : rgba(0, 0, 0, 0.5)    */
	-moz-box-shadow: 5px 5px 20px 0px grey;
}


div.date, div.humeur, div.createur, div.type {
	margin-left:10px;
	margin-right:10px;
	float:left;
	text-align:left;
	font-size:0.9em;
}

</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script type="text/javascript">

// va chercher dans la liste des fournisseurs, les valeurs qui commence par ce qu'a tapé l'utilisateur
function affiche_fournisseur() {
	$('#liste-fournisseur').html('');
	var query = document.selecteur.recherche.value.toLowerCase() ;
	if (query.length >= 1) // si au moins deux caractères de renseigné
		for(i=0; i<fournisseurs.length ; i++) // pour chaque fournisseur
			//$('#debug').append("'"+fournisseurs[i][0].substr(0,query.length)+"'    '"+query+"'<br>");
			if (	fournisseurs[i][0].substr(0,query.length) == query
				||	fournisseurs[i][1].substr(0,query.length) == query) { // code fournisseur trouvé
				$('#liste-fournisseur').append(
							'<tr class="fournisseur' + (!fournisseurs[i][2] ? ' fournisseur-annule':'') + '">' +
								'<td onclick="goTo(\''+fournisseurs[i][0]+'\')">' +fournisseurs[i][1].toUpperCase() +
							(fournisseurs[i][3] > 0 ? '&nbsp;&nbsp;('+fournisseurs[i][3]+')' : '') + '</td>' +
							'</tr>'
					);
			} // fin if
}	



/* va voir le detail d'un fournisseur */
function goTo(id) {
	document.location.href='detail_fournisseur.php?id='+escape(id);
}


// stock la liste des fournisseurs
var fournisseurs = new Array();
<?
// récupère la liste des fournisseurs
$res = mysql_query("SELECT id,nom,code_rubis,affiche,(SELECT COUNT(id) FROM fournisseur_commentaire WHERE fournisseur_commentaire.code_fournisseur=fournisseur.code_rubis AND fournisseur_commentaire.supprime=0) AS nb_com FROM fournisseur") or die("ne peux pas retrouver la liste des fournisseurs ".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
	fournisseurs.push(['<?=addslashes(strtolower($row['code_rubis']))?>','<?=addslashes(strtolower($row['nom']))?>',<?=$row['affiche']?>,<?=$row['nb_com']?>]);
<? } ?>

</script>
</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<div id="liste-container">

<table class="liste" id="liste-dernier-commentaire">
	<caption>20 derniers commentaires</caption>
<?
// récupère la liste des Dernier com'
$sql = <<<EOT
SELECT	date_creation as last_visite, code_fournisseur as numero, createur, `type`, humeur, nom
FROM	fournisseur_commentaire FC
	LEFT JOIN fournisseur F
		ON FC.code_fournisseur = F.code_rubis
WHERE	supprime=0
	AND	date_creation <= NOW() and date_creation >= DATE_SUB(NOW(),INTERVAL 1 YEAR) -- un an maximum
ORDER BY	date_creation DESC
LIMIT 0,20
EOT;

$res = mysql_query($sql) or die("ne peux pas retrouver la liste des derniers commentaires".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
	<tr>
		<td onclick="goTo('<?=$row['numero']?>')"><?=$row['nom']?><br/>
		<div class="date">
		<?	switch ($row['type']) {
				case 'visite_mcs': ?><img src="gfx/mcs-icon.png"	style="vertical-align:top;" title="Visite de l'artisan à MCS"/><?	break;
				case 'visite_artisan': ?><img src="gfx/artisan.png" style="vertical-align:top;" title="Visite chez l'artisan"/><?		break;
				case 'telephone': ?><img src="gfx/telephone.png"	style="vertical-align:top;" title="Par téléphone"/><?				break;
				case 'fax': ?><img src="gfx/fax.png"				style="vertical-align:top;" title="Par fax"/><?						break;
				case 'courrier': ?><img src="gfx/courrier.png"		style="vertical-align:top;" title="Par courrier"/><?				break;
				case 'email': ?><img src="gfx/mail.png"				style="vertical-align:top;" title="Par mail"/><?					break;
				case 'autre': ?><img src="gfx/autre.png"			style="vertical-align:top;" title="Auytre"/><?						break;
			}
		?><?=$row['createur']?> le 
		<?	
			preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',$row['last_visite'],$last_visite);
			$last_visite_time = mktime(	$last_visite[4],	// hour
										$last_visite[5],	// min
										$last_visite[6],	// sec
										$last_visite[2],		// mounth
										$last_visite[3],		// day
										$last_visite[1]) ;	// year (4 digit)
			$last_visite_formater = date('d M Y',$last_visite_time);
			$last_visite_formater = $jours_mini[date('w',$last_visite_time)]." $last_visite_formater";
			echo $last_visite_formater;

			switch ($row['humeur']) {
				case 0: ?><?																													break;
				case 1: ?>&nbsp;<img src="/intranet/gfx/weather-clear.png" alt="Content" title="Content" style="vertical-align:top;" /><?		break;
				case 2: ?>&nbsp;<img src="/intranet/gfx/weather-few-clouds.png" alt="Mausade" title="Mausade" style="vertical-align:top;" /><?	break;
				case 3: ?>&nbsp;<img src="/intranet/gfx/weather-storm.png" alt="Enervé" title="Enervé" style="vertical-align:top;" /><?			break;
			} ?>
		</div>
		</td>
	</tr>
<? } ?>
</table>

</div>


<form action="index.php" method="post" name="selecteur" style="margin:auto;width:20%;margin-top:10px;">
<input type="hidden" name="id" value="" />
<input type="hidden" name="action" value="" />

<fieldset><legend>Rechercher un fournisseur</legend>
<input type="text" name="recherche" size="25" onkeyup="affiche_fournisseur()" value="" />
</fieldset>
</form>

<table id="liste-fournisseur" style="margin:auto;width:40%;margin-top:10px;"></div>

<div id="debug"></div>

</body>
</html>
<?
mysql_close($mysql);
?>