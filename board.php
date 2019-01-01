<?php
  $config = parse_ini_file('conf/config.ini');
  $uploads = $config['uploadDir'];
  $boardImage = $config['boardImage'];
  $headerDir = $config['headerDir'];

	if (!isset($_GET['page'])){
		header('Location: http://'.$config['url'].'/board.php?page=1');
	}
	if ($_GET['page'] != null) {
		switch($_GET['page']){
			case "1":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 10";
				break;
			case "2":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 10, 10";
				break;
			case "3":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 20, 10";
				break;
			case "4":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 30, 10";
				break;
			case "5":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 40, 10";
				break;
			case "6":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 50, 10";
				break;
			case "7":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 60, 10";
				break;
			case "8":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 70, 10";
				break;
			case "9":
				$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT 80, 10";
				break;

			default:
				echo "<p>Page Number invalid</p>";
		}

	}

  	$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
	$result = $conn->query($sql);

 		echo "<html>";
        echo "<head>";
		echo "<title>".$config['boardName']."</title>";
		echo <<<EOT
			<script>
			window.onload = function(){
				var theme = localStorage.getItem('theme');
				if (theme == "default"){
					document.getElementById('theme_css').href = '/default.css';
				};
				if (theme == "dark"){
    				document.getElementById('theme_css').href = '/dark.css';
				};
			}
			</script>

			<link rel="stylesheet" type="text/css" href="/default.css" id="theme_css">
			</head>
			<body>
EOT;
		if ($config['isImage'] = 1){
			echo "<img class='header' src='".$headerDir."/".$boardImage."'>";
		}
		if ($config['isImage'] = 0){
			echo "<h1 class='header'>".$config['boardName']."</h1>";
		}
		echo <<<EOT
			<div>
				<div style="text-align:center;margin-right:auto;margin-left:auto;width:30%;">
					<table>
						<tr><h3><span class="postFieldTitle">Make Post</span></h3></tr>
						<form action="post.php" method="POST" enctype="multipart/form-data">
							<tr><td><span class="postField">File:</span></td><td><input name="image" type="file"></td></tr>
    	                    <tr><td><span class="postField">Username (optional): </span></td><td><input name="username" type="text" value="Anonymous"></td></tr>
							<td><span class="postField">Comment:</span></td><td><textarea name="comment"></textarea></td></tr>
							<tr><td><input type="submit" value="Submit" name="submit"></td></tr>
						</form>
					</table>
				</div>
EOT;
			if ($result->num_rows > 0){
				while($row1 = $result->fetch_assoc()){
						$BumpID = $row1['id'];
						$sql2 = "SELECT * FROM POSTS WHERE id = $BumpID";
						$getPosts = $conn->query($sql2);
						if ($getPosts->num_rows > 0){
							while($row = $getPosts->fetch_assoc()){
								$filepath = $config['uploadDir']."/".$row['filename'];
								if (strlen($row['oldfilename']) > 18 ){
									$pathinfo = pathinfo("$filepath");
									$ext = $pathinfo['extension'];
									$shortened = substr($row['oldfilename'], 0, 15);
								$filename = $shortened."...".$ext;
							}
							else {
								$filename = $row['oldfilename'];
							}
							$imageinfo = getimagesize($config['uploadDir']."/".$row['filename']);
							$width=$imageinfo[0];
							$height=$imageinfo[1];
							$size = filesize($config['uploadDir']."/".$row['filename']);
							$sizekb = round($size/1024);

							$baseURL = "http://".$config['url']."/cgi-bin/ip.pl?ip=";
							$ip = $row["ip"];
							$requestURL = "$baseURL"."$ip";

							$request = file_get_contents($requestURL);

							if ($request !== false){
								$json = json_decode($request);
								$flag = $json->{'code'}.".gif";
								$flagCode = "<img src=/flags/$flag></img>";
							}

							$id = $row['id'];

							$getReplies = "SELECT * FROM POSTS WHERE reply='$id'";
							$link = mysql_connect($config['host'], $config['user'], $config['password']);
							mysql_select_db($config['database'], $link);
							$result2 = mysql_query($getReplies, $link) or die(mysql_error());
							$num_replies = mysql_num_rows($result2);

							echo "<br>";
							echo "<div class='postfront' id='".$id."'>";
							echo "<table>";
							if(!is_null($row["filename"])){
								echo "<tr><td><span class='fronttext'><a href='$filepath'>".$filename."</a>(".$width."x".$height.") $sizekb KB</span></td></tr>";
								echo "<tr><td><a href=".$config['uploadDir']."/".$row["filename"] ."><img style='max-height:250px;' src=".$config['uploadDir']."/".$row["filename"]."></td>";
							}
							echo "<td class='info'>";
							if(!$row["name"]){
								$username = "Anonymous";
							}
							if($row['name']){
								$username = $row['name'];	
							}
							echo "<span class='frontname'>".$username." </span><span class='fronttext'; font-size: 10pt;'> No.".$row['id'].date(' m/d/Y h:m:s', $row["time"])." $flagCode"."</span>";
							echo "<br><span class='fronttext'>". $row["comment"]."</span>";
							echo "<br><br>";
							echo "<span class='fronttext'>".$num_replies." Replies"."</span><br>";
							echo "<a href='http://".$config['url']."/thread.php?id=".$row["id"]."'>View Thread</a>";
							echo "</td>";
							echo "</tr>";
							echo "</table>";
							echo "</div>";
						}
				}
			}
		}
echo <<<EOL
						</tbody>
					</table>
					<hr>
					<br>
					<div align="center">
					<select id="themeSwitch">
  						<option value="default">Default</option>
  						<option value="dark">Dark</option>
					</select>
					<input type="submit" id="themeSub" value="Apply Theme"></input>
					<script>
						document.getElementById("themeSub").onclick = function(){
							var e = document.getElementById('themeSwitch');
							var themeSet = e.options[e.selectedIndex].value;
							localStorage.removeItem('theme');
							localStorage.setItem('theme', themeSet);
							window.location.reload();
						}
					</script>

					</div>
EOL;
if ($_GET['page'] != 9){
	$nextPage = (int)$_GET['page'] + 1;
	echo '<a href = "/board.php?page=' . $nextPage . '" style="float:right;">Next</a>';
}
if ($_GET['page'] != 1){
	$prevPage = (int)$_GET['page'] - 1;
	echo '<a href="/board.php?page='.$prevPage.'" style="float:left;"> Previous</a>';
}
echo <<<EOL
				</body>
			</html>
EOL;
?>
