<?
include('../inc/config.php');
session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();
$info_user = $_SESSION['info_user'];

$code_user = $info_user['username'];
$nom_user = $info_user['name'];

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
$entete['SNTLIJ'] = $annee;

// reference
$ref = $_POST['reference'] ;
if ($code_cab && $code_user == '056039') { # commande de la CAB56 --> on met le code adh CAB dans la ref client
	$ref = sprintf('%03d',$code_cab)."/$ref";
}
$entete['SNTRFC'] = substr($ref,0,20);

// chantier
$entete['SNTCHA'] = $code_cab ? sprintf('%03d',$code_cab) : 'SANS'; # code chantier CAB ou 'SANS'


// header
$buffer =  "SNOCLI;SNOBON;SNTROF;SNTCHA;SNTBOS;SNTBOA;SNTBOM;SNTBOJ;SNTLIS;SNTLIA;SNTLIM;SNTLIJ;SNTRFC;SNTRFS;SNTRFA;SNTRFM;SNTRFJ;SNTVTE;SNTTTR;SNTPRO;SNTGAL;SEOLIG;SENART;SENROF;SENTYP;SENQTE;SENCSA\r\n";

// detail
$ligne = 1;
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

	$buffer .= join(';',array(
				$entete['SNOCLI'], # n° client
				date('YmjHis'),	   # numero unique
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
				'EMP',
				'NON',
				'CDC',
				'O',
				$detail['SEOLIG'],
				$detail['SENART'],
				'R',
				$detail['SENTYP'],
				$detail['SENQTE'],
				$detail['SENCSA']
			) // fin array
		)."\r\n";	 // fin join
}


// copie du buffer dans le fichier temp
$ini = parse_ini_file('../scripts/pop2rubis.ini',true);
$TEMP = fopen($ini['file']['path_file'],'a') ; //or die "Ne peux pas creer le fichier CSV temporaire '".$ini['file']['path_temporary_file']."'";
fwrite($TEMP,$buffer);
fclose($TEMP);

//echo $buffer;

?><html>
<head>
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
		<td class="prix"><?=$_SESSION['panier'][$i][QTE] * $_SESSION['panier'][$i][PRIX]?> &euro;</td>
	</tr>	
<?		$total += $_SESSION['panier'][$i][QTE] * $_SESSION['panier'][$i][PRIX];
}
?>
<tr>
	<td colspan="5">&nbsp;</td>
	<td class="prix">Total HT</td>
	<td class="prix"><?=$total?> &euro;</td>
</tr>
</table>

<div class="hide_when_print" style="text-align:center;">
	Votre commande a été envoyé aux services de <?=SOCIETE?> et sera traitée dans les plus brefs délais.<br/><br/>
	<input id="autre_commande" type="button" value="Passer une autre commande" class="button" style="background-image:url(gfx/arrow_right_green_32.png);padding-left:40px;" />
	<input id="print_page" type="button" value="Imprimer cette page" class="button" style="background-image:url(gfx/printer-ok.png);padding-left:40px;" />
	<input id="deconnexion" type="button" value="Déconnexion" class="button annuler" style="background-image:url(gfx/delete_32.png);padding-left:40px;" />
	<br/><br/>
</div>
</div>
</body>
</html>