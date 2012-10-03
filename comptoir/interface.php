<?php
include('../inc/config.php');
session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();

if (isset($_GET['autre_commande']) && $_GET['autre_commande'] == 1)
	unset($_SESSION['panier']);

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Plan de vente</title>
		<script>
		function op() {
		 // This function is for folders that do not open pages themselves.
		 // See the online instructions for more information.
		}
		</script>

	 </head>
	 <frameset cols="30%,*" onResize="if (navigator.family == 'nn4') window.location.reload()"> 
		<frame src="arbre.php" name="treeframe" />
		<frame src="affiche_article.php" name="basefrm" />
	</frameset> 
</html>
