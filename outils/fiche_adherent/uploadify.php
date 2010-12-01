<?php

if (!empty($_FILES)) {
	$fournisseur = strtoupper($_POST['artisan']);
	$destination_dir = str_replace('//','/',dirname($_SERVER['SCRIPT_FILENAME']).'/files/'.$fournisseur) ;
	if (!file_exists($destination_dir)) 
		mkdir($destination_dir); // on tente de créer un sous répertoire pour les fichiers

	move_uploaded_file($_FILES['Filedata']['tmp_name'],$destination_dir.'/'.$_FILES['Filedata']['name']);
	echo "1";
}

?>