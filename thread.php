<?php
$config = parse_ini_file('conf/config.ini');
$thread = ($_GET["id"]);
$uploads = $config['uploadDir'];
$boardImage = $config['boardImage'];
$headerDir = $config['headerDir'];

if(!isset ($_GET["id"])){
    echo("invalid url");
}

else 
{
    $conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
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
			</head>				
			<body>
				<table style="width:100%;height:100%;"cellspacing="0" cellpadding=4">
					<tbody>
						<tr style="width:100%">
EOT;
		if ($config['isImage'] === "yes"){
			echo "<img class='header' src='".$headerDir."/".$boardImage."'>";
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
									<form action="postReply.php" method="POST" enctype="multipart/form-data">
                                    <tr><td><span class="postField">File:</span></td><td><input name="image" type="file"></td></tr>
EOT;
		echo "<input type='hidden' name='thread' value='".$thread."'>";
		echo <<<EOT
												<tr><td><span class="postField">Name:</span></td><td><input name="username" value="Anonymous" type="text"></td></tr>
												<td><span class="postField">Reply:</span></td><td><textarea name="comment"></textarea></td></tr>
												
												<tr><td><input type="submit" value="Submit" name="submit"></td></tr>
											</form>
											</tr>
											</table>
										</div>							
								
EOT;
       	 if ($result->num_rows > 0){
				while($row = $result->fetch_assoc()){
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
					echo "<br>";
					echo "<div class='op' id='".$row["id"]."'>";
        	    	echo "<table>";
					echo "<a href='$filepath'>".$filename."</a> <span> (".$height."x".$width.") $sizekb KB</span>";
					echo "<span>".$imagesize."</span>";
					echo "<td style='vertical-align:top; font-size: 10pt;'><a href='$filepath'><img src='$filepath' class='post'></a></td>";
					echo "<td style='vertical-align:top; font-size: 10pt;' id='post' >No.". $row["id"] . " ";
					if(!$row["name"]){
							$opusername = "Anonymous";
						}
						if($row['name']){
							$opusername = $row['name'];			
					}
                    echo "<span class='name'>".$opusername."</span> ";
					echo date('m/d/Y h:m:s', $row["time"]);
					echo "<br><span class='text'>". $row["comment"]."</span></td>";
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
							$filepath = $config['uploadDir']."/".$row['filename'];
							$filename = $row['oldfilename'];
							if (strlen($row['oldfilename']) > 18 ){
								$pathinfo = pathinfo("$filepath");
								$ext = $pathinfo['extension'];
								$shortened = substr($row['oldfilename'], 0, 15);
								$filename = $shortened."...".$ext;
							}
							$imageinfo = getimagesize($config['uploadDir']."/".$row['filename']);
							$width=$imageinfo[0];
							$height=$imageinfo[1];
							$size = filesize($config['uploadDir']."/".$row['filename']);
							$sizekb = round($size/1024);
							echo "<tr><td><span class='imagedesc'><a href='$filepath'>".$filename."</a> (".$width."x".$height.") $sizekb KB </span></td></tr>";
							echo "<tr><td><a href=".$config['uploadDir']."/".$row["filename"] ."><img class='post' src=".$config['uploadDir']."/".$row["filename"]."></td>";	
						}
						echo "<td class='info'>";
						if(!$row["name"]){
							$username = "Anonymous";
						}
						if($row['name']){
							$username = $row['name'];			
						}
						echo "<span class='name'>".$username." </span><span class='text'; font-size: 10pt;'> No.".$row['id'].date(' m/d/Y h:m:s', $row["time"])."</span>";

						if (preg_match('/[\x3E\x3E]/', $row["comment"])){
    						$splitString = preg_split('/([\x3e\x3e]+\d+)/', $row["comment"], -1, PREG_SPLIT_DELIM_CAPTURE);
  							$splitNumber = count($splitString);
							echo "<br>";
							for ($i = 0; $i <= $splitNumber; $i++){
        						if (preg_match('/[\x3E\x3E]/', $splitString[$i])){
									preg_match_all('!\d+!', $splitString, $postnum);
									echo "<br><span class='text'><a id='reply' style='color:#FF0000;margin:0;' href='#' onclick='document.getElementById('"."$postnum"."').scrollIntoView();>"."$splitString[$i]"."</a>";
            						print "<span>" . "" . "</b>\n";
        						}
        						else {
            						print "$splitString[$i]\n";
        						}
    						}
							echo "</span>";
						}
						else {
							echo "<br><span class='text'>". $row["comment"]."</span>";
						}
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