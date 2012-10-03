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
	<textarea name="emplacements" style="width:20em;height:20em;">M23 I 005 00 D
D21 P 002 10 A
P12 I 001 20 A
D01 I 003 30 B
M01 I 005 40 C
D16 P 002 50 A
M23 I 005 60 D
D21 P 002 70 A
P12 I 001 80 A
D01 I 003 30 B
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

<!--<fieldset><legend>Format de page</legend>
	<input type="radio" name="format_page" value="A4" id="format_A4" checked="checked"/><label for="format_A4">A4</label>
	<input type="radio" name="format_page" value="A3" id="format_A3"/><label for="format_A3">A3</label>
	<br/>
	<input type="radio" name="orientation_page" value="P" id="P"/><label for="P">Portrait</label>
	<input type="radio" name="orientation_page" value="L" id="L" checked="L"/><label for="paysage">Paysage</label>
</fieldset>-->

<fieldset><legend>Format d'&eacute;tiquettes</legend>
	<!--<input type="radio" name="format_etiquette" value="petit" id="petit"/><label for="petit">Petit (3x2cm)</label><br/>-->
	<!--<input type="radio" name="format_etiquette" value="grand" id="grand"/><label for="grand">Grand (9x8cm)</label><br/>-->
	<input type="radio" name="format_etiquette" value="L6009" id="L6009" checked="checked" /><label for="L6009">Avery L6009 45.7x21.2mm (48 etiq/page)</label><br/>
	<input type="radio" name="format_etiquette" value="L7993" id="L7993" checked="checked" /><label for="L7993">Avery L7993 99.1x67.7mm ( 8 etiq/page)</label>
</fieldset>

<!--<fieldset><legend>Nombre d'&eacute;tiquettes maximum par lignes</legend>
	Par ligne : <select id="colonne" name="colonne">
					<option value="1">1</option><option value="2">2</option><option value="3">3</option>
					<option value="4">4</option><option value="5">5</option><option value="6">6</option>
				</select>
</fieldset>-->

<input type="submit" class="button valider" value="Générer les étiquettes" /><br/><br/>

<!--<fieldset><legend>Aperçu</legend>
	<div id="apercu"></div>
</fieldset>-->
</form>
</body>
</html>