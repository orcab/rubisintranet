<?

include('../../inc/config.php');
include('../../inc/ping/ping.php'); # import ping(ip)

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
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
	-moz-border-radius:15px;
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
	-moz-border-radius:15px;
	background:white;
	display:none;
	position:absolute;
	color:green;
	font-size:1.2em;
	z-index:99;
}


div#detail-utilisateur table {
	font-size:0.6em;
}

div#detail-utilisateur table th {
	text-align:left;
}

div#detail-utilisateur table td,div#detail-utilisateur table th {
	vertical-align:bottom;
}

table#ordi {
	width:50%;
	border-collapse:collapse;
}

table#ordi td {
	border:solid 1px grey;
	padding:1px;
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
				if (ereg('^PEUT_',$constante)) { // une constante de droit ?>
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
							if (ereg('^PEUT_',$constante)) { // une constante de droit ?>
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
							if (ereg('^PEUT_',$constante)) { // une constante de droit
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

</script>
</head>
<body>

<form name="utilisateur" method="POST">
<input type="hidden" name="id" value="">


<div id="debug"></div>

<!-- boite de dialogue pour faire patienté pendant l'ajax -->
<div id="dialogue"></div>


<!-- boite qui affiche les détails de l'article -->
<div id="detail-utilisateur">
<table>
	<caption>Edition du détail pour <strong id="detail_utilisateur"></strong></caption>
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
		<th>Tél</th>
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
				<option value="0">Employé</option>
				<option value="1">Imprimante</option>
				<option value="2">Borne Wifi</option>
				<option value="3">Douchette Wifi</option>
				<option value="4">Serveur</option>
				<option value="5">PABX</option>
				<option value="6">Switch</option>
				<option value="7">Router</option>
				<option value="8">VirtualBox</option>
			</select>
		</td>
	</tr>
	</tr>
		<tr>
		<th style="vertical-align:top;">Droit</th>
		<td style="text-align:right;">
<?			foreach (get_defined_constants() as $constante => $valeur) {
				if (ereg('^PEUT_',$constante)) { // une constante de droit ?>
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
$res = mysql_query("SELECT id,prenom,CONCAT(prenom,' ',nom) AS utilisateur,email, ip, LOWER(machine) AS machine, printer FROM employe ORDER BY printer ASC, prenom ASC, nom ASC") or die ("Ne peux pas récupérer la liste des postes : ".mysql_error());
while($row = mysql_fetch_array($res)) {
	if ($row['ip']) {
?>
	<tr>
		<td>
				<a href="javascript:detail_utilisateur(<?=$row['id']?>,'<?=$row['prenom']?>');"><?=$row['utilisateur']?></a>
<?				if($row['printer'] == 1 || $row['printer'] == 2) { // imprimante ou borne wifi ?>
					<a href="http://<?=$row['ip']?>" target="_blank">[LIEN]</a>
<?				} ?>
		</td>
		<td><?=$row['machine']?></td>
		<td><?=$row['ip']?></td>
		<td style="text-align:center;"><?

	error_reporting(E_ALL ^ E_WARNING);
	set_time_limit(60);

	$etat = ping($row['ip']);

	echo '<img src="gfx/';
	switch ($row['printer']) {
		case 0 : echo 'computer-'.($etat?'ok':'bad').'.png'; break;
		case 1 : echo 'printer-'.($etat?'ok':'bad').'.png'; break;
		case 2 : echo 'borne-wifi-'.($etat?'ok':'bad').'.png'; break;
		case 3 : echo 'douchette-'.($etat?'ok':'bad').'.png'; break;
		case 4 : echo 'serveur-'.($etat?'ok':'bad').'.png'; break;
		case 5 : echo 'pabx-'.($etat?'ok':'bad').'.png'; break;
		case 6 : echo 'switch-'.($etat?'ok':'bad').'.png'; break;
		case 7 : echo 'router-'.($etat?'ok':'bad').'.png'; break;
		case 8 : echo 'virtual-'.($etat?'ok':'bad').'.png'; break;
	}
	echo '">';
?>
	</td>
	<td style="text-align:center;">
<?
	//if (FALSE) { // si PC allumé
	if ($etat && in_array($row['printer'],array(0,4,8)) { // si PC allumé

		$port = 5900 ;
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (socket_connect($socket, $row['ip'], $port) == TRUE) { // connexion réussi, VNC allumé
			socket_close($socket);
			echo '<img src="gfx/vnc-ok.png">';
		} else { // VNC éteint
			echo '<img src="gfx/vnc-bad.png">';
		}
	}
?>
	</td>
	</tr>
<? } //fin if IP
} // fin while
?>
</table>

</form>
</body>
</html>