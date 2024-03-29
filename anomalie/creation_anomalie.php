<?php

include('../inc/config.php');
define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

if		(isset($_POST['id']))	$id = mysql_escape_string($_POST['id']) ;
elseif	(isset($_GET['id']))	$id = mysql_escape_string($_GET['id']) ;
else							$id = 0 ;

$can_edit		= FALSE ;

//echo "<pre>\n";
//print_r($_POST);
//echo "\n</pre>";

if ($id) { // mode modificaiton, on r�cupere les infos de l'anomalie
	$res = mysql_query("SELECT *,DATE_FORMAT(date_creation,'%d/%m/%Y') AS date_creation_formatee, DATE_FORMAT(date_creation,'%H:%i:%s') AS minute_creation_formatee, DATE_FORMAT(date_cloture,'%d/%m/%Y') AS date_cloture_formatee,TIME_TO_SEC(TIMEDIFF(NOW(),date_creation)) AS temps_ecoule FROM anomalie WHERE id=$id") or die ("Ne peux pas r�cup�rer les infos de l'anomalie n�$id. ".mysql_error());
	$row_anomalie = mysql_fetch_array($res);

	$is_createur	= FALSE ;
	$is_responsable	= FALSE ;
	// verifie s'il l'on est le cr�ateur
	$is_createur	= e('ip',mysql_fetch_array(mysql_query("SELECT ip FROM employe WHERE prenom='$row_anomalie[createur]'"))) == $_SERVER['REMOTE_ADDR'] ? TRUE:FALSE ;

	// v�rifie s'il l'on est un responsable
	$emails_chefs_de_pole = array();
	foreach ($CHEFS_DE_POLE as $p=>$chef)
		if ($row_anomalie['pole'] & $p) $emails_chefs_de_pole[] = "email='$chef[email]'";

	// on recherche les ip des chefs de pole
	$res = mysql_query("SELECT ip FROM employe WHERE ".join(' or ',$emails_chefs_de_pole));
	while($row = mysql_fetch_array($res))
		if($_SERVER['REMOTE_ADDR'] == $row['ip']) { // si ip actuel est dans la liste des chef de pole --> on est reponsable
			$is_responsable = TRUE ; break;
		}

	$can_edit = ($is_createur || $is_responsable) && ($row_anomalie['temps_ecoule'] < MAX_TIME_ANOMALIE_DELETION) ;
	//print_r($row_anomalie);
}


// CREATION D'UNE NOUVELLE ANOMALIE
if		(isset($_POST['action']) && $_POST['action']=='creation_anomalie') {
	$post_escaped = array_map('mysql_escape_string',$_POST);
	$date_creation = join('-',array_reverse(explode('/',$post_escaped['date_creation']))).date(' H:i:s');

	$pole = (isset($_POST['pole_logistique'])	&& $_POST['pole_logistique']	=='on'	? POLE_LOGISTIQUE	: 0) |
			(isset($_POST['pole_commerce'])		&& $_POST['pole_commerce']		=='on'	? POLE_COMMERCE		: 0) |
			(isset($_POST['pole_exposition'])	&& $_POST['pole_exposition']	=='on'	? POLE_EXPOSITION	: 0) |
			(isset($_POST['pole_administratif'])&& $_POST['pole_administratif']	=='on'	? POLE_ADMINISTRATIF: 0) |
			(isset($_POST['pole_informatique'])	&& $_POST['pole_informatique']	=='on'	? POLE_INFORMATIQUE	: 0) |
			(isset($_POST['pole_litige'])		&& $_POST['pole_litige']		=='on'	? POLE_LITIGE		: 0) |
			(isset($_POST['pole_autre'])		&& $_POST['pole_autre']			=='on'	? POLE_AUTRE		: 0) |
			(isset($_POST['pole_transport'])	&& $_POST['pole_transport']		=='on'	? POLE_TRANSPORT	: 0);

	$resp_coop	= isset($post_escaped['resp_coop']) ? $post_escaped['resp_coop']	: 0;
	$resp_adh	= isset($post_escaped['resp_adh'])	? $post_escaped['resp_adh']		: 0;
	$resp_four	= isset($post_escaped['resp_four']) ? $post_escaped['resp_four']	: 0;

	$sql = <<<EOT
INSERT INTO anomalie
	(date_creation,createur,artisan,fournisseur,pole,evolution,resp_coop,resp_adh,resp_four,probleme,num_retour,supprime)
		VALUES
	('$date_creation','$post_escaped[createur]','$post_escaped[artisan]','$post_escaped[fournisseur]',$pole,$post_escaped[evolution],$resp_coop,$resp_adh,$resp_four,'$post_escaped[probleme]',UCASE('$post_escaped[num_probleme]'),0)
EOT;

	// faire un envoi de mail au chef de pole
	$html = <<<EOT
	Nouvelle anomalie concernant l'adh�rent <b>$_POST[artisan]</b><br>
	Cr��e par <b>$_POST[createur]</b> le $_POST[date_creation]<br><br>
	
	Informations compl�mentaires :<br>
	Fournisseur : <b>$_POST[fournisseur]</b><br><br>

	<u>Probl�me rencontr�</u> :<br>
	$_POST[probleme]
EOT;

	
	require_once '../inc/xpm2/smtp.php';
	$mail = new SMTP;
	$mail->Delivery('relay');
	$mail->Relay(SMTP_SERVEUR,SMTP_USER,SMTP_PASS,(int)SMTP_PORT,'autodetect',SMTP_TLS_SLL ? SMTP_TLS_SLL:false);
	$emails_deja_envoye = array();
	foreach ($CHEFS_DE_POLE as $p=>$chef) {
		if (($row_anomalie['pole'] & $p) && !in_array($chef['email'],$emails_deja_envoye)) {
			if ($pole & $p)	$mail->AddTo($chef['email'],$chef['nom']) or die("Erreur d'ajout de destinataire");
			array_push($emails_deja_envoye,$chef['email']); // on enregistre l'email pour ne pas lui envoyer d'autre mail
		}
	}
	$mail->From(e('email',mysql_fetch_array(mysql_query("SELECT email FROM employe WHERE prenom='$_POST[createur]'"))));
	$mail->Html($html);
	$sent = $mail->Send("Nouvelle Anomalie : $_POST[artisan]");


	//echo '<font color="#ff0000">'.$sql.'</font>';
	mysql_query($sql) or die("Ne peux pas cr�er votre nouvelle anomalie. ".mysql_error());
	$last_insert_id = mysql_insert_id($mysql);

	if ($_POST['evolution'] == 2) { // anomalie directement clotur�e
		// le commentaire
		mysql_query("INSERT INTO anomalie_commentaire (id_anomalie,date_creation,createur,`type`,humeur,commentaire,supprime) VALUES ($last_insert_id,now(),'$post_escaped[createur]','autre',0,'Anomalie clotur�e',0)") or die("Impossible de rajouter un commentaire de cloture. ".mysql_error());
		// la date de cloture
		mysql_query("UPDATE anomalie SET date_cloture=NOW() WHERE id=$last_insert_id") or die("Impossible de mettre une date de cloture. ".mysql_error());
	}
	

	// faire un redirect sur la liste de toutes les anomalies
	header('Location: historique_anomalie.php');

}

// MODE MODIFICATION
elseif (isset($_POST['action']) && $_POST['action']=='modification_anomalie') {

	$post_escaped = array_map('mysql_escape_string',$_POST);
	$pole = (isset($_POST['pole_logistique'])	&& $_POST['pole_logistique']	=='on'	? POLE_LOGISTIQUE	: 0) |
			(isset($_POST['pole_commerce'])		&& $_POST['pole_commerce']		=='on'	? POLE_COMMERCE		: 0) |
			(isset($_POST['pole_exposition'])	&& $_POST['pole_exposition']	=='on'	? POLE_EXPOSITION	: 0) |
			(isset($_POST['pole_administratif'])&& $_POST['pole_administratif']	=='on'	? POLE_ADMINISTRATIF: 0) |
			(isset($_POST['pole_informatique'])	&& $_POST['pole_informatique']	=='on'	? POLE_INFORMATIQUE	: 0) |
			(isset($_POST['pole_litige'])		&& $_POST['pole_litige']		=='on'	? POLE_LITIGE		: 0) |
			(isset($_POST['pole_autre'])		&& $_POST['pole_autre']			=='on'	? POLE_AUTRE		: 0) |
			(isset($_POST['pole_transport'])	&& $_POST['pole_transport']		=='on'	? POLE_TRANSPORT	: 0);

	$resp_coop	= isset($post_escaped['resp_coop']) ? $post_escaped['resp_coop']	: 0;
	$resp_adh	= isset($post_escaped['resp_adh'])	? $post_escaped['resp_adh']		: 0;
	$resp_four	= isset($post_escaped['resp_four']) ? $post_escaped['resp_four']	: 0;

	$sql = <<<EOT
UPDATE anomalie SET
		pole=$pole,
		evolution=$post_escaped[evolution],
		resp_coop=$resp_coop,
		resp_adh=$resp_adh,
		resp_four=$resp_four
EOT;

	if (isset($_POST['fournisseur']))
		$sql .= " ,fournisseur='$post_escaped[fournisseur]' " ;

	if (isset($_POST['artisan']))
		$sql .= " ,artisan='$post_escaped[artisan]' " ;

	if (isset($_POST['createur']))
		$sql .= " ,createur='$post_escaped[createur]' " ;

	if (isset($_POST['probleme']))
		$sql .= " ,probleme='$post_escaped[probleme]' ";

	if (isset($_POST['num_retour']))
		$sql .= " ,num_retour=UCASE('$post_escaped[num_retour]') " ;

	$sql .= " WHERE id='".mysql_escape_string($id)."'" ;


	// EN CAS DE CLOTURE DE BON OU DE REOUVERTURE
	if ($post_escaped['evolution'] == 2 && $row_anomalie['evolution'] != 2) { // on rajoute un commentaire en cas de cloture
		if ($is_responsable) {
			// le commentaire
			mysql_query("INSERT INTO anomalie_commentaire (id_anomalie,date_creation,createur,`type`,humeur,commentaire,supprime) VALUES ($id,now(),'$post_escaped[createur]','autre',0,'Anomalie clotur�e',0)") or die("Impossible de rajouter un commentaire de cloture. ".mysql_error());
			// la date de cloture
			mysql_query("UPDATE anomalie SET date_cloture=NOW() WHERE id=$id") or die("Impossible de mettre une date de cloture. ".mysql_error());
		} else { // les droits ne sont pas suffisant
			die("Vous n'�tes pas le responsable de ce bon d'anomalie, vous ne pouvez pas le cloturer");
		}
	} else if ($row_anomalie['evolution'] == 2 && $post_escaped['evolution'] != 2) { // on rajoute un commentaire de r�ouverture de bon
		// le commentaire
		mysql_query("INSERT INTO anomalie_commentaire (id_anomalie,date_creation,createur,`type`,humeur,commentaire,supprime) VALUES ($id,now(),'$post_escaped[createur]','autre',0,'Anomalie r�ouverte',0)") or die("Impossible de rajouter un commentaire de cloture. ".mysql_error());
		// on enlevela date de cloture la date de cloture
		mysql_query("UPDATE anomalie SET date_cloture=NULL WHERE id=$id") or die("Impossible de supprimer la date de cloture. ".mysql_error());
	}

	//echo '<font color="#ff0000">'.$sql.'</font>';
	mysql_query($sql) or die("Ne peux pas cr�er votre nouvelle anomalie.<br/>$sql<br/>".mysql_error());

	// faire un redirect sur la liste de toutes les anomalies
	header('Location: historique_anomalie.php');
}


?>
<html>
<head>
	<title>Cr�ation d'une fichie anomalie</title>
<style>
a img { border:none; }

body {
	font-family:verdana;
}

h1 {
	text-transform:uppercase;
	text-align:center;
}

table.anomalie {
	border:solid 1px grey;
	width:80%;
	border-collapse:collapse;
}

th {
	text-align:right;
	vertical-align:top;
}

td,th {
	padding:3px;
	padding-top:15px;
	padding-bottom:0px;
	font-size:0.9em;
}

input,textarea { border:solid 2px #AAA; }

table.anomalie table.commentaire  {
	font-size:0.9em;
}

table.anomalie table.commentaire caption {
	font-weight:bold;
	background:#F0F0F0;
	text-align:left;
	padding:5px;
	vertical-align:center;
}

table.anomalie table.commentaire td {
	padding:3px;
	border:solid 1px grey;
	border-width:1px 0 0 0;
}

textarea#probleme { height:200px; }


select#completion_fourn {
	border:solid 1px #000080;
	display:none;
}

img.qrcode { display:none; }

@media print {
	.hide_when_print { display:none; }
	table.anomalie { width:100%; }
	img.qrcode { display:block; }
	td.mceToolbar  { display:none; }
	
}
</style>

<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../js/jscalendar/calendar-setup.js"></script>
<script type="text/javascript" src="../js/jquery.js"></script>
<script language="JavaScript" SRC="../js/mobile.style.js"></script>
<script type="text/javascript" src="../js/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
	tinyMCE.init({
		mode : 'textareas',
		theme : 'advanced',
		theme_advanced_buttons1_add : 'forecolor',
		theme_advanced_buttons2 : '',
		theme_advanced_buttons3 : ''
	});
</script>


<script language="javascript">
<!--

<? if ($id) { // mode modif ?>
	function delete_anomalie() {
		if (confirm("Voulez-vous vraiment supprimer cette anomalie ?"))
			document.location.href = 'historique_anomalie.php?action=delete_anomalie&id=<?=$id?>' ;
	}
<? } ?>


function delete_commentaire(id) {
	if (confirm("Voulez-vous vraiment supprimer ce commentaire ?"))
		document.location.href = 'historique_anomalie.php?action=delete_commentaire&id=' + id ;
}

function envoi_impression() {
	if (document.getElementById('probleme_parent')) {
		document.getElementById('probleme_parent').style.display='none'; // on cache le textarea
		document.getElementById('probleme_div').innerHTML = document.creation_anomalie.probleme.value; // on rempli le div avec le contenu du textarea
		document.getElementById('probleme_div').style.display='block'; // on affiche le div pour l'impression sans ascenseur
	}
	window.print(); // on ouvre la boite d'impression
}

function envoi_formulaire() {
	var erreur = '' ;

	with(document.creation_anomalie) {

		// si aucun pole n'est coch� -> erreur
		if (pole_logistique.type == 'hidden') { }// mode modif
		else { // mode creation 
			if (!pole_logistique.checked &&
				!pole_commerce.checked &&
				!pole_exposition.checked &&
				!pole_administratif.checked &&
				!pole_informatique.checked &&
				!pole_litige.checked &&
				!pole_autre.checked &&
				!pole_transport.checked)
				erreur = "Aucun p�le n'est coch�, veuillez en cocher au moins un.";
		}

		if (artisan.type == 'select-one')
			if (!artisan[artisan.selectedIndex].value)
				erreur = "Veuillez choisir un artisan.";

<? if(!$id) { // mode creation ?>
		if (!date_creation.value.match(/\d{1,2}\/\d{1,2}\/\d{4}/))
			erreur = "La date de cr�ation n'a pas le bon format. Le bon format est jj/mm/aaaa";

		if (!date_creation.value)
			erreur = "La date de cr�ation est vide.";
<? } ?>

		if (!erreur)	submit();
		else			alert(erreur);
	}
}



/////////////////:// COMPLETION POUR LES FOURNISSEURS ////////////////////////
///// AJAX ///////////////////////////////////

var http = null;
if		(window.XMLHttpRequest) // Firefox 
	   http = new XMLHttpRequest(); 
else if	(window.ActiveXObject) // Internet Explorer 
	   http = new ActiveXObject("Microsoft.XMLHTTP");
else	// XMLHttpRequest non support� par le navigateur 
   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

function complette_fourn(e) {
	var sel = document.creation_anomalie.completion_fourn ;
	var nb_el = sel.options.length ;
	var selIndex = sel.selectedIndex ;

	if (!document.creation_anomalie.fournisseur.value) {
		sel.style.display = 'none';
	} else if (e.keyCode == 40 && nb_el) { // fleche bas
		if (selIndex < sel.options.length - 1)
			sel.selectedIndex = selIndex + 1 ;
	}
	else if (e.keyCode == 38 && nb_el) { // fleche haut
		if (selIndex > 0)
			sel.selectedIndex = selIndex - 1 ;
	}
	else if (e.keyCode == 13 && nb_el) { // entr�e
		document.creation_anomalie.fournisseur.value = sel.options[selIndex].value ;
		sel.style.display = 'none';
	}
	else { // autre touche --> on recherche les fournisseurs
		val = document.creation_anomalie.fournisseur.value ;
		if (val.length > 0) {
			http.open("GET", "ajax.php?what=complette_fourn&val="+escape(val), true);
			http.onreadystatechange = handleHttpResponse_complette_fourn;
			http.send(null);
		}
	}
}

function handleHttpResponse_complette_fourn()
{
	if (http.readyState == 4)
	{	fournisseurs = eval('(' + http.responseText + ')'); // [id1,id2, ...]
		document.getElementById('completion_fourn').attributes['size'].value = fournisseurs.length;
		sel = document.creation_anomalie.completion_fourn ;

		// on vide le select
		while(sel.options.length > 0)
			sel.options[0] = null

		// on rempli avec les nouveaux fournisseurs
		for(i=0 ; i<fournisseurs.length ; i++)
			sel.options[sel.options.length] = new Option(fournisseurs[i],fournisseurs[i]);

		if (sel.options.length) {
			sel.selectedIndex = 0 ; // on selectionne le premier element de la liste
			$('#completion_fourn').show();
		}
		else
			$('#completion_fourn').hide();
	}	
}

function complette_fourn_click() {
	sel = document.creation_anomalie.completion_fourn ;
	document.creation_anomalie.fournisseur.value = sel.options[sel.selectedIndex].value ;
	sel.style.display = 'none';
}

////////////////// FIN COMPLETION POUR LES FOURNISSEURS ///////////////////////


function majusculize(champ) {
	document.creation_article.elements[champ].value = document.creation_article.elements[champ].value.toUpperCase();
}


$(document).ready(function(){
    var p = $("input[name=fournisseur]");
	var offset = p.offset();
	$('#completion_fourn').css('top',offset.top + 22);
	$('#completion_fourn').css('left',offset.left);
	$('#completion_fourn').css('position','absolute');
});

//-->
</script>
</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<form name="creation_anomalie" action="creation_anomalie.php" method="POST">
<?	if ($id) { // mode modification ?>
		<input type="hidden" name="action" value="modification_anomalie" />
		<input type="hidden" name="id" value="<?=$id?>" />
<?	} else { // mode creation ?>
		<input type="hidden" name="action" value="creation_anomalie" />
<?	} ?>

<!-- LIEN POUR LA CREATION DE NOUVELLE ANOMALIE-->
<div class="hide_when_print" style="text-align:left;float:left;width:100px;"><input type="button" class="button divers" style="background-image:url(gfx/anomalie_small.png);" onclick="javascript:document.location.href='historique_anomalie.php';" value="Historique des d'anomalies" /></div>

<h1>Fiche anomalie</h1>
<center>
<table class="anomalie">
<? if ($id) { ?>
	<tr>
		<th>N� d'anomalie :</th>
		<td><?=$id?></td>
		<td rowspan="7">
		<?	if ($id) { ?>
			<img src="../gfx/qrcode.php?text=<?
					echo urlencode(json_encode(array('t'=>'fiche_anomalie','c'=>$row_anomalie['artisan'],'b'=>$id,'d'=>time())));
			?>" class="qrcode"/>
		<?	} ?>
		</td>
	</tr>
<?	} ?>
	<tr>
		<th>Cr�ateur</th>
		<td>
<?			if ($id) { // mode modif ?>
				<?=$row_anomalie['createur']?>
<?			} else { ?>
				<select name="createur"><!-- liste des employ�s -->
<?					$res  = mysql_query("SELECT ip,prenom,UCASE(nom) as nom FROM employe WHERE printer=0 and nom<>'' ORDER BY prenom ASC");
					while ($row = mysql_fetch_array($res)) {
						// cherche les initiales du nom de famille
						$tmp = explode(' ',$row['nom']);
						$initiale_nom = '';
						foreach ($tmp as $mot)
							$initiale_nom .= substr($mot,0,1);

						if ($id) { //modif ?>
							<option value="<?=$row['prenom']?>"<?= $row_anomalie['createur']==$row['prenom'] ? ' selected':''?>><?=$row['prenom']?> <?=$initiale_nom?></option>
<?						} else { // creation ?>
							<option value="<?=$row['prenom']?>"<?= $_SERVER['REMOTE_ADDR']==$row['ip'] ? ' selected':''?>><?=$row['prenom']?> <?=$initiale_nom?></option>
<?						}
					} ?>
				</select>
<?			} ?>
		</td>
	</tr>
	<tr>
		<th>Date de cr�ation</th>
		<td>
			<? if ($id) { // mode modif ?>
				<?=$row_anomalie['date_creation_formatee']?>
			<? } else { ?>
				<input type="text" id="date_creation" name="date_creation" value="<?=date('d/m/Y')?>" size="8"/>
				<img src="../js/jscalendar/calendar.gif" id="trigger_date_creation" style="vertical-align:middle;cursor: pointer;"title="Date selector" />
				<img src="/intranet/gfx/delete_micro.gif" style="vertical-align:middle;" onclick="document.creation_anomalie.date_creation.value='';" />
				<script type="text/javascript">
				  Calendar.setup(
					{
					  inputField	: 'date_creation',         // ID of the input field
					  ifFormat		: '%d/%m/%Y',    // the date format
					  button		: 'trigger_date_creation',       // ID of the button
					  date			: '<?=date('d/m/Y')?>',
					  firstDay 		: 1
					}
				  );
				</script>
			<? } ?>
		</td>
	</tr>
	<tr>
		<th>Adh�rent</th>
		<td>
<?			if (($id && $can_edit) || !$id) { ?>
				<select name="artisan">
					<option value="">Choississez un adh�rent</option>
					<option value="MCS">MCS</option>
<?					$res  = mysql_query("SELECT nom FROM artisan WHERE suspendu=0 ORDER BY nom ASC");
					while ($row = mysql_fetch_array($res)) {
						if ($id) { //modif ?>
							<option value="<?=$row['nom']?>"<?=$row_anomalie['artisan']==$row['nom']?' selected':''?>><?=$row['nom']?></option>
<?						} else { // creation ?>
							<option value="<?=$row['nom']?>"><?=$row['nom']?></option>
<?						}	
					} ?>
				</select>
<?			} else { ?>
				<?=$row_anomalie['artisan']?>
				<input type="hidden" name="artisan" value="<?=$row_anomalie['artisan']?>" />
<?			} ?>
		</td>
	</tr>
	<tr>
		<th>Fournisseur</th>
		<td>
<?			if (($id && $can_edit) || !$id) { ?>
				<input type="text" name="fournisseur" size="15" value="<?= $id ? $row_anomalie['fournisseur']:'' ?>" onkeyup="complette_fourn(event);" autocomplete="off" onblur="majusculize(this.name);" />
				<br/>
				<select id="completion_fourn" name="completion_fourn" size="1" onclick="complette_fourn_click();"></select>
<?			} else { ?>
				<?=$row_anomalie['fournisseur']?>
				<input type="hidden" name="fournisseur" value="<?=$row_anomalie['fournisseur']?>" />
<?			} ?>
		</td>
	</tr>
	<tr>
		<th>N� de retour (Rubis)</th>
		<td>
<?			if (($id && $can_edit) || !$id) { ?>
				<input type="text" name="num_retour" size="11" maxlength="6" value="<?= $id ? $row_anomalie['num_retour']:'' ?>" onblur="majusculize(this.name);" />
<?			} else { ?>
				<?=$row_anomalie['num_retour']?>
				<input type="hidden" name="num_retour" value="<?=$row_anomalie['num_retour']?>" />
<?			} ?>
		</td>
	</tr>
	<tr>
		<th>P�le<br/>concern�(s)</th>
		<td>
<?			if (($id && $can_edit) || !$id) { ?>
				<label for="pole_logistique"	class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_LOGISTIQUE)	?' mobile-checked':'' ?>"><input type="checkbox" id="pole_logistique"		name="pole_logistique"		<?=($id && $row_anomalie['pole'] & POLE_LOGISTIQUE) ?	'checked="on"':'' ?>/>Logistique</label>
				<label for="pole_commerce"		class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_COMMERCE)		?' mobile-checked':'' ?>"><input type="checkbox" id="pole_commerce"			name="pole_commerce"		<?=($id && $row_anomalie['pole'] & POLE_COMMERCE) ?		'checked="on"':'' ?>/>Commerce</label>
				<label for="pole_exposition"	class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_EXPOSITION)	?' mobile-checked':'' ?>"><input type="checkbox" id="pole_exposition"		name="pole_exposition"		<?=($id && $row_anomalie['pole'] & POLE_EXPOSITION) ?	'checked="on"':'' ?>/>Exposition</label>
				<label for="pole_administratif" class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_ADMINISTRATIF)?' mobile-checked':'' ?>"><input type="checkbox" id="pole_administratif"	name="pole_administratif"	<?=($id && $row_anomalie['pole'] & POLE_ADMINISTRATIF) ?'checked="on"':'' ?>/>Administratif</label>
				<label for="pole_informatique"	class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_INFORMATIQUE) ?' mobile-checked':'' ?>"><input type="checkbox" id="pole_informatique"		name="pole_informatique"	<?=($id && $row_anomalie['pole'] & POLE_INFORMATIQUE) ?	'checked="on"':'' ?>/>Informatique</label>
				<label for="pole_litige"		class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_LITIGE)		?' mobile-checked':'' ?>"><input type="checkbox" id="pole_litige"			name="pole_litige"			<?=($id && $row_anomalie['pole'] & POLE_LITIGE) ?		'checked="on"':'' ?>/>Litige</label>
				<label for="pole_autre"			class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_AUTRE)		?' mobile-checked':'' ?>"><input type="checkbox" id="pole_autre"			name="pole_autre"			<?=($id && $row_anomalie['pole'] & POLE_AUTRE) ?		'checked="on"':'' ?>/>Autres (pr�cisez)</label>
				<label for="pole_transport"		class="mobile mobile-block<?=($id && $row_anomalie['pole'] & POLE_TRANSPORT)	?' mobile-checked':'' ?>"><input type="checkbox" id="pole_transport"		name="pole_transport"		<?=($id && $row_anomalie['pole'] & POLE_TRANSPORT) ?	'checked="on"':'' ?>/>Transport</label>
<?			} else { ?>
				<?= $row_anomalie['pole'] & POLE_LOGISTIQUE		? 'Logisitique<br/>':'' ?>
				<input type="hidden" name="pole_logistique"		value="<?=$row_anomalie['pole']&POLE_LOGISTIQUE?'on':''?>" />
				<?= $row_anomalie['pole'] & POLE_COMMERCE		? 'Commerce<br/>':'' ?>
				<input type="hidden" name="pole_commerce"		value="<?=$row_anomalie['pole']&POLE_COMMERCE?'on':''?>" />
				<?= $row_anomalie['pole'] & POLE_EXPOSITION		? 'Exposition<br/>':'' ?>
				<input type="hidden" name="pole_exposition"		value="<?=$row_anomalie['pole']&POLE_EXPOSITION?'on':''?>" />
				<?= $row_anomalie['pole'] & POLE_ADMINISTRATIF	? 'Adminsitratif<br/>':'' ?>
				<input type="hidden" name="pole_administratif"	value="<?=$row_anomalie['pole']&POLE_ADMINISTRATIF?'on':''?>" />
				<?= $row_anomalie['pole'] & POLE_INFORMATIQUE	? 'Informatique<br/>':'' ?>
				<input type="hidden" name="pole_informatique"	value="<?=$row_anomalie['pole']&POLE_INFORMATIQUE?'on':''?>" />
				<?= $row_anomalie['pole'] & POLE_LITIGE			? 'Litige<br/>':'' ?>
				<input type="hidden" name="pole_litige"			value="<?=$row_anomalie['pole']&POLE_LITIGE?'on':''?>" />
				<?= $row_anomalie['pole'] & POLE_AUTRE			? 'Autre<br/>':'' ?>
				<input type="hidden" name="pole_autre"			value="<?=$row_anomalie['pole']&POLE_AUTRE?'on':''?>" />
				<?= $row_anomalie['pole'] & POLE_TRANSPORT		? 'Transport<br/>':'' ?>
				<input type="hidden" name="pole_transport"		value="<?=$row_anomalie['pole']&POLE_TRANSPORT?'on':''?>" />
<?			} ?>
		</td>
	</tr>
	<tr>
		<th>Evolution</th>
		<td>
			<select name="evolution">
				<option value="0" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_red.png) no-repeat left;" <?= ($id && ($row_anomalie['evolution']==0)) ? 'selected="selected"':'' ?>>A traiter</option>
				<option value="1" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_yellow.png) no-repeat left;" <?= ($id && ($row_anomalie['evolution']==1)) ? 'selected="selected"':'' ?>>En cours</option>
				<option value="2" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_green.png) no-repeat left;" <?= ($id && ($row_anomalie['evolution']==2)) ? 'selected="selected"':'' ?>>Clotur�</option>
			</select>
			<?= ($id && ($row_anomalie['evolution']==2)) ? "Clotur� le $row_anomalie[date_cloture_formatee]" :'' ?>
		</td>
	</tr>
	<tr>
		<th style="text-align:center;">Responsabilit�<br/>COOP</th>
		<th style="text-align:center;">Responsabilit�<br/>ADHERENT</th>
		<th style="text-align:center;">Responsabilit�<br/>FOURNISSEUR</th>
	</tr>
	<tr>
		<td style="vertical-align:top;text-align:center;padding-top:0px;">
			<select name="resp_coop">
				<option value="0" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_green.png) no-repeat left;" <?= ($id && ($row_anomalie['resp_coop']==0)) ? 'selected="selected"':'' ?>>Pas responsable</option>
				<option value="1" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_yellow.png) no-repeat left;" <?= ($id && ($row_anomalie['resp_coop']==1)) ? 'selected="selected"':'' ?>>Partiellement responsable</option>
				<option value="2" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_red.png) no-repeat left;" <?= ($id && ($row_anomalie['resp_coop']==2)) ? 'selected="selected"':'' ?>>Completement responsable</option>
			</select>
		</td>
		<td style="vertical-align:top;text-align:center;padding-top:0px;">
			<select name="resp_adh">
				<option value="0" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_green.png) no-repeat left;"  <?= ($id && ($row_anomalie['resp_adh']==0)) ? 'selected="selected"':'' ?>>Pas responsable</option>
				<option value="1" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_yellow.png) no-repeat left;"  <?= ($id && ($row_anomalie['resp_adh']==1)) ? 'selected="selected"':'' ?>>Partiellement responsable</option>
				<option value="2" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_red.png) no-repeat left;"  <?= ($id && ($row_anomalie['resp_adh']==2)) ? 'selected="selected"':'' ?>>Completement responsable</option>
			</select>
		</td>
		<td style="text-align:center;padding-top:0px;padding-bottom:20px;">
			<select name="resp_four">
				<option value="0" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_green.png) no-repeat left;"  <?= ($id && ($row_anomalie['resp_four']==0)) ? 'selected="selected"':'' ?>>Pas responsable</option>
				<option value="1" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_yellow.png) no-repeat left;"  <?= ($id && ($row_anomalie['resp_four']==1)) ? 'selected="selected"':'' ?>>Partiellement responsable</option>
				<option value="2" style="padding-left:30px;height:20px;background:white url(/intranet/gfx/feu_red.png) no-repeat left;"  <?= ($id && ($row_anomalie['resp_four']==2)) ? 'selected="selected"':'' ?>>Completement responsable</option>
			</select>
		</td>
	</tr>
	<tr>
		<th style="background:#F0F0F0;text-align:left;padding:5px;vertical-align:center;" colspan="3">Probl�me rencontr�</th>
	</tr>
	<tr>
		<td colspan="3" style="padding-top:0px;">
			<? if (($id && $can_edit) || !$id) { // peux �diter le bon ?>
				<textarea id="probleme" name="probleme" class="hide_when_print" style="width:100%;display:block;"><?= $id ? $row_anomalie['probleme'] :''?></textarea>
			<? } else {	?>
					<div id="probleme" style="width:100%;display:block;border:solid 1px grey;"><?= $id ? $row_anomalie['probleme'] :''?></div>
			<?	} ?>
			<div id="probleme_div" name="probleme_div" style="width:100%;display:none;border:solid 1px grey;"></div>
		</td>
	</tr>
	<tr class="hide_when_print">
		<td style="text-align:center;padding-bottom:10px;" colspan="3">
			<input type="button" class="button valider" onclick="envoi_formulaire();" value="Enregistrer">&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="button" class="button annuler" onclick="document.location.href='historique_anomalie.php';" value="Annuler">&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="button" class="button printer divers" onclick="envoi_impression();" value="Imprimer">&nbsp;&nbsp;&nbsp;&nbsp;
			<? if ($id && $can_edit) { // mode modif ?>
				<input type="button" class="button divers" style="background-image:url(/intranet/gfx/cross.png);" onclick="delete_anomalie();" value="Supprimer l'anomalie">
			<? } ?>
		</td>
	</tr>

<? if ($id) { // rechercher s'il y a des commentaires sur ce probleme

			$res_commentaire = mysql_query("SELECT *,DATE_FORMAT(date_creation,'%d %b %Y') AS date_formater,DATE_FORMAT(date_creation,'%w') AS date_jour,DATE_FORMAT(date_creation,'%H:%i') AS heure_formater,TIME_TO_SEC(TIMEDIFF(NOW(),date_creation)) AS temps_ecoule FROM anomalie_commentaire WHERE id_anomalie='$id' AND supprime=0 ORDER BY date_creation ASC") or die("Ne peux pas afficher les commentaires anomalies ".mysql_error()); 
?>
			<tr>
				<td colspan="13" valign="top" style="padding:15px 0 0 0;">
					<table class="commentaire" style="" width="100%" cellspacing="0">
							<caption>Liste des commentaires</caption>
<?							while($row_commentaire = mysql_fetch_array($res_commentaire)) {
								$is_createur_commentaire = e('ip',mysql_fetch_array(mysql_query("SELECT ip FROM employe WHERE prenom='$row_commentaire[createur]'"))) == $_SERVER['REMOTE_ADDR'] ? TRUE:FALSE ;	
?>
							<tr>
								<td width="15%"><?=$jours_mini[$row_commentaire['date_jour']]?> <?=$row_commentaire['date_formater']?> <?=$row_commentaire['heure_formater']?></td>
								<td width="5%">
	<?								switch ($row_commentaire['humeur']) {
										case 0: ?>&nbsp;<?
											break;
										case 1: ?><img src="/intranet/gfx/weather-clear.png"><?
											break;
										case 2: ?><img src="/intranet/gfx/weather-few-clouds.png"><?
											break;
										case 3: ?><img src="/intranet/gfx/weather-storm.png"><?
											break;
									}
									
									if		($row_commentaire['type'] == 'autre' && $row_commentaire['commentaire'] == 'Anomalie clotur�e') { ?>
										<img src="/intranet/gfx/link.png">
<?									} elseif($row_commentaire['type'] == 'autre' && $row_commentaire['commentaire'] == 'Anomalie r�ouverte') { ?>
										<img src="/intranet/gfx/link_break.png">
<?									} ?>
								</td>
								<td width="10%"><?=$row_commentaire['createur']?>&nbsp;</td>
								<td width="10%"><?=$row_commentaire['type']?>&nbsp;</td>
								<td width="60%"><?=stripslashes($row_commentaire['commentaire'])?></td>
								<td width="5%">
<?									if		($is_createur_commentaire && $row_commentaire['type'] != 'autre' && ($row_commentaire['temps_ecoule'] < MAX_TIME_ANOMALIE_DELETION)) { ?>
										<a href="javascript:delete_commentaire(<?=$row_commentaire['id']?>);"><img src="/intranet/gfx/comment_delete.png"></a>
<?									} else { ?>&nbsp;<? } ?>
								</td>
							</tr>
<?							} ?>
					</table>
				</td>
			</tr>

<? } // fin recherche si commentaire ?>

</table>
</center>
</form>
</body>
</html>