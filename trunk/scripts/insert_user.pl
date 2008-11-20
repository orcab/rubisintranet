use strict;
use Mysql;
use Data::Dumper;
use Digest::MD5 qw(md5_hex);
use Config::IniFiles;
$| = 1; # active le flush direct

my $cfg = new Config::IniFiles( -file => "config.ini" );
my $dbh = Mysql->connect($cfg->val('MYSQL','HOST'),$cfg->val('MYSQL','BASE_JOOMLA'),$cfg->val('MYSQL','USER_JOOMLA'),$cfg->val('MYSQL','PASS_JOOMLA')) or die "Peux pas se connecter a la base";
$dbh->selectdb($cfg->val('MYSQL','BASE_JOOMLA')) or die "Peux pas selectionner la base";

use constant {
	CODE_CLIENT	=> 0,
	NOM_CLIENT	=> 1,
	EMAIL_CLIENT => 2,
	PASSWORD_CLIENT => 3
};

print "Suppression de la base utilisateur 'Registered' ...";
$dbh->query("DELETE FROM jos_users WHERE usertype='Registered';");
$dbh->query("DELETE FROM jos_core_acl_aro WHERE name<>'Benjamin';");
$dbh->query("DELETE FROM jos_core_acl_groups_aro_map WHERE group_id<>25;");
print " ok\n";



open(CSV,"<user.csv") or die "Peux pas ouvrir le fichier user.csv ($!)";
while(<CSV>) {
	chomp;
	my @champs = split(/;/);
	@champs = map {unquote(trim($_))} @champs ;

	next if !$champs[NOM_CLIENT] || !$champs[CODE_CLIENT] ;

	$champs[NOM_CLIENT] = ucwords(lc($champs[NOM_CLIENT]));

	my $salt = genRandomPassword() ;
	#my $salt = 'nR9skSewaNqusVaNyeGZvYZVaQuG81tY';
	my $getCryptedPassword = md5_hex($champs[PASSWORD_CLIENT].$salt);


	print "'".$champs[CODE_CLIENT]."' '".$champs[NOM_CLIENT]."' '".$champs[PASSWORD_CLIENT]."'\n";
	# INSERTION DANS LA TABLE JOS_USERS
	my $sql =	"INSERT INTO jos_users (username,name,password,email,usertype,gid,registerDate,params) VALUES (".
				"'".quotify($champs[CODE_CLIENT])."',". # code client
				"'".quotify($champs[NOM_CLIENT])."',".	# nom client
				"'$getCryptedPassword:$salt',".			# password
				"'".quotify($champs[EMAIL_CLIENT])."',".# email
				"'Registered',".						# usertype
				"18,".									# gid
				"NOW(),".								# registerDate
				"'language=fr-FR\\ntimezone=1');";		# params
	my $sth = $dbh->query($sql) or die("Ne peux pas inserer le user dans JOS_USERS '$sql'");
	my $id_user = $sth->{'mysql_insertid'};
	

	# INSERT DANS LA TABLE JOS_CORE_ACL_ARO
	my $sql =	"INSERT INTO jos_core_acl_aro (section_value,value,name) VALUES ('users',$id_user,'".quotify($champs[CODE_CLIENT])."');";		# params
	$sth = $dbh->query($sql) or die("Ne peux pas inserer le user dans JOS_CORE_ACL_ARO '$sql'");
	my $id_aro = $sth->{'mysql_insertid'};

	# INSERT DANS LA TABLE JOS_CORE_ACL_GROUPS_ARO_MAP
	my $sql =	"INSERT INTO jos_core_acl_groups_aro_map (group_id,aro_id) VALUES (18,$id_aro);";		# params
	$dbh->query($sql) or die("Ne peux pas inserer le user dans JOS_CORE_ACL_GROUPS_ARO_MAP '$sql'");


}
close(CSV);




sub trim {
	my $t = shift;
	$t =~ s/^\s+|\s+$//g;
	return $t ;
}

sub unquote {
	my $t = shift;
	$t =~ s/^"+|"+$//g;
	return $t ;
}

sub quotify {
	my $t = shift;
	$t =~ s/'/''/g;
	return $t ;
}

sub ucwords {
	my $t = shift;
	$t =~ s/(^|[\s\-\.])(.)/$1\U$2/g;
	return $t;
}


sub genRandomPassword()
{
	my @salt = ('a'..'z','A'..'Z',0..9,'_');
	my $makepass = '';

	for (my $i = 0; $i < 32; $i ++) {
		$makepass .= $salt[int(rand(63))];
	}

	return $makepass;
}
