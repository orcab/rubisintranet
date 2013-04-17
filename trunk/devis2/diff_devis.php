<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_GET['debug']) ? 1:0);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '' ;

$id_devis = null;
$id_new_devis = null;
$id_old_devis = null;

if (isset($_GET['id']) && $_GET['id']) {
	$id_devis = mysql_escape_string($_GET['id']);
} else {
	$message = "Erreur : Aucun num�ro de devis sp�cifi�";
}

if (isset($_GET['id_old']) && $_GET['id_old']) {
	$id_old_devis = mysql_escape_string($_GET['id_old']);
} else {
	$message = "Erreur : Aucun num�ro de devis OLD sp�cifi�";
}

if (isset($_GET['id_new']) && $_GET['id_new']) {
	$id_new_devis = mysql_escape_string($_GET['id_new']);
} else {
	$id_new_devis = $id_old_devis;
}
?>
<html>
<head>
<title>Diff�rence de version d'un devis</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<link rel="shortcut icon" type="image/x-icon" href="../gfx/creation_devis.ico" />
<style type="text/css">@import url(../js/boutton.css);</style>
<script language="JavaScript" SRC="../js/jquery.js"></script>
<script language="JavaScript" SRC="../js/mobile.style.js"></script>
<style type="text/css">@import url(diff.css);</style>
</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div style="color:red;"><?=$message?></div>
<br/>
<a href="diff_liste.php?id=<?=$id_devis?>">Revenir aux versions</a>
<br/><br/>
<div class="diffDeleted" style="width:10em;padding:2px;">Ligne supprim�e</div>
<div class="diffInserted" style="width:10em;padding:2px;">Ligne ajout�e</div>
<br/><br/>
<?

$res = mysql_query("SELECT id,devis,DATE_FORMAT(`date`,'%d/%m/%Y %H:%i') AS date_formater,LENGTH(devis) as taille FROM devis_history where id='$id_old_devis' or id='$id_new_devis' ORDER BY `date` DESC LIMIT 0,2") or die("Impossible de r�cup�rer les deux enregistrements");
$devis_old = '';
$devis_new = '';
while($row = mysql_fetch_array($res)) {
	if (!$devis_new)
		$devis_new = $row['devis'];
	else
		$devis_old = $row['devis'];
}
?>

<style>
table.diff {
	border-collapse: collapse;
	border:solid 1px grey;
}

table.diff th {
	border-right:solid 1px grey;
	border-bottom:solid 1px grey;
}

table.diff td {
	vertical-align : top;
 	white-space    : pre;
 	white-space    : pre-wrap;
 	font-family    : monospace;
 	padding-left:1em;
 	padding-right:1em;
 	border-right:solid 1px grey;
}

.diffDeleted {
	background-color: #FAA;
	color:red;
}

.diffInserted {
	background-color: lightgreen;
	color:green;
}
.diffUnmodified {
	background-color:#FFC;
}
</style>

<?
require_once '../inc/diff/class.Diff.php';
$html = Diff::toTable(Diff::compare($devis_old,$devis_new));


$data = array();
// reset du cursor
mysql_data_seek($res,0);
while($row = mysql_fetch_array($res)) {
	$data[] = $row;
	
}

$data = array_reverse($data);
$header  = "<tr>";
foreach ($data as $row) {
	$header .= "<th>Version $row[id] du $row[date_formater] ($row[taille] car)</th>";
}
$header .= "<tr>";

$html = preg_replace('/<table class="diff">\s*<tr>/', '<table class="diff"><tr>'.$header, $html);

echo $html;
?>
</body>
</html>
<? mysql_close($mysql); ?>