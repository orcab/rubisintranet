<?
include('../../../inc/config.php');

$message ='';

// on met a jour les état envoyée a reflex dans Rubis
if (		isset($_POST['action']) && $_POST['action'] == 'envoi_article'
		&&	isset($_POST['code_article']) && $_POST['code_article']) {
	
	$previous_directory = getcwd();
	chdir('c:/easyphp/www/intranet/scripts/Interfaces Rubis-Reflex') or die("Impossible de changer de répertoire de travail");
	exec('perl export-article-to-reflex.pl --article='.$_POST['code_article']); // envoi la demande de creation a reflex
	chdir($previous_directory) or die("Impossible de revenir au répertoire de travail");

	$message .= "Article envoyé";
}

?>
<html>
<head>
<title>Forcer l'envoi d'article dans Reflex</title>

<style>
body {
	font-family: verdana;
	font-size: 0.8em;
}

h1 {
    font-size: 1.2em;
}

.message {
    color: red;
    font-weight: bold;
    text-align: center;
}


</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--

function verif_form(){
	var form = document.article;
	var erreur = false;

	if (!form.code_article.value) {
		alert("Veuillez préciser un code article");
		erreur = true;
	}

	if (!erreur)
		form.submit();
}

//-->
</script>

</head>
<body>

<div class="message"><?=$message?></div>

<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>

<form name="article" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="action" value="envoi_article"/>
<div style="margin:auto;border:solid 1px grey;padding:20px;width:50%;">
	<h1>Forcer la descente d'un article dans Reflex</h1>
	Code article
	<input type="text" name="code_article" value="" placeholder="code" size="10"/>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Forcer la descente</a>
</div>
</form>
</body>
</html>


