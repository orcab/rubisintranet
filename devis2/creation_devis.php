<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$droit = recuperer_droit();

if (!($droit & PEUT_CREER_DEVIS)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'acc�der � cette partie de l'intranet");
}

$load_draft = FALSE;

// mode creation ou modification ?
if (isset($_GET['id'])) { // mode modification
	$id = mysql_escape_string($_GET['id']) ;
	$modif = TRUE;

	if (isset($_GET['load_draft']) && $_GET['load_draft'] == 1)
		$load_draft = TRUE;

} else {
	$representant = '';
	$res  = mysql_query("SELECT * FROM employe WHERE droit & $PEUT_CREER_DEVIS ORDER BY prenom ASC");
	while ($row = mysql_fetch_array($res))
		if ($_SERVER['REMOTE_ADDR']==$row['ip']) { // on a trouv� l'�diteur
			$representant = mysql_escape_string($row['prenom']); break;
		}

	// mode creation
	// on cr�e un faux devis qu'il faudra compl�ter
	$sql = <<<EOT
INSERT INTO devis	(`date`,date_maj,representant,		code_artisan,	artisan,	nom_client,	ville_client,	tel_client)
VALUES				(NOW(),	NOW(),	'$representant',	'EDITIO'	,'EDITION',	'EDITION',	'EDITION',		'EDITION')
EOT;
	mysql_query($sql) or die("Erreur dans la creation du dummy devis : ".mysql_error()."<br/>\n$sql");
	$id = mysql_insert_id();
	$modif=FALSE;
}

// recherche du l'entete du devis si en modification
$draft_find = FALSE;
if($modif) { // modif
	$res_devis ;

	// on test si l'on doit recharger un vieux devis
	if ($load_draft) {
		$res_devis = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d/%m/%Y') AS date_formater,DATE_FORMAT(`date`,'%H:%i') AS heure,DATE_FORMAT(date_maj,'%d/%m/%Y') AS date_maj_formater,CONCAT(DATE_FORMAT(`date`,'%b%y-'),id) AS numero FROM devis_draft WHERE id='$id' LIMIT 0,1") or die("Requete impossible ".mysql_error()) ;

	} else {
		// si on trouve un brouillon --> on met la variable draft_find � TRUE
		if (mysql_num_rows(mysql_query("SELECT id FROM devis_draft WHERE id='$id' LIMIT 0,1")))
			$draft_find = TRUE;

		$res_devis = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d/%m/%Y') AS date_formater,DATE_FORMAT(`date`,'%H:%i') AS heure,DATE_FORMAT(date_maj,'%d/%m/%Y') AS date_maj_formater,CONCAT(DATE_FORMAT(`date`,'%b%y-'),id) AS numero FROM devis WHERE id='$id' LIMIT 0,1") or die("Requete impossible ".mysql_error()) ;
	}

	$row_devis = mysql_fetch_array($res_devis);
}
?><html>
<head>
<title><?= $modif ? "Modification du $row_devis[numero]" : "Cr�ation du devis ".date('My')."-$id" ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_devis.ico" />
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../js/jscalendar/calendar-setup.js"></script>
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/mobile.style.js"></script>
<script language="javascript">

var auto_save = true;

<? if ($draft_find) { ?>
	if (confirm("Un brouillon a �t� trouv� pour votre devis\nVoulez vous le recharger ?")) {
		document.location.href="creation_devis.php?id=<?=$id?>&load_draft=1";
	}
<? } ?>


function affiche_adherent(obj) {
	var value_selected = obj.options[obj.selectedIndex].value ;
	if (value_selected == 'NON Adherent' || value_selected == 'CAB 56 (056039)')
		$('#artisan_nom_libre').show('fast');
	else
		$('#artisan_nom_libre').hide('fast');
}

function cache_sugest() {
	$('div#sugest').hide('normal');
}

function valide_form(mes_options) {
	auto_save = false;
	document.creation_devis.les_options.value = mes_options;
	document.creation_devis.submit();
}


// variables globales
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
						'<td style="padding-right:10px;">' + all_results[i].reference.toUpperCase().replace(recherche.toUpperCase(),'<strong>'+recherche.toUpperCase()+'</strong>') + '</td>' +
						'<td style="color:green;padding-right:10px;">'	+ all_results[i].nom_fournisseur + '</td>' +
						'<td style="padding-right:10px;width:500px;">'	+ all_results[i].designation1 + '</td>' +
						'<td style="font-weight:bold;">'				+ parseFloat(all_results[i].px_public).toFixed(2) + '&euro;</td>' +
					'</tr>'
		); // on affiche les suggestions
	}

	div.append('</tbody><tfoot><tr><td colspan="4">'+all_results.length+' r�sultat(s)&nbsp;&nbsp;&nbsp;&nbsp;');
	
	if (pageno > 1) div.append('<span class="navig"><a href="javascript:draw_page(1);">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+ parseInt(pageno-1) +');">&lt;prec.</a></span>&nbsp;&nbsp;&nbsp;&nbsp;');
	div.append('Page '+pageno);
	if (pageno < lastpage) div.append('&nbsp;&nbsp;&nbsp;&nbsp;<span class="navig"><a href="javascript:draw_page('+ parseInt(pageno+1) +');">suiv.&gt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+lastpage+');">&gt;&gt;</a></span>');

	div.append('<div style="float:right;"><a href="javascript:cache_sugest();">Fermer [X]</a></div></td></tr></tfoot></table>');
}


// ou a choisit une ligne parmis les propositions --> on insert les donn�es
function insert_ligne(id) {
	$('div#sugest').hide(); // on cache la boite
	
	// un loading pour patienter
	tr.children('td').children('input[name^=a_reference]').addClass('loading');

	$.getJSON('ajax.php', { what:'get_detail', val: id  },
			function(data){
				var tmp = tr.children('td') ;
				//tmp.children('div.modification').hide();
				tmp.children('input[name^=a_reference]').val(data.reference);
				tmp.children('input[name^=a_fournisseur]').val(data.nom_fournisseur);
				tmp.children('textarea[name^=a_designation]').val(data.designation1);
				tmp.children('textarea[name^=a_2designation]').val(data.designation2 + (data.code_mcs ? "\nCode MCS : "+data.code_mcs : ''));
				tmp.children('input[name^=a_qte]').val(1);
				tmp.children('input[name^=a_pu]').val(parseFloat(data.px_public).toFixed(2)); // prix expo
				tmp.children('span').children('input[name^=a_adh_pu]').val(parseFloat(data.px_adh).toFixed(2)); // prix adh
				
				if ($('#discret_mode').attr('checked'))
					$('.discret').show();
				else 
					$('.discret').hide();
				recalcul_total();

				// on supprime le loading
				tmp.children('input[name^=a_reference]').removeClass('loading');
			}
	);
	
}


function recalcul_total() {
	total = 0;
	option = 0;
	$('input[name^=a_qte]').each(function() {
		var parent_tr	= $(this).parents('tr');
		var parent_td	= parent_tr.children('td');
		var pu			= parseFloat(parent_td.children('input[name^=a_pu]').val().replace(',','.'));
		var pu_adh		= parseFloat(parent_td.children('span').children('input[name^=a_adh_pu]').val().replace(',','.'));
		var qte			= parseFloat(parent_td.children('input[name^=a_qte]').val().replace(',','.'));
		if (pu_adh <= 0 && qte > 0) // si le prix est a 0, on le met en �vidence
			parent_td.children('span').children('input[name^=a_adh_pu]').css('background-color','red').css('background-image','none');

		if (pu >= 0 && qte >=0) {
			var val = (Math.round(qte * pu * 100)/100) ;
			parent_tr.children('td[name^=a_pt]').html(val + '&euro;');
			// on v�rifie si c'est une option
			if (parent_td.children('input[name^=a_opt]').attr('checked')) // cas d'une option, on ne l'a compte pas dans le total
				option++;
			else
				total += val ;
		} else
			parent_tr.children('td[name^=a_pt]').text('');
	});

	$('span#total').text(Math.round(total * 100)/100);
	if (option > 0)
		$('span#options').text("Le total ne tient pas compte " + (option > 1 ? "des "+option+" options choisies" : "de l'option choisie"));
	else
		$('span#options').text('');
}


<?

$pattern_ligne = <<<EOT
<tr>
	<td>
		<img src="gfx/add.png" name="a_add" title="Ajoute une ligne au dessus" /><br/>
		<img src="../gfx/delete_micro.gif" name="a_del" title="Supprime la ligne" />
	</td>
	<td class="opt">
		<input type="checkbox" name="a_opt[]" />Opt.
		<input type="hidden"	name="a_hid_opt[]"	value="0"/>
	</td>
	<td><input type="text"		name="a_reference[]"	value=""		class="ref"			 autocomplete="off" /></td>
	<td><input type="text"		name="a_fournisseur[]"	value=""		class="fournisseur"	 /></td>
	<td>
		<textarea				rows="2"	class="designation designation1" name="a_designation[]"></textarea><br/>
		<textarea				rows="2"	class="designation designation2" name="a_2designation[]"></textarea>
<!--	<input type="hidden"	name="a_hid_maj[]"	value="0"/>
		<div class="modification">
			<img src="../gfx/info.png" /> Modifications apport�es &nbsp;&nbsp;&nbsp;
			<input type="checkbox" name="a_maj[]" />MAJ
		</div>
-->
	</td>
	<td>
		<input type="text"		name="a_qte[]"		value="0"	class="qte" onkeyup="recalcul_total();"/>
		
	</td>
	<td style="text-align:right;">
		<input type="text"		name="a_pu[]"		value="0"	class="pu"  onkeyup="recalcul_total();"/>
		<span class="discret"><br/>Adh <input type="text"	name="a_adh_pu[]"	value="0" class="pu" /></span>
		<div class="discret"></div>
	</td>
	<td name="a_pt"></td>
</tr>
EOT;

?>

function sauvegarde_auto() {
	var valeur_deja_vu = {} ;
	var data = {};
	data.what = 'sauvegarde_auto' ;
	for(var i=0 ; i<document.creation_devis.elements.length ; i++) {
		if (valeur_deja_vu[document.creation_devis.elements[i].name] == 1) { // on a deja vu la variable ?

			//alert(typeof(data[document.creation_devis.elements[i].name]));

			if (typeof(data[document.creation_devis.elements[i].name]) == 'undefined' || typeof(data[document.creation_devis.elements[i].name]) == 'string') { // le tablea n'est pas encore cr�e
				// il faut cree un tableau car il y a plusieur element avec le meme nom
				var tmp = data[document.creation_devis.elements[i].name]; // on sauvegarde l'ancienne valeur
				data[document.creation_devis.elements[i].name] = new Array();
				data[document.creation_devis.elements[i].name].push(tmp);
				data[document.creation_devis.elements[i].name].push(document.creation_devis.elements[i].value);
			} else { // le tableau est deja cr�e, il faut lui rajouter une entr�e
				data[document.creation_devis.elements[i].name].push(document.creation_devis.elements[i].value); //is undefined
			}

		} else { // premiere fois qu'on la voit
			data[document.creation_devis.elements[i].name] = document.creation_devis.elements[i].value ;
		}
		valeur_deja_vu[document.creation_devis.elements[i].name] = 1; // on a vu la variable		
	}

	// on doit faire une auto_save
	if (auto_save) {
		$('#sauvegarde').css({'visibility':'visible'});
		$.post('ajax.php', data,
			  function(data){
				$('#sauvegarde').fadeTo(3000,0);
				window.setTimeout("sauvegarde_auto()", 1000*60*2 );  // pour r�p�ter l'op�ration r�guli�rement toutes les 2min
		});
	}
}


function lance_recherche() {
	all_results = Array(); // on vide la m�moire des r�sultats

	// un loading pour patienter
	tr.children('td').children('input[name^=a_reference]').addClass('loading');

	// on recherche dans la BD les nouvelles conrerespondances
	$.getJSON('ajax.php', { what:'complette_via_ref', val:recherche },
		function(data){
			// on les stock plus un affichage ult�rieur
			all_results = data ;
			
			// on affiche la premier epage de r�sultat
			if (all_results.length > 0) draw_page(1);
			else						$('div#sugest').html('<img src="../gfx/attention.png" /> Aucun r�sultat');

			// placement du div
			$('div#sugest').css('top',div_offset.top + div_height + 5).css('left',div_offset.left).show('fast');

			// on supprime le loading
			tr.children('td').children('input[name^=a_reference]').removeClass('loading');
		} // fin fonction
	); // fin getJson
}


var pattern_ligne = '<?=preg_replace("/[\n\r]/",'',$pattern_ligne)?>' ;



$(document).ready(function(){
		
	// bouton qui ajoute une ligne � la fin du tableau
	$('#add_ligne').click(function() {
		$('#lignes tbody').append( pattern_ligne );
		if ($('#discret_mode').attr('checked')) $('.discret').show(); // affiche ou non les cases sp�cial a la creation de la ligne
		else									$('.discret').hide();
	}); // fin add ligne
	
	// bouton qui ajoute 10 lignes d'un coup
	$('#add_dix_ligne').click(function() {
		for(var i=0 ; i<=9 ; i++)
			$('#lignes tbody').append( pattern_ligne );
	});

	// click sur le mode discret
	$('#discret_mode').click(function() {
		if ($(this).attr('checked'))
			$('.discret').show();
		else 
			$('.discret').hide();
	});

	// active l'auto-save en cas de modification
	$('body').delegate('input[type=text],textarea','change',function(){ auto_save = true ; }); // en cas de modif d'une valeur, on r�active l'auto_save


	// en cas de changement, on affiche l'option de validation des modifications dans la base
//	$('textarea[name^=a_designation], input[name^=a_pu], input[name^=a_adh_pu]').change(function() {
//		var parent_td = $(this).parents('tr').children('td') ;
//		if (parent_td.children('input[name^=a_reference]').val() && parent_td.children('input[name^=a_fournisseur]').val()) // si on a une r�f�rence et un fournisseur, c'est que l'on edit un article. Sinon on �crit juste un com'
//			parent_td.children('div.modification').show();
//	});


	// click sur options
	$('body').delegate('input[name^=a_opt]','click',function() {
		$(this).parents('tr').children('td').children('input[name^=a_hid_opt]').val( $(this).attr('checked') ? '1' : '0'  );
		recalcul_total();
	});

	// click sur MAJ
/*	$('body').delegate('input[name^=a_maj]','click',function() {
		$(this).parents('tr').children('td').children('input[name^=a_hid_maj]').val( $(this).attr('checked') ? '1' : '0'  );
	});
*/

	// ajoute un ligne au dessus de la ligne courante
	$('body').delegate('img[name^=a_add]','click',function() {
		$(this).parents('tr').before( pattern_ligne );
	});

	// supprime une ligne du tableau en cliquant sur l'image
	$('body').delegate('img[name^=a_del]','click',function() {
		if (confirm("Voulez-vous vraiment supprimer cette ligne ?"))
			$(this).parents('tr').remove();  // supprime le TR
	});


	// on doit aller chercher les infos dans la BD et les ramener sur la page
	$('body').delegate('input[name^=a_reference]','keyup',function (e) {
		// supprime l'ancien timer pour que la recherche qui �tait lancer, ne se fasse pas
		clearTimeout(timer);

		if (e.which == 13 && all_results.length == 1) { // la touche ENREE et il n'y a qu'un seul r�sultat --> on le selectionne
			insert_ligne(all_results[0].id); return;
		}

		tr			= $(this).parents('tr');
		recherche	= $(this).val();
		div_offset	= $(this).offset();
		div_height	= $(this).height();
		if (recherche.length >= 3) { // au moins trois car pour lancer la recherche
			//lance la recherche dans 700 milisecond
			timer = setTimeout("lance_recherche()",700);
		}
	});


	// au chargement de la page, on ajoute une ligne au tableau des details
<?	if (!$modif) { // si aucune ligne sur le devis, on en propose une ?>
		$('#add_ligne').click();
<?	} ?>
	

	recalcul_total();

	// on lance la proc�dure auto-save
	window.setTimeout ("sauvegarde_auto()", 1000*60*2 ); // on sauve regulierement
}); // fin on document ready

</script>

<style>
body {
	font-family:verdana;
	font-size:0.8em;
}

sup {	font-size:10px; }

fieldset {	border:solid 1px #6290B3; }

.discret { display:none; }

fieldset#detail table tr {	border-bottom:dotted 1px #6290B3; }

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

fieldset#entete table th {
	text-align:right;
	padding-right:5px;
}


fieldset#detail table td,fieldset#detail table th {
	text-align:center;
	vertical-align:top;
}

fieldset#detail table td { padding-top:4px; }
fieldset#detail table td.opt,fieldset#detail table th.opt { text-align:left; }

textarea.designation {	width:100%; }
input.qte	{	width:3em; }
input.pu	{	width:5em; }


div#div_bouton {
	float:left;
	text-align:left;
}

div#div_total {
	float:right;
	text-align:right;
	font-weight:bold;
}

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

td.devis_id { font-weight:bold; }
input.qte { text-align:center; }
input.pu { text-align:right; }
/*div.modification { 
	display:none;
	font-size:0.8em;
}*/

div#sauvegarde {
	visibility:hidden;
	color:#6290B3;
	text-align:center;
	margin:0px;
	padding:0px;
}

input[type=text]:focus,
textarea:focus {
	background-color:#e7eef3;
}

input.loading {
	/*background-color:none;*/
	background-image:url(gfx/loading.gif);
	background-repeat:no-repeat;
	background-position:center right;
}

#add_ligne		{ background-image: url(gfx/plus_one.png); }
#add_dix_ligne	{ background-image: url(gfx/plus_ten.png);   padding-left: 25px; }

.designation {
	background-repeat: no-repeat;
    background-position: 93% 100%;
}
.designation1 { background-image: url(gfx/cadre_client.png); }
.designation2 { background-image: url(gfx/cadre_artisan.png); }

</style>

<body>
<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div id="sugest"></div><!-- pour la sugestion des r�sultat ajax -->

<form method="post" action="generation_devis_pdf.php" name="creation_devis">
<input type="hidden" name="les_options" value="" />
<input type="hidden" name="id_devis" value="<?=$id?>" />
<div id="cadre-exterieur">

<fieldset id="entete">
    <legend>Ent�te :</legend>
<table>
<tr>
	<th style="width:10%;">Repr�sentant</th>
	<td>
		<select name="artisan_representant" TABINDEX="1">
<?			$res  = mysql_query("SELECT * FROM employe WHERE droit & $PEUT_CREER_DEVIS ORDER BY prenom ASC");
			while ($row = mysql_fetch_array($res)) {
				if ($modif) { //modif ?>
					<option value="<?=$row['prenom']?>"<?= $row_devis['representant']==$row['prenom'] ? ' selected':''?>><?=$row['prenom']?></option>
<?				} else { // creation ?>
					<option value="<?=$row['prenom']?>"<?= $_SERVER['REMOTE_ADDR']==$row['ip'] ? ' selected':''?>><?=$row['prenom']?></option>
<?				}	
			} ?>
		</select>
	</td>
	<th>Client</th>
	<th>Nom</th>
	<td><input type="text" name="client_nom" size="45" TABINDEX="6" value="<?= $modif ? $row_devis['nom_client']: ''?>"></td>
</tr>
<tr>
	<th>Artisan</th>
	<td>
		<select name="artisan_nom" onchange="affiche_adherent(this);" TABINDEX="2">
			<option value="NON Adherent">Artisan NON ADHERENT</option>
<?			$res  = mysql_query("SELECT nom,numero FROM artisan WHERE suspendu='0' ORDER BY nom ASC") or die("Ne peux pas r�cup�rer la liste des adh�rents ".mysql_error());
			$a_trouve_artisan = FALSE ;
			$artisan_code = '';
			while ($row = mysql_fetch_array($res)) {
				if ($modif) { //modif ?>
					<option value="<?=$row['nom']?> (<?=$row['numero']?>)"<?
						if ($row_devis['artisan']==$row['nom']) {
							echo ' selected';
							$a_trouve_artisan = TRUE ;
							$artisan_code = $row['numero'];
						} elseif ($row['nom']=='CAB 56' && preg_match('/^CAB 56/i',$row_devis['artisan'])) {
							echo ' selected';
							$artisan_code = '';
						}
						?>><?=$row['nom']?></option>
<?				} else { // creation ?>
					<option value="<?=$row['nom']?> (<?=$row['numero']?>)"><?=$row['nom']?></option>
<?				}	
			} ?>
		</select><br/><input id="artisan_nom_libre" <?= $a_trouve_artisan ? 'style="display:none;"' : ''?> type="text" name="artisan_nom_libre" value="<?= $modif && !$a_trouve_artisan ? eregi_replace('^CAB 56 : ','',$row_devis['artisan']):''; ?>" />
		<!--<input type="hidden" name="artisan_code" value="<?=$artisan_code?>"/>-->
	</td>
	<td></td>
	<th>Adresse (ligne 1)</th>
	<td><input type="text" name="client_adresse" size="45" TABINDEX="7" value="<?= $modif ? $row_devis['adresse_client']: ''?>"></td>
</tr>
<tr>
	<th>Date</th>
	<td nowrap>
		<input type="text" id="devis_date" name="devis_date" value="<?= $modif ? $row_devis['date_formater'] : date('d/m/Y')?>" size="8">
		<img src="../js/jscalendar/calendar.gif" id="trigger" style="vertical-align:middle;cursor: pointer;"title="Date selector" />
		<script type="text/javascript">
		  Calendar.setup(
			{
			  inputField	: 'devis_date',         // ID of the input field
			  ifFormat		: '%d/%m/%Y',    // the date format
			  button		: 'trigger',       // ID of the button
			  date			: '<?= $modif ? $row_devis['date_formater'] : date('d/m/Y')?>',
			  firstDay 	: 1
			}
		  );
		</script>

	Heure <input type="text" name="devis_heure" size="5" maxlength="5" value="<?= $modif ? $row_devis['heure'] : date('G:i')?>" TABINDEX="5"></td>
	<td></td>
	<th>Adresse (ligne 2)</th>
	<td><input type="text" name="client_adresse2" size="45" TABINDEX="8" value="<?= $modif ? $row_devis['adresse_client2']: ''?>"></td>
</tr>
<tr>
	<td></td>
	<td></td>
	<td></td>
	<th>Code Postal / Ville</th>
	<td>
		<input type="text" name="client_codepostal" size="5" maxsize="5" TABINDEX="9" value="<?= $modif ? $row_devis['codepostal_client']: ''?>">
		<input type="text" name="client_ville" size="35" TABINDEX="10" value="<?= $modif ? $row_devis['ville_client']:''?>">
	</td>
</tr>
<tr>
	<td class="devis_id">Devis N�<?=$id?></td>
	<td style="padding-left:10px;">N� devis Rubis <input type="text" size="10" name="devis_num_devis_rubis" TABINDEX="13" value="<?= $modif ? $row_devis['num_devis_rubis']:''?>"/></td>
	<td></td>
	<th>T�l / Mobile</th>
	<td>
		<input type="text" name="client_telephone" TABINDEX="11" value="<?= $modif ? $row_devis['tel_client']: ''?>">
		<input type="text" name="client_telephone2" TABINDEX="12" value="<?= $modif ? $row_devis['tel_client2']: ''?>">
	</td>
</tr>
<tr>
	<td colspan="3"><label for="discret_mode" class="mobile"><input type="checkbox" id="discret_mode" name="discret_mode"/>Mode sp�cial</label></td>
	<th>Email</th>
	<td>
		<input type="text" name="client_email" TABINDEX="13" value="<?= $modif ? $row_devis['email_client']: ''?>" size="45">
	</td>
</tr>
</table>
</fieldset>


<div id="sauvegarde">Sauvegarde Auto <img src="gfx/loading4.gif" /></div>


<fieldset id="detail">
    <legend>Lignes :</legend>
	<table id="lignes">
		<thead>
		<tr>
			<th class="no_ligne"></th>
			<th class="opt">Opt</th>
			<th class="reference">R�f</th>
			<th class="fournisseur">Fournisseur</th>
			<th class="designation">D�signation</th>
			<th class="qte">Qte</th>
			<th class="pu">P.U.<sup>ht</sup></th>
			<th class="pt">P.T.<sup>ht</sup></th>
		</tr>
		</thead>
		<tbody>
<?	if ($modif) {
		$res_detail ;
		if ($load_draft)
			$res_detail = mysql_query("SELECT * FROM devis_ligne_draft WHERE id_devis='$id'");
		else
			$res_detail = mysql_query("SELECT * FROM devis_ligne WHERE id_devis='$id'");

		while($row_detail = mysql_fetch_array($res_detail)) {

			$custom_ligne = $pattern_ligne;
			if ($row_detail['option']) { // c'est d'une option
				$custom_ligne = preg_replace('/\sname="a_opt\[\]"\s/i',' name="a_opt[]" checked="checked" ',$custom_ligne);
				$custom_ligne = preg_replace('/\sname="a_hid_opt\[\]"\svalue="0"/i',' name="a_hid_opt[]" value="1" ',$custom_ligne);
			}
			if ($row_detail['ref_fournisseur'])
				$custom_ligne = preg_replace('/\sname="a_reference\[\]"\s+value=""\s/i',' name="a_reference[]" value="'.$row_detail['ref_fournisseur'].'" ',$custom_ligne);
			if ($row_detail['fournisseur'])
				$custom_ligne = preg_replace('/\sname="a_fournisseur\[\]"\s+value=""\s/i',' name="a_fournisseur[]" value="'.$row_detail['fournisseur'].'" ',$custom_ligne);
			if ($row_detail['designation']) {
				preg_match('/^(.*?)<desi2>(.*?)<\/desi2>$/smi',$row_detail['designation'],$matches);
				if (isset($matches[2])) {	// nouveau style avec le <desi2>
					$desi1 = $matches[1];
					$desi2 = $matches[2];
				} else {			// ancien style sans desi2
					$desi1 = $row_detail['designation'];
					$desi2 = '';
				}
				$custom_ligne = preg_replace('/\s+name="a_designation\[\]"><\/textarea>\s*/i',' name="a_designation[]">'.stripslashes($desi1).'</textarea>',$custom_ligne);
				$custom_ligne = preg_replace('/\s+name="a_2designation\[\]"><\/textarea>\s*/i',' name="a_2designation[]">'.stripslashes($desi2).'</textarea>',$custom_ligne);
			}
			if ($row_detail['qte'])
				$custom_ligne = preg_replace('/\sname="a_qte\[\]"\s+value="0"\s/i',' name="a_qte[]" value="'.$row_detail['qte'].'" ',$custom_ligne);
			if ($row_detail['puht'])
				$custom_ligne = preg_replace('/\sname="a_pu\[\]"\s+value="0"\s/i',' name="a_pu[]" value="'.$row_detail['puht'].'" ',$custom_ligne);
			if ($row_detail['pu_adh_ht'])
				$custom_ligne = preg_replace('/\sname="a_adh_pu\[\]"\s+value="0"\s/i',' name="a_adh_pu[]" value="'.$row_detail['pu_adh_ht'].'" ',$custom_ligne);

			echo my_utf8_decode($custom_ligne);
		}
	} ?>
		</tbody>
		<tfoot>
			<td style="text-align:left" colspan="8">
				<input type="button" value="Ajouter une ligne"	id="add_ligne"		class="button divers" />&nbsp;
				<input type="button" value="Ajouter dix lignes" id="add_dix_ligne"	class="button divers" />
			</td>
		</tfoot>
	</table>
</fieldset>

<fieldset class="total">
    <legend>Total :</legend>
	<div id="div_bouton">
		<input type="button" value="G�n�rer le devis"							class="button pdf divers" onclick="valide_form('');" />&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" value="G�n�rer le devis sans Ent�te"				class="button pdf divers" onclick="valide_form('no_header');" />&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" value="G�n�rer le devis en prix ADH"				class="button pdf divers discret" onclick="valide_form('px_adh');" />&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" value="G�n�rer le devis en prix ADH sans Ent�te"	class="button pdf divers discret" onclick="valide_form('px_adh,no_header');" />
	</div>
	<div id="div_total">
		<span id="options"></span>&nbsp;&nbsp;
		Total <sup>ht</sup>&nbsp;:&nbsp;&nbsp;<span id="total"></span> &euro;
	</div>
</fieldset>

</form>

</div>
<div class="hide_when_print" style="border:solid 1px black;margin-top:10px;">
<input type="button" class="button divers" style="background-image:url(../gfx/list.gif);margin:4px;" onclick="document.location.href='historique_devis.php';" value="Historique des devis">
</div>
</body>
</html>
<?
mysql_close($mysql);
?>