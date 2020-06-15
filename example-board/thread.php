<?php
$board = explode('/', $_SERVER['REQUEST_URI'])[1];
$boardCFG = parse_ini_file('../conf/config-boards.ini');
$config = parse_ini_file('../conf/config.ini');
$thread = ($_GET["id"]);
$uploads = $config['uploadDir'];
$boardImage = $config['boardImage'];
$headerDir = $config['headerDir'];

if(!isset ($_GET["id"])){
    echo("invalid url");
}

else 
{
    $conn = new mysqli($boardCFG['dbhost-'.$board], $boardCFG['dbuser-'.$board], $boardCFG['dbpassword-'.$board], $boardCFG['db-'.$board]);
    $sql = "SELECT * FROM POSTS WHERE id='$thread'";
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
EOT;
if ($result->num_rows > 0){
	while($row = $result->fetch_assoc()){
		echo "<meta property='og:title' content='".$boardCFG['boardinfo-'.$board]."'/>";
		echo "<meta property='og:image' content='https://".$config['url']."/".$config['uploadDir']."/".$row['filename']."'/>";
		echo "<meta property='og:description' content='".$row['comment']."'/>";
	


echo <<<EOT
			</head>				
			<body>
				<table style="width:100%;height:100%;"cellspacing="0" cellpadding=4">
					<tbody>
						<tr style="width:100%">
EOT;
		if ($config['isImage'] = 1){
			$images = $config['image'];
			echo "<a href='http://".$config['url']."/".$board."/'><img class='header' src='".$headerDir."/". $images[array_rand($images, 1)]."'></a>";
		}
		else{
			echo "<h1 class='header'>".$config['boardName']."</h1>";
		}
		echo <<<EOT
							<hr>
							<td style="white-space:nowrap;height:100%;width:95%;vertical-align:text-top;">
                                   <div style="text-align:center;margin-right:auto;margin-left:auto;width:30%;">
									<table>
									<tr>
										<h4><span class="postFieldTitle">Reply to Post</span></h4>
									</tr>
									<form action="../postReply.php" method="POST" enctype="multipart/form-data">
                                    <tr><td><span class="postField">File:</span></td><td><input name="image" type="file"></td></tr>
EOT;
		echo "<input type='hidden' name='thread' value='".$thread."'>";
		echo "<input type='hidden' name='board' value='".$board."'>";
		echo <<<EOT
												<tr><td><span class="postField">Name:</span></td><td><input name="username" value="Anonymous" type="text"></td></tr>
												<td><span class="postField">Reply:</span></td><td><textarea rows="5" cols="40" name="comment"></textarea></td></tr>
												
												<tr><td><input type="submit" value="Submit" name="submit"></td></tr>
											</form>
											</tr>
											</table>
										</div>							
								
EOT;
					$filepath = "http://".$config['url'].'/'.$config['uploadDir']."/".$row['filename'];
					$pathinfo = pathinfo("$filepath");
					$ext = $pathinfo['extension'];
					if (strlen($row['oldfilename']) > 18 ){
						$shortened = substr($row['oldfilename'], 0, 15);
						$filename = $shortened."...".$ext;
					
					}
					else {
						$filename = $row['oldfilename'];
					}

					$imageinfo = getimagesize('../'.$config['uploadDir']."/".$row['filename']);
					$width=$imageinfo[0];
					$height=$imageinfo[1];
					$size = filesize('../'.$config['uploadDir']."/".$row['filename']);
					$sizekb = round($size/1024);
					echo "<br>";
					echo "<div class='op' id='".$row["id"]."'>";
        	    	echo "<table>";
					echo "<a href=/'$filepath'>".$filename."</a> <span> (".$height."x".$width.") $sizekb KB</span>";
					echo "<span>".$imagesize."</span>";
					if($ext == "mp4" || $ext == "mov" || $ext == "ogg" || $ext == "m4v"){
						echo "<td style='vertical-align:top; font-size: 10pt;'><a href='$filepath'><video controls class='post'> <source src='$filepath'></video></a></td>";
					}
					else {
						echo "<td style='vertical-align:top; font-size: 10pt;'><a href='$filepath'><img src='$filepath' class='post'></a></td>";
					}
					echo "<td style='vertical-align:top; font-size: 10pt;' id='post' >No.". $row["id"] . " ";
					if(!$row["name"]){
							$opusername = "Anonymous";
					}
					if($row['name']){
							$opusername = $row['name'];			
					}
					$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
					if(preg_match($reg_exUrl, $row["comment"], $url)) {
						$res = preg_replace("/(\x3c(br)\x3e)/", "", $url[0]);
						$comment = preg_replace($reg_exUrl, '<a href="'.$res.'" rel="nofollow">'.$res.'</a>', $row["comment"]);
                    }
					else {
						$comment = $row['comment'];
					}
					if($row["adminPost"] == "1"){
						echo "<span class='admin'>ADMIN - ".$opusername ."</span> ";
					}
					else{
						echo "<span class='name'>".$opusername."</span> ";
					}
					echo date('m/d/Y h:m:s', $row["time"]);
					if ($row['country'] == null){
         				$country = "xx";
            		}
            
            		if ($row['country'] != null){
                		$country = $row['country'];
            		}
					print " <img src=/img/flags/".$country.".gif></img> ";
					$comment2 = preg_replace("/(>)(>)[\d+]+/", '<span class="text"><a id="reply" style="color:#FF0000;margin:0;" href="#">$0</a></span>', $comment);
					$comment3 = preg_replace("/^\s*[\x3e].*$/m", '<span class="quote">$0</span>', $comment2);
					echo "<br><span class='text'>". $comment3 ."</span></td>";
					echo "</tr>";
					echo "</table>";
					echo "</div>";
				}
				
				$sql2 = "SELECT * FROM POSTS WHERE reply='$thread'";
         	    $result2 = $conn->query($sql2);
            	if ($result2->num_rows > 0){
					while($row = $result2->fetch_assoc()){
						echo "<br>";
						echo "<div class='postreply' id='".$row['id']."'>";
						echo "<table>";	
						if(!is_null($row["filename"])){
							$filepath = "http://".$config['url'].'/'.$config['uploadDir']."/".$row['filename'];
							$filename = $row['oldfilename'];
							$pathinfo = pathinfo("$filepath");
							$ext = $pathinfo['extension'];
							if (strlen($row['oldfilename']) > 18 ){
								$shortened = substr($row['oldfilename'], 0, 15);
								$filename = $shortened."...".$ext;
							}
							$size = filesize('../'.$config['uploadDir']."/".$row['filename']);
							$sizekb = round($size/1024);
							if($ext == ".mp4" || $ext == ".mov" || $ext == ".ogg" || $ext == ".m4v"){
								echo "<tr><td><span class='imagedesc'><a href='$filepath'>".$filename."</a> $sizekb KB </span></td></tr>";
								echo "<tr><td><video controls class='post'><source src=".$filepath."></video></td>";	
							}
							else {
								$imageinfo = getimagesize('../'.$config['uploadDir']."/".$row['filename']);
								$width=$imageinfo[0];
								$height=$imageinfo[1];
								echo "<tr><td><span class='imagedesc'><a href='$filepath'>".$filename."</a> (".$width."x".$height.") $sizekb KB </span></td></tr>";
								echo "<tr><td><a href=".$filepath."><img class='post' src=".$filepath."></a></td>";	
							}
						}
						echo "<td class='info'>";
						if(!$row["name"]){
							$username = "Anonymous";
						}
						if($row['name']){
							$username = $row['name'];			
						}
					
					 	if ($row['country'] == null){
         					$country = "xx";
            			}
            
            			if ($row['country'] != null){
                			$country = $row['country'];
            			}

						$flagCode = "<img src=/img/flags/$country.gif></img>";
						
						$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
						if(preg_match($reg_exUrl, $row["comment"], $url)) {
							$res = preg_replace("/(\x3c(br)\x3e)/", "", $url[0]);
							$comment = preg_replace($reg_exUrl, '<a href="'.$res.'" rel="nofollow">'.$res.'</a>', $row["comment"]);
                    	}
						else {
							$comment = $row['comment'];
						}
						if($row["adminPost"] == "1"){
							echo "<span class='admin'>ADMIN - ".$username."</span>";
						}
						else{
							echo "<span class='name'>".$username."</span> ";
						}
						echo " </span><span class='text'; font-size: 10pt;'>No.".$row['id'].date(' m/d/Y h:i:s', $row["time"])." $flagCode"."</span>";
						$replyNumber = array();
						preg_match("/(?<=(\x3e)(\x3e))[\d+]+/m", $comment, $replyNumber);
						$comment2 = preg_replace("/(>)(>)[\d+]+/", '<span class="text"><a id="reply" style="color:#FF0000;margin:0;" href="#'.$replyNumber["0"].'">$0</a></span>', $comment);
						$comment3 = preg_replace("/^\s*[\x3e].*$/m", '<span class="quote">$0</span>', $comment2);
						echo "<br><span class='text'>". $comment3 ."</span>";
						echo "</td>";
						echo "</tr>";
						echo "</table>";
						echo "</div>";
					}
            	}
			}
            else{
                echo("thread ".$thread." doesn't exist");
            }
echo <<<EOT
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
				</body>	
			</html>
EOT;



}
$conn->close();
?>
