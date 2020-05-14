<?php
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

date_default_timezone_set("America/Los Angeles");

$config = parse_ini_file('conf/config.ini');
$boardCFG = parse_ini_file('conf/config-boards.ini');

$time = time();
$reply = 0;
$thread = (int)$_POST["thread"];
$username = $_POST["username"];
$ip = $_SERVER['REMOTE_ADDR'];
$board = $_POST['board'];

$comment0 = nl2br(str_replace("'", "&#39;", $_POST['comment']), false);
$comment1 = nl2br(str_replace("\"", "&#34;", $_POST['comment']), false);
$comment2 = strip_tags($comment1);

$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$isVideo = 0;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
$reader = new Reader($config['IPGeo']);
$record = $reader->country($ip);

$conn = new mysqli($boardCFG['dbhost-'.$board], $boardCFG['dbuser-'.$board], $boardCFG['dbpassword-'.$board], $boardCFG['db-'.$board]);
$connGlobal = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

if ($connGlobal->connect_error) {
    die('Database connection failed: '  . $connGlobal->connect_error);
}
if ($conn->connect_error) {
    die('Database connection failed: '  . $conn->connect_error);
}
$comment = $conn->real_escape_string($comment2);

$check = $connGlobal->query("SELECT * FROM BANS WHERE ip = '$ip'");
$banned = $check->num_rows;

if (!isset($_POST['username'])){
    $username = "Anonymous";
}

if ($banned == 1){
    setcookie("isBanned", "True", time() + ((86400 * 30)), "/");
    echo "Sorry, you are banned!";
    $uploadOk = 0;
    $conn->close();
    $connGlobal->close();
    exit();
}

if (isset($_COOKIE["isBanned"])){
    if ($banned == 0){
        $connGlobal->query("INSERT INTO BANS (ip, reason, isRangeban) VALUES ('$ip', 'AUTOMATIC: BAN EVASION', 0)") or die(mysqli_error($conn));
        $conn->close();
        $connGlobal->close();
        echo "Nice Try";
    }
    exit();
}

if ($banned == 1){
    echo "Sorry, you are banned!";
    $uploadOk = 0;
    $conn->close();
    $connGlobal->close();
    exit();
}
else {
    $countryCode = $record->country->isoCode;
    $country = strtolower($countryCode);

    if ($_FILES['image']['error'] == 4) {
        if ($reply == 0) {
            $replyTo = 0;
        }
        if(!$_POST['comment']) {
            echo "<p>Please supply a comment.</p>\n";
            $conn->close();
            $connGlobal->close();
        }
        $sql = "INSERT INTO POSTS (time, name, comment, reply, ip, country) VALUES ('$time', '$username', '$comment', '$thread', '$ip', '$country')";
        $conn->query($sql) or die(mysqli_error($conn));
        $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$thread&action=bump&board=$board");
        header ("location: http://" . $config['url']."/".$board."/thread.php?id=$thread");
        $conn->close();
        $connGlobal->close();
    }
    if ($_FILES['image']['error'] != 4){
        if(isset($_POST["submit"])) {
            if($imageFileType == "mp4" || $imageFileType == "webm" || $imageFileType == "ogg" || $imageFileType == "mov"){
                $isVideo = 1;
            }
            if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif" ) {
                $check = getimagesize($_FILES["image"]["tmp_name"]);
                if($check !== false) {
                    $uploadOk = 1;
                    $isVideo = 0;
                }
                else {
                    echo "File is not an image. ";
                    $uploadOk = 0;
                }
            }

            if (file_exists($target_file)) {
                echo "Sorry, file already exists.";
                $uploadOk = 0;
                exit();
            }
            if ($_FILES["image"]["size"] > 40000000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
                exit();
            }
        }
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        }
        else {
            $temp = explode(".", $_FILES["image"]["name"]);
            $newfilename = round(microtime(true)) . '.' . end($temp);

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir  ."/". $newfilename)) {
                $oldfilename = $_FILES["image"]["name"];
                $sql = ("INSERT INTO POSTS (time, name, filename, oldfilename, comment, reply, ip, country, isVideo) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$thread', '$ip', '$country', $isVideo)");
                $conn->query($sql) or die(mysqli_error($conn));
                $conn->close();
                $connGlobal->close();
                $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$thread&action=bump&board=$board");
                header ("location: http://" . $config['url']."/".$board."/thread.php?id=$thread");
            }
            else {
                echo "Sorry, there was an error uploading your file.";
                $conn->close();
                $connGlobal->close();
            }
        }
    }
    else {

    }
}
?>
