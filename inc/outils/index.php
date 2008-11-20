<?

include('../inc/config.php');

?><html>
<head>
<title>Intranet</title>
<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img { 
	border:none;
}

a {
	text-decoration:none;
}

a:hover {
	text-decoration:underline;
}

</style>

</head>
<body style="margin:0px;padding:0px;">
<div style="width:100%;background-color:#DDD;margin-bottom:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">Intranet <?=SOCIETE?> &gt;&gt; OUTILS</div>

<center>
<table style="width:70%;text-align:center;border:solid 1px #AAA;">
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="ordinateur/index.php"><img src="ordinateur/gfx/computer.png"><br>Ordinateur</a><br></td>
	<td><a href="plan_de_vente/index.html"><img src="../gfx/plan_de_vente.png"><br>Plan de vente</a></td>
	<td style="width:50%;padding-bottom:20px;"><a href="cde_client_cde_fournisseur/index.php"><img src="cde_client_cde_fournisseur/gfx/assoc.png"><br>Relier cde adhérents et cde fournisseurs</a></td>
</tr>
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="edi/index.php"><img src="edi/gfx/edi3.png"><br>EDI</a><br></td>
	<td><a href="feuille_tournee/feuille_tournee.php"><img src="feuille_tournee/gfx/feuille_tournee.png"><br>Feuille de tournée</a></td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td></td>
	<td></td>
</tr>
</table>
</center>



<div style="width:100%;background-color:#DDD;margin-top:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">
	<a href="wiki/">[Wiki]</b></a>&nbsp;&nbsp;&nbsp;&nbsp;
</div>

</body>
</html>