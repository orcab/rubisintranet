<?
include('../../inc/config.php');

define('DEBUG',false);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '' ;

if (DEBUG) { echo "<pre>" ; print_r($_POST) ; echo "</pre>\n"; }

$key1 = isset($_POST['key1']) ? $_POST['key1']:''; // n° de bon
$key2 = isset($_POST['key2']) ? $_POST['key2']:''; // n° de bon

?>
<html>
<head>
<title>Recherche dans la GED</title>
<style>

body {
	font-family:verdana;
	font-size:0.8em;
	margin:0;
}

td,th { font-size:0.8em; }
a > img { border:none; }
div#main { margin:5px; }

select {
	font-size:0.8em;
	font-family:verdana;
}

option.tiers-suspendu {
	color:#AAA;
	font-style:italic;
	text-decoration:line-through;
}

fieldset {
	border:solid 1px #6290B3;
	-moz-border-radius:5px;
	margin-bottom:2em;
}

fieldset legend {
	border:solid 1px #6290B3;
	background:#e7eef3;
	color:#325066;
	font-weight:bold;
	padding:3px;
	width:80px;
	text-align:center;
	-moz-border-radius:5px;
	-moz-box-shadow: 0 0 5px #6290B3;
}

div#more_options {
	display:<?= isset($_POST['more_options']) ? $_POST['more_options']:'none' ?>;
}

ul#resultats {
	list-style-type:none;
	padding:0px;
	margin:0px;
}

div.document {
	border:solid 1px #efefbb;
	float:left;
	text-align:center;
	margin:10px;
	padding:3px;
	font-size:0.7em;
}

div.document:hover {
	border-color:yellow;
	background-color:#fafaea;
	-moz-box-shadow: 0 0 5px yellow;
}

div.document img {
	width:100px;
	border:solid 1px #DDD;
}

div.document div.action {
	/*position:relative;
	top:-200px;
	text-align:left;*/
	opacity:0;
}

#message_printer {
	color:red;
	font-weight:bold;
}

div#footer {
	border-top:solid 1px #6290B3;
	position: fixed;
	bottom:0;
	width:100%;
	height: 30px; 
	background-color:white;
	padding-left:15px;
	padding-top:5px;
}

@media print {
	.hide_when_print { display:none; }
}
</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../../js/jscalendar/calendar-setup.js"></script>
<script language="javascript" src="../../js/jquery.js"></script>
<script language="javascript" src="../../js/mobile.style.js"></script>

<script language="javascript">
<!--

//appel le fichier php qui fera la conversion d'une image en fichier PDF
function img2pdf(filename) {
	top.preview.location.href='img2pdf.php?img_name='+filename;
}

function document_rejected() {
	top.preview.location.href='preview.php';
	document.location.href='rejected.php';
}

function verif_formulaire() {
	// met a jour la visibilité du choix du type de document
	document.recherche.more_options.value = $('#more_options').css('display') ;
	document.recherche.recherche.value = 1 ;
	return 1;
}

$(document).ready(function(){
	// binding
	$('.document').bind('mouseenter', function(event) {
		//$(this).children('.action').css({'left':'100px'}).animate({opacity: 1},400);
		$(this).children('.action').animate({opacity: 1},400);
	});

	$('.document').bind('mouseleave', function(event) {
		//$(this).children('.action').animate({opacity: 0},1000,function() { $(this).css({'left':'0px'}); });
		$(this).children('.action').animate({opacity: 0},1000);
	});

	count_documents();
});

function count_documents() {
	$.getJSON('ajax.php?what=count_documents', function(data) {
		if (typeof(data)=='object' && typeof(data)!='undefined') {
			//alert(data);
			$('#rejected_button ').attr('value',data.rejected+" document"+(data.rejected > 1 ? 's':'')+" rejeté"+(data.rejected > 1 ? 's':''));

			if (data.waiting > 0)
				$('#message_printer').text(data.waiting + " document"+(data.waiting > 1 ? 's':'')+" en cours d'analyse");
			else
				$('#message_printer').text('');
		}
	});
	setTimeout("count_documents()",1000 * 10); // toutes les 10sec, on check
}

//-->
</script>
</head>
<body>

<form name="recherche" method="post" action="<?=$_SERVER['PHP_SELF']?>" onsubmit="return verif_formulaire();">
<input type="hidden" name="more_options" value=""/>
<input type="hidden" name="recherche" value=""/>

<div id="main">

<fieldset><legend>Recherche</legend>
	<table>
	<tr>
		<td>N° de bon&nbsp;<input type="text" name="key1" value="<?=$key1?>" style="margin-right:2em;width:6em;"/></td>
		<td>
<!--			<select name="tier_adh">
				<option value="">Choix adhérent</option>
<?				$res_count = mysql_query("SELECT key2,count(id) as nb_document FROM ged_document GROUP BY key2") or die("Ne peux pas récupérer le comptage des nombre de document par adhérent ".mysql_error());
				$nb_document_per_adh = array();
				while ($row = mysql_fetch_array($res_count))
					$nb_document_per_adh[$row['key2']] = $row['nb_document'];

				$adherents = array();
				$res  = mysql_query("SELECT numero,nom,suspendu FROM artisan ORDER BY nom ASC") or die("Ne peux pas récupérer la liste des adhérents ".mysql_error());
				while ($row = mysql_fetch_array($res)) {
					$adherents[$row['numero']] = $row['nom'];
?>
					<option		value="<?=$row['numero']?>"
						class="
						<?=$row['suspendu']?'suspendu ':''?>
						<?=!isset($nb_document_per_adh[$row['numero']])?' no_document':''?>
						"
						<?=($type_tier=='adh' && $key2==$row['numero'])?' selected="selected"':'' ?>
						>
						<?=$row['nom']?>
						<?= isset($nb_document_per_adh[$row['numero']])?' ('.$nb_document_per_adh[$row['numero']].')':'' ?>
					</option>
<?				} ?>
			</select>
-->

		<select name="key2">
			<option value="">Choix du tiers</option>
			<optgroup label="Cession">
<?				$old_type = "0";
				$i=0;
				$res = mysql_query("SELECT * FROM tiers ORDER BY `type` ASC, nom ASC") or die("Ne peux pas récupérer la liste des tiers".mysql_error());
				while ($row = mysql_fetch_array($res)) {
					if ($row['type'] != $old_type && $i>0) { ?>
						</optgroup><optgroup label="<?
							switch($row['type']) {
								case '1' :	echo "Adhérents"; break;
								case '2' :	echo "Adhérents perso"; break;
								case '3' :	echo "Employés"; break;
								case '4' :	echo "Coopérative"; break;
								case '6' :	echo "Fournisseurs"; break;
								default:	echo "Divers"; break;
							}
					?>">
<?					} ?>
					<option	value="<?=$row['code']?>"
						class="<?=$row['suspendu']?'tiers-suspendu':''?> tiers-type-<?=$row['type']?>"
						<?=($key2==$row['code'])?' selected="selected"':''?>
						>
						<?=$row['nom']?>
					</option>
<?					$i++;
					 $old_type = $row['type'];
				} ?>
				</optgroup>
		</select>

		</td>
		<td><input type="submit" class="button valider" value="Rechercher"/></td>
	</tr>
	<tr>
		<td><input type="button" class="button" value="Plus d'options" style="background-image:url(../../js/boutton_images/document.png);" onclick="$('#more_options').toggle('fast');"/></td>
		<td>
<!--			<select name="tier_fourn">
				<option value="">Choix fournisseur</option>
<?				$res  = mysql_query("SELECT code_rubis,nom FROM fournisseur ORDER BY nom ASC") or die("Ne peux pas récupérer la liste des fournisseurs ".mysql_error());
				$fournisseurs = array();
				while ($row = mysql_fetch_array($res)) {
					$fournisseurs[$row['code_rubis']] = $row['nom'];
?>
					<option		value="<?=$row['code_rubis']?>"
						<?=($type_tier=='fourn' && $key2==$row['code_rubis'])?' selected="selected"':'' ?>
						>
						<?=$row['nom']?>
						<?= isset($nb_document_per_adh[$row['code_rubis']])?' ('.$nb_document_per_adh[$row['code_rubis']].')':'' ?>
					</option>
<?				} ?>
			</select>
-->
		</td>
		<td></td>
	</tr>
	</table>

	
	<!-- interface de selection des type de document -->
	<div id="more_options" style="margin-top:1em;">
<?		$i=1;
		$res_type_document  = mysql_query("SELECT * FROM ged_type_document ORDER BY libelle ASC") or die("Erreur dans la récupération des type de documents : ".mysql_error());
		while ($row2 = mysql_fetch_array($res_type_document)) {
				if (!isset($_POST['recherche'])) // cas de la premiere connexion --> on coche tous les documents
					$_POST['check-'.$row2['code']] = $row2['code'];
?>
				<label class="mobile" for="check-<?=$row2['code']?>"><input type="checkbox" name="check-<?=$row2['code']?>" id="check-<?=$row2['code']?>" value="<?=$row2['code']?>" <?= isset($_POST['check-'.$row2['code']]) ? 'checked="checked"':'' ?>/><?=$row2['libelle']?></label>
				<?= ($i++ % 2 == 0) ? '<br/><br/>':'' ?>
<?		} ?>

		<table>
		<tr>
			<td>Date d'impression entre le</td>
			<td>
				<input type="text" id="date_print_start" name="date_print_start" value="<?=isset($_POST['date_print_start'])?$_POST['date_print_start']:''?>" size="8">
				<img src="../../js/jscalendar/calendar.gif" id="push_date1" style="vertical-align:middle;cursor:pointer;" title="Choisir une date" class="hide_when_print"/>
				<script type="text/javascript">
					Calendar.setup({
						inputField	: 'date_print_start',         // ID of the input field
						ifFormat	: '%d/%m/%Y',    // the date format
						button		: 'push_date1',       // ID of the button
						date		: '',
						firstDay 	: 1
					});
				</script>
			</td>
			<td>et le 
				<input type="text" id="date_print_end" name="date_print_end" value="<?=isset($_POST['date_print_end'])?$_POST['date_print_end']:''?>" size="8">
				<img src="../../js/jscalendar/calendar.gif" id="push_date2" style="vertical-align:middle;cursor:pointer;" title="Choisir une date" class="hide_when_print"/>
				<script type="text/javascript">
					Calendar.setup({
						inputField	: 'date_print_end',         // ID of the input field
						ifFormat	: '%d/%m/%Y',    // the date format
						button		: 'push_date2',       // ID of the button
						date		: '',
						firstDay 	: 1
					});
				</script>
			</td>
		</tr>
		<tr>
			<td>Date de scan entre le</td>
			<td>
				<input type="text" id="date_scan_start" name="date_scan_start" value="<?=isset($_POST['date_scan_start'])?$_POST['date_scan_start']:''?>" size="8">
				<img src="../../js/jscalendar/calendar.gif" id="push_date3" style="vertical-align:middle;cursor:pointer;" title="Choisir une date" class="hide_when_print"/>
				<script type="text/javascript">
					Calendar.setup({
						inputField	: 'date_scan_start',         // ID of the input field
						ifFormat	: '%d/%m/%Y',    // the date format
						button		: 'push_date3',       // ID of the button
						date		: '',
						firstDay 	: 1
					});
				</script>
			</td>
			<td>et le 
				<input type="text" id="date_scan_end" name="date_scan_end" value="<?=isset($_POST['date_scan_end'])?$_POST['date_scan_end']:''?>" size="8">
				<img src="../../js/jscalendar/calendar.gif" id="push_date4" style="vertical-align:middle;cursor:pointer;" title="Choisir une date" class="hide_when_print"/>
				<script type="text/javascript">
					Calendar.setup({
						inputField	: 'date_scan_end',         // ID of the input field
						ifFormat	: '%d/%m/%Y',    // the date format
						button		: 'push_date4',       // ID of the button
						date		: '',
						firstDay 	: 1
					});
				</script>
			</td>
		</tr>
		<tr>
			<td colspan="3">Imprimé par <input type="text" size="4" value="<?=isset($_POST['print_by'])?$_POST['print_by']:''?>" name="print_by"/></td>
		</tr>
		<tr>
			<td colspan="3">Résultats groupés par
			<select name="order_by">
				<option value="key1"				<?=isset($_POST['order_by']) && $_POST['order_by']=='key1'?' selected="selected"':''?>>Adhérents/Fournisseurs</option>
				<option value="key2"				<?=((isset($_POST['order_by']) && $_POST['order_by']=='key2') || !isset($_POST['order_by']))?' selected="selected"':''?>>N° de document</option>
				<option value="code_type_document"	<?=isset($_POST['order_by']) && $_POST['order_by']=='code_type_document'?' selected="selected"':''?>>Type de document</option>
				<option value="date_print"			<?=isset($_POST['order_by']) && $_POST['order_by']=='date_print'?' selected="selected"':''?>>Date d'impression</option>
				<option value="date_scan"			<?=isset($_POST['order_by']) && $_POST['order_by']=='date_scan'?' selected="selected"':''?>>Date de scan</option>
				<option value="print_by"			<?=isset($_POST['order_by']) && $_POST['order_by']=='print_by'?' selected="selected"':''?>>Imprimeur</option>
			</select>
			<select name="order">
				<option value="ASC"	<?=isset($_POST['order']) && $_POST['order']=='ASC'?' selected="selected"':''?>>A->Z</option>
				<option value="DESC"<?=isset($_POST['order']) && $_POST['order']=='DESC'?' selected="selected"':''?>>Z->A</option>
			</select>
			</td>
		</tr>
		</table>
	</div>
</fieldset>

<fieldset><legend>Résultats</legend>
<?
$sql_condition = array();

array_push($sql_condition,"deleted=0"); // pas de document supprimé

$type_document = array();
foreach ($_POST as $key=>$val)
	if (substr($key,0,6) == 'check-') // type de document demandé
		array_push($type_document, "code_type_document='$val'");
array_push($sql_condition,"(".join(" OR ",$type_document).")"); // condition sur les type

if ($key1)
	array_push($sql_condition,"key1='".mysql_escape_string($key1)."'");
if ($key2)
	array_push($sql_condition,"key2='".mysql_escape_string($key2)."'");

if (isset($_POST['date_print_start']) && $_POST['date_print_start'])
	array_push($sql_condition,"DATE(date_print) >= '".mysql_escape_string(join('-',array_reverse(explode('/',$_POST['date_print_start']))))."'");
if (isset($_POST['date_print_end']) && $_POST['date_print_end'])
	array_push($sql_condition,"DATE(date_print) <= '".mysql_escape_string(join('-',array_reverse(explode('/',$_POST['date_print_end']))))."'");

if (isset($_POST['date_scan_start']) && $_POST['date_scan_start'])
	array_push($sql_condition,"DATE(date_scan) >= '".mysql_escape_string(join('-',array_reverse(explode('/',$_POST['date_scan_start']))))."'");
if (isset($_POST['date_scan_end']) && $_POST['date_scan_end'])
	array_push($sql_condition,"DATE(date_scan) <= '".mysql_escape_string(join('-',array_reverse(explode('/',$_POST['date_scan_end']))))."'");

if (isset($_POST['print_by']) && $_POST['print_by'])
	array_push($sql_condition,"print_by = '".mysql_escape_string($_POST['print_by'])."'");

$sql_condition = join("\nAND ",$sql_condition);

$sql_order = '';
if (isset($_POST['order_by']))
	$sql_order .= $_POST['order_by'];
else
	$sql_order .= "key1 ASC, key2";

if (isset($_POST['order']))
	$sql_order .= " ".$_POST['order'];
else
	$sql_order .= " ASC";
	

$sql = <<<EOT
SELECT *,
		DATE_FORMAT(date_print,'%d/%m/%Y')	AS date_print_formated,
		DATE_FORMAT(date_scan,'%d/%m/%Y')	AS date_scan_formated,
		DATE_FORMAT(date_scan,'%Y/%m/%d')	AS store_directory
FROM	ged_document
	LEFT JOIN ged_type_document
		ON ged_document.code_type_document=ged_type_document.code
WHERE
	$sql_condition
ORDER BY $sql_order
EOT;

// si un critere est demandé --> on fait la requete
$res = null;
if (isset($_POST['recherche']) && $_POST['recherche']==1)
	$res = mysql_query($sql) or die("Erreur dans la récupération des documents : ".mysql_error()."<pre style='color:red'>$sql</pre>");
?>

<ul id="resultats">
<?	while($res && $row = mysql_fetch_array($res)) { // pour chaque résultat ?>
	<li>
		<div class="document type-<?=$row['code_type_document']?>">
			<a href="documents/<?=$row['store_directory']?>/<?=$row['filename']?>" target="preview"><img src="thumbs/<?=$row['store_directory']?>/<?=basename($row['filename'],'.jpg')?>_thumb.jpg" alt="<?=$row['filename']?>"/></a><br/>
			<strong><?=$row['libelle']?></strong><br/>
			N° doc <strong><?=$row['key1']?></strong><br/>
			<strong><?
				if		($row['type_tier'] == 'adherent') // on a a faire a un document pour adh
					echo isset($adherents[$row['key2']]) ? $adherents[$row['key2']] : $row['key2'] ;
				elseif	($row['type_tier'] == 'fournisseur') // on a a faire a un document pour fournisseur
					echo isset($fournisseurs[$row['key2']]) ? $fournisseurs[$row['key2']] : $row['key2'] ;
				else
			?></strong><br/>
			Imp. <strong><?=$row['print_by']?></strong> le <?=$row['date_print_formated']?><br/>
			Scan le <?=$row['date_scan_formated']?><br/>
			Page <?=$row['page']?>
			<div class="action">
				<input type="button" value="PDF" class="button pdf divers" onclick="img2pdf('<?=$row['store_directory']?>/<?=$row['filename']?>');"/>
			</div>
		</div>
	</li>
<?	} ?>
</ul>
</fieldset>

</div><!-- fin main -->

<div id="footer">
	<input type="button" id="rejected_button" value="Documents rejetés" class="button divers" style="background-image:url(../../js/boutton_images/document.png);" onclick="document_rejected();"/>
	<span id="message_printer"></span>
</div>

</form>

</body>
</html>
<?
mysql_close($mysql);
?>