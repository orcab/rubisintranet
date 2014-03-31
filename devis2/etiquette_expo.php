<?
include('../inc/config.php');

$start = microtime(true);

?><html>
<head>
<title>Création d'étiquette expo</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />

<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../js/fontawesome/css/icon-custom.css">
<script type="text/javascript" src="../js/jquery.js"></script>
<script language="javascript">

var TVA1 = <?= TTC1/100 ?>;
var TVA2 = <?= TTC2/100 ?>;

// on load
$(document).ready(function() {
	// pour le debug
	//refresh_etiquette(document.getElementById('select-1'),'1');

	// on bind les events

	// on click sur l'édition du titre
	$('body').delegate('.icons i.edit','click',function(){
		var div_title = $(this).parent().parent().children('.title');

		// si l'on est pas deja en edition --> on edit
		if (div_title.children('input').size() <= 0) {
			// on affiche le bouton de savegarde
			$(this).parent().children('i.save').css('visibility','visible');
			// on cache le bouton d'édition
			$(this).css('visibility','hidden');

			// on remplace le texte par un input pour edition
			var titre = div_title.text(); // console.log(titre);
			div_title.html('<input type="text" size="50" value="'+titre+'" placeholder="Titre..." class="hide_when_print"/>');
			div_title.children().focus(); // place le curseur dans la zone editable
		}
	});

	// on click sur la sauvegarde du titre
	$('body').delegate('.icons i.save','click',function(){
		var div_title = $(this).parent().parent().children('.title');

		// si l'on est en edition --> on sauve
		if (div_title.has('input').size() > 0) {
			// on cache le bouton de savegarde
			$(this).css('visibility','hidden');
			// on affiche  le bouton d'édition
			$(this).parent().children('i.edit').css('visibility','visible');
			
			// on remplace le texte par un input pour edition
			var titre = div_title.children('input').val(); //console.log(titre);
			div_title.html(titre);

			// faire une sauvegarde des titres dans la base sql
			var titles = []; // listes des titres
			$('.title').each(function(){ titles.push($(this).html()); });

			$.ajax({
				type: 'POST',
				url:  'ajax_etiquette_expo.php',
				dataType: 'json',
				data: {	'what':'save_box_titles',
						'box':$(this).parents('table.articles').find('.box').html().replace(/^Box +/i,''),
						'titles':titles
					},
				success: function(data){ console.log(data); }
			});

		}
	});

	// on appuie sur entrée lors de l'edition d'un titre
	$('body').delegate('input[type=text]','keyup',function(e){
		if (e.which == 13) { // appuie sur entrée
			// on simule le click sur le bouton de sauvegarde
			$(this).parent().parent().children('.icons').children('.save').click();
		}
	});
});


function refresh_etiquette(sel,id) {
	var box = sel[sel.selectedIndex].value;
	//var box = 14 ;
	
	if (box) {
		$('#loading').css('visibility','visible'); // affiche le loading
		$('#etiquette'+id).html('');

		// va chercher le détail du box selectionné
		$.getJSON('ajax_etiquette_expo.php', { 'what':'get_detail_box', 'box': box } ,
			function(data){
				// affiche l'entete de l'étiquette
				var html = 	'<table class="articles"><caption><div class="logo_mcs"></div>'+ // logo mcs
							'<div class="box">Box '+box+'</div>'+ //titre du box
							'<div class="logo_artipole"></div>'+//logo artipole
							'<div class="title_icons">'+// container pour titre + icons
								'<div class="icons hide_when_print"><i class="icon-edit edit"></i> <i class="icon-save save"></i></div>'+ // icon
								'<div class="title"></div>'+	// titre
							'</div>'+
							'</caption></theader>';

				var total1 		= 0;
				var total2 		= 0;
				var soustotal1 	= 0;
				//var nb_article_dans_sousboxsoustotal1 	= 0;
				// affiche les articles en parcourant les sous box
				for(sousbox in data.sousboxs) {

					html += '<tbody class="sousbox">';
					html += '<tr><td colspan="5" class="titre-sousbox"><div class="title_icons">'+// container pour titre + icons
								'<div class="icons hide_when_print"><i class="icon-edit edit"></i> <i class="icon-save save"></i></div>'+ // icon
								'<div class="title"></div>'+	// titre
							'</div></td></tr>';
				
					soustotal1 = 0;

					for(article in data.sousboxs[sousbox]) {
						var erreur	= '';
						var cle		= data.sousboxs[sousbox][article].article;
						var qte		= data.sousboxs[sousbox][article].qte;
						var detail	= data.articles[cle];
			
						if (!detail.px_public)	// si aucun prix public renseigné
							erreur += "Aucun prix public de renseigné";

						if (!detail.reference) {
							erreur += "Aucune référence de renseignée pour l'article";
							detail.px_public = 0; // on met le prix à 0 car il sera faux de toute façon
						}

						html += '<tr>'+
									'<td class="type_article type_'+detail.activite+'_'+detail.famille+'" title="'+detail.famille+'"></td>' ;

						// si la designation cont-ient, "miroir", on change l'icon à la volée
						if (detail.designation.match(/miroir/i)) {
							detail.activite = 'meuble';
							detail.famille  = 'miroir';
						}

						html += 	'<td class="icon_article icon_'+detail.activite+'_'+detail.famille+'" title="'+detail.famille+'"></td>'+
									'<td class="fournisseur">'+
										'<div class="fournisseur">'+detail.fournisseur+'</div>'+
										'<div class="reference">'+detail.reference+'</div>'+
									'</td>'+
										'<td class="designation">'+(qte > 1 ? '<strong>x'+qte+'</strong> ':'') + detail.designation+
										'<div class="code_mcs">Code : '+detail.code_mcs+' '+
										(detail.mode=='pp' ? '<span class="pp">PP</span>':'')+'</div>'+ // prix public ou adh*1.5
										(erreur ? '<div class="erreur">'+erreur+'</div>' : '')+			// n'affiche l'erreur que si elle est présente
									'</td>'+
										'<td class="prix" nowrap="nowrap">'+(qte > 1 ? '<span class="qte">'+qte+'x</span> ':'') +
											(detail.px_public ? (detail.px_public * TVA1 + detail.px_public).toFixed(2)  + '&nbsp;&euro; <span class="ttc">ttc</span>':'NC')+
											(parseFloat(detail.ecotaxe).toFixed(2)>0 ? '<br/><span class="ecotaxe">dont '+parseFloat(detail.ecotaxe).toFixed(2)+"&euro; d'ecotaxe</span>":'')+
									'</td>'+
								'</tr>';
						// qte * prix ttc + ecotaxe
						total1 		+= qte * ((detail.px_public * TVA1 + detail.px_public) + parseFloat(detail.ecotaxe ? detail.ecotaxe:0));
						total2 		+= qte * ((detail.px_public * TVA2 + detail.px_public) + parseFloat(detail.ecotaxe ? detail.ecotaxe:0));
						soustotal1 	+= qte * ((detail.px_public * TVA1 + detail.px_public) + parseFloat(detail.ecotaxe ? detail.ecotaxe:0));
					}
					// affichage du sous total et de la barre de sérparation
					if (data.sousboxs[sousbox].length > 1) { // si plus d'un element dans le sousbox
						html += '<tr><td colspan="5" class="sous-total">Sous total : '+soustotal1.toFixed(2)+' &euro; ttc</td></tr>';
					}
					html += '</tbody><tbody class="espacement"><tr><td colspan="5">&nbsp;</td></tr></tbody>';
				}
				html += '<tfoot><tr><td colspan="5">Total : '+total1.toFixed(2)+' &euro; ttc (tva <?=TTC1?>%)<br/>'+total2.toFixed(2)+' &euro; ttc (tva <?=TTC2?>%)</td></tr></tfoot>'
				html += '</table>';

				$('#etiquette'+id).append(html);


				// affiche la liste des titres précédement enregistrés
				$.getJSON('ajax_etiquette_expo.php', { 'what':'get_box_titles', 'box': box } , function(json){
					//console.log(json);
					var titles_elm = $('.title');	// recupere les different titre de la page
					for(var i=0 ; i<json.length ; i++) {
						//console.log(titles_elm[i]);
						//console.log(json[i]);
						$(titles_elm[i]).html(json[i]); // affiche le titre dans le div
					}
				});


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
    color: #808080;
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
td#etiquette1 { border-right: 1px dotted #000000; }
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
table.articles tbody.sousbox { border: 1px solid #000000; }
table.articles tbody.espacement { height: 1em; }

td.type_article { width:10px; }
td.icon_article { width:32px; height:32px; background-repeat:no-repeat; background-position: center center;}

td.type_00B_B00 { background:grey; }
td.type_00B_B01 { background:purple; }
td.type_00B_B02 { background:red; }
td.type_00B_B03 { background:orange; }
td.type_00B_B04 { background:green; }
td.type_00B_B05 { background:salmon; }
td.type_00B_B06 { background:deeppink; }
td.type_00B_B07 { background:darkkhaki; }
td.type_00B_B08 { background:blue; }
td.type_00B_B09 { background:mediumpurple; }
td.type_00B_B10 { background:olive; }
td.type_00B_B11 { background:teal; }
td.type_00B_B12 { background:green; }
td.type_00B_B13 { background:steelblue; }
td.type_00B_B14 { background:peru; }
td.type_00B_B15 { background:darkslategray; }
td.type_00B_B16 { background:yellow; }
td.type_00B_B17 { background:IndianRed; }
td.type_00B_B18 { background:OrangeRed; }
td.type_00B_B19 { background:GreenYellow; }
td.type_00B_B20 { background:MediumSpringGreen; }

td.type_00D_D08 { background:lightgreen; }

td.icon_00B_B00 { /* receveur de douche */ }
td.icon_00B_B01 { background-image:url('gfx/icon/douche_32px.png'); }
td.icon_00B_B02 { background-image:url('gfx/icon/douche_32px.png'); }
td.icon_00B_B03 { background-image:url('gfx/icon/douche_paroi_32px.png'); }
td.icon_00B_B04 { background-image:url('gfx/icon/baignoire_32px.png'); }
td.icon_00B_B05 { background-image:url('gfx/icon/robinet_32px.png'); }
td.icon_00B_B06 { background-image:url('gfx/icon/spa_32px.png'); }
td.icon_00B_B07 { background-image:url('gfx/icon/spa_32px.png'); }
td.icon_00B_B08 { background-image:url('gfx/icon/wc_32px.png'); }
td.icon_00B_B09 { /* urinoir */ }
td.icon_00B_B10 { background-image:url('gfx/icon/vasque_32px.png'); }
td.icon_00B_B11 { background-image:url('gfx/icon/vasque_32px.png'); }
td.icon_00B_B12 { background-image:url('gfx/icon/vasque_32px.png'); }
td.icon_00B_B13 { /* bidet et fixation */ }
td.icon_00B_B14 { background-image:url('gfx/icon/robinet_32px.png'); }
td.icon_00B_B15 { background-image:url('gfx/icon/meuble_32px.png'); }
td.icon_00B_B16 { /* accessoire */ }
td.icon_00B_B17 { background-image:url('gfx/icon/evier_32px.png'); }
td.icon_00B_B18 { background-image:url('gfx/icon/robinet_32px.png'); }
td.icon_00B_B19 { /* saintaire collectivité */ }
td.icon_00B_B20 { background-image:url('gfx/icon/robinet_32px.png'); }
td.icon_meuble_miroir { background-image:url('gfx/icon/miroir_32px.png'); }

td.icon_00D_D08 { background-image:url('gfx/icon/radiateur_32px.png'); }

td.fournisseur { padding-left:5px; }
div.fournisseur { font-weight: bold; }
td.prix {
    font-weight: bold;
    text-align: right;
}
td.total { text-align: right; }
table.articles td {
    border-bottom: 1px dotted #000000;
    font-size: 0.7em;
    vertical-align: top;
}
table.articles tr:last-child td { border: medium none; }
.erreur { color: #FF0000; }
td.designation {
    font-style: italic;
    padding-left: 0.5cm;
}
.prix-conseille {
    font-size: 1.5em;
    text-align: left;
}
.ttc { font-weight: normal; }
.ecotaxe {
    font-size: 0.9em;
    font-weight: normal;
}
.code_mcs {
    color: #008000;
    font-size: 0.8em;
}
span.pp {
    background-color: #008000;
    border: medium none;
    color: #FFFFFF;
    font-weight: bold;
    padding-left: 2px;
    padding-right: 2px;
}
.qte { font-style: normal; }
.logo_mcs {
    background-image: url("gfx/logo_mcs_mini.png");
    background-repeat: no-repeat;
    float: left;
    height: 80px;
    width: 139px;
}
.logo_artipole {
    background-image: url("gfx/logo_artipole_mini.png");
    background-repeat: no-repeat;
    float: left;
    height: 60px;
    width: 176px;
}
.box {
    float: left;
    width: 33%;
    font-size: 0.8em;
}
td.titre-sousbox div {
    font-size: 1.2em;
    font-style: italic;
}
.title_icons {
    clear: both;
    width: 100%;
    margin-left: 5px;
}
.title {
    float: left;
    width: 85%;
}
.icons { float: left; }
i.save { visibility: hidden; }

tfoot td, .sous-total {
    text-align: right;
    padding-right: 1em;
}
tfoot tr {
    border: solid 1px black;
    font-weight: bold;
}
@media print {
.hide_when_print {
    display: none;
}
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
		on  A.NOART=S.NOART and S.DEPOT='EXP'
group by LOCAL,LOCA2,LOCA3
EOT;

	$locals = array();

	
	// pour le debug
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

	//$locals = array('X14'=>1);

	echo "<div class='debug'>Apres fetch : ".(microtime(true) - $start)."</div>";
	ksort($locals);
	echo "<div class='debug'>Apres ksort : ".(microtime(true) - $start)."</div>";

	for($i=1 ; $i<=2 ; $i++) { ?>
		<div id="choix-box-<?=$i?>" class="choix-box">Box <?=$i?> :
			<select id="select-<?=$i?>" onchange="refresh_etiquette(this,<?=$i?>);">
				<option value="">Choix d'un emplacement</option>
<?				foreach($locals as $box => $val) { ?>
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

<!--<script>refresh_etiquette('test',1);</script>-->

</body>
</html>
<?
?>