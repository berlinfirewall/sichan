<?php
  $config = parse_ini_file('conf/config.ini');
  $uploads = $config['uploadDir'];
  $boardImage = $config['boardImage'];
  $headerDir = $config['headerDir'];

	if (!isset($_GET['page'])){
		header('Location: http://'.$config['url'].'/board.php?page=1');
	}
	if ($_GET['page'] != null) {
			$sql = "SELECT id FROM BUMP ORDER BY number ASC LIMIT ".((15 * $_GET['page']) - 15).",15";
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
			echo "<a href = https://".$config['url']."><img class='header' src='".$headerDir."/".$boardImage."'></a>";
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

							$flag = $row["country"].".gif";
							$flagCode = "<img src=/flags/$flag></img>";

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
							$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
							if(preg_match($reg_exUrl, $row["comment"], $url)) {
								$comment = preg_replace($reg_exUrl, '<a href="'.$url[0].'" rel="nofollow">'.$url[0].'</a>', $row["comment"]);
                    		}
							else {
								$comment = $row['comment'];
							}
							$comment2 = preg_replace("/(>)(>)[\d+]+/", '<span class="text"><a id="reply" style="color:#FF0000;margin:0;" href="#">$0</a></span>', $comment);
							$comment3 = preg_replace("/^\s*[\x3e].*$/m", '<span class="frontquote">$0</span>', $comment2);
							echo "<span class='frontname'>".$username." </span><span class='fronttext'; font-size: 10pt;'> No.".$row['id'].date(' m/d/Y h:m:s', $row["time"])." $flagCode"."</span>";
							echo "<br><span class='fronttext'>". $comment3 ."</span>";
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
