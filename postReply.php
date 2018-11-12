<?php
date_default_timezone_set("America/Los Angeles");

$config = parse_ini_file('conf/config.ini');
$time = time();
$reply = 0;
$topic = (int)$_POST["topic"];
$thread = (int)$_POST["thread"];
$username = $POST["username"];
$ip = $_SERVER['REMOTE_ADDR'];
$isAnon = $_POST['anonymous'];
$comment = $_POST['comment'];
$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
if ($conn->connect_error) {
    die('Database connection failed: '  . $conn->connect_error);
}
if(!$_POST['comment']) {
    echo "<p>Please supply a comment.</p>\n";
}
if (!isset($_POST['username'])){
    $username = "Anonymous";
}
else {
    if ($_FILES['image']['error'] == 4) {
        if ($reply == 0) {
            $replyTo = 0;
        }
        $sql = $conn->prepare("INSERT INTO POSTS (time, name, comment, reply, ip) VALUES ('$time', '$username', '$comment', '$thread', '$ip')");
        $sql->execute() or die(mysqli_error($conn));
        echo ("Reply Submitted.");
        $conn->close();
    }
    if ($_FILES['image']['error'] != 4){
        if(isset($_POST["submit"])) {
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check !== false) {
                echo "File is an image - " . $check["mime"] . ". ";
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
                $sql = $conn->prepare("INSERT INTO POSTS (time, name, filename, oldfilename, comment, reply, ip) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$thread', '$ip')");
                $sql->execute() or die(mysqli_error($conn));
                $conn->close();
                echo "The reply with the file ". basename( $_FILES["image"]["name"]). " has been uploaded.";
                sleep(1);
                echo "<script type='text/javascript'>window.location.href = '".$config['url']."/thread.php?id='$thread';</script>";
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