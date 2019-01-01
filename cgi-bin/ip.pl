#!/usr/bin/perl -w
use Config::IniFiles;
use DBI;
use JSON;
use CGI qw(:standard);

my $cgi = CGI->new();
print header('application/json');
my $ip = $cgi->param("ip");
my $cfg = Config::IniFiles->new(-file => "/var/www/html/conf/config.ini");
my $db = $cfg->val('database', 'ipdb');
my $username = $cfg->val('database', 'user');
my $password = $cfg->val('database', 'password');
my $host = $cfg->val('database', 'host');
my $dbhString = 'DBI:mysql:'.$db.';host='.$host;

my $dbh = DBI->connect($dbhString, $username, $password) || die "Could not connect to database: $DBI::errstr";
my $sth = $dbh->prepare("SELECT c.country,c.code FROM ip2nationCountries c, ip2nation i WHERE i.ip < INET_ATON('$ip') AND c.code = i.country ORDER BY i.ip DESC LIMIT 0,1");
$sth->execute();

my $array_ref = $sth->fetchall_arrayref({country => 1, code => 1});

foreach my $row (@$array_ref){
	my %rec_hash = ('country' => $row->{country}, 'code' => $row->{code});
	my $json = encode_json \%rec_hash;
	print "$json\n";
}
$dbh->disconnect();
