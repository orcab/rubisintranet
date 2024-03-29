<?

include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$droit = recuperer_droit();

?>
<html>
<head>
<title>Liste des PC</title>

<style>

div#dialogue {
	padding:20px;
	border:solid 2px black;
	border-radius:15px;
	background:white;
	display:none;
	position:absolute;
	color:green;
	font-size:1.2em;
	z-index:99;
}

div#detail-utilisateur {
	padding:20px;
	border:solid 2px black;
	border-radius:15px;
	background:white;
	display:none;
	position:absolute;
	color:green;
	font-size:1.2em;
	z-index:99;
}


div#detail-utilisateur table { font-size:0.6em; }

div#detail-utilisateur table th { text-align:left; }

div#detail-utilisateur table td,div#detail-utilisateur table th { vertical-align:bottom; }

table#ordi {
	width:50%;
	border-collapse:collapse;
}

table#ordi td {
	border:solid 1px grey;
	padding:1px;
	height:32px;
}	
</style>
<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/infobulle.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script language="javascript">
<!--

function detail_utilisateur(id,prenom) {
	document.utilisateur.id.value=id;

	$('#detail-utilisateur').css('top',document.body.scrollTop +100);
	$('#detail-utilisateur').css('left',screen.availWidth / 2 - 300);

	document.utilisateur.detail_utilisateur_prenom.value='';
	document.utilisateur.detail_utilisateur_nom.value='';
	document.utilisateur.detail_utilisateur_email.value='';
	document.utilisateur.detail_utilisateur_loginor.value='';
	document.utilisateur.detail_utilisateur_code_vendeur.value='';
	document.utilisateur.detail_utilisateur_tel.value='';
	document.utilisateur.detail_utilisateur_ip.value='';
	document.utilisateur.detail_utilisateur_machine.value='';
	document.utilisateur.detail_utilisateur_printer.selectedIndex=0;
<?			foreach (get_defined_constants() as $constante => $valeur) {
				if (preg_match('/^PEUT_/',$constante)) { // une constante de droit ?>
					document.utilisateur.detail_utilisateur_<?=$constante?>.checked=false;
<?				}
			}
?>

	$('#detail_utilisateur').text(prenom);
	$('#detail_utilisateur_prenom').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_utilisateur_nom').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_utilisateur_email').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_utilisateur_loginor').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_utilisateur_code_vendeur').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_utilisateur_tel').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_utilisateur_ip').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_utilisateur_machine').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail-utilisateur').show();

	$.ajax({
			url: 'ajax.php',
			type: 'POST',
			data: 'what=detail_utilisateur&id='+id,
			success: function(result){
						var json = eval('(' + result + ')') ;
						
						$('#detail_utilisateur_prenom').css('background','white');
						document.utilisateur.detail_utilisateur_prenom.value=$.trim(json['prenom']);
						$('#detail_utilisateur_nom').css('background','white');
						document.utilisateur.detail_utilisateur_nom.value=$.trim(json['nom']);
						$('#detail_utilisateur_email').css('background','white');
						document.utilisateur.detail_utilisateur_email.value=$.trim(json['email']);
						$('#detail_utilisateur_loginor').css('background','white');
						document.utilisateur.detail_utilisateur_loginor.value=$.trim(json['loginor']);
						$('#detail_utilisateur_code_vendeur').css('background','white');
						document.utilisateur.detail_utilisateur_code_vendeur.value=$.trim(json['code_vendeur']);
						$('#detail_utilisateur_tel').css('background','white');
						document.utilisateur.detail_utilisateur_tel.value=$.trim(json['tel']);
						$('#detail_utilisateur_ip').css('background','white');
						document.utilisateur.detail_utilisateur_ip.value=$.trim(json['ip']);
						$('#detail_utilisateur_machine').css('background','white');
						document.utilisateur.detail_utilisateur_machine.value=$.trim(json['machine']);
						document.utilisateur.detail_utilisateur_printer.selectedIndex = json['printer'] ;

<?						foreach (get_defined_constants() as $constante => $valeur) {
							if (preg_match('/^PEUT_/',$constante)) { // une constante de droit ?>
								document.utilisateur.detail_utilisateur_<?=$constante?>.checked= <?=$valeur?> & json['droit'] ? true:false ;
<?							}
						}
?>
						if (json['debug']) $('#debug').html(json['debug']);
					}	
	});
}


<? if ($droit & PEUT_MODIFIER_UTILISATEUR) { ?>
function valider_detail_utilisateur() {
	$('#detail-utilisateur').hide();
	$('#dialogue').html("<img src=\"gfx/loading3.gif\" align=\"absmiddle\"> En cours de modification.");
	$('#dialogue').css('top',document.body.scrollTop +100);
	$('#dialogue').css('left',screen.availWidth / 2 - 300);
	$('#dialogue').show();

	$.ajax({
			url: 'ajax.php',
			type: 'POST',
			data:	'what=valider_detail_utilisateur&id='+document.utilisateur.id.value+
					'&prenom='		+$.trim(document.utilisateur.detail_utilisateur_prenom.value)+
					'&nom='			+$.trim(document.utilisateur.detail_utilisateur_nom.value)+
					'&email='		+$.trim(document.utilisateur.detail_utilisateur_email.value)+
					'&loginor='		+$.trim(document.utilisateur.detail_utilisateur_loginor.value)+
					'&code_vendeur='+$.trim(document.utilisateur.detail_utilisateur_code_vendeur.value)+
					'&ip='			+$.trim(document.utilisateur.detail_utilisateur_ip.value)+
					'&tel='			+$.trim(document.utilisateur.detail_utilisateur_tel.value)+
					'&machine='		+$.trim(document.utilisateur.detail_utilisateur_machine.value)+
					'&printer='		+document.utilisateur.detail_utilisateur_printer.selectedIndex+
					'&droit='		+ (<?
						$tmp = array();
						foreach (get_defined_constants() as $constante => $valeur) {
							if (preg_match('/^PEUT_/',$constante)) { // une constante de droit
								$tmp[] = "(document.utilisateur.detail_utilisateur_$constante.checked ? $valeur:0)";
							}
						}
						echo join(" + ",$tmp);
?>)  ,
			success: function(result){
						var json = eval('(' + result + ')') ;
						
						$('#dialogue').html('OK');
						$('#dialogue').fadeOut(2000);

						if (json['debug']) $('#debug').html(json['debug']);
					}	
	});
}
<? } // PEUT_MODIFIER_UTILISATEUR ?>

var machines_ip = new Array();

// document charg�
$(document).ready(function(){

	// on lance les tests IP
	//console.log(machines_ip);
	for(var i=0; i<machines_ip.length ; i++) {
		$('tr#'+machines_ip[i].ip.replace(/\./g,'_')+' td.ping').html('<img src="gfx/loading5.gif"/>');
		$('tr#'+machines_ip[i].ip.replace(/\./g,'_')+' td.vnc').html('<img src="gfx/loading5.gif"/>');

		$.getJSON('ajax.php?what=ping&ip='+machines_ip[i].ip+'&type='+machines_ip[i].type, function(result) {
				console.log(result);//   type:"+machines_ip[result.ip].type);

				var img = '<img src="gfx/';
				switch (result.type) {
					case '0' : img += 'computer-'	+	(result.ping?'ok':'bad')+'.png'; break;
					case '1' : img += 'printer-'	+	(result.ping?'ok':'bad')+'.png'; break;
					case '2' : img += 'borne-wifi-'	+	(result.ping?'ok':'bad')+'.png'; break;
					case '3' : img += 'douchette-'	+	(result.ping?'ok':'bad')+'.png'; break;
					case '4' : img += 'serveur-'	+	(result.ping?'ok':'bad')+'.png'; break;
					case '5' : img += 'pabx-'		+	(result.ping?'ok':'bad')+'.png'; break;
					case '6' : img += 'switch-'		+	(result.ping?'ok':'bad')+'.png'; break;
					case '7' : img += 'router-'		+	(result.ping?'ok':'bad')+'.png'; break;
					case '8' : img += 'virtual-'	+	(result.ping?'ok':'bad')+'.png'; break;
					case '9' : img += 'label-printer-'+	(result.ping?'ok':'bad')+'.png'; break;
					case '10': img += 'cellular-'	+	(result.ping?'ok':'bad')+'.png'; break;
					case '11': img += 'tablet-'		+	(result.ping?'ok':'bad')+'.png'; break;
				}
				img += '"/>';
				$('tr#'+result.ip.replace(/\./g,'_')+' td.ping').html(img);

				if		(result.vnc == -1)
					$('tr#'+result.ip.replace(/\./g,'_')+' td.vnc').html('<img src="gfx/vnc-bad.png"/>');
				else if (result.vnc == 1)
					$('tr#'+result.ip.replace(/\./g,'_')+' td.vnc').html('<img src="gfx/vnc-ok.png"/>');
				else
					$('tr#'+result.ip.replace(/\./g,'_')+' td.vnc').html('');
		});
	}

});

</script>
</head>
<body>

<form name="utilisateur" method="POST">
<input type="hidden" name="id" value="">


<div id="debug"></div>

<!-- boite de dialogue pour faire patient� pendant l'ajax -->
<div id="dialogue"></div>


<!-- boite qui affiche les d�tails de l'article -->
<div id="detail-utilisateur">
<table>
	<caption>Edition du d�tail pour <strong id="detail_utilisateur"></strong></caption>
	<tr>
		<th>Prenom</th>
		<td><input type="text" name="detail_utilisateur_prenom" id="detail_utilisateur_prenom" size="15"></td>
	</tr>
	<tr>
		<th>Nom</th>
		<td><input type="text" name="detail_utilisateur_nom" id="detail_utilisateur_nom" size="15"></td>
	</tr>
	<tr>
		<th>Email</th>
		<td><input type="text" name="detail_utilisateur_email" id="detail_utilisateur_email" size="15"></td>
	</tr>
	<tr>
		<th>Login Loginor</th>
		<td><input type="text" name="detail_utilisateur_loginor" id="detail_utilisateur_loginor" size="15"></td>
	</tr>
	<tr>
		<th>Code vendeur</th>
		<td><input type="text" name="detail_utilisateur_code_vendeur" id="detail_utilisateur_code_vendeur" size="15"></td>
	</tr>
	<tr>
		<th>T�l</th>
		<td><input type="text" name="detail_utilisateur_tel" id="detail_utilisateur_tel" size="15"></td>
	</tr>
	<tr>
		<th>IP</th>
		<td><input type="text" name="detail_utilisateur_ip" id="detail_utilisateur_ip" size="15"></td>
	</tr>
	<tr>
		<th>Machine</th>
		<td><input type="text" name="detail_utilisateur_machine" id="detail_utilisateur_machine" size="15"></td>
	</tr>
	</tr>
	<tr>
		<th>Type</th>
		<td>
			<select name="detail_utilisateur_printer">
				<option value="0">Employ�</option>
				<option value="1">Imprimante</option>
				<option value="9">Imrimante &eacute;tiquette</option>
				<option value="2">Borne Wifi</option>
				<option value="3">Douchette Wifi</option>
				<option value="4">Serveur</option>
				<option value="5">PABX</option>
				<option value="6">Switch</option>
				<option value="7">Router</option>
				<option value="8">VirtualBox</option>
				<option value="10">Cellulaire</option>
				<option value="11">Tablette</option>
			</select>
		</td>
	</tr>
	</tr>
	<tr>
		<th style="vertical-align:top;">Droit</th>
		<td style="text-align:right;">
<?			foreach (get_defined_constants() as $constante => $valeur) {
				if (preg_match('/^PEUT_/',$constante)) { // une constante de droit ?>
					<?=$constante?> <input type="checkbox" name="detail_utilisateur_<?=$constante?>"><br>
<?				}
			}
?>		</td>
	</tr>
	<tr>
		<td><? if ($droit & PEUT_MODIFIER_UTILISATEUR) { ?>
				<input value="Valider" class="button valider" type="button" onclick="valider_detail_utilisateur();">
			<? } ?>
		</td>
		<td><input value="Annuler" class="button annuler" type="button" onclick="$('#detail-utilisateur').hide();"></td>
	</tr>
</table>
</div>


<table id="ordi" align="center">
	<caption></caption>
	<tr>
		<th>Utilisateur</th>
		<th>Poste</th>
		<th>IP</th>
		<th>Etat</th>
		<th>VNC</th>
	</tr>
<?
$res = mysql_query("SELECT id,prenom,CONCAT(prenom,' ',nom) AS utilisateur,email, ip, LOWER(machine) AS machine, printer FROM employe ORDER BY printer ASC, prenom ASC, nom ASC") or die ("Ne peux pas r�cup�rer la liste des postes : ".mysql_error());
while($row = mysql_fetch_array($res)) {
	if ($row['ip']) {
?>
	<tr id="<?=preg_replace('/\./','_',$row['ip'])?>">
		<td>
			<script type="text/javascript">
			<!--
				machines_ip.push({
					'ip':'<?=$row['ip']?>',
					'type':'<?=$row['printer']?>',
				});
			//-->
			</script>
			<a href="javascript:detail_utilisateur(<?=$row['id']?>,'<?=$row['prenom']?>');"><?=$row['utilisateur']?></a>
<?			if($row['printer'] == 1 || $row['printer'] == 2) { // imprimante ou borne wifi ?>
				<a href="http://<?=$row['ip']?>" target="_blank">[LIEN]</a>
<?			} ?>
		</td>
		<td><?=$row['machine']?></td>
		<td><?=$row['ip']?></td>
		<td style="text-align:center;" class="ping"></td>
		<td style="text-align:center;" class="vnc"></td>
	</tr>
<? } //fin if IP
} // fin while
?>
</table>

</form>
</body>
</html>