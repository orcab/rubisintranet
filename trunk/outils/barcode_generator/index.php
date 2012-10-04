<html>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-15">
<head>
<style>

fieldset {
	width:30% ;
	margin-bottom:1em;
}

#apercu table td {
    border: solid 1px black;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>
<script type="text/javascript" src="../../js/jquery.js"></script>
<script language="javascript">


</script>
</head>
<body>
<form method="post" action="etiquette.php">

<fieldset style="float:right;"><legend>Liste des emplacements</legend>
	<textarea name="emplacements" style="width:25em;height:20em;">M23 I 005 00 D
D21 P 002 10 A
P12 I 001 20 A
D01 I 003 30 B
M01 I 005 40 C
D16 P 002 50 A
M23 I 005 60 D
D21 P 002 70 A
P12 I 001 80 A
D01 I 003 90 B
M01 I 005 60 C
D16 P 002 10 A
M23 I 005 80 D
D21 P 002 20 A
P12 I 001 10 A
D01 I 003 30 B
M01 I 005 60 C
D16 P 002 10 A
P12 I 001 10 A
D01 I 003 30 B
M01 I 005 60 C
D16 P 002 10 A
M23 I 005 80 D
D21 P 002 20 A
P12 I 001 10 A
D01 I 003 30 B
M01 I 005 60 C
D16 P 002 10 A</textarea>
</fieldset>

<fieldset><legend>Format d'&eacute;tiquettes</legend>
	<!--<input type="radio" name="format_etiquette" value="petit" id="petit"/><label for="petit">Petit (3x2cm)</label><br/>-->
	<!--<input type="radio" name="format_etiquette" value="grand" id="grand"/><label for="grand">Grand (9x8cm)</label><br/>-->
	<input type="radio" name="format_etiquette" value="L6009" id="L6009" checked="checked" /><label for="L6009">Avery L6009 45.7x21.2mm (48 etiq/page)</label><br/>
	<input type="radio" name="format_etiquette" value="L7993" id="L7993" checked="checked" /><label for="L7993">Avery L7993 99.1x67.7mm ( 8 etiq/page)</label>
	<dd><input type="checkbox" name="arrow" value="arrow" checked=""/>&nbsp;Avec les flêches</dd>
</fieldset>

<input type="submit" class="button valider" value="Générer les étiquettes" /><br/><br/>

</form>
</body>
</html>