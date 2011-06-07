<?
include('../inc/config.php');

$start = microtime(true);

?><html>
<head>
<title>Création d'étiquette expo</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<script type="text/javascript" src="../js/jquery.js"></script>
<script language="javascript">

var TVA = 0.196;

function refresh_etiquette(sel,id) {
	var box = sel[sel.selectedIndex].value;
	
	if (box) {
		$('#loading').css('visibility','visible'); // affiche le loading
		$('#etiquette'+id).html('');

		// va chercher le détail du box selectionné
		$.getJSON('ajax_etiquette_expo.php', { 'what':'get_detail_box', 'val': box  } ,
			function(data){
				// classe le tableau des article en fonction des sousbox pour les mettre ensembles
				var ordre_sousbox = {};
				for(article in data) {
					if (data[article].sousbox) {
						if (typeof(ordre_sousbox[data[article].sousbox]) == 'undefined') // tableau pas encore créé
							ordre_sousbox[data[article].sousbox] = [];
						ordre_sousbox[data[article].sousbox].push(data[article]);
					} else {
						if (typeof(ordre_sousbox['commun']) == 'undefined') // tableau pas encore créé
							ordre_sousbox['commun'] = [];
						ordre_sousbox['commun'].push(data[article]);
					}
				}

				// affiche les articles en parcourant les sous box
				var html = '<table class="articles"><caption>Box '+box+'</caption><tbody class="sousbox">';
				var total = 0;
				var old_sousbox = '';

				for(sousbox in ordre_sousbox) {
					if (sousbox != old_sousbox) // changement de sous box, on déclare un nouveau group
						html += '</tbody><tbody class="espacement"><tr><td colspan="3">&nbsp;</td></tr></tbody><tbody class="sousbox">';

					for(article in ordre_sousbox[sousbox]) {
						var erreur = '';
						var detail = ordre_sousbox[sousbox][article];

						if (!detail.px_public)
							erreur += "La référence fournisseur n'a pas été trouvé dans le catalogue fournisseur";

						html += '<tr>'+
								'<td class="fournisseur">'+
								'<div class="fournisseur">'+detail.fournisseur+'</div>'+
								'<div class="reference">'+detail.reference+'</div></td>'+
								'<td class="designation">'+(detail.qte > 1 ? '<strong>x'+detail.qte+'</strong> ':'') + detail.designation+
								'<div class="hide_when_print" style="color:green;">Code : '+detail.code_expo+'</div>'+
								'<div class="erreur">'+erreur+'</div></td>'+
								'<td class="prix" nowrap="nowrap">'+(detail.qte > 1 ? '<span style="font-style:normal;">'+detail.qte+'x</span> ':'') + (detail.px_public ? (detail.px_public * TVA + detail.px_public).toFixed(2)  + '&nbsp;&euro; <span class="ttc">ttc</span>':'NC')+'</td>'+
								'</tr>';
					}

					old_sousbox = sousbox;
				}
				html += '</tbody></table>';

				$('#etiquette'+id).append(html);
				$('#loading').css('visibility','hidden'); // supprime le loading
			}
		);
	}
}

</script>

<style>
body {
    font-family: verdana;
    font-size: 0.8em;
}

div.choix-box {
    border: medium none;
    float: left;
    text-align: center;
    width: 45%;
}

div#loading {
    text-align: center;
    visibility: hidden;
    width: 100%;
}

div.debug {
    color: grey;
    display: none;
    font-size: 0.7em;
}

table#etiquette {
    font-family: verdana;
    font-size: 8px;
    height: 190mm;
    width: 285mm;
}

#etiquette1, #etiquette2 {
    height: 100%;
    padding: 0 0.5cm 0.5cm;
    vertical-align: top;
    width: 50%;
}

td#etiquette1 { border-right: 1px dotted black; }
td#etiquette2 { }

table.articles {
    border-collapse: collapse;
    width: 100%;
}

table.articles caption {
    font-size: 1.5em;
    font-weight: bold;
    margin: 0 0 5px;
    text-align: center;
}

table.articles tbody.sousbox { border: solid 1px black; }

table.articles tbody.espacement { height: 1em; }

div.fournisseur { font-weight: bold; }

td.prix {
    font-weight: bold;
    text-align: right;
}

td.total { text-align: right; }

table.articles td {
    border-bottom: 1px dotted black;
    font-size: 0.7em;
    vertical-align: top;
}

.erreur { color: red; }

td.designation {
    font-style: italic;
    padding-left: 0.5cm;
}

.prix-conseille {
    font-size: 1.5em;
    text-align: left;
}

.ttc { font-style:normal; }

@media print {
	.hide_when_print { display:none; }
}
</style>

<body>
<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<form method="post" action="" name="etiquette_expo" id="etiquette_expo" class="hide_when_print">
<fieldset id="choix_box">
    <legend>Listes des box :</legend>
<?
	// va récupérer la liste des box dispo dans les cases localisation des fiches de stocks
	$sql = <<<EOT
select
	S.LOCAL,S.LOCA2,S.LOCA3
from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 A
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 S
		on  A.NOART=S.NOART and S.DEPOT='${LOGINOR_DEPOT}'
where
	A.ACTIV = '00S'
and (LOCAL<>'' or LOCA2<>'' or LOCA3<>'')
group by LOCAL,LOCA2,LOCA3
EOT;

	$locals = array();

	echo "<div class='debug'>Avant connexion : ".(microtime(true) - $start)."</div>";
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	echo "<div class='debug'>Apres connexion : ".(microtime(true) - $start)."</div>";
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");
	echo "<div class='debug'>Apres requette : ".(microtime(true) - $start)."</div>";

	while($row = odbc_fetch_array($res)) {
		foreach (array('LOCAL','LOCA2','LOCA3') as $field) {
			foreach (split('/',$row[$field]) as $local) {
				$box = substr(trim(strtoupper($local)),0,3);
				if (substr($box,0,1) == 'X')
					$locals[$box] = 1;
			}
		}
	}

	echo "<div class='debug'>Apres fetch : ".(microtime(true) - $start)."</div>";
	ksort($locals);
	echo "<div class='debug'>Apres ksort : ".(microtime(true) - $start)."</div>";
	
	for($i=1 ; $i<=2 ; $i++) { ?>
		<div id="choix-box-<?=$i?>" class="choix-box">Box <?=$i?> :
			<select onchange="refresh_etiquette(this,<?=$i?>);">
				<option value="">Choix d'un emplacement</option>
<?				foreach($locals as $box => $val) {
	//			for($j=1 ; $j<=99 ; $j++) {
?>
					<option value="<?=$box?>"><?=$box?></option>
<?				} ?>
			</select>
		</div>
<?	}
	echo "<div class='debug'>Apres affichage : ".(microtime(true) - $start)."</div>";	
?>
</fieldset>
</form>

<div id="loading" class="hide_when_print"><img src="gfx/loading4.gif"/> Patientez un instant ...</div>

<table id="etiquette">
<tr>
	<td id="etiquette1"></td>
	<td id="etiquette2"></td>
</tr>
</table>

</body>
</html>
<?
?>