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
	height:150px;
	padding-left:50px;
	font-weight:bold;
	padding-top:5px;
	font-size:0.8em;
}


</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/infobulle/infobulle.js"></script>
<style type="text/css">@import url(js/infobulle/infobulle.css);</style>

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
	<h2 style="font-size:0.9em;color:grey;font-weight:normal;">Evenements à MCS dans la semaine à venir</h2>
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
select `start`,`end`,summary,description from events
where
     (`start`>=date('now') and `start`<=date('now','+7 day')) 
     or
     (`end`>=date('now') and `end`<=date('now','+7 day'))     
order by
      `start` ASC
limit 0,4
EOT;
				$res = $sqlite->query($sql) or die("Impossible de lancer la requete de selection des events de la semaine suivantes : ".array_pop($sqlite->errorInfo()));

				// on affiche les evenements classés
				while ($row = $res->fetch(PDO::FETCH_ASSOC)) {

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
					<div class="cal-event" style="background:url(gfx/calendar.gif) no-repeat 4px top;margin-bottom:20px;float:left;width:20%;padding-left:26px;border-right:solid 1px #CCC;border-bottom:solid 1px #CCC;margin-right:20px;height:100px;border-radius:10px;">
						<div class="cal-date" style="font-weight:bold;">
							<?=$date_start_formater?>
							<?=$heure_start <> '00:00'?$heure_start:'' ?>
							<img src="gfx/arrow.png" style="vertical-align:bottom;"/>
							<?=$date_end_formater <> $date_start_formater ? $date_end_formater:''?>
							<?=$heure_end <> '00:00'?$heure_end:'' ?>
						</div>
						<div class="cal-summary" style="font-weight:normal;">
							<?=utf8_decode($row['summary'])?>
						</div>
						<div class="cal-description" style="color:grey;font-weight:normal;">
							<?=isset($row['description']) ? utf8_decode($row['description']):''?>
						</div>
					</div>
<?				}
			} else {
				?>Impossible de trouver le fichier json <em><?=$ini['files']['sqlite_output']?></em><?
			}
		} else {
			 ?>Impossible de trouver le fichier de configuration <em><?=$ini_filename?></em><?
		}
	?>
</div>
</body>
</html>