<html>
<head></head>
<body>

<pre>
<?php
if (isset($_POST['texte']) && $_POST['texte']) {
	foreach (explode("\n",$_POST['texte']) as $ligne)
		echo wordwrap($ligne,$_POST['size'],"\t");
}
?>
</pre>

<form name="wordwrap" method="POST">
<textarea name="texte" cols="80" rows="20">
Copier le texte ici
</textarea><br/>
Colonne de <input type="text" name="size" value="40" size="1"/> caractères maximum<br/>
<input type="submit" value="Tronche moi tout ça !"/>
</form>
</body>
</html>