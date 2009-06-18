<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$droit = recuperer_droit();

if (!($droit & PEUT_CREER_DEVIS)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
}


// mode creation ou modification ?
$id = isset($_GET['id']) ? mysql_escape_string($_GET['id']) : '' ;

// recherche du l'entete du devis si en modification
if($id) { // modif
	$res_devis = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d/%m/%Y') AS date_formater,DATE_FORMAT(`date`,'%H:%i') AS heure,DATE_FORMAT(date_maj,'%d/%m/%Y') AS date_maj_formater,CONCAT(DATE_FORMAT(`date`,'%b%y-'),id) AS numero FROM devis WHERE id='$id' LIMIT 0,1") or die("Requete impossible ".mysql_error()) ;
	$row_devis = mysql_fetch_array($res_devis);
}

//print_r($row_devis);

?><html>
<head>
<title><?= $id ? "Modification du $row_devis[numero]" : "Création de devis" ?></title>
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_devis.ico" />
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/jscalendar/calendar-brown.css);</style>
<style type="text/css">@import url('paging.css');</style>
<script type="text/javascript" src="../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../js/jscalendar/calendar-setup.js"></script>
<script type="text/javascript" src="paging.js"></script>
<script type="text/javascript" src="../js/jquery.js"></script>
<script language="javascript">
function affiche_adherent() {
	if (document.creation_devis.artisan_nom.options[document.creation_devis.artisan_nom.selectedIndex].value != 'NON Adherent')
		$('#artisan_nom_libre').hide('fast');
	else
		$('#artisan_nom_libre').show('fast');
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
	$('input[type=text]').unbind('blur');
	$('input[type=text]').unbind('focus');
	$('input[type=text]').blur(function()	{	$(this).css('background','');	});
	$('input[type=text]').focus(function()	{	$(this).css('background','#e7eef3');	});

	// click sur options
	$('input[name=checkbox]').unbind('click');
	$('input[type=checkbox]').click(function() {
		$(this).parent('td').parent('tr').children('td').children('input[name^=a_hid_opt]').val( $(this).attr('checked') ? '1' : '0'  );
		recalcul_total();
	});

	// ajoute un ligne au dessus de la ligne courante
	$('img[name^=a_add]').unbind('click');
	$('img[name^=a_add]').click(function() {
		$(this).parent().parent().before( pattern_ligne );
		make_all_bind();
	});

	// supprime une ligne du tableau en cliquant sur l'image
	$('img[name^=a_del]').unbind('click');
	$('img[name^=a_del]').click(function() {
		$(this).parent().parent().remove();  // supprime le TR
	});


	// on doit aller chercher les infos dans la BD et les ramener sur la page
	$('input[name^=a_reference]').unbind('keyup');
	$('input[name^=a_reference]').keyup(function() {
		tr = $(this).parents().parents('tr');
		recherche = $(this).val();
		var div_offset = $(this).offset();
		var div_height = $(this).height();
		//alert(recherche + ' ' + recherche.length);
		if (recherche.length >= 2) { // au moins deux car pour lancer la recherche
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
						'<td style="padding-right:10px;">' + all_results[i].reference.toUpperCase().replace(recherche.toUpperCase(),'<strong>'+recherche.toUpperCase()+'</strong>') + '<td>' +
						'<td style="color:green;padding-right:10px;">'		+ all_results[i].fournisseur + '<td>' +
						'<td style="padding-right:10px;width:500px;">'		+ all_results[i].designation +
								(all_results[i].couleur ? '<br/>Couleur : '	+all_results[i].couleur:'') +
								(all_results[i].taille ? '<br/>Taille : '	+all_results[i].taille:'') +'<td>' +
						'<td style="font-weight:bold;">' + Math.round(all_results[i].prix * 100)/100 + '&euro;<td>' +
					'</tr>'
		); // on affiche les suggestions
	}

	div.append('</tbody><tfoot><tr><td colspan="4">'+all_results.length+' résultat(s)&nbsp;&nbsp;&nbsp;&nbsp;');
	
	if (pageno > 1) div.append('<span class="navig"><a href="javascript:draw_page(1);">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+ parseInt(pageno-1) +');">&lt;prec.</a></span>&nbsp;&nbsp;&nbsp;&nbsp;');
	div.append('Page '+pageno);
	if (pageno < lastpage) div.append('&nbsp;&nbsp;&nbsp;&nbsp;<span class="navig"><a href="javascript:draw_page('+ parseInt(pageno+1) +');">suiv.&gt;</a>&nbsp;&nbsp;&nbsp;<a href="javascript:draw_page('+lastpage+');">&gt;&gt;</a></span>');

	div.append('<div style="float:right;"><a href="javascript:cache_sugest();">Fermer [X]</a></div></td></tr></tfoot></table>');
}


function insert_ligne(id) {
	//alert(id);
	$('div#sugest').hide(); // on cache la boite
	$.getJSON('ajax.php', { what:'get_detail', val: id  } ,
			function(data){
				//alert(data.couleur);
				var tmp = tr.children('td') ;
				tmp.children('input[name^=a_reference]').val(data.reference);
				tmp.children('input[name^=a_fournisseur]').val(data.fournisseur);
				tmp.children('textarea[name^=a_designation]').val(
								data.designation +
								(data.couleur ? '\nCouleur : '+data.couleur:'') +
								(data.taille ? '\nTaille : '+data.taille:''));
				tmp.children('input[name^=a_qte]').val(1);

				// pour le débug des prix.
				tmp.children('input[name^=a_pu]').parents('td').html('<input type="text" name="a_pu[]" value="" class="pu" onkeyup="recalcul_total();"/>');
				tmp.children('input[name^=a_pu]').val((Math.round(data.prix		* 100)/100)); // prix expo
				tmp.children('input[name^=a_adh_pu]').val((Math.round(data.px_adh	* 100)/100)); // prix adh

				// debug des différents prix calculé.
				tmp.children('input[name^=a_pu]').after('<br/>coop '	+ Math.round(data.px_coop		* 100)/100 + 
														'<br/>adh '		+ Math.round(data.px_adh		* 100)/100 + 
														'<br/>expo '	+ Math.round(data.px_expo		* 100)/100 + 
														'<br/>pub '		+ Math.round(data.px_public	* 100)/100
														);
				recalcul_total();
			}
	);
	
}


function recalcul_total() {
	total = 0;
	option = 0;
	$('input[name^=a_qte]').each(function() {
		var pu	= parseFloat($(this).parents('tr').children('td').children('input[name^=a_pu]').val());
		var qte = parseFloat($(this).parents('tr').children('td').children('input[name^=a_qte]').val());
		if (pu >= 0 && qte >=0) {
			var val = (Math.round(qte * pu * 100)/100) ;
			$(this).parents('tr').children('td[name^=a_pt]').html(val + '&euro;');
			// on vérifie si c'est une option
			if ($(this).parents('tr').children('td').children('input[name^=a_opt]').attr('checked')) // cas d'une option, on ne l'a compte pas dans le total
				option++;
			else
				total += val ;
		} else
			$(this).parents('tr').children('td[name^=a_pt]').text('');
	});

	$('span#total').text(Math.round(total * 100)/100);
	if (option > 0)
		$('span#options').text("Le total ne tient pas compte des " + option + " option(s) choisit");
	else
		$('span#options').text('');
}


<?

$pattern_ligne = <<<EOT
<tr>
	<td>
		<img src="gfx/add.png" name="a_add" title="Ajoute une ligne au dessus" /><br/>
		<img src="../gfx/delete_micro.gif" name="a_del" title="Supprime la ligne" />
		<input type="hidden"	name="a_adh_pu[]"	value="0"/>
		<input type="hidden"	name="a_hid_opt[]"	value="0"/>
	</td>
	<td class="opt"><input type="checkbox" name="a_opt[]" /><label for="a_opt">Opt.</label></td>
	<td><input type="text"		name="a_reference[]"	value=""		class="ref"			 autocomplete="off" /></td>
	<td><input type="text"		name="a_fournisseur[]"	value=""		class="fournisseur"	 /></td>
	<td><textarea				name="a_designation[]"	rows="3"	class="designation"></textarea></td>
	<td><input type="text"		name="a_qte[]"		value="0"	class="qte" onkeyup="recalcul_total();"/></td>
	<td><input type="text"		name="a_pu[]"		value="0"	class="pu"  onkeyup="recalcul_total();"/></td>
	<td name="a_pt"></td>
</tr>
EOT;

?>


var pattern_ligne = '<?=ereg_replace("[\n\r]",'',$pattern_ligne)?>' ;


$(document).ready(function(){
	
	$('#add_dix_ligne').click(function() {
		for(var i=0 ; i<=9 ; i++)
			$('#lignes tbody').append( pattern_ligne );
		make_all_bind();
	});

	// ajoute une ligne à la fin du tableau
	$('#add_ligne').click(function() {
		$('#lignes tbody').append( pattern_ligne );
		make_all_bind();
	}); // fin add ligne
	

	// au chargement de la page, on ajoute une ligne au tableau des details
<?	if (!$id) { // si aucune ligne sur le devis, on en propose une ?>
		$('#add_ligne').click();
<?	} ?>

	make_all_bind();
	recalcul_total();
}); // fin on document ready

</script>

<style>

body {
	font-family:verdana;
	font-size:0.8em;
}

sup {	font-size:10px; }

fieldset {	border:solid 1px #6290B3; }
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

</style>

<body>
<div id="sugest"></div><!-- pour la sugestion des résultat ajax -->

<form method="post" action="generation_devis_pdf.php" name="creation_devis">
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
				if ($id) { //modif ?>
					<option value="<?=$row['prenom']?>"<?= $row_devis['representant']==$row['prenom'] ? ' selected':''?>><?=$row['prenom']?></option>
<?				} else { // creation ?>
					<option value="<?=$row['prenom']?>"<?= $_SERVER['REMOTE_ADDR']==$row['ip'] ? ' selected':''?>><?=$row['prenom']?></option>
<?				}	
			} ?>
		</select>
	</td>
	<th>Client</th>
	<th>Nom</th>
	<td><input type="text" name="client_nom" size="45" TABINDEX="6" value="<?= $id ? $row_devis['nom_client']: ''?>"></td>
</tr>
<tr>
	<th>Artisan</th>
	<td>
		<select name="artisan_nom" onchange="affiche_adherent();" TABINDEX="2">
			<option value="NON Adherent">Artisan NON ADHERENT</option>
<?			$res  = mysql_query("SELECT nom FROM artisan WHERE suspendu=0 ORDER BY nom ASC");
			$a_trouve_artisan = FALSE ;
			while ($row = mysql_fetch_array($res)) {
				if ($id) { //modif ?>
					<option value="<?=$row['nom']?>"<? if ($row_devis['artisan']==$row['nom']) { echo ' selected'; $a_trouve_artisan = TRUE ; } ?>><?=$row['nom']?></option>
<?				} else { // creation ?>
					<option value="<?=$row['nom']?>"><?=$row['nom']?></option>
<?				}	
			} ?>
		</select><br/><input id="artisan_nom_libre" <?= $a_trouve_artisan ? 'style="visibility:hidden;"' : ''?> type="text" name="artisan_nom_libre" value="<?= $id && !$a_trouve_artisan ? $row_devis['artisan']:''; ?>">
	</td>
	<td></td>
	<th>Adresse (ligne 1)</th>
	<td><input type="text" name="client_adresse" size="45" TABINDEX="7" value="<?= $id ? $row_devis['adresse_client']: ''?>"></td>
</tr>
<tr>
	<th>Date</th>
	<td nowrap>
		<input type="text" id="devis_date" name="devis_date" value="<?= $id ? $row_devis['date_formater'] : date('d/m/Y')?>" size="8">
		<img src="../js/jscalendar/calendar.gif" id="trigger" style="vertical-align:middle;cursor: pointer;"title="Date selector" />
		<script type="text/javascript">
		  Calendar.setup(
			{
			  inputField	: 'devis_date',         // ID of the input field
			  ifFormat		: '%d/%m/%Y',    // the date format
			  button		: 'trigger',       // ID of the button
			  date			: '<?= $id ? $row_devis['date_formater'] : date('d/m/Y')?>',
			  firstDay 	: 1
			}
		  );
		</script>

	Heure <input type="text" name="devis_heure" size="5" maxlength="5" value="<?= $id ? $row_devis['heure'] : date('G:i')?>" TABINDEX="5"></td>
	<td></td>
	<th>Adresse (ligne 2)</th>
	<td><input type="text" name="client_adresse2" size="45" TABINDEX="8" value="<?= $id ? $row_devis['adresse_client2']: ''?>"></td>
</tr>
<tr>
	<td></td>
	<td></td>
	<td></td>
	<th>Code Postal / Ville</th>
	<td>
		<input type="text" name="client_codepostal" size="5" maxsize="5" TABINDEX="9" value="<?= $id ? $row_devis['codepostal_client']: ''?>">
		<input type="text" name="client_ville" size="35" TABINDEX="10" value="<?= $id ? $row_devis['ville_client']:''?>">
	</td>
</tr>
<tr>
	<td class="devis_id"><?= $id ? "Devis N°$id" :'' ?></td>
	<td>
	</td>
	<td></td>
	<th>Tél / Mobile</th>
	<td>
		<input type="text" name="client_telephone" TABINDEX="11" value="<?= $id ? $row_devis['tel_client']: ''?>">
		<input type="text" name="client_telephone2" TABINDEX="12" value="<?= $id ? $row_devis['tel_client2']: ''?>">
	</td>
</tr>
<tr>
	<td></td>
	<td></td>
	<td></td>
	<th>Email</th>
	<td>
		<input type="text" name="client_email" TABINDEX="13" value="<?= $id ? $row_devis['email_client']: ''?>" size="45">
	</td>
</tr>
</table>
</fieldset>

<fieldset id="detail">
    <legend>Lignes :</legend>
	<table id="lignes">
		<thead>
		<tr>
			<th class="no_ligne">#</th>
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
<?	if ($id) {
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
			if ($row_detail['designation'])
				$custom_ligne = preg_replace('/\s+class="designation"\s*><\/textarea>/i',' class="designation">'.$row_detail['designation'].'</textarea>',$custom_ligne);
			if ($row_detail['qte'])
				$custom_ligne = preg_replace('/\sname="a_qte\[\]"\s+value="0"\s/i',' name="a_qte[]" value="'.$row_detail['qte'].'" ',$custom_ligne);
			if ($row_detail['puht'])
				$custom_ligne = preg_replace('/\sname="a_pu\[\]"\s+value="0"\s/i',' name="a_pu[]" value="'.$row_detail['puht'].'" ',$custom_ligne);
			if ($row_detail['pu_adh_ht'])
				$custom_ligne = preg_replace('/\sname="a_adh_pu\[\]"\s+value="0"\s/i',' name="a_adh_pu[]" value="'.$row_detail['pu_adh_ht'].'" ',$custom_ligne);

			echo $custom_ligne;
		}
	} ?>
		</tbody>
		<tfoot>
			<td style="text-align:left" colspan="8">
				<input type="button" value="Ajouter une ligne" id="add_ligne" class="button" style="background:#e7eef3;" />&nbsp;
				<input type="button" value="Ajouter dix lignes" id="add_dix_ligne" class="button" style="background:#e7eef3;" />
			</td>
		</tfoot>
	</table>
</fieldset>

<fieldset class="total">
    <legend>Total :</legend>
	<div id="div_bouton">
		<input type="submit" value="Générer le devis" class="button pdf" style="background-color:#e7eef3;" />
		<input type="button" value="Générer le devis en prix ADH" style="border:none;background:none;color:white;" />
	</div>
	<div id="div_total">
		<span id="options"></span>&nbsp;&nbsp;
		Total <sup>ht</sup>&nbsp;:&nbsp;&nbsp;<span id="total"></span> &euro;
	</div>
</fieldset>

</form>

</div>
</body>
</html>
<?
mysql_close($mysql);
?>