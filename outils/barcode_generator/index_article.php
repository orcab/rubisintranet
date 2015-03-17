<html>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-15">
<head>
<title>Etiquettes article</title>
<style>

fieldset {
	width:30% ;
	margin-bottom:1em;
}

#apercu table td {
    border: solid 1px black;
}

input[name=jump] {
	width:2em;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>
<script type="text/javascript" src="../../js/jquery.js"></script>
<script language="javascript">


</script>
</head>
<body>
<form method="post" action="etiquette_article.php">

<fieldset style="float:right;"><legend>Liste des articles (exemple : 02001843)</legend>
	<textarea name="articles" style="width:25em;height:20em;"></textarea>
</fieldset>

<fieldset><legend>Format d'&eacute;tiquettes article</legend>
	<!--<input type="radio" name="format_etiquette" value="A4" id="A4" /><label for="A4">A4 (1 etiq/page)</label><br/>
	<input type="radio" name="format_etiquette" value="L6009" id="L6009" /><label for="L6009">Avery L6009 45.7x21.2mm (48 etiq/page)</label><br/>-->
	<input type="radio" name="format_etiquette" value="L6011" id="L6011" selected="selected"/><label for="L6011">Avery L6011 63.5x29.6mm (27 etiq/page)</label><br/>
	<!--<input type="radio" name="format_etiquette" value="L7993" id="L7993" checked="checked" /><label for="L7993">Avery L7993 99.1x67.7mm ( 8 etiq/page)</label>-->
	<br/><br/>
	Laisser <input name="jump" type="text" onkeyup="this.value=this.value.replace(/\D/,'');" value="0"/> étiquette(s) blanche au début.
</fieldset>

<input type="submit" class="button valider" value="Générer les étiquettes article" /><br/><br/>

</form>
</body>
</html>