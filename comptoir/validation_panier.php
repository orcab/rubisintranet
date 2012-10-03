<?
include('../inc/config.php');
session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();
$info_user = $_SESSION['info_user'];

$code_user = $info_user['username'];
$nom_user = $info_user['name'];

?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/tactile.css);</style>
<style>
body {
	background-color: white;
	font-family: verdana,helvetica;
	margin:0px;
	margin-top:5px;
}
a img { border:none; }
.menu {  }
h1 a:hover { color:yellow; }

td {
	text-decoration: none;
}

a {
	text-decoration: none;
	color: black;
}

ul {
	padding-left:15px;
	list-style-image:url(gfx/puce.png);
}

legend {
	font-weight:bold;
	color:#0C3A6D;
}



div#cadre-panier,div#cadre-reference {
	width:90%;
	margin:5px;
	margin-top:10px;
	margin-bottom:10px;
	padding:0px;
	border:solid 1px #0C3A6D;
	margin-left:auto;
	margin-right:auto;
}

div#cadre-panier {
	margin-top:2em;
}


div#ou {
	text-align:center;
	font-weight:bold;
	font-size:2em;
	margin-left:auto;
	margin-right:auto;
	margin-top:1em;
	margin-bottom:1em;
}

div#cadre-panier h1,div#cadre-reference h1 {
	background-color:#0C3A6D;
	color:white;
	text-align:center;
	padding:2px;
	margin:0px;
}

h1 a {
	color:white;
}

div#panier {
	margin-left:0px;
	margin-right:0px;
	margin-bottom:5px;
}

div#panier table {
	width:100%;
	margin:0px;	
	border-spacing: 0px;
	border-collapse: collapse;
}

div#panier td {
	padding:2px;
	vertical-align:top;
	
}

div#panier tr { border-bottom:dotted 1px grey; }
div#panier tr:last-child { border-bottom:none; }

div#panier th {
	background:#F1F1F1;
	text-align:left;
}

div#livraison table {
	width:100%;
	margin-top:10px;
}

td.code_article {
	color:#529214;
	font-weight:bold;
}

td.qte { font-weight:normal; }
input.qte { text-align:right; }
ul#adresses { margin:0px; }


input.accept {
	background-image:url(gfx/validate_32.png);
	background-position: center center;
	width:50px;
}

input.delete {
	background-image:url(gfx/delete_32.png);
	background-position: center center;
	width:50px;
}

</style>

<!--[if IE]>
<style>
div#panier table { width:97%; }
div#livraison table { width:97%; }
ul#adresses { margin:0px; margin-left:10px; margin-bottom:5px; }
div#panier td { border-bottom:dotted 1px grey; }
</style>
<![endif]-->

<style type="text/css">@import url(../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../js/jscalendar/calendar-setup.js"></script>
<script language="javascript" src="../js/jquery.js"></script>
<script language="javascript">
<!--

function modif_panier(no_ligne,conditionnement) {

	var qte = document.panier.elements['qte_'+no_ligne].value ;

	if (conditionnement > 1) { // vérifie le conditionnement
		if ((qte % conditionnement) != 0) {
			var multiple_sup = Math.ceil(qte / conditionnement) * conditionnement ;
			if (confirm("La quantité commandée ("+qte+") n'est pas un multiple de "+conditionnement+"\nVoulez vous arrondir à "+multiple_sup+" ?")) {
				document.panier.elements['qte_'+no_ligne].value = multiple_sup;
				qte = multiple_sup;
			} else {
				return;
			}
		}
	}

	if (qte > 0) {
		$.ajax({url: 'ajax.php',
				type: 'GET',
				data: 'what=modif_panier&no_ligne='+escape(no_ligne)+'&qte='+qte,
				success: function(result){ document.location.href="validation_panier.php"; }	
		});
	} else {
		alert("Quantité à 0. Saisissez une quantité");
	}
}


function delete_panier(no_ligne) {
	if (confirm("Voulez vous vraiment supprimer cet article ?")) {
		$.ajax({
				url: 'ajax.php',
				type: 'GET',
				data: 'what=delete_panier&no_ligne='+escape(no_ligne),
				success: function(result){
							//var json = eval('(' + result + ')') ;
							document.location.href="validation_panier.php";
						}	
		});
	}
}



function validation_commande(){

	if (!document.panier.reference.value) {
		alert("Veuillez indiquer une référence pour cette commande");
		$('#reference').css({'background':'#F88'});
	} else {
		return true;
	}
	return false;
}


// une fois la page chargé.
$(document).ready(function() {
	// click sur un champs texte, on selectionne tout le texte par défaut
	$('input[type=text]').focusin(function(){
		$(this).select();
	});
});

//-->
</script>

</head>

<body>
<form name="panier" method="POST" action="envoi_commande.php" style="margin-bottom:5px;" onsubmit="return validation_commande();">

<div id="cadre-panier">
	<h1>Bonjour <?=$nom_user?>&nbsp;&nbsp;&nbsp;Votre panier</h1>
	<div id="panier">
		<table>
			<tr>
				<th>Code</th>
				<th>Désignation</th>
				<th>Qte</th>
				<th>Fournisseur</th>
				<th>Réf</th>
				<th>PU. HT</th>
				<th>Total HT</th>
			</tr>

<?		$total = 0 ;
		if (isset($_SESSION['panier'])) {
			for($i=0 ; $i<sizeof($_SESSION['panier']) ; $i++) { ?>
				<tr>
					<td class="code_article"><?=$_SESSION['panier'][$i][CODE_ARTICLE]?></td>
					<td class="designation">
						<?=ereg_replace("\n","<br/>",$_SESSION['panier'][$i][DESIGNATION])?>
						<!-- condtionnement et unité -->
<?						if ($_SESSION['panier'][$i][CONDITIONNEMENT] > 1) { ?>
							<strong class="condi">Vendu par <?=$_SESSION['panier'][$i][CONDITIONNEMENT]?><?=$_SESSION['panier'][$i][UNITE]?></strong>
<?						} ?>
					</td>
					<td class="qte" nowrap>
						<input type="text" class="qte" name="qte_<?=$i?>" size="2" value="<?=$_SESSION['panier'][$i][QTE]?>" />
						<?= $_SESSION['panier'][$i][CONDITIONNEMENT] > 1 ? $_SESSION['panier'][$i][UNITE] :'' ?>
						<input type="button" class="accept button" onclick="modif_panier(<?=$i?>,'<?=$_SESSION['panier'][$i][CONDITIONNEMENT]?>');"/>
						<input type="button" class="delete button" onclick="delete_panier(<?=$i?>);"/>
					</td>
					<td><?=$_SESSION['panier'][$i][FOURNISSEUR]?></td>
					<td style="font-weight:bold;"><?=$_SESSION['panier'][$i][REF_FOURNISSEUR]?></td>
					<td><?=$_SESSION['panier'][$i][PRIX]?>&euro;</td>
					<td style="font-weight:bold;"><?=$_SESSION['panier'][$i][PRIX] * $_SESSION['panier'][$i][QTE]?>&euro;</td>
				</tr>
<?			
				$total += $_SESSION['panier'][$i][PRIX] * $_SESSION['panier'][$i][QTE];
			}
		}		?>
			<tr>
				<td colspan="4"></td>
				<td colspan="2" style="font-size:1.1em;font-weight:bold;color:#529214;">Total HT</td>
				<td style="font-size:1.1em;font-weight:bold;color:#529214;"><?=$total?>&euro;</td>
			</tr>
		</table>
	</div>
	<h1><a href="interface.php">Continuer a remplir le panier <img src="gfx/down-arrow-32.png" style="vertical-align:bottom;"/></a></h1>
</div>

<div id="ou">OU</div>

<div id="cadre-reference">
	<h1>Finaliser votre commande :</h1>
	<br/>
	<center>
		<span style="font-size:1.3em;font-weight:bold;">Indiquer une référence client :</span> <input type="text" id="reference" name="reference" value="" size="35" maxlength="20"/><br/><br/>
		<input class="button valider" type="submit" style="background-image:url(gfx/validate_32.png);padding-left:40px;font-size:1.5em;" value="Validation définitive"/>
		<br/><br/>
	</center>
</div>
	</form>
	</body>
</html>