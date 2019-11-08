<?php
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

date_default_timezone_set("America/Los Angeles");

$config = parse_ini_file('conf/config.ini');
$time = time();
$reply = 0;
$thread = (int)$_POST["thread"];
$username = $POST["username"];
$ip = $_SERVER['REMOTE_ADDR'];

$comment0 = nl2br(str_replace("'", "&#39;", $_POST['comment']), false);
$comment1 = nl2br(str_replace("\"", "&#34;", $_POST['comment']), false);


$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$isVideo = 0;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
$reader = new Reader($config['IPGeo']);
$record = $reader->country($ip);

$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
$comment = $conn->real_escape_string($comment1);

if ($conn->connect_error) {
    die('Database connection failed: '  . $conn->connect_error);
}
$check = $conn->query("SELECT * FROM BANS WHERE ip = '$ip'");
$banned = $check->num_rows;

if (!isset($_POST['username'])){
    $username = "Anonymous";
}
if ($banned == 1){
    echo "Sorry, you are banned!";
    $uploadOk = 0;
    $conn->close();
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
        }
        $sql = "INSERT INTO POSTS (time, name, comment, reply, ip, country) VALUES ('$time', '$username', '$comment', '$thread', '$ip', '$country')";
        $conn->query($sql) or die(mysqli_error($conn));
        $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$thread&action=bump");
        header ("location: http://" . $config['url']."/thread.php?id=$thread");
        $conn->close();
    }
    if ($_FILES['image']['error'] != 4){
        if(isset($_POST["submit"])) {
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
            if($imageFileType == "mp4" || $imageFileType == "webm" || $imageFileType == "ogg"){
                $uploadOk = 1;
                $isVideo = 1;
            }

            if (file_exists($target_file)) {
                echo "Sorry, file already exists.";
                $uploadOk = 0;
                exit();
            }
            if ($_FILES["image"]["size"] > 25000000) {
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
                $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$thread&action=bump");
                header ("location: http://" . $config['url']."/thread.php?id=$thread");
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
