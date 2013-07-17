<?
include('../../../inc/config.php');

$message ='';

// on met a jour les état envoyée a reflex dans Rubis
if (	isset($_POST['action']) && $_POST['action'] == 'update_etat_reflex'
	&&	isset($_POST['type_cde']) && $_POST['type_cde']
	&&	isset($_POST['num_cde']) && $_POST['num_cde']
	) {
	foreach($_POST as $key => $val) {
		if (preg_match('/^ligne_(.+)$/i',$key,$matches)) {
			list($num_tier,$ligne) = explode('_',$matches[1]);

			if (	($val && !array_key_exists("etat_reflex_${num_tier}_${ligne}",$_POST))	// on vient de cocher la case
				||	(!$val && array_key_exists("etat_reflex_${num_tier}_${ligne}",$_POST))	// on vient de décocher la case
			) {
				$sql = '';
				if 		($_POST['type_cde'] == 'client') {
					$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 set DET06='".(array_key_exists("etat_reflex_${num_tier}_${ligne}",$_POST)?'I':'')."' where NOBON='".mysql_escape_string($_POST['num_cde'])."' and NOCLI='".mysql_escape_string($num_tier)."' and NOLIG='".mysql_escape_string($ligne)."'";
				} elseif($_POST['type_cde'] == 'fournisseur') {
					$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 set CFD31='".(array_key_exists("etat_reflex_${num_tier}_${ligne}",$_POST)?'ENV':'')."' where CFBON='".mysql_escape_string($_POST['num_cde'])."' and NOFOU='".mysql_escape_string($num_tier)."' and CFLIG='".mysql_escape_string($ligne)."'";
				}

				$rubis  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Rubis via ODBC ($LOGINOR_DSN)");
				$res = odbc_exec($rubis,$sql)  or die("Impossible de lancer la modification de ligne : <br/>$sql");
				//$message .="$sql<br>";

			}
		}
	}
	$message .= "Le bon $_POST[type_cde] $_POST[num_cde] a été mis à jour";
}

?>
<html>
<head>
<title>Voir les lignes envoyées à Reflex</title>

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
	var form = document.cde;
	//var value_type_cde = form.type_cde[form.type_cde.selectedIndex].value;
	var erreur = false;

	if (!form.num_cde.value) {
		alert("Veuillez préciser un n° de commande");
		erreur = true;
		
	} else if (form.num_cde.value.length != 6) {
		alert("Le n° de commande doit faire 6 caractères");
		erreur = true;
	}

	if (	!document.getElementById('fournisseur').checked
		&& 	!document.getElementById('client').checked) {
		alert("Veuillez préciser un type de commande");
		erreur = true;
	}

	if (!erreur)
		form.submit();
}

//-->
</script>

</head>
<body>
<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>

<div class="message"><?=$message?></div>

<form name="cde" method="POST" action="lignes.php">
<div style="margin:auto;border:solid 1px grey;padding:20px;width:50%;">
	<h1>Voir les lignes qui ont été envoyés à Reflex</h1>
	N° de cde Rubis
	<input type="text" name="num_cde" value="" placeholder="N° de cde" size="10" maxlength="6"/>
	<label for="fournisseur" style="margin-right:1em;">	<input type="radio" name="type_cde" value="fournisseur" id="fournisseur"/>Fournisseur</label>
	<label for="client" style="margin-right:1em;">		<input type="radio" name="type_cde" value="client" id="client"/>Client</label>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Voir les lignes</a>
</div>
</form>
</body>
</html>