<?
if (isset($_GET['what']) && $_GET['what'] == 'delete_image' &&
	isset($_GET['filename']) && isset($_GET['filename'])) { ////// supprime une image defenitivement et son thumb
	
	rename('rejected/'.$_GET['filename'],'deleted/'.$_GET['filename']); // deplace le fichier dans le répertoire de delete
	$thumbs = preg_replace('/\.jpg$/','_thumb.jpg',$_GET['filename']);
	unlink("thumbs/$thumbs");	// supprime le thumb
}


elseif (isset($_GET['what']) && $_GET['what'] == 'count_documents') { ////// compte les image en cours de traitement
	echo json_encode(array(		'waiting'		=>	sizeof(glob('temp/*.jpg')),
								'rejected'	=>	sizeof(glob('rejected/*.jpg')),
						)
					);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'rotate_image' &&
		isset($_GET['filename']) && isset($_GET['filename'])) { ////// tourne une image a 180° et la réinjecte dans le scanner

	$image			= imagecreatefromjpeg("rejected/$_GET[filename]");	// charge l'image en mémoire
	$rotated_image	= imagerotate($image, 180, 0);													// fait tourner l'image a 180°
	imagejpeg($rotated_image, "temp/$_GET[filename]", 100);				// sauve l'image dans le répertoire de traitement
	imagedestroy($image);																			// libère la mémoire
	imagedestroy($rotated_image);

	unlink("rejected/$_GET[filename]");											// supprime l'image
	unlink("thumbs/".preg_replace('/\.jpg$/','_thumb.jpg',$_GET['filename']));	// supprime le thumb
}
?>