#!/usr/bin/perl -w

use DBI;
use strict;
use warnings;
use CGI qw(:standard);
use Config::IniFiles;

my $cgi = CGI->new();
print $cgi->header("text/plain");
my $thread = $cgi->param('id');
my $action = $cgi->param('action');
my $board = $cgi->param('board');

my $cfg = Config::IniFiles->new(-file => "/var/www/html/conf/config-boards.ini", -php_compat => 1);
my $db = $cfg->val($board, "db-$board");
my $username = $cfg->val($board, "dbuser-$board");
my $password = $cfg->val($board, "dbpassword-$board");
my $host = $cfg->val($board, "dbhost-$board");
my $dbhString = "DBI:mysql:$db;host=$host";

my $dbh = DBI->connect($dbhString, $username, $password) || die "Could not connect to database: $DBI::errstr";


if ($action eq "new"){
	my $getNumbers = $dbh->prepare("SELECT *  FROM BUMP ORDER BY number DESC");
	$getNumbers->execute();
	
	my @stash;
	
	while (my $array_ref = $getNumbers->fetchrow_arrayref) {
		push @stash, [ @$array_ref ];
	}
	
	foreach my $array_ref (@stash){
		my $nextNumber = ${$array_ref}[1] + 1;
		my $id = ${$array_ref}[0];
		my $downshift = $dbh->prepare ("UPDATE BUMP SET number = '$nextNumber' WHERE id = '$id'"); 
		$downshift->execute();
	}
	my $newThread = $dbh->prepare("INSERT INTO BUMP (id,number) VALUES ('$thread', '1')");
	$newThread->execute();
	print "finished";
	$dbh->disconnect();
	
}

if ($action eq "bump"){
	my $checkIfNumberOne = $dbh->prepare("SELECT * FROM BUMP WHERE number='1' AND id='$thread'");
	$checkIfNumberOne->execute();
	
	my $result = eval {$checkIfNumberOne->fetchrow_arrayref->[1]};
	if ($result){
		print "already number one";
		$dbh->disconnect();
	}
	
	else{
		my $getThreadRank = $dbh->prepare("SELECT number FROM BUMP WHERE id = '$thread'");
		$getThreadRank->execute();
		
		my @stash1;
		while (my $array_ref = $getThreadRank->fetchrow_arrayref) {
			push @stash1, [ @$array_ref ];
		}
		
		my $setZero = $dbh->prepare("UPDATE BUMP SET number = '0' WHERE id = '$thread'");
		$setZero->execute();

		my $getNumbers = $dbh->prepare("SELECT * FROM BUMP WHERE id != '$thread' ORDER BY number DESC");
		$getNumbers->execute();
	
		my @stash2;
	
		while (my $array_ref = $getNumbers->fetchrow_arrayref) {
			push @stash2, [ @$array_ref ];
		}
	
		foreach my $array_ref (@stash2){
			my $nextNumber = ${$array_ref}[1] + 1;
			my $id = ${$array_ref}[0];
			my $downshift = $dbh->prepare("UPDATE BUMP SET number = '$nextNumber' WHERE id = '$id'"); 
			$downshift->execute();
		}
		my $toTop = $dbh->prepare("UPDATE BUMP SET number = '1' WHERE id = '$thread'");
		
		foreach my $array_ref(@stash1){
			my $prevRank = ${$array_ref}[0];
			my $getLessThan = $dbh->prepare("SELECT * FROM BUMP WHERE number > '$prevRank'");
			$getLessThan->execute();
			my $array_ref2 = $getLessThan->fetchall_arrayref({id => 1, number => 1});
			foreach my $row (@$array_ref2){
				my $prevNumber = $row->{number} - 1;
				my $threadID = $row->{id};
				my $upshift = $dbh->prepare("UPDATE BUMP SET number = '$prevNumber' WHERE id='$threadID'");
				$upshift->execute();
			}
			
		}
		
		$toTop->execute();
		print "finished";
		$dbh->disconnect();
	}
}
else{
	$dbh->disconnect();
}
