<?

include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$droit = recuperer_droit();

if (!($droit & PEUT_CHANGER_EDI)) {
	echo "Vous n'avez l'autorisation pour accèdez à cette partie" ;
	exit;
}

?>
<html>
<head>
<title>Liste des artisans</title>

<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img	{ border:none; }
a		{ text-decoration:none; }
a:hover { text-decoration:underline; }

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


div#detail-utilisateur table { font-size:0.6em; }
div#detail-utilisateur table th { text-align:left; }
div#detail-utilisateur table td,div#detail-utilisateur table th { vertical-align:bottom; }

table#artisan {
	width:50%;
	border-collapse:collapse;
}

table#artisan td {
	border:solid 1px grey;
	padding:10px 0 10px 0;
}	
</style>
<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/infobulle.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script language="javascript">
<!--
	
function save_frequence(obj) {
	var info = new Array();
	info = obj.name.split(/_/);

	$.ajax({
			url: 'ajax.php',
			type: 'POST',
			data: 'what=save_frequence&numero='+info[1]+'&type_doc='+info[2]+'&val='+obj[obj.selectedIndex].value,
			success: function(result){
						if (result) {
							var json = eval('(' + result + ')') ;
							if (json['debug']) $('#debug').html(json['debug']);
						}
					}	
	});

	return true;
}

//-->
</script>
</head>
<body>

<form name="documents" method="POST">
<input type="hidden" name="id" value="">

<div id="debug"></div>

<!-- boite de dialogue pour faire patienté pendant l'ajax -->
<div id="dialogue"></div>

<table id="artisan" align="center">
	<caption></caption>
<?
$sql = <<<EOT
SELECT	nom,numero,email,AR,BL,RELIQUAT,AVOIR
FROM	artisan
			left join send_document on artisan.numero = send_document.numero_artisan
WHERE	
		email<>''
	and email IS NOT NULL
ORDER	BY nom ASC
EOT;


$i = 15 ;
$res = mysql_query($sql) or die ("Ne peux pas récupérer la liste des artisans : ".mysql_error());
while($row = mysql_fetch_array($res)) {
	if (($i % 15) == 0) { // tous les 15 champs, on réaffiche l'entete ?>
		<tr>
			<th>Artisan</th>
			<th>email</th>
			<th>AR</th>
			<th>BL</th>
			<th>RELIQUAT</th>
			<th>AVOIR</th>
		</tr>
<?	} ?>
	<tr>
		<td><?=$row['nom']?></td>
		<td><?=$row['email']?></td>
	
<?		foreach (array('AR','BL','RELIQUAT','AVOIR') as $type_doc) { ?>
			<td>
				<select name="select_<?=$row['numero']?>_<?=$type_doc?>" onchange="save_frequence(this);change_color(this);">
					<option style="background:white;" value=""<?=$row[$type_doc]==''?' selected':''?>>Pas d'envoi</option>
					<option style="background:yellow;" value="1,2,3,4,5"<?=$row[$type_doc]=='1,2,3,4,5'?' selected':''?>>Quotidien</option>
					<optgroup style="background-color:#44F;" label="Hebdomadaire">
						<option style="background:#CCF;" value="1"<?=$row[$type_doc]=='1'?' selected':''?>>Lundi</option>
						<option style="background:#AAF;" value="2"<?=$row[$type_doc]=='2'?' selected':''?>>Mardi</option>
						<option style="background:#88F;" value="3"<?=$row[$type_doc]=='3'?' selected':''?>>Mercredi</option>
						<option style="background:#66F;" value="4"<?=$row[$type_doc]=='4'?' selected':''?>>Jeudi</option>
						<option style="background:#44F;" value="5"<?=$row[$type_doc]=='5'?' selected':''?>>Vendredi</option>
					</optgroup>
					<optgroup style="background-color:#AF4;" label="Variables">
						<option style="background:#DFD;" value="1,3,5"<?=$row[$type_doc]=='1,3,5'?' selected':''?>>Lun,Mer,Ven</option>
						<option style="background:#CFC;" value="1,3"<?=$row[$type_doc]=='1,3'?' selected':''?>>Lun,Mer</option>
						<option style="background:#AFA;" value="1,4"<?=$row[$type_doc]=='1,4'?' selected':''?>>Lun,Jeu</option>
						<option style="background:#8F8;" value="1,5"<?=$row[$type_doc]=='1,5'?' selected':''?>>Lun,Ven</option>
						<option style="background:#6F6;" value="2,4"<?=$row[$type_doc]=='2,4'?' selected':''?>>Mar,Jeu</option>
						<option style="background:#4F4;" value="2,5"<?=$row[$type_doc]=='2,5'?' selected':''?>>Mar,Ven</option>
						<option style="background:#2F2;" value="3,5"<?=$row[$type_doc]=='3,5'?' selected':''?>>Mer,Ven</option>
					</optgroup>
				</select>
			</td>
<?		} ?>
	</tr>
<?		$i++;
	} // fin while ?>
</table>

</form>

<script language="javascript">
<!--
for(i=0 ; document.documents.elements.length ; i++) {
	if (document.documents.elements[i].type == 'select-one') {
		document.documents.elements[i].style.backgroundColor = document.documents.elements[i].options[document.documents.elements[i].selectedIndex].style.backgroundColor;
	} 
}

function change_color(obj) {
	obj.style.backgroundColor = obj.options[obj.selectedIndex].style.backgroundColor;
}

//-->
</script>

</body>
</html>