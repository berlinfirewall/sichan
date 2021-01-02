<?php
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

$time = time();
date_default_timezone_set("America/Los Angeles");
$config = parse_ini_file('conf/config.ini');

$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;

$acceptableFiles = array('jpg', 'png', 'jpeg', 'gif', 'mov', 'ogg', 'mp4', 'webm');
$fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

$reply = 0;
$thread = (int)$_POST["thread"];
$username = $_POST["username"];
$ip = $_SERVER['REMOTE_ADDR'];
$board = $_POST['board'];
$reader = new Reader($config['IPGeo']);
$record = $reader->country($ip);

$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

if ($conn->connect_error) {
    die('Database connection failed: '  . $conn->connect_error);
}
$comment = nl2br(str_replace("'", "&#39;", $_POST['comment']), false);
$comment = nl2br(str_replace("\"", "&#34;", $comment), false);
$comment = strip_tags($comment);
$comment = $conn->real_escape_string($comment);

$check = $connGlobal->query("SELECT * FROM BANS WHERE ip = '$ip'");
$banned = $check->num_rows;

if ($banned == 1){
    setcookie("isBanned", "True", time() + ((86400 * 30)), "/");
    echo "Sorry, you are banned!";
    $uploadOk = 0;
    $conn->close();
    exit();
}

if (isset($_COOKIE["isBanned"])){
    if ($banned == 0){
        $conn->query("INSERT INTO BANS (ip, reason, isRangeban) VALUES ('$ip', 'AUTOMATIC: BAN EVASION', 0)") or die(mysqli_error($conn));
        $conn->close();
        echo "Nice Try";
    }
    exit();
}

if (!in_array($fileType, $acceptableFiles)){
    echo "File type .$fileType invalid.";
}

else {
    if ($_FILES['image']['error'] == 4) {
        if ($reply == 0) {
            $replyTo = 0;
        }
        if(!$_POST['comment']) {
            echo "<p>Please supply a comment.</p>\n";
            $conn->close();
        }
<<<<<<< HEAD
        $sql = "INSERT INTO `$board-POSTS` (time, name, comment, reply, ip, country, adminPost) VALUES ('$time', '$username', '$comment', '$thread', '$ip', '$country', '0')";
=======
        $sql = "INSERT INTO $board-POSTS (time, name, comment, reply, ip, country, adminPost) VALUES ('$time', '$username', '$comment', '$thread', '$ip', '$country', '0')";
>>>>>>> 972930159ef4e9cb05024f4879fd421e70448241
        $conn->query($sql) or die(mysqli_error($conn));
        $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$thread&action=bump&board=$board");
        header ("location: http://" . $config['url']."/".$board."/thread.php?id=$thread");
        $conn->close();
        $connGlobal->close();
    }

    if ($_FILES['image']['error'] != 4){
        if(isset($_POST["submit"])) {
            if($fileType == "mp4" || $fileType == "webm" || $fileType == "ogg" || $fileType == "mov"){
                $uploadOk = 1;
            }
            if($fileType == "jpg" || $fileType == "png" || $fileType == "jpeg" || $fileType == "gif" ) {
                $check = getimagesize($_FILES["image"]["tmp_name"]);
                if($check !== false) {
                    $uploadOk = 1;
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
            if ($_FILES["image"]["size"] > 30000000) {
                echo "Sorry, your file is too large.";
                $uploadOk = 0;
                exit();
            }
        }
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        }
        else {
            $countryCode = $record->country->isoCode;
            $country = strtolower($countryCode);
            $temp = explode(".", $_FILES["image"]["name"]);
            $newfilename = round(microtime(true)) . '.' . end($temp);

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir  ."/". $newfilename)) {
                $oldfilename = $_FILES["image"]["name"];
<<<<<<< HEAD
                $sql = ("INSERT INTO `$board-POSTS` (time, name, filename, oldfilename, comment, reply, ip, country, adminPost) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$thread', '$ip', '$country', '0')");
=======
                $sql = ("INSERT INTO $board-POSTS (time, name, filename, oldfilename, comment, reply, ip, country, adminPost) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$thread', '$ip', '$country', '0')");
>>>>>>> 972930159ef4e9cb05024f4879fd421e70448241
                $conn->query($sql) or die(mysqli_error($conn));
                $conn->close();
                $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$thread&action=bump&board=$board");
                header ("location: http://" . $config['url']."/".$board."/thread.php?id=$thread");
            }
            else {
                echo "Sorry, there was an error uploading your file.";
                $conn->close();
            }
        }
    }
    else {

    }
}
?>
