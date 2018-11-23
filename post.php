
<?php
date_default_timezone_set("America/Los Angeles");
$time = time();

$target_dir = "user_upload/";
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

$topic = $_POST["topic"];
$comment = $_POST["comment"];
$username = $_COOKIE['sfsruser'];
$ip = $_SERVER['REMOTE_ADDR'];

$conn = new mysqli('localhost', 'perluser', 'RlRegBTrKfq4tfsY', 'SFSR_login');

if ($conn->connect_error) {
    die('Database connection failed: '  . $conn->connect_error);
}


if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
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
    if (move_uploaded_file($_FILES["image"]["tmp_name"], "user_upload/" . $newfilename)) {
        $oldfilename = $_FILES["image"]["name"];
        $sql = $conn->prepare("INSERT INTO uploads (time, user, filename, oldfilename, topic, comment) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$topic', '$comment')");
        $sql->execute();
        $conn->close();
        echo "The file ". basename( $_FILES["image"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
        $conn->close();
    }
}
?>
