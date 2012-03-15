<?
include('../inc/config.php');

//$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
//$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

?><html>
<head>
<title>Consultation des tarifs EXPO</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(devis.css);</style>
<script type="text/javascript" src="../js/jquery.js"></script>
<script language="javascript">

function ucFirst(str) {
	if (str.length > 0)
		return str[0].toUpperCase() + str.substring(1);
	else
		return str;
}

function cache_sugest() {
	$('div#sugest').hide('normal');
}


var timer ;
var tr ;
var div_offset;
var div_height;
var all_results = new Array();
var nb_results_by_page = 20 ;
var recherche = '';


function draw_page(pageno) {
	var div = $('div#sugest');
	lastpage   = Math.ceil(all_results.length / nb_results_by_page);

	div.html('<table id="results"><tbody>'); // on vide la boite de sugestion
	
	for(i=nb_results_by_page * (pageno-1) ; i<all_results.length && i<nb_results_by_page * (pageno-1) + nb_results_by_page ; i++) {
		div.append(	'<tr onclick="insert_ligne(\''+all_results[i].rowid+'\');">' + 
						'<td class="ref">' + all_results[i].reference.toUpperCase().replace(recherche.toUpperCase(),'<strong>'+recherche.toUpperCase()+'</strong>')+'</td>'+
						'<td class="fournisseur">'	+ all_results[i].nom_fournisseur														+ '</td>' +
						'<td class="logo">'			+ (all_results[i].code_mcs ? '<img src="gfx/logo_mcs_micro.png"/>':'')					+ '&nbsp;</td>' +
						'<td class="designation">'	+ all_results[i].designation1															+ '</td>' +
						'<td class="px">'			+ parseFloat(all_results[i].px_public).toFixed(2)										+ '&euro;</td>' +
						'<td class="'+(all_results[i].px_from == 'pp' ? 'pp':'') +'">'+ (all_results[i].px_from == 'pp' ? 'pp':'&nbsp;')	+ '</td>' +
					'</tr>'
		); // on affiche les suggestions
	}

	div.append('</tbody><tfoot><tr><td colspan="4">'+all_results.length+' résultat(s)&nbsp;&nbsp;&nbsp;&nbsp;');
	
	if (pageno > 1) div.append('<span class="navig"><a href="javascript:draw_page(1);">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+ parseInt(pageno-1) +');">&lt;prec.</a></span>&nbsp;&nbsp;&nbsp;&nbsp;');
	div.append('Page '+pageno);
	if (pageno < lastpage) div.append('&nbsp;&nbsp;&nbsp;&nbsp;<span class="navig"><a href="javascript:draw_page('+ parseInt(pageno+1) +');">suiv.&gt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+lastpage+');">&gt;&gt;</a></span>');

	div.append('<div style="float:right;"><a href="javascript:cache_sugest();">Fermer [X]</a></div></td></tr></tfoot></table>');
}


// ou a choisit une ligne parmis les propositions --> on insert les données
function insert_ligne(id) {
	 // on cache la boite
	$('div#sugest').hide();

	// on met un loading
	tr.children('td').children('input[name^=a_reference]').addClass('loading');

	// on lance la recherche d'information
	$.getJSON('ajax.php', { what:'get_detail', val: id  } ,
		function(data){
			// on affecte les valeurs au champs HTML
			tr.children('td[class^=reference]')		.text(data.reference);
			tr.children('td[class^=fournisseur]')	.text(data.nom_fournisseur);
			tr.children('td[class^=designation]')	.html(data.designation1 + '<br/>' + data.designation2 + (data.code_mcs ? '<br/><span class="code_mcs">Code MCS : '+data.code_mcs+'</span>' : ''));
			tr.children('td[class^=px_avec_coef]')	.html(parseFloat(data.px_avec_coef).toFixed(2)	+ '&euro;'); // prix expo
			tr.children('td[class^=px_public]')		.html(parseFloat(data.px_public).toFixed(2)		+ '&euro;'); // prix pub
			tr.children('td[class^=modification]')	.html(data.date_application_format); // date application tarif

			// on ajoute une class pour le prix le plus bas
			if (data.px_avec_coef < data.px_public)
				tr.children('td[class^=px_avec_coef]').addClass('px_utilise');
			else
				tr.children('td[class^=px_public]').addClass('px_utilise');

			// on supprime le loading
			tr.children('td[class^=fournisseur]').removeClass('loading');

			// on rajoute une nouvelle ligne de recherche
			$('#lignes tbody').append( pattern_ligne );
		}
	);	
}

<?
$pattern_ligne = <<<EOT
<tr>
	<td class="reference"><input type="text" name="a_reference[]" size="10" value="" class="ref" autocomplete="off" /></td>
	<td class="fournisseur"></td>
	<td class="designation"></td>
	<td class="px_avec_coef"></td>
	<td class="px_public"></td>
	<td class="modification"></td>
</tr>
EOT;
?>


var pattern_ligne = '<?=ereg_replace("[\n\r]",'',$pattern_ligne)?>' ;


function lance_recherche() {
	//on affiche le sablier de recherche
	tr.children('td').children('input[name^=a_reference]').addClass('loading');
	all_results = Array(); // on vide la mémoire des résultats

	// on recherche dans la BD les nouvelles conrerespondances
	$.getJSON('ajax.php', { what:'complette_via_ref', val: recherche  } ,
		function(data){
			// on les stock plus un affichage ultérieur
			all_results = data ;
			
			// on affiche la premier epage de résultat
			if (all_results.length > 0) draw_page(1);
			else						$('div#sugest').html('<img src="../gfx/attention.png" /> Aucun résultat');

			// placement du div
			$('div#sugest').css('top',div_offset.top + div_height + 5).css('left',div_offset.left).show('fast');

			//on cache le sablier de recherche
			tr.children('td').children('input[name^=a_reference]').removeClass('loading');
		} // fin fonction
	); // fin getJson
}



$(document).ready(function(){
	// ajoute une ligne à la fin du tableau
	$('#lignes tbody').append( pattern_ligne );

	// ON FAIT LES BIND
	$('body').delegate('input[name^=a_reference]','keyup',function (e) {
		// supprime l'ancien timer pour que la recherche qui était lancer, ne se fasse pas
		clearTimeout(timer);

		 // la touche ENREE et il n'y a qu'un seul résultat --> on le selectionne
		if (e.which == 13 && all_results.length == 1) {
			insert_ligne(all_results[0].id); return;
		}
		
		tr			= $(this).parents('tr');
		div_offset	= $(this).offset();
		div_height	= $(this).height();
		recherche	= $(this).val();
		if (recherche.length >= 3) { // au moins trois car pour lancer la recherche
			//lance la recherche dans 700 milisecond
			timer = setTimeout("lance_recherche()",700);
		}
	});
}); // fin on document ready

</script>

<style>

fieldset {
	width:85%;
	margin:auto;
	margin-top:20px;
	border:solid 1px #6290B3;
	-moz-border-radius:10px;
}

fieldset#detail table tr { border-bottom:dotted 1px #6290B3; }
fieldset#detail table tr:last-child { border-bottom:none; }

fieldset legend {
	border:solid 1px #6290B3;
	background:#e7eef3;
	color:black;
	font-weight:bold;
	padding:3px;
}

div#cadre-exterieur {
	border:solid 1px black;
	padding:15px;
}

fieldset#entete, fieldset#detail { margin-bottom:20px; }

fieldset table {
	width:100%;
	border-collapse:collapse;
}

fieldset table td, fieldset table th {
	padding:0px;
	padding-bottom:5px;
	font-size:0.8em;
}

table#lignes td, table#lignes th { vertical-align:top; }
table#lignes .reference, table#lignes .fournisseur, table#lignes .designation { text-align:left; }

fieldset#detail table td { padding-top:4px; }

table#lignes th.reference	{ width:110px; }
table#lignes th.designation { width:400px; }
table#lignes th.fournisseur { width:120px; }

table#lignes .px_avec_coef,table#lignes .px_public { text-align:right; }
table#lignes .px_utilise { font-weight:bold; color:red; }

table#lignes td.pub { color:grey; }
table#lignes td.modification { text-align:center; }

.code_mcs { font-style:italic; color:grey; }

</style>

<body>
<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div id="sugest"></div><!-- pour la sugestion des résultat ajax -->

<form method="post" action="" name="creation_devis">
<input type="hidden" name="dummy" value="0"/>
<fieldset id="detail">
    <legend>Consultation des prix :</legend>
	<table id="lignes">
		<thead>
		<tr>
			<th class="reference">Réf</th>
			<th class="fournisseur">Fournisseur</th>
			<th class="designation">Désignation</th>
			<th class="px_avec_coef">Px adh avec coef<sup>ht</sup></th>
			<th class="px_public">Px Public<sup>ht</sup></th>
			<th class="modification">Date tarif</th>
		</tr>
		</thead>
		<tbody>

		</tbody>
	</table>
</fieldset>

</form>

</body>
</html>
<? //mysql_close($mysql); ?>