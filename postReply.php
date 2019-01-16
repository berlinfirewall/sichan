<?php
date_default_timezone_set("America/Los Angeles");

$config = parse_ini_file('conf/config.ini');
$time = time();
$reply = 0;
$thread = (int)$_POST["thread"];
$username = $POST["username"];
$ip = $_SERVER['REMOTE_ADDR'];
$isAnon = $_POST['anonymous'];
$comment = nl2br(str_replace("'", "&#39;", $_POST['comment']), false);
$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

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
    if ($_FILES['image']['error'] == 4) {
        if ($reply == 0) {
            $replyTo = 0;
        }
        if(!$_POST['comment']) {
            echo "<p>Please supply a comment.</p>\n";
            $conn->close();
        }
        $sql = "INSERT INTO POSTS (time, name, comment, reply, ip) VALUES ('$time', '$username', '$comment', '$thread', '$ip')";
        $conn->query($sql) or die(mysqli_error($conn));
        $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$thread&action=bump");
        header ("location: http://" . $config['url']."/thread.php?id=$thread");
        $conn->close();
    }
    if ($_FILES['image']['error'] != 4){
        if(isset($_POST["submit"])) {
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check !== false) {
                $uploadOk = 1;
            } else {
                echo "File is not an image. ";
                $uploadOk = 0;
            }
        }
        if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
        }
        if ($_FILES["image"]["size"] > 5000000) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
        }
        else {
            $temp = explode(".", $_FILES["image"]["name"]);
            $newfilename = round(microtime(true)) . '.' . end($temp);
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir  ."/". $newfilename)) {
                $oldfilename = $_FILES["image"]["name"];
                $sql = ("INSERT INTO POSTS (time, name, filename, oldfilename, comment, reply, ip) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$thread', '$ip')");
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
