<?

include('inc/config.php');

session_start();
$_SESSION = array();
session_destroy();

?><html>
<head>
<title>Intranet</title>
<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img { 
	border:none;
}

a {
	text-decoration:none;
}

a:hover {
	text-decoration:underline;
}

img:hover {
	-moz-transform: scale(1.1);
}

div#header {
	width:96%;
	background-image:-moz-linear-gradient( bottom , #fdfdfd, #ddd );
	margin-bottom:10px;
	height:30px;
	padding-left:50px;
	font-weight:bold;
	padding-top:10px;
}

div#footer {
	background-image:-moz-linear-gradient( top , #fdfdfd, #eee );
	width:96%;
	margin-top:10px;
	height:180px;
	padding-left:50px;
	font-weight:bold;
	padding-top:5px;
	font-size:0.8em;
}

/* style des evenements */
div#footer h2 {
	font-size:0.9em;
	color:grey;
	font-weight:normal;
}

div.cal-event {
	background:url(gfx/calendar.gif) no-repeat 4px top;
	margin-bottom:10px;
	float:left;
	width:20%;
	padding-left:26px;
	border-right:solid 1px #CCC;
	border-bottom:solid 1px #CCC;
	margin-right:20px;
	height:100px;
	border-bottom-right-radius: 10px;
	display:none;
}

div.cal-date {
	font-weight:bold;
}

div.cal-summary {
	font-weight:normal;
}

div.cal-description {
	color:grey;
	font-weight:normal;
}

div.cal-location {
	color:#88F;
	font-weight:normal;
}

</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.ui.all.js"></script>
<script>

var event_block = 0; // block en cours d'affichage

// quand tout est chargé
$(document).ready(function(){
	// on affiche les 4 premiers events
	for(var i=0 ; i<4 ; i++)
		$('#cal-event-'+i).slideDown();
	event_block = 0; // on a affiché le premier block
});



function prev_events() {
	// on affiche les 4 events precedent
	if (event_block>0) { // si on est pas deja sur le premier groupe
		for(var i=event_block*4 ; i<(event_block*4 + 4) ; i++)
			$('#cal-event-'+i).hide();
		
		event_block--;
		for(var i=event_block*4 ; i<(event_block*4 + 4) ; i++)
			$('#cal-event-'+i).slideDown();
	}
}

function next_events() {
	// on affiche les 4 events suivants
	if (event_block < Math.round(total_events/4)) { // si on est pas deja sur le dernier groupe
		for(var i=event_block*4 ; i<(event_block*4 + 4) ; i++)
			$('#cal-event-'+i).hide();
		
		event_block++;
		for(var i=event_block*4 ; i<(event_block*4 + 4) ; i++)
			$('#cal-event-'+i).slideDown();
	}
}

</script>

</head>
<body style="margin:0px;padding:0px;">
<div id="header">Intranet <?=SOCIETE?></div>

<center>
<table style="width:70%;text-align:center;border:solid 1px #AAA;">
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="article/creation_article.php"><img src="article/gfx/creation_article.png"><br>Création d'article</a><br></td>
	<td><a href="commande_fournisseur/historique_commande.php"><img src="commande_fournisseur/gfx/commande_fournisseur.png"><br>Commandes Fournisseur</a><br></td>
	<td style="width:50%;padding-bottom:20px;"><a href="devis2/index.html"><img src="devis/gfx/creation_devis.png"><br>Devis Exposition</a><br></td>
</tr>
<tr>
	<td><a href="article/historique_creation_article.php"><img src="article/gfx/historique_creation_article.png"><br>Historique article</a><br></td>
	<td><a href="devis_rubis/historique_devis.php"><img src="devis_rubis/gfx/devis_rubis.png"><br>Devis Rubis</a><br></td>
	<td><a href="anomalie/index.html"><img src="anomalie/gfx/anomalie.png"><br>Anomalies</a><br><br></td>
</tr>
<tr>
	<td><a href="outils/index.php"><img src="outils/gfx/icon_tools.png"><br>Outils</a></td>
	<td><a href="commande_adherent/historique_commande.php"><img src="commande_adherent/gfx/commande_adherent.png"><br>Commande Adhérents</a><br></td>
	<td><a href="tarif2/"><img src="tarif2/gfx/catalogue.png"><br>Catalogue papier</a><br></td>
	<td></td>
</tr>
</table>
</center>

<div id="footer">
	<h2>Evenements à MCS dans la semaine à venir</h2>
	<img src="gfx/precedent.png" style="clear:both;margin:auto;display:block;margin-bottom:5px;" onclick="prev_events();"/>
	<?	// charge le fichier json des evenements
		$ini_filename = 'scripts/ical2sqlite.ini';
		if (file_exists($ini_filename)) {
			$ini = parse_ini_file($ini_filename,true);
			if (file_exists($ini['files']['sqlite_output'])) {
				
				try {
					$sqlite = new PDO('sqlite:'.$ini['files']['sqlite_output']); // success
					//$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
				} catch (PDOException $exception) {
					echo "Erreur dans l'ouverture de la base de données. Merci de prévenir Benjamin au 02.97.69.00.69 ou d'envoyé un mail à <a href='mailto:benjamin.poulain@coopmcs.com&subject=Historique commande en ligne'>Benjamin Poulain</a>";
					die ($exception->getMessage());
				}

				$sql = <<<EOT
SELECT `start`,`end`,summary,description,location
FROM events
WHERE
     (`start`>=date('now') AND `start`<=date('now','+1 year')) 
     OR
     (`end`>=date('now') AND `end`<=date('now','+1 year'))     
ORDER BY
      `start` ASC
EOT;
				$res = $sqlite->query($sql) or die("Impossible de lancer la requete de selection des events de la semaine suivantes : ".array_pop($sqlite->errorInfo()));

				// on affiche les evenements classés
				$i=0;
				while ($row = $res->fetch(PDO::FETCH_ASSOC)) {

					// hack pour les evenement sur une journée (ou plusieurs). la date de fin doit etre diminuer de 1
					if (preg_match('/ 00:00:00$/',$row['start']) &&
						preg_match('/(\d{4})-(\d{2})-(\d{2}) 00:00:00$/',$row['end'],$matches)) {
						$date_end_time = mktime(	0,	// hour
													0,	// min
													0,	// sec
													$matches[2],				// mounth
													$matches[3] - 1,				// day
													$matches[1]) ;			// year (4 digit)
						$row['end'] = date('Y-m-d 00:00:00',$date_end_time);

						//echo "end:'$row[end]'";
					}

					preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',$row['start'],$date_start);
					preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',$row['end'],$date_end);

					$date_start_time = mktime(	isset($date_start[4])?$date_start[4]:0,	// hour
												isset($date_start[5])?$date_start[5]:0,	// min
												isset($date_start[6])?$date_start[6]:0,	// sec
												$date_start[2],				// mounth
												$date_start[3],				// day
												$date_start[1]) ;			// year (4 digit)
					$date_start_formater = date('d M Y',$date_start_time);
					$heure_start = date('H:i',$date_start_time);
					$date_start_formater = $jours_mini[date('w',$date_start_time)]." $date_start_formater";

					$date_end_time = mktime(	isset($date_end[4])?$date_end[4]:0,	// hour
												isset($date_end[5])?$date_end[5]:0,	// min
												isset($date_end[6])?$date_end[6]:0,	// sec
												$date_end[2],				// mounth
												$date_end[3],				// day
												$date_end[1]) ;			// year (4 digit)
					$date_end_formater = date('d M Y',$date_end_time);
					$heure_end = date('H:i',$date_end_time);
					$date_end_formater = $jours_mini[date('w',$date_end_time)]." $date_end_formater";
?>
					<div class="cal-event" id="cal-event-<?=$i?>">
						<div class="cal-date">
							<?=$date_start_formater?> <?=$heure_start <> '00:00'?$heure_start:'' ?>
<?							if ($date_end_formater <> $date_start_formater || $heure_end <> '00:00') { ?>
								<img src="gfx/arrow.png" style="vertical-align:bottom;"/>
<?							} ?>
							<?=$date_end_formater <> $date_start_formater ? $date_end_formater:''?> <?=$heure_end <> '00:00'?$heure_end:'' ?>
						</div>
						<div class="cal-summary"><?=utf8_decode($row['summary'])?></div>
						<div class="cal-description"><?=stripslashes(str_replace('\n','<br/>',utf8_decode($row['description'])))?></div>
						<div class="cal-location"><?=$row['location'] ? "Lieu : ".utf8_decode($row['location']):''?></div>
					</div>
<?	
					$i++;
				} // fin while events
			} else {
				?>Impossible de trouver le fichier json <em><?=$ini['files']['sqlite_output']?></em><?
			}
		} else {
			 ?>Impossible de trouver le fichier de configuration <em><?=$ini_filename?></em><?
		}
	?>

	<img src="gfx/suivant.png" style="clear:both;margin:auto;display:block;" onclick="next_events();"/>
</div>

<script>
	var total_events = <?=$i?>;
</script>
</body>
</html>