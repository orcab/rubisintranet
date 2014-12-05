<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$droit = recuperer_droit();

if (!($droit & PEUT_CREER_DEVIS)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
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
		if ($_SERVER['REMOTE_ADDR']==$row['ip']) { // on a trouvé l'éditeur
			$representant = mysql_escape_string($row['prenom']); break;
		}

	// mode creation
	// on crée un faux devis qu'il faudra compléter
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
		// si on trouve un brouillon --> on met la variable draft_find à TRUE
		if (mysql_num_rows(mysql_query("SELECT id FROM devis_draft WHERE id='$id' LIMIT 0,1")))
			$draft_find = TRUE;

		$res_devis = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d/%m/%Y') AS date_formater,DATE_FORMAT(`date`,'%H:%i') AS heure,DATE_FORMAT(date_maj,'%d/%m/%Y') AS date_maj_formater,CONCAT(DATE_FORMAT(`date`,'%b%y-'),id) AS numero FROM devis WHERE id='$id' LIMIT 0,1") or die("Requete impossible ".mysql_error()) ;
	}

	$row_devis = mysql_fetch_array($res_devis);
}
?><html>
<head>
<title><?= $modif ? "Modification du $row_devis[numero]" : "Création du devis ".date('My')."-$id" ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_devis.ico" />
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/jscalendar/calendar-brown.css);</style>
<style type="text/css">@import url(devis.css);</style>
<script type="text/javascript" src="../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../js/jscalendar/calendar-setup.js"></script>
<script type="text/javascript" src="../js/jquery.js"></script>
<script language="javascript" src="../js/utf8.js"></script>
<script type="text/javascript" src="../js/mobile.style.js"></script>
<script language="javascript">

var auto_save = true;

<? if ($draft_find) { ?>
	if (confirm("Un brouillon a été trouvé pour votre devis\nVoulez vous le recharger ?")) {
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


// gestion des phrases
var last_btn_phrase_push;
var phrases = [];
<?	// selection des phrases pré-établies pour les designations
	$res = mysql_query("SELECT mot_cle,phrase FROM phrase WHERE app='devis' AND deleted=0 ORDER BY mot_cle ASC") or die("Requete de selection des phrases pré-enreistrées impossible ".mysql_error()) ;
	while($row = mysql_fetch_array($res)) { ?>
		phrases['<?=preg_replace("/'/","",$row['mot_cle'])?>'] = "<?=utf8_decode(preg_replace("/[\n|\r]+/",'\\n',preg_replace('/"/',"\\\"",$row['phrase'])))?>";

		//" juste pour la coloration syntaxique
<?	} ?>

function affiche_choix_phrase(btn_elm) {
	// placement du div
	last_btn_phrase_push = btn_elm;	// on stock quel bouton a été appuyer pour pouvoir affecté le texte au bon textarea ensuite (les div sont générique)
	var div_offset = $(btn_elm).offset();
	var div_height = $(btn_elm).height();
	var html = '<ul>';
	for(var mot_cle in phrases) {
		html += '<li>'+mot_cle+'</li>';
	}
	html += '</ul><div style="float:right;text-align:right;margin-top:1em;"><a href="javascript:cache_choix_phrase();">Fermer [X]</a><br/><a href="modification_phrase.php?app=devis" target="_blank">Editer les phrases</a></div>';

	$('div#phrase').css({'top': div_offset.top + div_height + 5, 'left': div_offset.left }).html(html).show('fast');
}

function cache_choix_phrase() {
	$('div#phrase').hide('fast');
}

// on clique sur la phrase de notre choix
function affiche_phrase(li_elm) {
	var phrase= phrases[$(li_elm).text()];
	cache_choix_phrase();

	// colle la phrase dans le bon cadre
	var textarea;
	textarea = $(last_btn_phrase_push).next('textarea.designation');
	$(textarea).val($(textarea).val().length > 0 ? $(textarea).val() + "\n" + phrase : phrase);
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
	lastpage   = Math.ceil(all_results.length / nb_results_by_page);
	html = '<table id="results"><tbody>'; // on construit la boite de sugestion
	
	for(i=nb_results_by_page * (pageno-1) ; i<all_results.length && i<nb_results_by_page * (pageno-1) + nb_results_by_page ; i++) {
		html +=	'<tr onclick="insert_ligne(\''+all_results[i].rowid+'\');">' + 
						'<td class="ref">' + all_results[i].reference.toUpperCase().replace(recherche.toUpperCase(),'<strong>'+recherche.toUpperCase()+'</strong>')+'</td>'+
						'<td class="fournisseur">'	+ all_results[i].nom_fournisseur										+ '</td>' +
						'<td class="logo">'			+ (all_results[i].code_mcs ? '<img src="gfx/logo_mcs_micro.png"/>':'')	+ '&nbsp;</td>' +
						'<td class="designation">'	+ all_results[i].designation1											+ '</td>' +
						'<td class="px">'			+ parseFloat(all_results[i].px_public).toFixed(2)						+ '&euro;</td>' +
						'<td class="'+(all_results[i].px_from == 'pp' ? 'pp':'')+'">'+ (all_results[i].px_from == 'pp' ? 'pp':'&nbsp;') + '</td>' +
						'<td class="ecotaxe">'+ (all_results[i].ecotaxe > 0 ? '('+all_results[i].ecotaxe+'&euro;)':'&nbsp;')	+ '</td>' +
					'</tr>' ; // on affiche les suggestions
	} // fino pour chaque résultat

	html +=	'</tbody><tfoot><tr><td colspan="7">'+all_results.length+' résultat(s)&nbsp;&nbsp;&nbsp;&nbsp;';
	
	if (pageno > 1)
		html +=	'<span class="navig"><a href="javascript:draw_page(1);">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+ parseInt(pageno-1) +');">&lt;prec.</a></span>&nbsp;&nbsp;&nbsp;&nbsp;';
	html +=	'Page '+pageno ;
	if (pageno < lastpage)
		html +=	'&nbsp;&nbsp;&nbsp;&nbsp;<span class="navig"><a href="javascript:draw_page('+ parseInt(pageno+1) +');">suiv.&gt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+lastpage+');">&gt;&gt;</a></span>';

	html +=	'<div style="float:right;"><a href="javascript:cache_sugest();">Fermer [X]</a></div></td></tr></tfoot></table>';

	$('div#sugest').html(html); // rendering du résultat
}


// ou a choisit une ligne parmis les propositions --> on insert les données
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
				tmp.children('textarea[name^=a_designation]').val(data.designation1 + (data.ecotaxe>0 ? "\nDont "+data.ecotaxe.replace('.',',')+"€ d'ecotaxe" : ''));
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
	var total		= 0.0;
	var total_adh	= 0.0;
	var option		= 0;
	$('input[name^=a_qte]').each(function() {
		var parent_tr	= $(this).parents('tr');
		var parent_td	= parent_tr.children('td');
		var pu			= parseFloat(parent_td.children('input[name^=a_pu]').val().replace(',','.'));
		var pu_adh		= parseFloat(parent_td.children('span').children('input[name^=a_adh_pu]').val().replace(',','.'));
		var qte			= parseFloat(parent_td.children('input[name^=a_qte]').val().replace(',','.'));

		if (pu_adh <= 0 && qte > 0) // si le prix est a 0, on le met en évidence
			parent_td.children('span').children('input[name^=a_adh_pu]').css('background-color','red').css('background-image','none');

		if (pu >= 0 && qte >=0) {
			var val		= qte * pu ;
			var val_adh = qte * pu_adh ;
			parent_tr.find('span.total_px_public').html(val.toFixed(2) + '&euro;');
			parent_tr.find('span.total_px_adh').html(val_adh.toFixed(2) + '&euro;');
			// on vérifie si c'est une option
			if (parent_td.children('input[name^=a_opt]').attr('checked')) { // cas d'une option, on ne l'a compte pas dans le total
				option++;
			} else {
				total		+= val ;
				total_adh	+= val_adh ;
			}
		} else {
			parent_tr.find('span.total_px_public').text('');
			parent_tr.find('span.total_px_adh').text('');
		}
	});

	$('span#total').text(total.toFixed(2));
	$('span#total_adh').text(total_adh.toFixed(2));
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
	<td nowrap="nowrap" class="designation">
		<input type="button" class="phrase phrase_cli" value="..." />
		<textarea				rows="2"	class="designation designation1" name="a_designation[]"></textarea>
		<br/>
		<input type="button" class="phrase phrase_adh" value="..." />
		<textarea				rows="2"	class="designation designation2" name="a_2designation[]"></textarea>
		<br/>
		<input type="button" class="colorpicker colorpicker_color" value="A" style="font-weight:bold;"/>
		<input type="button" class="colorpicker colorpicker_background-color" value="&nbsp;&nbsp;"/>
		<input type="hidden" name="a_designation_color[]" value=""/>
		<input type="hidden" name="a_designation_background-color[]" value=""/>
	</td>
	<td>
		<input type="text"		name="a_qte[]"		value="0"	class="qte" onkeyup="recalcul_total();"/>
	</td>
	<td style="text-align:right;">
		<input type="text"		name="a_pu[]"		value="0"	class="pu"  onkeyup="recalcul_total();"/>
		<span class="discret"><br/>Adh <input type="text"	name="a_adh_pu[]"	value="0" class="pu" /></span>
		<div class="discret"></div>
	</td>
	<td name="a_pt">
		<span class="total_px_public"></span>
		<span class="discret"><br/><span class="total_px_adh"></span></span>
	</td>
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

			if (typeof(data[document.creation_devis.elements[i].name]) == 'undefined' || typeof(data[document.creation_devis.elements[i].name]) == 'string') { // le tablea n'est pas encore crée
				// il faut cree un tableau car il y a plusieur element avec le meme nom
				var tmp = data[document.creation_devis.elements[i].name]; // on sauvegarde l'ancienne valeur
				data[document.creation_devis.elements[i].name] = new Array();
				data[document.creation_devis.elements[i].name].push(tmp);
				data[document.creation_devis.elements[i].name].push(document.creation_devis.elements[i].value);
			} else { // le tableau est deja crée, il faut lui rajouter une entrée
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
				window.setTimeout("sauvegarde_auto()", 1000*60*2 );  // pour répéter l'opération régulièrement toutes les 2min
		});
	}
}


function lance_recherche() {
	all_results = Array(); // on vide la mémoire des résultats

	// un loading pour patienter
	tr.children('td').children('input[name^=a_reference]').addClass('loading');

	// on recherche dans la BD les nouvelles conrerespondances
	$.getJSON('ajax.php', { what:'complette_via_ref', val:recherche },
		function(data){
			// on les stock plus un affichage ultérieur
			all_results = data ;
			
			// on affiche la premier epage de résultat
			if (all_results.length > 0) draw_page(1);
			else						$('div#sugest').html('<img src="../gfx/attention.png" /> Aucun résultat');

			// placement du div
			$('div#sugest').css({ 'top':div_offset.top + div_height + 5 , 'left':div_offset.left }).show('fast');

			// on supprime le loading
			tr.children('td').children('input[name^=a_reference]').removeClass('loading');
		} // fin fonction
	); // fin getJson
}


var pattern_ligne = '<?=preg_replace("/[\n\r]/",'',$pattern_ligne)?>' ;

$(document).ready(function(){
		
	// bouton qui ajoute une ligne à la fin du tableau
	$('#add_ligne').click(function() {
		$('#lignes tbody').append( pattern_ligne );
		if ($('#discret_mode').attr('checked')) $('.discret').show(); // affiche ou non les cases spécial a la creation de la ligne
		else									$('.discret').hide();
	}); // fin add ligne
	
	// bouton qui ajoute 10 lignes d'un coup
	$('#add_dix_ligne').click(function() {
		for(var i=0 ; i<=9 ; i++)
			$('#lignes tbody').append( pattern_ligne );
	});

	// click sur le mode discret
	$('#discret_mode').click(function() {
		if ($(this).attr('checked'))	$('.discret').show();
		else							$('.discret').hide();
	});
	
	// active l'auto-save en cas de modification
	$('body').delegate('input[type=text],textarea','change',function(){
		auto_save = true ;
	}); // en cas de modif d'une valeur, on réactive l'auto_save


	// click sur options
	$('body').delegate('input[name^=a_opt]','click',function() {
		$(this).parents('tr').children('td').children('input[name^=a_hid_opt]').val( $(this).attr('checked') ? '1' : '0'  );
		recalcul_total();
	});

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
		// supprime l'ancien timer pour que la recherche qui était lancer, ne se fasse pas
		clearTimeout(timer);

		if (e.which == 13 && all_results.length == 1) { // la touche ENREE et il n'y a qu'un seul résultat --> on le selectionne
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

	// affichage des phrases pré-enregistrées
	$('body').delegate('input.phrase','click',function() {
		affiche_choix_phrase(this);
	});

	// affichage des phrases pré-enregistrées
	$('div#phrase').delegate('li','click',function() {
		affiche_phrase(this);
	});

	// affichage des phrases pré-enregistrées
	$('body').delegate('input.colorpicker_color','click',function() {
		createColorPicker(this,'color');
	});

	$('body').delegate('input.colorpicker_background-color','click',function() {
		createColorPicker(this,'background-color');
	});

	$('body').delegate('canvas.colorpicker','mousemove',function(e) {
		var pos = findPos(this);
		var c = this.getContext('2d');
		var p = c.getImageData(e.pageX - pos.x, e.pageY - pos.y, 1, 1).data; 
		var hex = "#" + ("000000" + rgbToHex(p[0], p[1], p[2])).slice(-6);
		$(this).parent('td').children('textarea.designation').css($(this).attr('data-type'),hex);
	});

	$('body').delegate('canvas.colorpicker','click',function(e) {
		var pos = findPos(this);
		var c = this.getContext('2d');
		var p = c.getImageData(e.pageX - pos.x, e.pageY - pos.y, 1, 1).data; 
		var hex = '#' + ('000000' + rgbToHex(p[0], p[1], p[2])).slice(-6);

		var parent_td = $(this).parent('td');
		parent_td.children('textarea.designation').css($(this).attr('data-type'),hex);
		if ($(this).attr('data-type') == 'color') {
			parent_td.children('input.colorpicker_color').css('color',hex);
			parent_td.children('input[name^=a_designation_color]').val(p[0]+','+p[1]+','+p[2]);
		} else {
			parent_td.children('input.colorpicker_background-color').css('background-color',hex);
			parent_td.children('input[name^=a_designation_background]').val(p[0]+','+p[1]+','+p[2]);
		}
		$(this).remove();
	});


	// au chargement de la page, on ajoute une ligne au tableau des details
<?	if (!$modif) { // si aucune ligne sur le devis, on en propose une ?>
		$('#add_ligne').click();
<?	} ?>

	recalcul_total();

	// on lance la procédure auto-save
	window.setTimeout ("sauvegarde_auto()", 1000*60*2); // on sauve regulierement
}); // fin on document ready


function createColorPicker(elem,prop) {
	var pos = findPos(elem);
	var canvas = document.createElement('canvas');
	$(canvas).addClass('colorpicker').css({'left':pos.x - 75,'top':pos.y - 75}).attr('data-type',prop);
	$(elem).after(canvas);
	
	var context = canvas.getContext('2d');
	var wheel = new Image();
  	wheel.src = '../js/colorpicker/wheel.png';
  	wheel.onload = function() {
		context.drawImage(wheel,0,0);
	}
}

function findPos(obj) {
    var curleft = 0, curtop = 0;
    if (obj.offsetParent) {
        do {
            curleft += obj.offsetLeft;
            curtop += obj.offsetTop;
        } while (obj = obj.offsetParent);
        return { x: curleft, y: curtop };
    }
    return undefined;
}

function rgbToHex(r, g, b) {
    if (r > 255 || g > 255 || b > 255)
        throw "Invalid color component";
    return ((r << 16) | (g << 8) | b).toString(16);
}


</script>

<style>

fieldset {	border:solid 1px #6290B3; }

.discret {
	display:none;
	color:#5595C6;
}

.discret input { color:#5595C6; }
input.discret  { color:#5595C6; }

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
	margin-bottom:70px;
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

#div_bouton {
	text-align:left;
	vertical-align:top;
	padding-top: 5px;
}

#div_total {
	text-align:right;
	font-weight:bold;
	width:33%;
	vertical-align:top;
	padding-top: 5px;
}

td.devis_id { font-weight:bold; }
input.qte	{ text-align:center;	width:3em; }
input.pu	{ text-align:right;		width:5em; }
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

span#options {
	font-weight:normal;
	font-size:0.8em;
}

/* sert a mettre les totaux en face des input de prix unitaire */
.total_px_public	{ line-height: 140%; }
.total_px_adh		{ line-height: 200%; }


#add_ligne		{ background-image: url(gfx/plus_one.png); }
#add_dix_ligne	{ background-image: url(gfx/plus_ten.png);   padding-left: 25px; }

textarea.designation {
	background-repeat: no-repeat;
    background-position: 93% 100%;
	width:30em;
}
.designation1 { background-image: url(gfx/cadre_client.png); }
.designation2 { background-image: url(gfx/cadre_artisan.png); }

input.phrase { vertical-align:top; }

/* phrases */
div#phrase {
	border:solid 1px #6290B3;
	background:#e7eef3;
	font-size:0.7em;
	display:none;
	position:absolute;
	top:0;
	left:0;
	cursor:pointer;
	padding:3px;
	padding-bottom: 70px;
}

div#phrase ul {
	list-style-image: url('gfx/arrow.png');
	padding-left: 20px;
    margin: 0;
}

#historique-devis {
	margin-right:3em;
}

#pied-de-page {
    border: solid 1px black;
    position: fixed;
    bottom: 5;
    width: 98%;
	text-align: left;
	padding-right:2em;
	background-color:white;
	font-size:0.9em;
}

canvas.colorpicker {
	width:300px;
	height:150px;
	border:none;
	cursor:crosshair;
	position:absolute;
}

</style>

<body>
<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div id="sugest"></div><!-- pour la sugestion des résultat ajax -->
<div id="phrase"></div><!-- pour la sugestion des phrases -->

<form method="post" action="generation_devis_pdf.php" name="creation_devis">
<input type="hidden" name="les_options" value="" />
<input type="hidden" name="id_devis" value="<?=$id?>" />

<div id="cadre-exterieur">

<fieldset id="entete">
    <legend>Entête :</legend>
<table>
<tr>
	<th style="width:10%;">Représentant</th>
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
<?			$res  = mysql_query("SELECT nom,numero FROM artisan WHERE suspendu='0' ORDER BY nom ASC") or die("Ne peux pas récupérer la liste des adhérents ".mysql_error());
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
	<td class="devis_id">Devis N°<?=$id?></td>
	<td style="padding-left:10px;">N° devis Rubis <input type="text" size="10" name="devis_num_devis_rubis" TABINDEX="13" value="<?= $modif ? $row_devis['num_devis_rubis']:''?>"/></td>
	<td></td>
	<th>Tél / Mobile</th>
	<td>
		<input type="text" name="client_telephone" TABINDEX="11" value="<?= $modif ? $row_devis['tel_client']: ''?>">
		<input type="text" name="client_telephone2" TABINDEX="12" value="<?= $modif ? $row_devis['tel_client2']: ''?>">
	</td>
</tr>
<tr>
	<td colspan="3"></td>
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
			<th class="reference">Réf</th>
			<th class="fournisseur">Fournisseur</th>
			<th class="designation">Désignation</th>
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

				// style $row_detail['designation_color'] et $row_detail['designation_background-color']
				$style = array();
				if (strlen($row_detail['designation_color'])>0) {
					$tmp = explode(',', $row_detail['designation_color']);
					$color_hex = rgb2hex($tmp[0],$tmp[1],$tmp[2]);
					$color_rgb = $row_detail['designation_color'];

					$custom_ligne = preg_replace('/\s+name="a_designation_color\[\]"\s+value=""/i',' name="a_designation_color[]" value="'.$color_rgb.'" ',$custom_ligne);

					$custom_ligne = preg_replace('/\s+colorpicker_color" value="A" style="font-weight:bold;"/i',' colorpicker_color" value="A" style="font-weight:bold;color:'.$color_hex.'" ',$custom_ligne);

					$style[] = "color:$color_hex";
				}

				if (strlen($row_detail['designation_background-color'])>0) {
					$tmp = explode(',', $row_detail['designation_background-color']);
					$color_hex = rgb2hex($tmp[0],$tmp[1],$tmp[2]);
					$color_rgb = $row_detail['designation_background-color'];

					$custom_ligne = preg_replace('/\s+name="a_designation_background-color\[\]"\s+value=""/i',' name="a_designation_background-color[]" value="'.$color_rgb.'" ',$custom_ligne);

					$custom_ligne = preg_replace('/\s+colorpicker_background-color"/i',' colorpicker_background-color" style="background-color:'.$color_hex.'" ',$custom_ligne);

					$style[] = "background-color:$color_hex";
				}

				// custom style
				if (sizeof($style)>0) {
					$custom_ligne = preg_replace('/\s+name="a_designation\[\]">/i',' style="'.join(';',$style).'" name="a_designation[]">',$custom_ligne);
					$custom_ligne = preg_replace('/\s+name="a_2designation\[\]">/i',' style="'.join(';',$style).'" name="a_2designation[]">',$custom_ligne);
				}
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

</form>
</div>


<table class="hide_when_print" id="pied-de-page">
<tr>
	<td style="width:33%;height:50px;vertical-align:top;">
		<input type="button" class="button divers" id="historique-devis" style="background-image:url(../gfx/list.gif);margin:4px;" onclick="document.location.href='historique_devis.php';" value="Historique des devis">
		<label for="discret_mode" class="mobile"><input type="checkbox" id="discret_mode" name="discret_mode"/>Mode sp&eacute;cial</label>
	</td>
	<td id="div_bouton">
		<input type="button" value="Générer le devis"							class="button pdf divers" onclick="valide_form('');" />&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="button" value="Générer le devis en prix ADH"				class="button pdf divers discret" onclick="valide_form('px_adh');" />&nbsp;&nbsp;&nbsp;&nbsp;
	</td>
	<td id="div_total">
		<span id="options"></span>&nbsp;&nbsp;
		Total <sup>ht</sup>&nbsp;:&nbsp;&nbsp;<span id="total"></span> &euro;
		<span class="discret"><br/>Total adh <sup>ht</sup>&nbsp;:&nbsp;&nbsp;<span id="total_adh"></span> &euro;</span>
	</td>
</tr>
</table>

</body>
</html>
<? mysql_close($mysql);


function rgb2hex($r, $g, $b, $uppercase=false, $shorten=false) {
	$out = '';
	 
	if ($shorten && ($r + $g + $b) % 17 !== 0) $shorten = false;
	
	foreach (array($r, $g, $b) as $c) {
		$hex = base_convert($c, 10, 16);
		if ($shorten) $out .= $hex[0];
		else $out .= ($c < 16) ? ('0'.$hex) : $hex;
	}
	return '#'.($uppercase ? strtoupper($out) : $out);
}
?>