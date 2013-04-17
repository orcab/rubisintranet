<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_GET['debug']) ? 1:0);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '' ;

$id_devis = 0;
if (isset($_GET['id']) && $_GET['id']) {
	$id_devis = mysql_escape_string($_GET['id']);
} else {
	$message = "Erreur : Aucun num�ro de devis sp�cifi�";
}
?>
<html>
<head>
<title>Diff�rence d'un devis</title>
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
}
a:hover {
    text-decoration: underline;
}
.add-car { color:green; }
.minus-car { color:red; }
</style>
<style type="text/css">@import url(../js/boutton.css);</style>
<script language="JavaScript" SRC="../js/jquery.js"></script>
<script language="JavaScript" SRC="../js/mobile.style.js"></script>
<SCRIPT LANGUAGE="JavaScript">
<!--

$(document).ready(function(){
	
	// v�rifie que quand on coche une case, on n'a pas 3 cases coch�es en m�me temps
	$('input[type=checkbox]').bind('click',function(){
		if ($('input:checked').length > 2) {
			alert("Vous ne pouvez pas comparer plus de 2 historiques en m�me temps");
			$(this).removeAttr('checked');
		}
	});


	// affiche les diff�rences des options selectionn�es
	$('input#analyse_diff').bind('click',function(){
		if ($('input:checked').length == 2) {
			var id1 = $('input:checked').first().attr('id').replace(/^check_/,'');
			var id2 = $('input:checked').last().attr('id').replace(/^check_/,'');

			document.location.href=	'diff_devis.php?id=<?=$id_devis?>&'+
									'id_old=' +  (id1<=id2?id1:id2) + '&' +
									'id_new=' + (id1>id2?id1:id2) ;
		} else {
			alert("Vous ne pouvez comparer que 2 historiques en m�me temps");
		}
	});

});

//-->
</SCRIPT>
</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div style="color:red;"><?=$message?></div>

<br/>
<a href="historique_devis.php">Revenir � l'historique des devis</a>

<form name="diff_devis" action="diff_devis.php" method="POST">
<table id="diff">
<thead>
	<tr>
		<th>Utilisateur</th>
		<th>Date</th>
		<th>Taille</th>
		<th>Diff</th>
		<th><input type="button" id="analyse_diff" value="Afficher les diff�rences"/></th>
	</tr>
</thead>
<tbody>
<?
$i=0;
$id_new = '';
$old_taille = 0;
$res = mysql_query("SELECT id,user,DATE_FORMAT(`date`,'%d/%m/%Y %H:%i') AS date_formater,LENGTH(devis) as taille FROM devis_history WHERE id_devis='$id_devis' ORDER BY `date` DESC") or die("Impossible de selectionn� la liste des diff�rences : ".mysql_error());
while ($row = mysql_fetch_array($res)) { ?>
	<tr>
		<td><?=$row['user']?></td>
		<td><?=$row['date_formater']?></td>
		<td>
			<?=$row['taille']?>
			<? if ($i>0) { // on affiche la diff�rence de taille avec la version pr�c�dente
				if ($row['taille']-$old_taille != 0) { ?>
					<?= $row['taille']-$old_taille>0 ? '<span class="add-car">(+':'<span class="minus-car">(-' ?> <?=abs($row['taille']-$old_taille)?>)</span>
			<? 	}
			} ?>
		</td>
		<td><a href="diff_devis.php?id=<?=$id_devis?>&id_old=<?=$row['id']?>&id_new=<?=$id_new?>"><?= $i<=0 ? 'actuel':'diff' ?></a></td>
		<td><input type="checkbox"<?= $i<=1 ? ' checked="checked"':'' ?> id="check_<?=$row['id']?>"/> <?=$row['id']?></td>
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