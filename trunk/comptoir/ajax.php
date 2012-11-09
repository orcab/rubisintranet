<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();
$info_user = $_SESSION['info_user'];

//////////////////////// AJOUT d'UNE LIGNE AU PANIER //////////////////////////////////////////:
if (isset($_GET['what'])			&& $_GET['what'] == 'ajout_panier' &&
	isset($_GET['code_article'])	&& $_GET['code_article']) {

	$code_articles = explode(',',$_GET['code_article']); // tous les code articles à ajouter
	foreach ($code_articles as $code_qte) {
		list($code_article,$qte) = explode(':',$code_qte);

		// on va chercher toute les infos de l'article
		$code_article_escape = mysql_escape_string(trim($code_article));
		$sql = <<<EOT
SELECT	designation,fournisseur,ref_fournisseur,prix_net,activite,conditionnement,unite
FROM	article
WHERE	code_article='$code_article_escape'
EOT;
		$row = mysql_fetch_array(mysql_query($sql)) ;

		$update = false;
		if (isset($_SESSION['panier'])) { // panier deja existant, soit on met a jour, soit on crée
			for($i=0 ; $i<sizeof($_SESSION['panier']) ; $i++) {
				if ($_SESSION['panier'][$i][CODE_ARTICLE] == $code_article) { // si code article déjà présent, on met a jour la quantité
					$_SESSION['panier'][$i][QTE] += $qte;
					$update = true;
				}
			}

			if (!$update) // article non présent, on le rajoute
				array_push($_SESSION['panier'],array(	$code_article,$qte,$row['designation'],$row['fournisseur'],$row['ref_fournisseur'],$row['prix_net'],$row['activite'],$row['conditionnement'],$row['unite']));

		} else { // panier vide --> creation du tout premier article
			$_SESSION['panier'] = array(array($code_article,$qte,$row['designation'],$row['fournisseur'],$row['ref_fournisseur'],$row['prix_net'],$row['activite'],$row['conditionnement'],$row['unite']));
		}

	} // fin for $code_articles
	echo html_panier();
}



//////////////////////// MODIFICATION D'UNE LIGNE AU PANIER //////////////////////////////////////////:
elseif (isset($_GET['what'])	&& $_GET['what'] == 'modif_panier' &&
		isset($_GET['no_ligne'])&&
		isset($_GET['qte'])		&& $_GET['qte']) {

	$_SESSION['panier'][$_GET['no_ligne']][QTE] = $_GET['qte']; // on met a jour la quantité
}



//////////////////////// SUPPRESION D'UNE LIGNE AU PANIER //////////////////////////////////////////:
elseif (isset($_GET['what'])	&& $_GET['what'] == 'delete_panier' &&
		isset($_GET['no_ligne'])) {
		
		// on parcours le tableau et on saute la ligne qu'il faut supprimer
		$tmp = array();
		for($i=0 ; $i<sizeof($_SESSION['panier']) ; $i++) {
			if ($i == $_GET['no_ligne']) // la ligne s supprimer
				continue;
			else
				array_push($tmp,$_SESSION['panier'][$i]);
		}
		// on copie le tableau sans la ligne dans la session
		$_SESSION['panier'] = $tmp;

		echo html_panier();
}

//////////////////////// SUPPRESION DE TOUT LE PANIER //////////////////////////////////////////:
elseif (isset($_GET['what'])	&& $_GET['what'] == 'delete_panier_all') {
		$_SESSION['panier'] = array();
		echo html_panier();
}


//////////////////////// SAUVEGARDE DU PANIER EN CDE FAVORITE //////////////////////////////////////////:
elseif (	isset($_GET['what'])		&& $_GET['what'] == 'save_panier_as_favori'
		&&	isset($_GET['code_user'])	&& $_GET['code_user']
		&&	isset($_GET['nom_panier'])	&& $_GET['nom_panier']) {
	if (isset($_SESSION['panier'])) { // panier existant --> on enregistre en panier favoris
		// insertion de la cde favorite dans la base
		mysql_query("INSERT IGNORE INTO mcs_cde_favoris (code_user,nom_favori) VALUES ('".mysql_escape_string($_GET['code_user'])."','".mysql_escape_string($_GET['nom_panier'])."')");

		// on regarde le résultat de l'insertion --> si aucune insertion alors le nom existe deja pour cette utilisateur --> renvoi erreur
		if (mysql_affected_rows($mysql) <= 0) {
			// renvoyer une erreur car l'insertion n'a pas eu lieu
			echo "Erreur : Un panier favori avec le nom '$_GET[nom_panier]' existe deja"; // code erreur
		} else {
			$id_panier_favoris = mysql_insert_id($mysql);
			for($i=0 ; $i<sizeof($_SESSION['panier']) ; $i++) { // pour chaque article du panier
				// on insert dans la base l'article en favoris panier
				mysql_query("INSERT IGNORE INTO mcs_cde_ligne_favoris (id_cde,code_article,qte) VALUES ('$id_panier_favoris','".mysql_escape_string($_SESSION['panier'][$i][CODE_ARTICLE])."','".mysql_escape_string($_SESSION['panier'][$i][QTE])."')");
			}
		}
	}
}


//////////////////////// SUPPRESION D'UNE COMMANDE FAVORITE //////////////////////////////////////////:
elseif (isset($_GET['what'])	&& $_GET['what'] == 'delete_cde_favori' &&
		isset($_GET['id'])) {
	mysql_query("DELETE FROM mcs_cde_favoris WHERE id='".mysql_escape_string($_GET['id'])."'") ;
}


//////////////////////// SUPPRESION D'UNE ADRESSE FAVORITE //////////////////////////////////////////:
elseif (isset($_GET['what'])	&& $_GET['what'] == 'delete_adr' &&
		isset($_GET['id'])) {
	mysql_query("DELETE FROM ".MYSQL_PREFIX."adr_livraison WHERE id='".mysql_escape_string($_GET['id'])."'") ;
}


//////////////////////// AFFICHE LE PANIER COURANT //////////////////////////////////////////:
elseif (isset($_GET['what'])	&& $_GET['what'] == 'get_panier') {
	echo html_panier();
}


//////////////////////// AFFICHE LES CDE FAVORITES //////////////////////////////////////////:
elseif (	isset($_GET['what'])		&& $_GET['what'] == 'get_favori'
		&&	isset($_GET['code_user'])	&& $_GET['code_user']) {
	echo html_cde_favori($_GET['code_user']);
}



//////////////////////// AFFICHE LE BEST OF //////////////////////////////////////////:
elseif (	isset($_GET['what'])		&& $_GET['what'] == 'get_bestof') {
	define('PERIOD_IN_MOUNTH',6);
	$date_before		= date('Ymd',mktime(0,0,0,date('m')-PERIOD_IN_MOUNTH,date('d'),date('Y')));
	$date_today			= date('Ymd');
		
	$sql = <<<EOT
select  CODAR, DS1DB, DS2DB,
--SUM(QTESA) as somme,
COUNT(ENTETE.NOBON) as nb
from AFAGESTCOM.ADETBOP1 DETAIL
 left join AFAGESTCOM.AENTBOP1 ENTETE
  on DETAIL.NOBON = ENTETE.NOBON and DETAIL.NOCLI=ENTETE.NOCLI
where
DETAIL.NOCLI='$info_user[username]'
and ETSBE=''
and CONCAT(DTSAS,CONCAT(DTSAA,DTSAM)) >= '$date_before'
and CONCAT(DTSAS,CONCAT(DTSAA,DTSAM)) <= '$date_today'
and TRAIT='F' and PROFI='1'
and DETAIL.DEPOT='AFA'
and ENTETE.TYVTE='EMP'
group by CODAR,DS1DB, DS2DB
order by nb DESC
FETCH FIRST 10 ROWS ONLY
EOT;

	//echo $sql;
	$html = '';
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,$sql) ;
	while($row = odbc_fetch_array($res)) {
		$row = array_map('trim',$row);
		$html .= '<tr><td class="code_article"><a href="affiche_article.php?search_text='.urlencode($row['CODAR']).'" target="basefrm">'.$row['CODAR'].'</a></td>' .
				'<td class="designation"><a href="affiche_article.php?search_text='.urlencode($row['CODAR']).'" target="basefrm">'.convertLatin1ToHtml($row['DS1DB']).'<br/>'.convertLatin1ToHtml($row['DS2DB']).'</a></td>'.
				'<td><input type="button" class="affiche_article button" title="'.urlencode($row['CODAR']).'"/></td></tr>';
	}
	echo $html;
}



// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}


function html_panier() {
	if (isset($_SESSION['panier'])) {
		if (sizeof($_SESSION['panier']) == 0) {
			return 'Votre panier est vide';
		}
		$html = '<table>';
		for($i=0 ; $i<sizeof($_SESSION['panier']) ; $i++) {
			$html .= '<tr><td class="code_article">'.$_SESSION['panier'][$i][CODE_ARTICLE].'</td>'.
					'<td  class="designation">'.convertLatin1ToHtml($_SESSION['panier'][$i][DESIGNATION]).'</td>'.
					'<td class="qte">x'.$_SESSION['panier'][$i][QTE].'</td>'.
					'<td><input type="button" class="supprime-article button" onclick="delete_panier('.$i.');"/></td></tr>';
		}
		$html .= "</table>" ;

		return $html;
	} else {
		return 'Votre panier est vide';
	}
}


function html_cde_favori($code_user) {
	$html = '<ul>';

		define('MYSQL_PREFIX','mcs_');
		$MYSQL_PREFIX = MYSQL_PREFIX;
		$sql = <<<EOT
SELECT	
		cde_favoris.id, cde_favoris.nom_favori,
		ligne_favoris.code_article, ligne_favoris.qte,
		article.designation, article.fournisseur, article.ref_fournisseur
FROM	
		${MYSQL_PREFIX}cde_favoris cde_favoris, ${MYSQL_PREFIX}cde_ligne_favoris ligne_favoris, article
WHERE	
		code_user='$code_user'
	AND cde_favoris.id=ligne_favoris.id_cde
	AND ligne_favoris.code_article=article.code_article
ORDER BY
		cde_favoris.id
EOT;
		$res = mysql_query($sql) or die("Erreur dans la selection des commande favorites. ".mysql_error().". $sql") ; // selectionne les panier favori
		$i=0;
		$old_nom_favori = '';
		$old_id = '';
		$article = array();
		$again = false ;

		while (($row = mysql_fetch_array($res)) || $again) {
			$again = false ;

			if ($i==0) {  // tout premier passage
				$old_nom_favori	= $row['nom_favori'] ;
				$old_id			= $row['id'] ;
			}
			
			if ($row['nom_favori'] != $old_nom_favori || $i == mysql_num_rows($res)+1) { // nouvelle commande favorite ou dernière ligne

				if ($i == mysql_num_rows($res)+1) { // cas du dernier article
					array_push($article,array(	'code_article'=>$row['code_article'],
												'qte'=>$row['qte'],
												'designation'=>$row['designation'],
												'fournisseur'=>$row['fournisseur'],
												'ref_fournisseur'=>$row['ref_fournisseur'])
								);
				}

				$html .= "<li class=\"cde_favori\" id=\"cde_$old_id\" onclick=\"$(this).children().toggle('fast');\">$old_nom_favori (".sizeof($article)." art.)<ol>";
						$js_panier = array();
						foreach ($article as $key => $val) { 
							array_push($js_panier,"$val[code_article]:$val[qte]");
							$html .= "<li>$val[code_article] <span class=\"ref_fournisseur\">$val[fournisseur] $val[ref_fournisseur]</span> (x$val[qte])<br/><span class=\"designation\">".convertLatin1ToHtml($val['designation'])."</span></li>";
						}
						$html .=	"<a onclick=\"remplace_panier('".join(',',$js_panier)."');\">- Remplacer le panier par cette cde.</a>".
									"<a onclick=\"ajoute_panier('".join(',',$js_panier)."');\">- Ajouter cette cde. au panier</a>".
									"<a onclick=\"delete_cde($old_id);\" style=\"color:grey;\">- Supprimer cette commande</a></ol></li>";
				$old_nom_favori = $row['nom_favori'];
				$old_id			= $row['id'] ;
				$article = array();
			}
			array_push($article,array('code_article'=>$row['code_article'],'qte'=>$row['qte'],'designation'=>$row['designation'],'fournisseur'=>$row['fournisseur'],'ref_fournisseur'=>$row['ref_fournisseur']  ));

			$i++;

			if ($i == mysql_num_rows($res)) { // dernier element
				$again = true;
			}
		}
	$html .= '</ul>';

	return $html;
}
?>