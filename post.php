
<?php
$config = parse_ini_file('conf/config.ini');
date_default_timezone_set("America/Los Angeles");
$time = time();

$target_dir = $config['uploadDir'];
$target_file = $target_dir . basename($_FILES["image"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
$comment = nl2br(str_replace("'", "&#39;", $_POST['comment']), false);
$username = $_POST['username'];
$ip = $_SERVER['REMOTE_ADDR'];

$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

$comment = $conn->real_escape_string($_POST["comment"]);
$check = $conn->query("SELECT * FROM BANS WHERE ip = '$ip'");
$banned = $check->num_rows;

if (!isset($_POST['username'])){
    $username = "Anonymous";
}
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
        exit();
    }
}
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
    exit();
}
if ($banned == 1){
    echo "Sorry, you are banned!";
    $uploadOk = 0;
    $conn->close();
    exit();
}
if ($_FILES["image"]["size"] > 5000000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
    exit();
}
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
    echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    $uploadOk = 0;
    exit();
}
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
    exit();
}
else {
    $temp = explode(".", $_FILES["image"]["name"]);
    $newfilename = round(microtime(true)) . '.' . end($temp);
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir ."/". $newfilename)) { 
        $oldfilename = $_FILES["image"]["name"];
        
        $baseURL = "http://localhost/cgi-bin/ip.pl?ip=";
		$requestURL = "$baseURL"."$ip";
		$request = file_get_contents($requestURL);

		if ($request !== false){
			$json = json_decode($request);
			$country = strtolower($json->{'code'});
		}

        $sql = $conn->query("INSERT INTO POSTS (time, name, filename, oldfilename, comment, ip, country) VALUES ('$time', '$username', '$newfilename', '$oldfilename', '$comment', '$ip', '$country')") or die(mysqli_error($conn));
        echo "The file ". basename( $_FILES["image"]["name"]). " has been uploaded.";
        $sql2 = "SELECT id FROM POSTS WHERE time = '$time' AND name = '$username' AND ip = '$ip'";
        $getID = $conn->query($sql2) or die(mysqli_error($conn));
        while ($row = $getID->fetch_assoc()){
            $id = $row['id'];
            $bump = file_get_contents("http://".$config['url']."/cgi-bin/bump.pl?id=$id&action=new");
            print "<p>".$bump."<p>";
        }
        $conn->close();
        sleep(1);
        echo "<script type='text/javascript'>window.location.href = 'board.php';</script>";
    } else {
        echo "Sorry, there was an error uploading your file.";
        $conn->close();
    }
}
?>
