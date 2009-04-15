<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$erreur   = FALSE ;
$message  = '' ;

/*
$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL AND code_vendeur<>'' ORDER BY prenom ASC");
$vendeurs = array();
while($row = mysql_fetch_array($res)) {
	$vendeurs[$row['code']] = $row['prenom'];
}
$vendeurs['LN'] = 'Jean René';
$vendeurs['MAR'] = 'Marc';
*/
?>
<html>
<head>
<title>Vente à emporter</title>
<style>
a img { border:none; }

@media print {
	.hide_when_print { display:none; }
}
</style>

<!-- STYLE POUR UN TERMINAL DEPORTE -->
<style>
body {
	margin:0px;
	border:solid 1px green;
	font-family:verdana;
	width:240px;
	height:210px;
}

body, input {
	color:yellow;
	background:black;
}

table {
	 width:100%;
	 border-collapse:collapse;
}

td {
	font-size:0.6em;
	/*border:dotted 1px #040;*/
	padding:0px;
	vertical-align:center;
}

th {
	font-size:0.7em;
	color:white;
	text-align:left;
}

td.libelle,td.prix,td.qte {
	text-align:right;
}

td.valeur {
	text-align:left;
}

table#articles td {
	border:none;
	border-bottom:dotted 1px darkgreen;
	border-top:dotted 1px darkgreen;
	font-family:smalls font;
}

td.code {
	vertical-align:top;
	color:lightgreen;
	font-size:10px;
	padding-right:2px;
}

td.designation {
	font-size:10px;
	padding-right:2px;
}

td.qte {
	padding-right:2px;
	color:lightgreen;
	font-size:0.8em;
}

.desi1 {
	
}

.desi2 {
	color: #6E6F00;
}

input {
	border:solid 1px #6E6F00;
	font-family:verdana;
	font-size:1.3em;
}

</style>
</head>
<body>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="emporter" action="toto.php" method="POST">
<input type="hidden" name="action" value="">

<table>
	<tr>
		<th colspan="4">Entête</th>
	</tr>

	<tr>
		<td class="libelle">Vendeur</td>
		<td class="valeur"><input type="text" name="vendeur" value="AFJJR" size="5"/></td>
		<td class="libelle">Date</td>
		<td class="valeur"><input type="text" name="date" value="26/11/2008" size="10"/></td>
	</tr>
	<tr>
		<td class="libelle">Adh</td>
		<td class="valeur"><input type="text" name="adh" value="056038" size="6"/></td>
		<td class="valeur" colspan="2" >Broceliande SARL</td>
	</tr>
	<tr>
		<td class="libelle">Référence</td>
		<td class="valeur" colspan="3"><input type="text" name="reference" value="KERPLAT" size="24"/></td>
	</tr>

	<tr>
		<th colspan="4">Articles</th>
	</tr>

	<tr><td colspan="4"><!-- autre tableau pour les articles -->

	<table id="articles">
	<tr>
		<td class="code">04008598</td>
		<td class="designation"><div class="desi1">MINI KARDALU ROND 12V GX5,3 50W 965740</div><div class="desi2">RESISTEX  965740</div></td>
		<td class="qte">4</td>
		<td class="prix">6,23</td>
	</tr>
	<tr>
		<td class="code">04008515</td>
		<td class="designation"><div class="desi1">APPLIQUE POSEIDON II 2*60W G9</div><div class="desi2">PAULMANN  992.94</div></td>
		<td class="qte">1</td>
		<td class="prix">100,85</td>
	</tr>
	<tr>
		<td class="code">02003114</td>
		<td class="designation"><div class="desi1">KANDO MBLE SS VASQUE HETRE/BEIGE 70CM</div><div class="desi2">DELABIE 374001</div></td>
		<td class="qte">20</td>
		<td class="prix">0,89</td>
	</tr>
	<tr>
		<td class="code">10000011</td>
		<td class="designation"><div class="desi1">BURIN PLAT SDS-PLUS  L250  2 609 390 394</div><div class="desi2">BOSCH</div></td>
		<td class="qte">1</td>
		<td class="prix">23,00</td>
	</tr>
	</table>

	</td></tr><!-- fin tableau des articles -->

	<tr>
		<th colspan="4">Flash</th>
	</tr>
	<tr>
		<td class="libelle">Code</td>
		<td class="valeur"><input type="text" name="code_article" value="01002456" size="9"/></td>
		<td class="libelle">Qte</td>
		<td class="valeur"><input type="text" name="qte" value="100" size="10"/></td>
	</tr>
</table>

</form>
</body>
</html>
<?
//mysql_close($mysql);
?>