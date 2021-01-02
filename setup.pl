#!/usr/bin/perl -w
require DBI;
require Config::IniFiles;

use DBI;
use Cwd qw(cwd);
use strict;
use warnings;

my $wd = cwd;
print "Welcome to SIchan setup. This script will help you through the setup process of the board. \n";
print "This setup wizard assumes all Perl and PHP dependencies are installed (DBI, Config::IniFiles, GeoIP2-php), a MySQL server is running and set up with a root login. Press Enter to Continue\n";
<STDIN>;

my $name;
do {
        print "What is the name of your website? \n";
	chomp($name = <STDIN>);
}
until($name ne "");

my $url;
do {
        print "What is the URL of this directory? \n";
      	chomp($url = <STDIN>);
}
until($name ne "");

print "Please type in the MySQL host, and hit enter. (Default: localhost)\n";
print "(localhost) ";
chomp(my $host = <STDIN>);
if ($host eq ""){
	$host = "localhost";
}

print "Please type in the MySQL port number, and hit enter. (Default: 3306)\n";
print "(3306) ";
chomp(my $port = <STDIN>);
if ($port eq ""){
	$port = "3306";
}

print "Please type in your MySQL root password, and hit enter.\n";
chomp(my $passwd = <STDIN>);

my $db;
do{
	print "Please type in what the database name should be\n";
	chomp ($db = <STDIN>);
}
until($db ne "");

print "What should the maximum number of characters in a post be? (Default: 8192) \n";
print "(8192) ";
chomp(my $maxWords = <STDIN>);
if ($maxWords eq ""){
        $maxWords = "8192";
}

print "Do you have banner Image(s)? Or do you want the imageboard title to be text. If yes, type the banner directory. If not, leave this blank and hit enter.\n";
chomp(my $bannerDir = <STDIN>);
my $isImage;
if ($bannerDir eq ""){
	$isImage = 0;
}
else{
	$isImage = 1;
}

print "What should the user upload directory be? Default is user_upload.\n";
print "(user_upload) ";
chomp(my $uploadDir = <STDIN>);
if ($uploadDir eq ""){
        $uploadDir = "user_upload";
}

print "What should the MySQL user for the imageboard be? Default boarduser.\n";
print "(boarduser) ";
chomp(my $boardUser = <STDIN>);

if ($boardUser eq ""){
	$boardUser = "boarduser";
}

print "Type in a password for the user. Otherwise, a random password will be generated \n";
chomp(my $userPasswd = <STDIN>);

if ($userPasswd eq ""){
	my @randomString;
	my @chars = ('A'..'Z', 'a'..'z', '0'..'9', '!', '?', '<', '>');
	for (my $i=0; $i<=15; $i++){
     		push(@randomString, ($chars[rand @chars]));
	}
$userPasswd = (join("",@randomString)).'!';
print "USER PASSWORD IS $userPasswd\n";
}

my $dbh = DBI->connect("DBI:mysql:host=$host;port=$port","root","$passwd", {RaiseError=>1}) || die "Could not connect to database: $DBI::errstr";
$dbh->do("CREATE DATABASE `$db`"); #create db
print "Created Database $db\n";
$dbh->do("CREATE USER `$boardUser`@`localhost` IDENTIFIED BY '$userPasswd'"); #create user
print "Created MySQL User $boardUser\n";
print "Creating config directory\n";
print `mkdir -v conf`;
print "Adding data to config file\n";
open(FH, '>>', 'conf/config.ini') or die $!;
print FH "[database]\n";
print FH "database=$db\n";
print FH "user=$boardUser\n";
print FH "password=\"$userPasswd\"\n";
print FH "host=$host\n";
print FH "IPGeo=ADD IT HERE\n\n";
print FH "[header]\n";
print FH "siteName=$name\n";
print FH "isImage=$isImage\n\n";
print FH "[directory]\n";
print FH "uploadDir=$uploadDir\n";
print FH "headerDir=$bannerDir\n\n";
print FH "[url]\n";
print FH "url=$url\n\n";
close(FH);

print "Creating .htaccess\n";
open(FH, '>>', 'conf/.htaccess') or die $!;
print FH "Require local";
close(FH);

print "How many boards would you like to create? \n";
chomp(my $numBoards = <STDIN>);

for(my $i=1; $i<=$numBoards; $i++){ #create board tables
	print "Enter abbreviation for board $i (ie. enter g for /g/, int for /int/)\n";
	chomp(my $boardName = <STDIN>);
	$boardName = uc($boardName);
	print "Enter board title for board $boardName (ie. for /int/ - International)\n";
	chomp(my $title = <STDIN>);
	open(FH, '>>', 'conf/config.ini') or die $!;
	print FH "[board-$boardName]\n";
	print FH "boardTitle-$boardName=$title\n";
	close(FH);
        $dbh->do("CREATE TABLE `$db`.`$boardName-POSTS` (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, comment varchar($maxWords), name varchar(32), time int, filename varchar(32), oldFilename varchar(255), reply int, ip varchar(15), country varchar(2), adminPost int)");
	$dbh->do("CREATE TABLE `$db`.`$boardName-BUMP` (id int PRIMARY KEY, number int, isPinned int)");
	print "Created board $boardName\n";
	$boardName = lc($boardName);
	print `mkdir -v $boardName && cp -v board-files/* $boardName/`;

}

print "Creating bans table\n";
$dbh->do("CREATE TABLE `$db`.`BANS` (ip varchar(15), reason varchar(1024))");
print "Granting Permissions\n";
$dbh->do("GRANT SELECT, INSERT ON `$db`.* TO `$boardUser`@`localhost`");

print "Installing Composer\n";
print `curl -sS https://getcomposer.org/installer | php`;
print "Installing GeoIP2 module";
print `php composer.phar require geoip2/geoip2:~2.0`;

print `mkdir -v $uploadDir`;
print `chmod 766 -v $uploadDir`;

print "Setup Complete\n";
print "The only step left is to now download the GeoLite2-Country mmdb file from https://dev.maxmind.com/geoip/geoip2/geolite2/, and set the IPGeo field in the config.ini file to the directory of that .mmdb file. After that, everything should work!\n";
print "You also can (and should) now delete this file.";
print "Good Luck with your new Imageboard!\n";
$dbh->disconnect;
