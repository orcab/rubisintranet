<? include('../../inc/config.php');

ob_end_flush(); // pour l'affichage au fur et a mesure

define('PLOMBIER',		1<<0);
define('ELECTRICIEN',	1<<1);
define('TAILLE_MAXIMUM_MESSAGE',250);
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$droit = recuperer_droit();
if (!($droit & PEUT_ENVOYER_DES_SMS)) { // n'a pas le droit de d'envoyer des sms
	die("Vos droits ne vous permettent pas d'acc�der � cette partie de l'intranet");
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Envoi de SMS</title>

<style>
body {
	font-family: verdana;
	font-size: 0.8em;
}

h1 {
    font-size: 1.2em;
}

.info {
	margin:auto;
	width:50%;
    color: green;
	background-color:#7F7;
    font-weight: bold
;    text-align: center;
}

.erreur {
	color: red;
	background-color:#F88;
}

#recherche {
	text-align:center;
	margin:auto;
	margin-top:1em;
	border:solid 1px grey;
	padding:20px;
	width:80%;
}

#lignes {
    border: 1px solid black;
    border-collapse: collapse;
    margin-top: 1em;
    width:55%;
    margin:auto;
}
#lignes th, #lignes td {
    border: 1px solid #CCCCCC;
    font-size: 0.9em;
    text-align: center;
}
tr.annule {
    color: #CCCCCC;
    text-decoration: line-through;
}
caption {
    background-color: #DDD;
    padding: 2px;
}

.qualite-afz {
	background-color:#FAA
}

.qualite-afz .qualite {
	color:#F00;
}

.support .significient {
	font-size:1.2em;
}

.legende {
    margin: auto;
    margin-top: 2em;
    font-size: 0.7em;
    width: 55%;
}

#message {
	width:50%;
}

#nb_car {
	width:50%;
	margin:auto;
}

.attention {
	background-color:#F77;
	color:red;
	font-weight:bold
}


/* l'outil de manipulation des select */
#combo{
	text-align:center;
}
select#chooseplaylist, select#selplaylist{
    width:300px;
    display:inline-block;
} 
ul#btselmulti{ 
    display:inline-block;
    list-style-type:none;
    margin:0 5px;
    padding:0;
}
ul#btselmulti li{
    cursor:pointer;
}
#labelplaylist{
    display:block;
    margin:5px 0 0 0;
}

/* phrases */
input.phrase { vertical-align:top; }

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
}

div#phrase ul {
	list-style:none;
	padding-left: 20px;
    margin: 0;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../js/useful.js"></script>
<script type="text/javascript" src="../../js/jquery.js"></script>
<script language="javascript">
<!--

$(document).ready(function(){
	$('#message').bind('keyup',function(){
    	var nb_car = $(this).val().length;
		$('#nb_car').text(nb_car + " utilis�");
		if (nb_car > <?=TAILLE_MAXIMUM_MESSAGE?>) {
			$('#nb_car').addClass('attention');
		} else {
			$('#nb_car').removeClass('attention');
		}
    });


	// affichage des phrases pr�-enregistr�es
	$('body').delegate('input.phrase','click',function() {
		affiche_choix_phrase(this);
	});

	// affichage des phrases pr�-enregistr�es
	$('div#phrase').delegate('li','click',function() {
		affiche_phrase(this);
	});

}) ;


jQuery(function(){
/* -- Passer les �l�ments d'un select � l'autre -- */
    jQuery('li','#btselmulti').click(function(){
        var action = jQuery(this).attr('id');
        switch(action){
            case 'addall':
                var ids = jQuery('#chooseplaylist option'); var dest = jQuery('#selplaylist');
                break;
            case 'addsel':
                var ids = jQuery('#chooseplaylist option:selected'); var dest = jQuery('#selplaylist');
                break;
            case 'quitsel':
                var ids = jQuery('#selplaylist option:selected'); var dest = jQuery('#chooseplaylist');
                break;
            case 'quitall':
                var ids = jQuery('#selplaylist option'); var dest = jQuery('#chooseplaylist');
                break;
            }
        changedata(ids, dest);
        putsels();
    });
});
 
/* -- Ecrit les �l�ments s�lectionn�s dans le select de destination et les efface de celui d'origine -- */ 
function changedata(ids, dest){
    ids.each(function(){
    	dest.append("<option value='" + jQuery(this).val() + "'>" + jQuery(this).text() + "</option>");
    });
    jQuery(ids).remove();
} 
 
/* -- Ecrit les �lements dans s�lectionn�s le hidden (text pour l'exemple) -- */ 
function putsels(){
    var listsels = new Array();
    var listsels_to_post = new Array();
    jQuery('#selplaylist option').each(function(){
        listsels.push(jQuery(this).val());
        listsels_to_post.push(jQuery(this).text().substr(jQuery(this).text().length - 10,10));
    });

    jQuery("#phone_number").val(listsels_to_post.join(','));
}


/* add custum number */
function add_custom_number() {
	var phone_number = $('#custom_number').val().replace(/[^0-9\+]/g,'');
	$('#selplaylist').append("<option value='" + phone_number + "'>" + phone_number + "</option>");
	putsels();
}


function ajouter_group(groupid) {
	jQuery('#chooseplaylist option').each(function(){
	 	var params = $(this).val().split('-');
		if (parseInt(params[2]) & groupid) { // le type de groupe	
	 		$(this).attr('selected','selected');
	 	}

	 	$('#addsel').click();
	});
}


function verif_form() {
	var nb_car = $('#message').val().length;
	var erreur = false;

	$('#message').val(removeDiacritics($('#message').val()));

	if (nb_car > <?=TAILLE_MAXIMUM_MESSAGE?>) {
		alert("Votre message est trop long ("+nb_car+" car)");
		erreur = true;
	}

	if (nb_car <= 0) {
		alert("Votre message est vide");
		erreur = true;
	}

	if (jQuery('#selplaylist option').length <= 0) {
		alert("Aucun destinataire de choisi");
		erreur = true;
	}

	if (!erreur)
		document.cde.submit();
}


// gestion des phrases
var last_btn_phrase_push;
var phrases = [];
<?	// selection des phrases pr�-�tablies pour les designations
	$res = mysql_query("SELECT mot_cle,phrase FROM phrase WHERE app='sms' AND deleted=0 ORDER BY mot_cle ASC") or die("Requete de selection des phrases pr�-enreistr�es impossible ".mysql_error()) ;
	while($row = mysql_fetch_array($res)) { ?>
		phrases['<?=preg_replace("/'/","",$row['mot_cle'])?>'] = "<?=utf8_decode(preg_replace("/[\n|\r]+/",'\\n',preg_replace('/"/',"\\\"",$row['phrase'])))?>";
<?	} ?>

function affiche_choix_phrase(btn_elm) {
	// placement du div
	last_btn_phrase_push = btn_elm;	// on stock quel bouton a �t� appuyer pour pouvoir affect� le texte au bon textarea ensuite (les div sont g�n�rique)
	var div_offset = $(btn_elm).offset();
	var div_height = $(btn_elm).height();
	var html = '<ul>';
	for(var mot_cle in phrases) {
		html += '<li>'+mot_cle+'</li>';
	}
	html += '</ul><div style="float:right;text-align:right;margin-top:1em;"><a href="javascript:cache_choix_phrase();">Fermer [X]</a><br/><a href="../../devis2/modification_phrase.php?app=sms" target="_blank">Editer les phrases</a></div>';

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
	$('#message').val($('#message').val().length > 0 ? $('#message').val() + "\n" + phrase : phrase);
}


//-->
</script>

</head>
<body>
<div id="phrase"></div><!-- pour la sugestion des phrases -->
<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils</a>

<? if (		isset($_POST['action']) && $_POST['action']=='envoi-sms'
		&& 	isset($_POST['phone_number']) && $_POST['phone_number']
		&&	isset($_POST['message']) && $_POST['message']
	) { // on envoi les sms via la passerelle
	
	$phone_numbers = explode(',',$_POST['phone_number']);
	foreach($phone_numbers as $phone_number) {
		if (sendSMS($phone_number,$_POST['message'])) // envoi du SMS
			echo "<div class='info'>Message envoy� � $phone_number</div>";
		else
			echo "<div class='info erreur'>Erreur dans l'envoi du message � $phone_number</div>";

		flush(); // envoi l'affichage de l'avancement des envois de SMS
	}

	// historise l'envoi du sms
	mysql_query("INSERT INTO sms_historique (`date`,expediteur,message,destinataire) VALUES (NOW(),'".mysql_escape_string($_SERVER['REMOTE_ADDR'])."','".mysql_escape_string($_POST['message'])."','".mysql_escape_string($_POST['phone_number'])."')") or die("ne peux sauvegarder l'envoi dans l'historique ".mysql_error());
} ?>


<form name="cde" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="action" value="envoi-sms"/>
<input type="hidden" id="phone_number" name="phone_number" value=""/>
<div id="recherche">
	<h1>Envoi de SMS</h1>

<div id="combo">
    <select id="chooseplaylist" name="chooseplaylist" size="20" multiple="multiple">
<?    	if ($_SERVER['SERVER_ADDR'] == '10.211.14.46') { // que en test ?>
    		<option value="artisan-poulai-3">Poulain Benjamin - 0620389002</option>
<?		}

$res = mysql_query("SELECT * FROM artisan where categorie='1' and suspendu=0 and numero<>'056039' ORDER BY nom ASC") or die("ne peux pas retrouver les infos de l'artisan ".mysql_error());
while ($row = mysql_fetch_array($res)) {
	$phone_number = '';

	for($i=1 ; $i<=4 ; $i++) // pour les 4 numero de tel
		if (preg_match('/^\s*0\s*[67]/',$row['tel'.$i])) { // on regarde celui qui commence par 06 ou 07
			$phone_number = preg_replace('/[^0-9]/','',$row['tel'.$i]); // supprime tout ce qui n'est pas un chiffre
			break;
		}

	if ($phone_number) { ?>
		<option value="artisan-<?=$row['numero']?>-<?=$row['activite']?>"><?=$row['nom']?> - <?=$phone_number?></option>
<? 	}
} ?>
    </select>

    <ul id="btselmulti"> 
        <li id="addall"><a class="btn btn-info" style="width:20px;"><i class="icon-double-angle-right" title="select all"></i></a><br/><br/></li>
        <li id="addsel"><a class="btn btn-info" style="width:20px;"><i class="icon-angle-right" title="select one"></i></a><br/><br/></li> 
        <li id="quitsel"><a class="btn btn-info" style="width:20px;"><i class="icon-angle-left" title="unselect one"></i></a><br/><br/></li> 
        <li id="quitall"><a class="btn btn-info" style="width:20px;"><i class="icon-double-angle-left" title="unselect all"></i></a><br/><br/><br/><br/></li> 
    </ul> 

    <select id="selplaylist" name="selplaylist" size="20" multiple="multiple"></select>

    <br/><br/>
    <a class="btn btn-info" onclick="ajouter_group(<?=PLOMBIER?>);"><i class="icon-arrow-right"></i> Ajouter les plombiers</a>
    <a class="btn btn-info" onclick="ajouter_group(<?=ELECTRICIEN?>);"><i class="icon-arrow-right"></i> Ajouter les electriciens</a>
    <br/><br/>
    <input type="text" id="custom_number" name="custom_number" size="12" maxsize="15" placeholder="Autre num�ro"/>
    <a class="btn btn-info" onclick="add_custom_number();"><i class="icon-arrow-right"></i> Ajouter ce num&eacute;ro</a>

</div>

<h2>Message (<?=TAILLE_MAXIMUM_MESSAGE?> car maximum)</h2>
<input type="button" class="phrase phrase_cli" value="..." />
<textarea name="message" id="message" cols="50" rows="5"></textarea>
<div id="nb_car"></div>
<br/><br/>

<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Envoyer</a>
</div>
</form>

<?
//var_dump($_POST);
?>

</body>
</html>