<?php

$message  = '' ;

// demande de deconnexion
if (isset($_GET['deconnexion']) && $_GET['deconnexion'] == 1) {
	session_start();
	session_unset();
	session_destroy();
}

// on a des infos d'identifications
if ((isset($_POST['username']) && isset($_POST['password'])) || isset($_POST['barcode'])) {
	include('../inc/config.php');

	define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
	$sql =	" SELECT	numero,nom,email FROM	artisan ".
			" WHERE	(numero='".mysql_escape_string($_POST['username'])."' AND password='".mysql_escape_string(strtoupper($_POST['password']))."' ".
			" ||	(ean13='".mysql_escape_string($_POST['barcode'])."')) ".
			" LIMIT	0,1 " ;

	$res = mysql_query($sql) or die("Ne peux pas lancer la requete d'identification '$sql' ".mysql_error());
	if (mysql_num_rows($res) == 1) { // si une ligne, on est identifié
		$row = mysql_fetch_array($res);
		session_start();
		$_SESSION['info_user']['name']		= $row['nom'];
		$_SESSION['info_user']['username']	= $row['numero'];
		$_SESSION['info_user']['email']		= $row['email'];
		header('Location: interface.php'); // on redirige vers l'interface de commande

	} else { // erreur dans l'identification
		$message = "Impossible de vous identifier. Veuillez réessayer";
	}
}



// page d'accueil sans identification
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style>
body { margin:0px; }
body,pre { font-family: verdana,helvetica; }
a img { border:none; }
img { vertical-align: middle; }

#message { 
	color: red;
    border: solid 2px red;
    width: 50%;
    text-align: center;
    margin: 0 auto;
    padding: 1em;
    background: #FDD;
    border-radius: 10px;
    position: absolute;
    top: 1em;
    left:  20%;
}

#id-container, #barcode-container {
    border: solid 1px #6290B3;
    width: 35%;
    float: left;
    margin-left: 10%;
    text-align: center;
    padding: 1em 0 1em 0;
    margin-top: 20%;
    border-radius: 30px;
    background-color: #E7EEF3;
    color: #4D6E87;
	height: 130px;
}

.second-screen { display:none; }

</style>
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/tactile.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<script language="javascript">
<!--

var barcode = '';
var keyCode = {'48':'0', '49':'1', '50':'2', '51':'3', '52':'4', '53':'5', '54':'6', '55':'7', '56':'8', '57':'9'};


// on load
$(document).ready(function(){

	// on click sur la case avec les identifiants
	$('body').delegate('#id-container','click',function(){
		if ($('#id-container .second-screen').css('display') == 'none') {
			$('#id-container .first-screen').hide('fast');
			$('#id-container .second-screen').show('fast');
			$('#username').focus();
		}
	});


	// on click sur la case avec le code barre
	$('body').delegate('#barcode-container','click',function(){
		if ($('#barcode-container .second-screen').css('display') == 'none') {
			$('#barcode-container .first-screen').hide('fast');
			$('#barcode-container .second-screen').show('fast');
			$('#barcode').focus();
		}
	});

	
	$('body').keypress(function(event) {
//		console.log("Barcode='"+barcode+"'");
//		console.log("keyCode[event.which]='"+keyCode[event.which]+"'");
//		console.log("event.which='"+event.which+"'");
		
		if ( event.which == 13 ) {								// entrée --> on envoi
			document.login.barcode.value = barcode;
			document.login.submit();
		} else if (event.which >= 48 && event.which <= 57) {	// un chiffre --> on stock
			barcode += keyCode[event.which] ;
		} else {												// on reset si autre chose
			barcode = '';
		}
	});


});

//-->
</script>
</head>
<body>
<form method="post" action="<?=$_SERVER['PHP_SELF']?>" name="login">
<div id="message"><?=$message?></div>
<div id="id-container">
	<div class="first-screen">
		Je connais mes identifiants et mot de passe<br/><br/>
		<img src="gfx/password.png"/>
	</div>
	<div class="second-screen" style="text-align:right;margin-right:25%;">
		Code utilisateur : <input id="username" name="username" type="text" value="" size="13" style="margin-bottom:1em;"/><br/>
		Mot de passe : <input id="password" name="password" type="password" value="" size="13" style="margin-bottom:1em;"/><br/>
		<input type="submit" value="Se connecter"/>
	</div>
</div>

<div id="barcode-container">
	<div class="first-screen">
		Je dispose d'une carte avec un code barre<br/><br/>
		<img src="gfx/barcode.png"/>
	</div>
	<div class="second-screen">
		Scanner votre carte pour vous connecter<br/><br/>
		<img src="gfx/barcode_reader.png">&nbsp;<input id="barcode" name="barcode" type="password" value="" size="13" maxlength="13"/>
	</div>
</div>

</form>
</body>
</html>