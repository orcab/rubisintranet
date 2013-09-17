<?
include('inc/config.php');

session_start();
$_SESSION = array();
session_destroy();

?><html>
<head>
<title>Intranet</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img { border:none; }
a { text-decoration:none; }
a:hover { text-decoration:underline; }

img:hover { -moz-transform: scale(1.1); }

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
	padding-left:50px;
	font-weight:bold;
	padding-top:5px;
	font-size:0.8em;
    height: 250px;
}

/* style des evenements */

div#footer h2 {
	font-size:0.9em;
	color:grey;
	font-weight:normal;
}

div#events {
    height: 198px;
    overflow: auto;
    width: 87%;
    margin: auto;
}
div.cal-event {
    border-bottom: 1px solid #CCCCCC;
    border-bottom-right-radius: 10px;
    border-right: 1px solid #CCCCCC;
    float: left;
    margin-bottom: 10px;
    margin-right: 20px;
    min-height: 90px;
    width: 23em;
    background-image: -moz-linear-gradient(-230deg, #DFDFDF, white);
}
div.anniversaire	{ background-image: -moz-linear-gradient(-230deg, lightblue, white); }
div.conges			{ background-image: -moz-linear-gradient(-230deg, lightgreen, white); }
div.inventaire		{ background-image: -moz-linear-gradient(-230deg, yellow, white); }
div.ferie			{ background-image: -moz-linear-gradient(-230deg, pink, white); }
div.cal-date {
    background: url("gfx/calendar.gif") no-repeat scroll 4px top transparent;
    font-weight: bold;
    height: 16px;
    padding-left: 26px;
}
div.cal-summary {
    font-weight: normal;
    padding-left: 26px;
}
div.cal-description {
    color: grey;
    font-weight: normal;
    padding-left: 26px;
}
div.cal-location {
    color: #8888FF;
    font-weight: normal;
    padding-left: 26px;
}

</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.ui.all.js"></script>

</head>
<body style="margin:0px;padding:0px;">
<div id="header">Intranet <?=SOCIETE?></div>

<center>
<table style="width:70%;text-align:center;border:solid 1px #AAA;">
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="article/creation_article.php"><img src="article/gfx/creation_article.png"><br>Cr�ation d'article</a><br></td>
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
	<td><a href="commande_adherent/historique_commande.php"><img src="commande_adherent/gfx/commande_adherent.png"><br>Commande Adh�rents</a><br></td>
	<td><a href="tarif2/"><img src="tarif2/gfx/catalogue.png"><br>Catalogue papier</a><br></td>
	<td></td>
</tr>
</table>
</center>

<div id="footer">
	<h2>Evenements � MCS dans l'ann&eacute;e � venir</h2>
	<div id="events">
	<?	// charge le fichier json des evenements
		$ini_filename = 'scripts/ical2sqlite.ini';
		if (file_exists($ini_filename)) {
			$ini = parse_ini_file($ini_filename,true);
			if (file_exists($ini['files']['sqlite_output'])) {
				try {
					$sqlite = new PDO('sqlite:'.$ini['files']['sqlite_output']); // success
					//$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
				} catch (PDOException $exception) {
					echo "Erreur dans l'ouverture de la base de donn�es. Merci de pr�venir Benjamin au 02.97.69.00.69 ou d'envoy� un mail � <a href='mailto:benjamin.poulain@coopmcs.com&subject=Historique commande en ligne'>Benjamin Poulain</a>";
					die ($exception->getMessage());
				}

				$sql = <<<EOT
SELECT 	`start`,`end`,summary,description,location,frequency
FROM 	events
WHERE
		(`start`>=date('now') AND `start`<=date('now','+1 year'))
	OR
		(`end`>=date('now') AND `end`<=date('now','+1 year'))
	OR
		frequency='YEARLY'
ORDER BY
		`start` ASC
EOT;
				$res = $sqlite->query($sql) or die("Impossible de lancer la requete de selection des events de la semaine suivantes : ".array_pop($sqlite->errorInfo()));

				$rows = array(); // pour enregistrer les r�sultats

				// on passe une premiere fois sur le tableau pour changer les dates des events p�riodiques
				while ($row2 = $res->fetch(PDO::FETCH_ASSOC)) {
					if ($row2['frequency'] == 'YEARLY') { // on repete l'evenement tous les ans peut import le debut et la fin de l'event
						$this_year = date('Y');
						$row2['start'] 	= preg_replace('/^\d{4}-/',$this_year.'-',$row2['start']);
						$row2['end'] 	= preg_replace('/^\d{4}-/',$this_year.'-',$row2['end']);
					}
					array_push($rows,$row2); // on stock les infos pour un 2eme passage
				}


				// sort by date start function
				function sortByEventStartingDate($a, $b) {
				    if ($a['start'] == $b['start'])	return 0;
				    
				    return ($a['start']  < $b['start'] ) ? -1 : 1;
				}
				uasort($rows, 'sortByEventStartingDate');


				// on affiche les evenements class�s
				$i=0;
				foreach ($rows as $row) {

					// hack pour les evenement sur une journ�e (ou plusieurs). la date de fin doit etre diminuer de 1
					if (preg_match('/ 00:00:00$/',$row['start']) &&
						preg_match('/(\d{4})-(\d{2})-(\d{2}) 00:00:00$/',$row['end'],$matches)) {
						$date_end_time = mktime(	0,					// hour
													0,					// min
													0,					// sec
													$matches[2],		// mounth
													$matches[3] - 1,	// day
													$matches[1]) ;		// year (4 digit)
						$row['end'] = date('Y-m-d 00:00:00',$date_end_time);

						if ($row['end'] < date('Y-m-d')) // si la date de fin modifi� est inf�rieur � aujourd'hui --> on saute
							continue;
						//echo "end:'$row[end]'";
					}

					preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',$row['start'],$date_start);
					preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',$row['end'],$date_end);

					$date_start_time = mktime(	isset($date_start[4])?$date_start[4]:0,	// hour
												isset($date_start[5])?$date_start[5]:0,	// min
												isset($date_start[6])?$date_start[6]:0,	// sec
												$date_start[2],							// mounth
												$date_start[3],							// day
												$date_start[1]) ;						// year (4 digit)
					$date_start_formater = date('d M Y',$date_start_time);
					$heure_start = date('H:i',$date_start_time);
					$date_start_formater = $jours_mini[date('w',$date_start_time)]." $date_start_formater";

					$date_end_time = mktime(	isset($date_end[4])?$date_end[4]:0,	// hour
												isset($date_end[5])?$date_end[5]:0,	// min
												isset($date_end[6])?$date_end[6]:0,	// sec
												$date_end[2],						// mounth
												$date_end[3],						// day
												$date_end[1]) ;						// year (4 digit)
					$date_end_formater = date('d M Y',$date_end_time);
					$heure_end = date('H:i',$date_end_time);
					$date_end_formater = $jours_mini[date('w',$date_end_time)]." $date_end_formater";
?>
					<div class="cal-event<? // gestion des evenements particulier
											if		(preg_match('/\banniversaires?\b/i',$row['summary']))
												echo ' anniversaire';
											elseif (preg_match('/\bcong[�e]s? +/i',utf8_decode($row['summary'])))
												echo  ' conges';
											elseif (preg_match('/\binventaires?\b/i',$row['summary']))
												echo  ' inventaire';
											elseif (preg_match('/\bf[�e]ri[�e]s?\b/i',utf8_decode($row['summary'])))
												echo  ' ferie';
										?>">
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
				?>Impossible de trouver la base sqlite iCal <em><?=$ini['files']['sqlite_output']?></em><?
			}
		} else {
			 ?>Impossible de trouver le fichier de configuration <em><?=$ini_filename?></em><?
		}
	?>
	</div><!-- fin events -->
</div><!-- fin footer -->

</body>
</html>