<?
include('../inc/config.php');
?><html>
<head>
<title>Menu Douchette</title>
<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
	margin:0px;
	padding:0px;
	color:white;
	background-color:black;
}

a img { 
	border:none;
}

a {
	text-decoration:none;
	color:white;
}

a:hover {
	text-decoration:underline;
	color:yellow;
}

</style>

</head>
<body>
<center>
<table style="width:70%;text-align:center;border:solid 1px #AAA;">
<tr>
	<td></td>
	<td><a href="add_gencode.php"><img src="gfx/add_gencode.gif"><br>Saisie de GENCODE</a><br/><br/></td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td><a href="comparateur_prix.php"><img src="gfx/comparateur_prix.gif"><br>Comparateur de prix</a><br/><br/></td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td>
	<!--<a href="photo_indexer.php"><img src="gfx/photo_indexer.gif"><br>Association de photos</a>-->
		<a href="vl_produit.php"><img src="gfx/vl_produit.gif"><br>VL des produits</a>
	</td>
	<td></td>
</tr>
</table>
</center>
</body>
</html>