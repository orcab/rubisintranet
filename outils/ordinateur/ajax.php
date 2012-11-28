<?

include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");



if (isset($_POST['what']) && $_POST['what'] == 'detail_utilisateur' &&
	isset($_POST['id']) && trim($_POST['id'])) {

	$res = mysql_query("SELECT * FROM employe WHERE id='".mysql_escape_string($_POST['id'])."'") ;
	$row = mysql_fetch_array($res);
	echo "{prenom:'$row[prenom]', nom:'$row[nom]', email:'$row[email]', loginor:'$row[loginor]', code_vendeur:'$row[code_vendeur]', ip:'$row[ip]', tel:'$row[tel]', machine:'$row[machine]',printer:'$row[printer]',droit:'$row[droit]'}";
}


elseif (isset($_POST['what']) && $_POST['what'] == 'valider_detail_utilisateur' &&
		isset($_POST['id']) && $_POST['id']) {

	$sql = "UPDATE employe set ".	"prenom='".		mysql_escape_string($_POST['prenom'])."',".
									"nom='".		mysql_escape_string($_POST['nom'])."',".
									"email='".		mysql_escape_string($_POST['email'])."',".
									"loginor='".	mysql_escape_string($_POST['loginor'])."',".
									"code_vendeur='".mysql_escape_string($_POST['code_vendeur'])."',".
									"tel='".		mysql_escape_string($_POST['tel'])."',".
									"ip='".			mysql_escape_string($_POST['ip'])."',".
									"machine='".	mysql_escape_string($_POST['machine'])."',".
									"printer=".		mysql_escape_string($_POST['printer']).",".
									"droit='".		mysql_escape_string($_POST['droit'])."'".
			" where id='".mysql_escape_string($_POST['id'])."'";
	mysql_query($sql) ;

	echo "{}";
}


// test si l'ip répond au ping
elseif (isset($_GET['what']) && $_GET['what'] == 'ping' &&
		isset($_GET['ip']) && preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$_GET['ip'])) {

	include_once('../../inc/ping/ping.php'); # import ping(ip)
	error_reporting(E_ALL ^ E_WARNING);
	set_time_limit(60);

	$ping = ping($_GET['ip']);
	$vnc = 0;
	if ($ping && in_array($_GET['type'],array(0,4,8))) { // si PC allumé et peut supporté VNC
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (socket_connect($socket, $_GET['ip'], '5900') == TRUE) { // connexion réussi, VNC allumé
			socket_close($socket);
			$vnc = 1;
		} else {
			$vnc = -1;
		}
	}

	echo json_encode(array(	'ip'	=> $_GET['ip'],
							'vnc'	=> $vnc,
							'ping'	=> $ping,
							'type'	=> $_GET['type']
						  )
					);
}


// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}
?>