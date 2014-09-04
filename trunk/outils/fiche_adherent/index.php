<?
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message ='' ;
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Fiches Artisans</title>
<style>
body {
	font-family:verdana;
	font-size:0.9em;
}
a img { border:none; }
input,textarea { border:solid 2px #AAA; }
fieldset {
	border-radius:6px;
	border:solid 1px grey;
}

input#recherche { width:100%; }

table#liste-artisan,
table#liste-artisan-favoris {
	border-spacing: 0px;
	border-collapse: collapse;
}

tr.artisan > td { /* premiere case qui contient le nom du artisan */
	border:solid 1px grey;
	border-top:none;
	font-weight:bold;
	background-image:-moz-linear-gradient( top , #fdfdfd, #eee );
	padding:3px;
	padding-left:5px;
}

/* supprime la bordure sur une row artisan */
tr.artisan > td:first-child,tr.artisan > td:nth-child(2) { border-right:none; }
tr.artisan > td:nth-child(2),tr.artisan > td:nth-child(3) { border-left:none; }

/* case du num�ro d'adh�rent */
tr.artisan > td:nth-child(3) {
	width:4em;
	text-align:right;
	padding-right:5px;
}

tr.artisan:hover > td {
	background-image:-moz-linear-gradient( top, #83b8e2, #5393c5 );
	color:white;
	text-shadow:grey 0px -1px;
	box-shadow: 0 0 9px #6a9dca;
}

tr.artisan td.numero {
	font-size:0.8em;
	color:grey;
	font-weight:normal;
}

tr.artisan:hover td.numero { color:white; }

table#liste-artisan td,
table#liste-artisan-favoris td { border-top:solid 1px grey; } /* premiere et 2eme case artisan */
table#liste-artisan,
table#liste-artisan-favoris { cursor:pointer; }

/* titre du tableau favoris */
table#liste-artisan-favoris > caption {
	color:white;
	background-image:-moz-linear-gradient( top, #83b8e2, #5393c5 );
	font-weight:bold;
	text-transform:uppercase;
	padding:3px;
	text-shadow:grey 0px -1px;
}

/* table des adh�rents non visit� */
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

#liste-artisan-non-visite { float:right; }
#liste-dernier-visite { margin-bottom:20px; }
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
	box-shadow: 0 0 9px #6a9dca;
}

.ville {
	color:grey;
	font-size:0.8em;
}

.artisan-annule {
	text-decoration:line-through;
	color:#999;
	font-weight:normal;
	background:url(gfx/hachure.gif);
}

div#intervention {
	padding:20px;
	border:solid 2px black;
	border-radius:10px;
	background:white;
	display:none;
	position:absolute;
	/* (inset ?)    x-offset  y-offset  blur-raduis  spread-radius   color  --> for opactiy : rgba(0, 0, 0, 0.5)    */
	box-shadow: 5px 5px 20px 0px grey;
}


div.date, div.humeur, div.createur, div.type {
	margin-left:10px;
	margin-right:10px;
	float:left;
	text-align:left;
	font-size:0.9em;
}

</style>

<link rel="stylesheet" href="../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../js/fontawesome/css/icon-custom.css">

<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script type="text/javascript">

const CODE_ARTISAN		= 0;
const NOM_ARTISAN		= 1;
const SUSPENDU_ARTISAN	= 2;
const NB_COM_ARTISAN	= 3;
const FAVORIS_ARTISAN	= 4;
const NOM_ARTISAN_RECHERCHE = 5;

// va chercher dans la liste des artisans, les valeurs qui commence par ce qu'a tap� l'utilisateur
function affiche_artisan() {
	$('#liste-artisan').html('');	// efface le tableau de r�sultat
	var query = document.selecteur.recherche.value.toLowerCase().replace(/[^0-9a-z ]/,'') ;
	if (query.length >= 1) // si au moins un caract�re de renseign�
		for(i=0; i<artisans.length ; i++) // pour chaque artisan
			if (	artisans[i][NOM_ARTISAN_RECHERCHE].indexOf(query) > -1 // nom artisan trouv�
				||	artisans[i][NOM_ARTISAN].indexOf(query) > -1
				||	artisans[i][CODE_ARTISAN].indexOf(query) > -1)// code artisan trouv�
						draw_row_artisan('liste-artisan',artisans[i]); // ajoute une ligne aux tableaux de r�sultat
}	


function draw_row_artisan(table,artisan) {
	$('#'+table).append(
		'<tr class="artisan' + (!artisan[SUSPENDU_ARTISAN] ? ' artisan-annule':'') + '">' +
			'<td class="stars" onclick="toogle_star(\''+artisan[CODE_ARTISAN]+'\')"><img id="star-'+artisan[CODE_ARTISAN]+'" src="gfx/star-'+(artisan[FAVORIS_ARTISAN] == '1' ? 'on':'off')+'.png"/></td>' +
			'<td onclick="goTo(\''+artisan[CODE_ARTISAN]+'\')">' +artisan[NOM_ARTISAN].toUpperCase() +	// nom de l'artisan
			(artisan[NB_COM_ARTISAN] > 0 ? '&nbsp;&nbsp;('+artisan[NB_COM_ARTISAN]+')' : '') + '</td>' +		// nombre d'intervention
			'<td class="numero">' + artisan[CODE_ARTISAN] + '</td>' +								// numero de l'artisan
		'</tr>'
	);
}


/* va voir le detail d'un artisan */
function goTo(id) {
	document.location.href='detail_artisan.php?id='+escape(id);
}

/* place un adh�rent en favoris ou non */
function toogle_star(id) {
	$.ajax({
		type: 'GET',
		url:  'ajax.php',
		data: 'what=toogle_star&id='+id,
		dataType: 'json',
		success: function(json){
			for(i=0; i<artisans.length ; i++) // pour chaque artisan
				if (artisans[i][CODE_ARTISAN] == id) {  //met a jour la valeur favoris dans le JS
					artisans[i][FAVORIS_ARTISAN] = !artisans[i][FAVORIS_ARTISAN];
					break;
				}
			$('img#star-'+id).attr('src','gfx/star-'+(json.favoris == '1' ? 'on':'off')+'.png'); // modifie l'image
		}
	});
}


// stock la liste des artisans
var artisans = new Array();
<?
// r�cup�re la liste des artisans
$sql = <<<EOT
SELECT	nom,artisan.numero,suspendu,	-- from artisan
		favoris,	-- from artisan_info
		(SELECT COUNT(id) FROM artisan_commentaire WHERE artisan_commentaire.code_artisan=artisan.numero AND artisan_commentaire.supprime=0) AS nb_com
		FROM artisan
			LEFT JOIN artisan_info
				ON artisan.numero=artisan_info.numero
EOT;
$res = mysql_query($sql) or die("ne peux pas retrouver la liste des artisans ".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
	artisans.push([	'<?=addslashes(strtolower($row['numero']))?>',
					'<?=addslashes(strtolower($row['nom']))?>',
					<?= !$row['suspendu'] ?>,
					<?=$row['nb_com']?>,
					<?=$row['favoris']?>,
					'<?=preg_replace('/[^0-9a-z ]/i','',strtolower($row['nom']))?>'
					]
	);
<? } ?>

// au chargement du document
$(document).ready(function(){
	// charge le tableau des adh�rents favoris
	//$('#liste-artisan-favoris').append

	for(i=0; i<artisans.length ; i++) // pour chaque artisan
		if (artisans[i][FAVORIS_ARTISAN])
			draw_row_artisan('liste-artisan-favoris',artisans[i]);
});

</script>
</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<table class="liste" id="liste-artisan-non-visite">
	<caption>Non visit� depuis 1 an</caption>
<?
// r�cup�re la liste des artisans non visit� depuis 1 an
$sql = <<<EOT
SELECT	nom,artisan.numero,
		UNIX_TIMESTAMP((SELECT date_creation FROM artisan_commentaire WHERE artisan_commentaire.code_artisan=artisan.numero AND artisan_commentaire.supprime=0 AND `type`='visite_artisan' ORDER BY date_creation DESC LIMIT 0,1)) AS last_visite,
		ville
FROM	artisan
WHERE		suspendu=0
ORDER BY nom ASC
EOT;
$now = time();
$one_year_in_second = 60*60*24*365;
$res = mysql_query($sql) or die("ne peux pas retrouver la liste des artisans non visit� ".mysql_error());
while($row = mysql_fetch_array($res)) {
		$date_diff = $row['last_visite'] ? $now - $row['last_visite'] : $one_year_in_second;
?>	
<?	if ($date_diff >= $one_year_in_second) { ?>
		<tr><td onclick="goTo('<?=$row['numero']?>');" nowrap><?=$row['nom']?> <?=$row['last_visite']?> <span class="ville">(<?=$row['ville']?>)</span></td></tr>
<?	}
} ?>
</table>


<div id="liste-container">

<table class="liste" id="liste-dernier-visite">
	<caption>Visit� depuis 3 mois</caption>
<?
// r�cup�re la liste des artisans visit�s recemement
$sql = <<<EOT
SELECT	DATE_FORMAT(date_creation,'%d %b %Y') as last_visite_format, DATE_FORMAT(date_creation,'%w') as day_of_week, code_artisan as numero, createur, humeur, nom
FROM	artisan_commentaire AC
	LEFT JOIN artisan A
		ON AC.code_artisan = A.numero
WHERE	supprime=0
	AND	date_creation <= NOW() and date_creation >= DATE_SUB(NOW(),INTERVAL 3 MONTH) -- un an maximum
	AND `type`='visite_artisan'
ORDER BY	date_creation DESC
EOT;

$artisan_deja_affiche = array();

$res = mysql_query($sql) or die("ne peux pas retrouver la liste des artisans visit� dernierement".mysql_error());
while($row = mysql_fetch_array($res)) {
	if (!isset($artisan_deja_affiche[$row['numero']])) { ?>
		<tr>
			<td onclick="goTo('<?=$row['numero']?>')"><?=$row['nom']?><br/>
			<div class="date">
			<img src="gfx/artisan.png" style="vertical-align:top;" title="Visite chez l'artisan"/>
				<?=$row['createur']?> le <?=$jours_mini[$row['day_of_week']]?> <?=$row['last_visite_format']?>
			<?	
				switch ($row['humeur']) {
					case 0: ?><?																													break;
					case 1: ?>&nbsp;<img src="/intranet/gfx/weather-clear.png" alt="Content" title="Content" style="vertical-align:top;" /><?		break;
					case 2: ?>&nbsp;<img src="/intranet/gfx/weather-few-clouds.png" alt="Mausade" title="Mausade" style="vertical-align:top;" /><?	break;
					case 3: ?>&nbsp;<img src="/intranet/gfx/weather-storm.png" alt="Enerv�" title="Enerv�" style="vertical-align:top;" /><?			break;
				} ?>
			</div>
			</td>
		</tr>
<?		$artisan_deja_affiche[$row['numero']] = true;
	}
} ?>
</table>


<table class="liste" id="liste-dernier-commentaire">
	<caption>20 derniers commentaires</caption>
<?
// r�cup�re la liste des artisans visit�s recemement
$sql = <<<EOT
SELECT	date_creation as last_visite, code_artisan as numero, createur, `type`, humeur, nom
FROM	artisan_commentaire AC
	LEFT JOIN artisan A
		ON AC.code_artisan = A.numero
WHERE	supprime=0
ORDER BY	date_creation DESC
LIMIT 0,20
EOT;

$res = mysql_query($sql) or die("ne peux pas retrouver la liste des derniers commentaires".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
	<tr>
		<td onclick="goTo('<?=$row['numero']?>')"><?=$row['nom']?><br/>
		<div class="date">
		<?	switch ($row['type']) {
				case 'visite_mcs': ?><img src="gfx/mcs-icon.png"	style="vertical-align:top;" title="Visite de l'artisan � MCS"/><?	break;
				case 'visite_artisan': ?><img src="gfx/artisan.png" style="vertical-align:top;" title="Visite chez l'artisan"/><?		break;
				case 'telephone': ?><img src="gfx/telephone.png"	style="vertical-align:top;" title="Par t�l�phone"/><?				break;
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
				case 3: ?>&nbsp;<img src="/intranet/gfx/weather-storm.png" alt="Enerv�" title="Enerv�" style="vertical-align:top;" /><?			break;
			} ?>
		</div>
		</td>
	</tr>
<? } ?>
</table>

</div>

<form action="index.php" method="post" name="selecteur" style="margin:auto;width:20%;margin-top:10px;">

<fieldset><legend>Rechercher un artisan</legend>
	<input type="text" id="recherche" name="recherche" onkeyup="affiche_artisan()" value="" />
</fieldset>
</form>

<table id="liste-artisan" style="margin:auto;width:40%;margin-top:10px;"></table>

<table id="liste-artisan-favoris" style="margin:auto;width:40%;margin-top:10px;">
	<caption>FAVORIS</caption>
</table>

<br/>
<a class="btn" href="trombinoscope_artisan.php"><i class="icon-ok"></i> G�n�rer le trombinoscope des artisans</a>
<!-- KCLIENP1 (table contact) / ACLIENP1.CLID8 (administrateur)-->


<div id="debug"></div>

</body>
</html>
<?
mysql_close($mysql);
?>