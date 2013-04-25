<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_GET['debug']) ? 1:0);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '' ;

$id_devis = null;
$id_new_devis = null;
$id_old_devis = null;

if (isset($_GET['id']) && $_GET['id']) {
	$id_devis = mysql_escape_string($_GET['id']);
} else {
	$message = "Erreur : Aucun numéro de devis spécifié";
}

if (isset($_GET['id_old']) && $_GET['id_old']) {
	$id_old_devis = mysql_escape_string($_GET['id_old']);
} else {
	$message = "Erreur : Aucun numéro de devis OLD spécifié";
}

if (isset($_GET['id_new']) && $_GET['id_new']) {
	$id_new_devis = mysql_escape_string($_GET['id_new']);
} else {
	$id_new_devis = $id_old_devis;
}
?>
<html>
<head>
<title>Différence de version d'un devis</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<link rel="shortcut icon" type="image/x-icon" href="../gfx/creation_devis.ico" />
<style type="text/css">@import url(../js/boutton.css);</style>
<script language="JavaScript" SRC="../js/jquery.js"></script>
<script language="JavaScript" SRC="../js/mobile.style.js"></script>
<style type="text/css">@import url(diff.css);</style>

<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../js/fontawesome/css/icon-custom.css">
</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div style="color:red;"><?=$message?></div>
<br/>
<a class="btn" href="diff_liste.php?id=<?=$id_devis?>"><i class="icon-arrow-left"></i> Revenir aux versions</a>
<br/><br/>
<div class="diffDeleted" style="width:10em;padding:2px;">Ligne supprimée</div>
<div class="diffInserted" style="width:10em;padding:2px;">Ligne ajoutée</div>
<br/><br/>
<?
$sql = <<<EOT
SELECT
	devis_history.id,devis,DATE_FORMAT(`date`,'%w') AS date_jour, DATE_FORMAT(`date`,'%d/%m/%Y %H:%i') AS date_formater,LENGTH(devis) AS taille,`date`, LEFT(devis,2) as COMPRESS,
	devis_history.user as ip_user, prenom as user_name

FROM
				devis_history
	left join 	employe
		on employe.ip = devis_history.user
WHERE
	devis_history.id='$id_old_devis' OR devis_history.id='$id_new_devis' ORDER BY `date` DESC LIMIT 0,2
EOT;
$res = mysql_query($sql) or die("Impossible de récupérer les deux enregistrements");
$devis_old = '';
$devis_new = '';
while($row = mysql_fetch_array($res)) {
	if ($row['COMPRESS'] == 'xœ') // compression GZIP
		$row['devis'] = gzuncompress($row['devis']);

	if (!$devis_new)
		$devis_new = $row['devis'];
	else
		$devis_old = $row['devis'];
}
?>

<style>
a {
	text-decoration:none;
}

th a {
	font-weight:normal;
}

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
caption {
    background-color: #DDD;
    font-size: 1.1em;
    padding: 2px;
}
th {
    font-size: 0.8em;
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

$header	 = "<caption>Devis n° $id_devis</caption>\n";
$header .= "<tr>";
if (isset($data[1])) {
	$row = $data[1];
	$res = mysql_query("SELECT id FROM devis_history WHERE id_devis='$id_devis' AND `date`<'$row[date]' ORDER BY `date` DESC LIMIT 0,1") or die("Impossible de récupérer la version précédente : ".mysql_error());
	
	$header .= "<th>";
	$tmp = mysql_fetch_array($res);
	if (mysql_num_rows($res))
		$header .= "<a class='btn btn-small' href='$_SERVER[PHP_SELF]?id=$id_devis&id_old=$tmp[id]&id_new=$id_old_devis'><i class='icon-chevron-left'></i> Version précédente</a>";
	else
		$header .= "1ère version";
	$header .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;r$row[id] du ".$jours_mini[$row['date_jour']]." $row[date_formater] par $row[user_name] ($row[taille] car)</th>";
}

if (isset($data[0])) {
	$row = $data[0];
	$res = mysql_query("SELECT id FROM devis_history WHERE id_devis='$id_devis' AND `date`>'$row[date]' ORDER BY `date` ASC LIMIT 0,1") or die("Impossible de récupérer la version précédente : ".mysql_error());
	

	$header .= "<th>r$row[id] du ".$jours_mini[$row['date_jour']]." $row[date_formater] par $row[user_name] ($row[taille] car)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$tmp = mysql_fetch_array($res);
	if (mysql_num_rows($res))
		$header .= "<a class='btn btn-small' href='$_SERVER[PHP_SELF]?id=$id_devis&id_old=$id_new_devis&id_new=$tmp[id]'>Version suivante <i class='icon-chevron-right'></i></a>";
	else
		$header .= "Version actuel";

	$header .= "</th>";
}

$header .= "<tr>";

$html = preg_replace('/<table class="diff">\s*<tr>/', '<table class="diff"><tr>'.$header, $html);

echo $html;
?>
</body>
</html>
<? mysql_close($mysql); ?>