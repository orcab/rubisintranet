<style>
ul#tabnav {
    font: bold 11px verdana, arial, sans-serif;
    list-style-type: none;
	padding:0px;
    padding-bottom: 24px;
    border-bottom: 1px solid #66b9cc;
    margin: 0;
}

ul#tabnav li {
    float: left;
    height: 21px;
    margin: 2px 1px 0 1px;
	/*border:solid 1px black;*/
	padding:0px;
}

#tabnav div.icon {
	position:relative;
	top:-10px;
	width:100%;
	/*border:solid 1px red;*/
	background-repeat:no-repeat;
	background-position:5px 3px;
}

ul#tabnav li.active {
    border-bottom: 1px solid #fff;
}

#tabnav div.libelle {
    float: left;
    display: block;
    color: #666;
    text-decoration: none;
    padding: 4px;
	padding-left:30px;
	border:solid 1px #66b9cc;
	-moz-border-radius:7px 7px 0 0;
	background-color:#e8f6f8;
}

</style>

<script language="javascript">
function redirect(ou) {
	document.location.href=ou;
}
</script>

<ul id="tabnav" class="hide_when_print">

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/index.php")) { // test si le fichier exists ?>
		<li>
			<div class="libelle">Accueil</div>
			<div class="icon"	onclick="redirect('/intranet');"
								style="background-image:url(/intranet/gfx/icon-home-mini.png);"								
								onmouseover="$(this).prev('div.libelle').css('background','white');"
								onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
		</li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/article/creation_article.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'article/creation_article.php') !== false ?' class="active"':''?>>
		<div class="libelle">Création articles</div>
		<div class="icon"	onclick="redirect('/intranet/article/creation_article.php');"
							style="background-image:url(/intranet/gfx/icon-article-mini.png);"								
							onmouseover="$(this).prev('div.libelle').css('background','white');"
							onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
	</li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/article/historique_creation_article.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'article/historique_creation_article.php') !== false ?' class="active"':''?>>
		<div class="libelle">Historique articles</div>
		<div class="icon"	onclick="redirect('/intranet/article/historique_creation_article.php');"
							style="background-image:url(/intranet/gfx/icon-article-histo-mini.png);"								
							onmouseover="$(this).prev('div.libelle').css('background','white');"
							onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
	</li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/commande_adherent/historique_commande.php")) { // test si le fichier exists ?>
    <li<?= stripos($_SERVER['SCRIPT_FILENAME'],'commande_adherent/historique_commande.php') !== false ?' class="active"':''?>>
		<div class="libelle">Cde Adhérents</div>
		<div class="icon"	onclick="redirect('/intranet/commande_adherent/historique_commande.php');"
							style="background-image:url(/intranet/gfx/icon-cde-adh-mini.png);"								
							onmouseover="$(this).prev('div.libelle').css('background','white');"
							onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
	</li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/commande_fournisseur/historique_commande.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'commande_fournisseur/historique_commande.php') !== false ?' class="active"':''?>>
		<div class="libelle">Cde Fournisseurs</div>
		<div class="icon"	onclick="redirect('/intranet/commande_fournisseur/historique_commande.php');"
							style="background-image:url(/intranet/gfx/icon-cde-fourn-mini.png);"								
							onmouseover="$(this).prev('div.libelle').css('background','white');"
							onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
	</li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/devis_rubis/historique_devis.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'devis_rubis/historique_devis.php') !== false ?' class="active"':''?>>
		<div class="libelle">Devis Rubis</div>
		<div class="icon"	onclick="redirect('/intranet/devis_rubis/historique_devis.php');"
							style="background-image:url(/intranet/gfx/icon-devis-rubis-mini.png);"								
							onmouseover="$(this).prev('div.libelle').css('background','white');"
							onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
	</li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/devis2/historique_devis.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'devis2/historique_devis.php') !== false ?' class="active"':''?>>
		<div class="libelle">Devis Expo</div>
		<div class="icon"	onclick="redirect('/intranet/devis2/historique_devis.php');"
							style="background-image:url(/intranet/gfx/icon-devis-expo-mini.png);"								
							onmouseover="$(this).prev('div.libelle').css('background','white');"
							onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
	</li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/anomalie/historique_anomalie.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'anomalie/historique_anomalie.php') !== false ?' class="active"':''?>>
		<div class="libelle">Anomalies</div>
		<div class="icon"	onclick="redirect('/intranet/anomalie/historique_anomalie.php');"
							style="background-image:url(/intranet/gfx/icon-anomalie-mini.png);"								
							onmouseover="$(this).prev('div.libelle').css('background','white');"
							onmouseout="$(this).prev('div.libelle').css('background','#e8f6f8');">&nbsp;</div>
	</li>
<?	} ?>
</ul>