<style>
ul#tabnav {
    font: bold 11px verdana, arial, sans-serif;
    list-style-type: none;
    padding-bottom: 24px;
    border-bottom: 1px solid #66b9cc;
    margin: 0;
}

ul#tabnav li {
    float: left;
    height: 21px;
    background-color:#e8f6f8;
    margin: 2px 2px 0 2px;
    border: 1px solid #66b9cc;
}

ul#tabnav li.active {
    border-bottom: 1px solid #fff;
    background-color: #fff;
}

#tabnav a {
    float: left;
    display: block;
    color: #666;
    text-decoration: none;
    padding: 4px;
}

#tabnav a:hover {
    background-color: #fff;
}
</style>

<ul id="tabnav" class="hide_when_print">

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/index.php")) { // test si le fichier exists ?>
		<li><a href="/intranet"><img src="/intranet/gfx/home_mini.png" style="vertical-align:top;border:none;"/> Accueil</a></li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/article/creation_article.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'article/creation_article.php') !== false ?' class="active"':''?>><a href="/intranet/article/creation_article.php">Création articles</a></li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/article/historique_creation_article.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'article/historique_creation_article.php') !== false ?' class="active"':''?>><a href="/intranet/article/historique_creation_article.php">Historique articles</a></li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/commande_adherent/historique_commande.php")) { // test si le fichier exists ?>
    <li<?= stripos($_SERVER['SCRIPT_FILENAME'],'commande_adherent/historique_commande.php') !== false ?' class="active"':''?>><a href="/intranet/commande_adherent/historique_commande.php">Cde Adhérents</a></li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/commande_fournisseur/historique_commande.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'commande_fournisseur/historique_commande.php') !== false ?' class="active"':''?>><a href="/intranet/commande_fournisseur/historique_commande.php">Cde Fournisseurs</a></li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/devis_rubis/historique_devis.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'devis_rubis/historique_devis.php') !== false ?' class="active"':''?>><a href="/intranet/devis_rubis/historique_devis.php">Devis Rubis</a></li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/devis2/historique_devis.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'devis/historique_devis.php') !== false ?' class="active"':''?>><a href="/intranet/devis/historique_devis.php">Devis Expo</a></li>
<?	} ?>

<?	if (file_exists("$_SERVER[DOCUMENT_ROOT]/intranet/anomalie/historique_anomalie.php")) { // test si le fichier exists ?>
	<li<?= stripos($_SERVER['SCRIPT_FILENAME'],'anomalie/historique_anomalie.php') !== false ?' class="active"':''?>><a href="/intranet/anomalie/historique_anomalie.php">Anomalies</a></li>
<?	} ?>
</ul>