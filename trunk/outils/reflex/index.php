<?
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$droit = recuperer_droit();

?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
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

</style>

</head>
<body style="margin:0px;padding:0px;">
<div style="width:100%;background-color:#DDD;margin-bottom:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;"><a href="../../index.php">Intranet <?=SOCIETE?></a> &gt;&gt; <a href="../index.php">OUTILS</a> &gt;&gt; Reflex</div>

<center>
<table style="width:70%;text-align:center;border:solid 1px #AAA;">
<tr>
	<td style="width:33%;padding-bottom:20px;"><a href="lignes_envoyees/index.php"><img src="lignes_envoyees/gfx/lignes_envoyees.png"><br/>Lignes envoyées à Reflex</a></td>
	<td style="width:33%;padding-bottom:20px;"><a href="stock_chute/index.php"><img src="stock_chute/gfx/stock_chute.png" style="width:90px;"><br/>Stock des produits</a></td>
	<td style="width:33%;padding-bottom:20px;"><a href="envoi_article/index.php"><img src="envoi_article/gfx/envoi_article.png" style="width:90px;"><br/>Forcer la descente d'article dans Reflex</a></td>
</tr>
<tr>
	<td style="width:33%;padding-bottom:20px;"></td>
	<td style="width:33%;padding-bottom:20px;"><a href="prepa_progress/index.php"><img src="prepa_progress/gfx/prepa_progress.png" style="width:90px;"><br/>Avancement des prepa</a></td>
	<td style="width:33%;padding-bottom:20px;"></td>
</tr>
</table>
</center>
</body>
</html>