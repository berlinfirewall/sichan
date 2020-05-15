<?php
	$board = explode('/', $_SERVER['REQUEST_URI'])[1];
	$config = parse_ini_file('../conf/config.ini');
  	$boardCFG = parse_ini_file('../conf/config-boards.ini');
	$uploads = $config['uploadDir'];
	$headerDir = $config['headerDir'];
    if (!isset($_GET['page'])){
	  		header('Location: http://'.$config['url'].'/'.$board.'/index.php?page=1');
	}
	if ($_GET['page'] != null) {
			$sql = "SELECT * FROM BUMP WHERE `isPinned`='1' UNION SELECT * FROM (SELECT * FROM BUMP ORDER BY `number` ASC) AS posts LIMIT ".((15 * $_GET['page']) - 15).",15";
	}

  	$conn = new mysqli($boardCFG['dbhost-'.$board], $boardCFG['dbuser-'.$board], $boardCFG['dbpassword-'.$board], $boardCFG['db-'.$board]);
	$result = $conn->query($sql);

 		echo "<html>";
        echo "<head>";
		echo "<title>".$boardCFG['boardTagline-'.$board]."</title>";
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
			<script>
			function hide(post){
				x = document.getElementById(post);
				if (x.style.display === "none"){
					x.style.display = "block";
				}
				else {
					x.style.display = "none";
				}
			}
			</script>

			<link rel="stylesheet" type="text/css" href="/default.css" id="theme_css">
			</head>
			<body>
EOT;
		if ($config['isImage'] == 1){
			$images = $config['image'];
			echo "<a href='http://".$config['url']."'><img class='header' src='".$headerDir."/". $images[array_rand($images, 1)]."'></a>";
			echo "<h2 class='frontheader'>".$boardCFG['boardTagline-'.$board]."</h4>";
		}
		if ($config['isImage'] == 0){
			echo "<h1 class='header'>".$config['boardName']."</h1>";
		}
		echo <<<EOT
			<div>
				<div style="text-align:center;margin-right:auto;margin-left:auto;width:30%;">
					<table>
						<tr><h3><span class="postFieldTitle">Make Post</span></h3></tr>
						<form action="../post.php" method="POST" enctype="multipart/form-data">
							<tr><td><span class="postField">File:</span></td><td><input name="image" type="file"></td></tr>
    	                    <tr><td><span class="postField">Username (optional): </span></td><td><input name="username" type="text" value="Anonymous"></td></tr>
							<td><span class="postField">Comment:</span></td><td><textarea rows="5" cols="40" name="comment"></textarea></td></tr>
							<input type="hidden" name="userID" id="userID" value="">
EOT;
							echo '<input type="hidden" name="board" id="board" value="'.$board.'">';

		echo <<<EOT
							<script>
								if (typeof(Storage) !== "undefined"){
									var UserID = localStorage.getItem("userID");
									if (UserID !== null){
										document.getElementById("userID").value = UserID;
									}

								}
							</script>
							<tr><td><input type="submit" value="Submit" name="submit"></td></tr>
						</form>
					</table>
				</div>
EOT;
			if ($result->num_rows > 0){
				while($row1 = $result->fetch_assoc()){
						$BumpID = $row1['id'];
						$isPinned = $row1['isPinned'];
						$sql2 = "SELECT * FROM POSTS WHERE id = $BumpID";
						$getPosts = $conn->query($sql2);
						
						if ($getPosts->num_rows > 0){
							while($row = $getPosts->fetch_assoc()){
								$filepath = "../".$config['uploadDir']."/".$row['filename'];
								$pathinfo = pathinfo("$filepath");
								$ext = $pathinfo['extension'];
								if (strlen($row['oldfilename']) > 18 ){
									$shortened = substr($row['oldfilename'], 0, 15);
		 							$filename = $shortened."...".$ext;
								}
								else {
									$filename = $row['oldfilename'];
								}
							$imageinfo = getimagesize("../".$filepath);
							$width=$imageinfo[0];
							$height=$imageinfo[1];
							$size = filesize('../'.$config['uploadDir']."/".$row['filename']);
							$sizekb = round($size/1024);
							if ($row['country'] == null){
         						$country = "xx";
            				}
            
            				if ($row['country'] != null){
                				$country = $row['country'];
            				}
							$flagCode = "<img src=/img/flags/".$country.".gif></img> ";


							$id = $row['id'];

							$getReplies = "SELECT * FROM POSTS WHERE reply='$id'";
							$link = mysql_connect($config['host'], $config['user'], $config['password']);
							mysql_select_db($boardCFG['db-'.$board], $link);
							$result2 = mysql_query($getReplies, $link) or die(mysql_error());
							$num_replies = mysql_num_rows($result2);

							echo "<br>";
							echo "<div class='postfront' id='".$id."'>";
							echo "<table>";
							if($isPinned == "1"){
								$pinned ="<img src=/img/sticky.gif></img>";
							}
							if($isPinned != "1"){
								$pinned = "";
							}
							if(!is_null($row["filename"])){
								echo "<tr><td><span class='fronttext'><a href='$filepath'>".$filename."</a>(".$width."x".$height.") $sizekb KB $pinned</span></td></tr>";
								if($ext == "mp4" || $ext == "mov" || $ext == "ogg" || $ext == "m4v"){
									echo "<td style='vertical-align:top; font-size: 10pt;'><a href='$filepath'><video controls class='post'> <source src='$filepath'></video></a></td>";
								}
								else {
						        	echo "<tr><td><a href=/".$filepath ."><img style='max-height:250px;max-width:300px;' src='https://".$config['url'].'/'.$filepath."'></td>";
								}
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
							if($row["adminPost"] == "1"){
								echo "<span class='admin'>ADMIN - ".$username."</span>";
							}
							else{
								echo "<span class='frontname'>".$username."</span> ";
							}
							$comment2 = preg_replace("/(>)(>)[\d+]+/", '<span class="text"><a id="reply" style="color:#FF0000;margin:0;" href="#">$0</a></span>', $comment);
							$comment3 = preg_replace("/^\s*[\x3e].*$/m", '<span class="frontquote">$0</span>', $comment2);
							echo "<span class='fronttext'; font-size: 10pt;'> No.".$row['id'].date(' m/d/Y h:i:s', $row["time"])." $flagCode"."</span> <a href='#' onclick='hide(".$id.");return false'>hide</a>";
							echo "<br><span class='fronttext'>". $comment3 ."</span>";
							echo "<br><br>";
							echo "<span class='fronttext'>".$num_replies." Replies"."</span><br>";
							echo "<a href='http://".$config['url']."/".$board."/thread.php?id=".$row["id"]."'>View Thread</a>";
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
$nextPage = (int)$_GET['page'] + 1;
echo '<a href = "https://'.$config['url'].'/'.$board.'/index.php?page='. $nextPage .'" style="float:right;margin-bottom:20px;font-size:20pt;margin-right:20px;">Next</a>';
if ($_GET['page'] != 1){
	$prevPage = (int)$_GET['page'] - 1;
	echo '<a href = "https://'.$config['url'].'/'.$board.'/index.php?page='.$prevPage.'" style="float:left;margin-bottom:20px;font-size:20pt;margin-left:20px;padding:5px;"> Previous</a>';
}
echo <<<EOL
				</body>
			</html>
EOL;
?>
