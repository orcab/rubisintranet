<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

?><html>
<head>
<title>Consultation des tarifs EXPO</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
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


var tr ;
var all_results = new Array();
var nb_results_by_page = 20 ;
var recherche = '';

function make_all_bind() {

	// colorisation des input quand la souris est dessus
	$('input[name^=a_reference]').unbind('blur');
	$('input[name^=a_reference]').unbind('focus');
	$('input[name^=a_reference]').blur(function() {	$(this).css('background','');	});
	$('input[name^=a_reference]').focus(function(){	$(this).css('background','#e7eef3');	});

	// on doit aller chercher les infos dans la BD et les ramener sur la page
	$('input[name^=a_reference]').unbind('keyup');
	$('input[name^=a_reference]').keyup(function (e) {
		if (e.which == 13 && all_results.length == 1) { // la touche ENREE et il n'y a qu'un seul résultat --> on le selectionne
			insert_ligne(all_results[0].id); return;
		}

		tr = $(this).parents('tr');
		recherche = $(this).val();
		var div_offset = $(this).offset();
		var div_height = $(this).height();
		//alert(recherche + ' ' + recherche.length);
		if (recherche.length >= 3) { // au moins trois car pour lancer la recherche
			//on affiche le sablier de recherche
			tr.children('td[class^=fournisseur]').addClass('loading');

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
					tr.children('td[class^=fournisseur]').removeClass('loading');
				} // fin fonction
			); // fin getJson
		}
	});
}

function draw_page(pageno) {
	var div = $('div#sugest');
	lastpage   = Math.ceil(all_results.length / nb_results_by_page);

	div.html('<table id="results"><tbody>'); // on vide la boite de sugestion
	
	for(i=nb_results_by_page * (pageno-1) ; i<all_results.length && i<nb_results_by_page * (pageno-1) + nb_results_by_page ; i++) {
		div.append(	'<tr onclick="insert_ligne(\''+all_results[i].id+'\');">' + 
						'<td style="padding-right:10px;">' + all_results[i].reference.toUpperCase().replace(recherche.toUpperCase(),'<strong>'+recherche.toUpperCase()+'</strong>') + '</td>' +
						'<td style="color:green;padding-right:10px;">'		+ all_results[i].fournisseur + '</td>' +
						'<td style="padding-right:10px;width:500px;">'		+ all_results[i].designation + '</td>' +
						'<td style="font-weight:bold;">' + Math.round(all_results[i].px_expo * 100)/100 + '&euro;</td>' +
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
	//alert(id);
	$('div#sugest').hide(); // on cache la boite
	$.getJSON('ajax.php', { what:'get_detail', val: id  } ,
			function(data){
				//alert();
				//var tmp = tr.children('td') ;
				var tmp = tr ;
				tmp.children('td[class^=reference]').text(data.reference);
				tmp.children('td[class^=fournisseur]').text(data.fournisseur);
				tmp.children('td[class^=designation]').text(data.designation);
				tmp.children('td[class^=marge_coop]').text( '+' + <?=MARGE_COOP?> + '%' ); // marge de la coop
				tmp.children('td[class^=px coop]').html((Math.round(data.px_achat_coop	* 100)/100) + '&euro;'); // prix coop
				tmp.children('td[class^=px adh]').html(	(Math.round(data.px_adh			* 100)/100) + '&euro;'); // prix adh
				tmp.children('td[class^=px expo]').html((Math.round(data.px_expo		* 100)/100) + '&euro;'); // prix expo
				tmp.children('td[class^=px pub]').html(	(Math.round(data.px_public		* 100)/100) + '&euro;'); // prix pub
				tmp.children('td[class^=modification]').html( ucFirst(data.qui) + '<br/>' +
										(data.date_modification_format ? data.date_modification_format : data.date_creation_format) ); // derniere modif

				$('#lignes tbody').append( pattern_ligne );
				make_all_bind();
			}
	);	
}

<?
$pattern_ligne = <<<EOT
<tr>
	<td class="reference"><input type="text" name="a_reference[]" size="10" value="" class="ref" autocomplete="off" /></td>
	<td class="fournisseur"></td>
	<td class="designation"></td>
	<td class="px coop"></td>
	<td class="marge_coop"></td>
	<td class="px adh"></td>
	<td class="px expo"></td>
	<td class="px pub"></td>
	<td class="modification"></td>
</tr>
EOT;
?>


var pattern_ligne = '<?=ereg_replace("[\n\r]",'',$pattern_ligne)?>' ;


$(document).ready(function(){
	// ajoute une ligne à la fin du tableau
	$('#lignes tbody').append( pattern_ligne );
	make_all_bind();
}); // fin on document ready

</script>

<style>

body {
	font-family:verdana;
	font-size:0.8em;
}

sup { font-size:10px; }

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

table#lignes th.reference { width:110px; }
table#lignes th.designation { width:400px; }
table#lignes th.fournisseur { width:120px; }


div#sugest {
	border:solid 1px #6290B3;
	background:#e7eef3;
	font-size:0.7em;
	display:none;
	position:absolute;
	top:0;
	left:0;
	cursor:pointer;
	padding:3px;
}

div#sugest tr:hover { background:yellow; }

#results td { border:solid 1px black; }
#results td.fournisseur { color:green; }
#results td.designation { font-style:italic; }

span.navig { font-size:1.5em; }

span.navig a {
	text-decoration:none;
	color:red;
}

span#options {
	font-weight:normal;
	font-size:0.8em;
}

table#lignes .px { text-align:right; padding-right:3px;}
table#lignes td.coop { color:green; }
table#lignes td.adh { color:blue; }
table#lignes td.expo { color:red; font-weight:bold; }
table#lignes td.pub { color:grey; }
table#lignes td.modification { text-align:center; }
table#lignes td.marge_coop { font-size:0.6em; color:grey; text-align:center; }

.loading {
	background-color:none;
	background-image:url(gfx/loading4.gif);
	background-repeat:no-repeat;
	background-position:top left;
}
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
			<th class="px coop">Px Coop<sup>ht</sup></th>
			<th></th>
			<th class="px adh">Px Adh<sup>ht</sup></th>
			<th class="px expo">Px Expo<sup>ht</sup></th>
			<th class="px pub">Px Pub<sup>ht</sup></th>
			<th class="modification">Dernière<br/>modif par</th>
		</tr>
		</thead>
		<tbody>

		</tbody>
	</table>
</fieldset>

</form>

</body>
</html>
<?
mysql_close($mysql);
?>