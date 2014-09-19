<?php
/* script chargée d'envoyer des mails.
 Suite à la mise a jour de sécurité d'OVH, perl n'arrive plus a envoyer de mail sans une indetification POP3 qui ne fonctionne pas.

 argument d'entrée :
 json
 {
 	server:'xxx.xxx.xxx',
 	'user':'xxx',
 	'password':'xxx',
 	'port':ddd,
 	'tls':bool,
 	'to':[ 
 			[email:'email', name:'name'},
 			[email:'email', name:'name'}...
 		}
 	'from':'email',
 	'html':'html',
 	'subject':'subject'
 }
*/

$stdin = trim(fgets(STDIN)); // lit une ligne depuis STDIN
$json = json_decode($stdin);

if ($json->{'debug'}) {
	echo "FROM PHP :\n".$stdin."\n";
	var_dump($json);
}

set_include_path(get_include_path() . PATH_SEPARATOR . 'c:/easyphp/www/intranet/');
require_once 'inc/xpm2/smtp.php';
$mail = new SMTP;
$mail->Delivery('relay');
$mail->Relay($json->{'server'},$json->{'user'},$json->{'password'},(int)$json->{'port'},'autodetect',$json->{'tls'} ? $json->{'tls'}:false) or exit(-4);

while (list($email,$name) = each($json->{'to'}))
	$mail->AddTo($email, $name) or exit(-3);

$mail->From($json->{'from'});
$mail->Html($json->{'html'});

if ($sent = $mail->Send($json->{'subject'}))
	exit(0);
else
	exit(-1);

?>