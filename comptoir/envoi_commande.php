<?
include('../inc/config.php');
session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();
$info_user = $_SESSION['info_user'];

$code_user = $info_user['username'];
$nom_user = $info_user['name'];

// connexion à la base MYSQL
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

// connexion à la base Loginor
$loginor  	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");


// pour les clients CAB
$code_cab = '';
if (preg_match('/^CAB(\d+)$/i',$code_user,$matches)) {
	$code_user = '056039';
	$code_cab = $matches[1];
}

$entete = array();

// date
$now	= date('d/m/Y');
$siecle = substr(date('Y'),0,2);
$annee	= substr(date('Y'),2,2);
$mois	= date('m');
$jour	= date('d');
$entete['SNOCLI'] = $code_user ;
$entete['SNTBOS'] = $siecle;
$entete['SNTBOA'] = $annee;
$entete['SNTBOM'] = $mois;
$entete['SNTBOJ'] = $annee;
$entete['SNTLIS'] = $siecle;
$entete['SNTLIA'] = $annee;
$entete['SNTLIM'] = $mois;
$entete['SNTLIJ'] = $jour;

// reference
$ref = $_POST['reference'] ;
if ($code_cab && $code_user == '056039') { # commande de la CAB56 --> on met le code adh CAB dans la ref client
	$ref = sprintf('%03d',$code_cab)."/$ref";
}
$entete['SNTRFC'] = substr($ref,0,20);

// chantier
$entete['SNTCHA'] = $code_cab ? sprintf('%03d',$code_cab) : 'SANS'; # code chantier CAB ou 'SANS'

// header
$buffer = "SNOCLI;SNOBON;SNTROF;SNTCHA;SNTBOS;SNTBOA;SNTBOM;SNTBOJ;SNTLIS;SNTLIA;SNTLIM;SNTLIJ;SNTRFC;SNTRFS;SNTRFA;SNTRFM;SNTRFJ;SNTVTE;SENTCD;SNTTTR;SNTPRO;SNTGAL;SEOLIG;SENART;SENROF;SENTYP;SENNBR;SENQTE;SENCSA;SNTCAM;SNTNOM;SNTCA1;SNTCA2;SNTCRU;SNTCVI;SNTCCP;SNTCBD;SENRP1\r\n";

// detail des articles
$ligne = 1;
$date = date('YmjHis');
for($i=0 ; $i<sizeof($_SESSION['panier']) ; $i++) {
	
	$detail = array();

	$detail['SEOLIG'] = sprintf('%03d',$ligne);
	$detail['SENART'] = $_SESSION['panier'][$i][CODE_ARTICLE];
	$detail['SENTYP'] = '';
	$detail['SENQTE'] = preg_replace('/\./',',',$_POST["qte_$i"]);
	$detail['SENCSA'] ='';
	$ligne += 2;

	if ($entete['SNOCLI'] == 'benjamin')
		$entete['SNOCLI'] = 'POULAI'; // patch pour le code client de benjamin


	# vérifie si le produit est commandable par nombre de conditionnment ou par unité
	$nombre = '';
	$sql = "select CONDI as CONDITIONNEMENT, CDCON as CONDITIONNEMENT_DIVISBLE from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where NOART='".$detail['SENART']."'";
	$res = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete : $sql");
	while($row = odbc_fetch_array($res)) {
		if ($row['CONDITIONNEMENT_DIVISBLE'] == 'NON' && $row['CONDITIONNEMENT'] && $row['CONDITIONNEMENT']>1) { # si un condi non divible est renseigné, on doit le commandé par nombre
			$nombre = ceil($detail['SENQTE'] / $row['CONDITIONNEMENT']);
		}
	}
	unset($row);
	
	$remise = remiseArticle($_SESSION['panier'][$i],$code_user);

	$buffer .= join(';',array(
				$entete['SNOCLI'], # n° client
				$date,			   # numero unique
				'R',               # R
				$entete['SNTCHA'], # code chantier
				$entete['SNTBOS'], # date bon
				$entete['SNTBOA'],
				$entete['SNTBOM'],
				$entete['SNTBOJ'],
				$entete['SNTLIS'], # date de liv
				$entete['SNTLIA'],
				$entete['SNTLIM'],
				$entete['SNTLIJ'],
				$entete['SNTRFC'], # reference
				$entete['SNTBOS'], # date cde client
				$entete['SNTBOA'],
				$entete['SNTBOM'],
				$entete['SNTBOJ'],
				'LIV',
				'',
				'NON',
				'CDC',
				'O',
				$detail['SEOLIG'],
				$detail['SENART'],
				'R',
				$detail['SENTYP'],
				$nombre,
				$detail['SENQTE'],
				$detail['SENCSA'],
				'CPT',
				$nom_user,
				'Comptoir MCS',
				'',
				'',
				'',
				'',
				'',
				$remise		# remise eventuelle
			) // fin array
		)."\r\n";	 // fin join
}


// copie du buffer dans le fichier CSV
if ($_SERVER['SERVER_ADDR'] == '10.211.14.46') { // serveur de test
	$csv_filename = 'WEB/CPT.CSV';

} else {	// serveur de prod

	$letter= 'T';
	$location = '\\\\10.211.200.1\\QDLS\\AFA';
	$user = LOGINOR_USER;
	$pass = LOGINOR_PASS;
	system("net use $letter: \"$location\" $pass /user:$user /persistent:no>nul 2>&1");
	$csv_filename = "$letter:/WEB/CDC.CSV";
	//$csv_filename = "$letter:/TEST.CSV";
}

$CSV = fopen($csv_filename,'a') or die("Ne peux pas ouvrir le fichier CSV '$csv_filename'"); // ouvre en mode ajout (append)
fwrite($CSV,$buffer) or die("Ne peux pas ecrire les données dans le fichier CSV '$csv_filename'");
fclose($CSV);

//echo $buffer;

?><html>
<head>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../js/fontawesome/css/bootstrap.css">
<link rel="stylesheet" href="../js/fontawesome/css/font-awesome.min.css">
<!--[if IE 7]>
<link rel="stylesheet" href="../js/fontawesome/css/font-awesome-ie7.min.css">
<![endif]-->
<link rel="stylesheet" href="../js/fontawesome/css/icon-custom.css">
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/tactile.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<style>
body {
	background-color: white;
	font-family: verdana,helvetica;
	margin:10px;
	margin-top:5px;
}
a img { border:none; }

h1 {
	font-size:1.1em;
}

table {
	margin-top:10px;
	margin-bottom:10px;
	border:solid 1px grey;
	width:100%;
}

td,th {
	border:solid 1px grey;
	font-size:0.9em;
}

th {
	text-align:right;
}

td.prix {
	text-align:right;
}

@media print {
	.hide_when_print { display:none; }
}

div#cadre-panier {
	width:90%;
	margin:5px;
	margin-top:2em;
	padding:0px;
	border:solid 1px #0C3A6D;
	margin-left:auto;
	margin-right:auto;
}

div#cadre-panier h1 {
	background-color:#0C3A6D;
	color:white;
	text-align:center;
	padding:2px;
	margin:0px;
}

.remise {
	text-align:center;
}

</style>

<script type="text/javascript">
<!--

$(document).ready(function() {
	// clique sur le bouton autre commande
	$('body').delegate('#autre_commande','click',function(){
		document.location.href='interface.php?autre_commande=1';
	})

	// clique sur le bouton deconnexion
	$('body').delegate('#deconnexion','click',function(){
		document.location.href='index.php?deconnexion=1';
	});

	// clique sur le bouton print
	$('body').delegate('#print_page','click',function(){
		window.print()
	});
});
//-->
</script>

</head>

<body>

<div id="cadre-panier">
<h1>Votre commande est partie en préparation</h1>

<table cellspacing="0" cellpadding="5"><caption>Données livraison et commentaires</caption>
	<tr><th>Date</th><td><?=$now?></td></tr>
	<tr><th>Réf</th><td><?=$_POST['reference']?></td></tr>
</table>

<table cellspacing="0" cellpadding="2"><caption>Commande WEB de <?=$nom_user?> (<?=$code_user?>)</caption>
<tr>
	<tr>
		<th>Code</th>
		<th>Désignation</th>
		<th>Qte</th>
		<th>Fournisseur</th>
		<th>Réf</th>
		<th>PU. HT</th>
		<th class="remise">Remise</th>
		<th>Total HT</th>
	</tr>
<?
// on parcours les lignes de la commande
$total = 0;
for($i=0 ; $i<sizeof($_SESSION['panier']) ; $i++) { ?>
	<tr>
		<td><font color='#529214'><b><?=$_SESSION['panier'][$i][CODE_ARTICLE]?></b></font></td>
		<td><?=$_SESSION['panier'][$i][DESIGNATION]?></td>
		<td><font color='#529214'><b><?=$_SESSION['panier'][$i][QTE]?></b></font></td>
		<td><?=$_SESSION['panier'][$i][FOURNISSEUR]?></td>
		<td><font color='#529214'><b><?=$_SESSION['panier'][$i][REF_FOURNISSEUR]?></b></font></td>
		<td class="prix"><?=$_SESSION['panier'][$i][PRIX]?> &euro;</td>
<?
			$remise = remiseArticle($_SESSION['panier'][$i],$code_user);
			$total_ligne_sans_remise = $_SESSION['panier'][$i][QTE] * $_SESSION['panier'][$i][PRIX];
			$total_ligne_avec_remise = $total_ligne_sans_remise - $remise/100*$total_ligne_sans_remise;
?>
		<td class="remise"><?=($remise ? $remise.'%':'&nbsp;')?></td>
		<td class="prix"><?=sprintf('%.02f',$total_ligne_avec_remise)?> &euro;</td>
	</tr>	
<?		$total += $total_ligne_avec_remise;
}
?>
<tr>
	<td colspan="6">&nbsp;</td>
	<td class="prix">Total HT</td>
	<td class="prix"><?=sprintf('%0.2f',$total)?> &euro;</td>
</tr>
</table>

<div class="hide_when_print" style="text-align:center;">
	Votre commande a été envoyé aux services de <?=SOCIETE?> et sera traitée dans les plus brefs délais.<br/><br/>

	<div class="hide_when_print" style="text-align:center;margin-top:1em; margin-bottom:10px;">
		<a class="btn" href="javascript:window.print();"><i class="icon-print icon-2x"></i> Imprimer cette page</a>&nbsp;&nbsp;&nbsp;
		<a class="btn" href="interface.php?autre_commande=1"><i class="icon-shopping-cart icon-2x"></i> Faire une autre commande</a>&nbsp;&nbsp;&nbsp;
		<a class="btn btn-danger" href="index.php?deconnexion=1"><i class="icon-signout icon-2x"></i> Deconnexion</a>
	</div>

</div>
</div>
</body>
</html>