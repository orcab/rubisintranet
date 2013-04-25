<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_GET['debug']) ? 1:0);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '' ;

$id_devis = 0;
if (isset($_GET['id']) && $_GET['id']) {
	$id_devis = mysql_escape_string($_GET['id']);
} else {
	$message = "Erreur : Aucun numéro de devis spécifié";
}
?>
<html>
<head>
<title>Différence d'un devis</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<link rel="shortcut icon" type="image/x-icon" href="../gfx/creation_devis.ico" />
<style>

a img {
    border: medium none;
}
#diff {
    width: 80%;
    margin: auto;
    border: solid 1px black;
    margin-top: 1em;
    border-collapse: collapse;
}
#diff th,#diff td {
    text-align: left;
    border: solid 1px #ccc;
}
#diff th {
    border-color: black;
}
a {
    text-decoration: none;
    font-weight: normal;
}
a:hover {
    text-decoration: underline;
}
.add-car { color:green; }
.minus-car { color:red; }

caption {
    background-color: #DDD;
    font-size: 1.1em;
    padding: 2px;
}
</style>
<style type="text/css">@import url(../js/boutton.css);</style>
<script language="JavaScript" SRC="../js/jquery.js"></script>
<script language="JavaScript" SRC="../js/mobile.style.js"></script>
<SCRIPT LANGUAGE="JavaScript">
<!--

$(document).ready(function(){
	
	// vérifie que quand on coche une case, on n'a pas 3 cases cochées en même temps
	$('input[type=checkbox]').bind('click',function(){
		if ($('input:checked').length > 2) {
			alert("Vous ne pouvez pas comparer plus de 2 historiques en même temps");
			$(this).removeAttr('checked');
		}
	});


	// affiche les différences des options selectionnées
	$('#analyse_diff').bind('click',function(){
		if ($('input:checked').length == 2) {
			var id1 = $('input:checked').first().attr('id').replace(/^check_/,'');
			var id2 = $('input:checked').last().attr('id').replace(/^check_/,'');

			document.location.href=	'diff_devis.php?id=<?=$id_devis?>&'+
									'id_old=' +  (id1<=id2?id1:id2) + '&' +
									'id_new=' + (id1>id2?id1:id2) ;
		} else {
			alert("Vous ne pouvez comparer que 2 historiques en même temps");
		}
	});

});

//-->
</SCRIPT>

<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../js/fontawesome/css/icon-custom.css">

</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div style="color:red;"><?=$message?></div>

<br/>
<a class="btn" href="historique_devis.php"><i class="icon-arrow-left"></i> Revenir à l'historique des devis</a>

<form name="diff_devis" action="diff_devis.php" method="POST">
<table id="diff">
<caption>Historique du devis n°<?=$id_devis?></caption>
<thead>
	<tr>
		<th>Utilisateur</th>
		<th>Date</th>
		<th>Taille</th>
		<th style="text-align:center;">Diff</th>
		<th><a class="btn" id="analyse_diff" href="#"><i class="icon-exchange"></i> Afficher les différences</a></th>
	</tr>
</thead>
<tbody>
<?
$i=0;
$id_new = '';
$old_taille = 0;
$sql = <<<EOT
SELECT
	devis_history.id,devis_history.user as ip_user,DATE_FORMAT(`date`,'%w') AS date_jour, DATE_FORMAT(`date`,'%d/%m/%Y %H:%i') AS date_formater,LENGTH(devis) as taille,
	prenom as user_name
FROM
				devis_history
	left join 	employe
		on employe.ip = devis_history.user
WHERE
	id_devis='$id_devis' ORDER BY `date` DESC
EOT;
$res = mysql_query($sql) or die("Impossible de selectionné la liste des différences : ".mysql_error());
while ($row = mysql_fetch_array($res)) { ?>
	<tr>
		<td><?=$row['user_name']?></td>
		<td><?=$jours_mini[$row['date_jour']]?> <?=$row['date_formater']?></td>
		<td>
			<?=$row['taille']?>
			<? if ($i>0) { // on affiche la différence de taille avec la version précédente
				if ($row['taille']-$old_taille != 0) { ?>
					<?= $row['taille']-$old_taille>0 ? '<span class="add-car">(+':'<span class="minus-car">(-' ?> <?=abs($row['taille']-$old_taille)?>)</span>
			<? 	}
			} ?>
		</td>
		<td style="text-align:center;">
<?			if ($i>0) { ?>
				<a class="btn btn" href="diff_devis.php?id=<?=$id_devis?>&id_old=<?=$row['id']?>&id_new=<?=$id_new?>"><i class="icon-exchange"></i></a>
<?			} ?>
		</td>
		<td><input type="checkbox"<?= $i<=1 ? ' checked="checked"':'' ?> id="check_<?=$row['id']?>"/> r<?=$row['id']?></td>
	</tr>
<? 
	$old_taille = $row['taille'];
	$id_new = $row['id'];
	$i++;
} ?>
</tbody>
</table>
</form>
</body>
</html>
<? mysql_close($mysql); ?>