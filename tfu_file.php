<?php
/**
 * TWG Flash uploader 2.3
 * 
 * Copyright (c) 2004-2006 TinyWebGallery
 * written by Michael Dempfle
 * 
 *   This file does the following:
 *     - Rename a file
 *     - Delete a file
 *     - Preview an image
 *     - Get the fielsize of an file
 *     - Download an image
 * 
 *   If an image was detected: jpj, png or gif the images are resized to fit in 
 *   the preview box (90 x 55). For all other files not image is returned!
 * 
 *   Authentification is done by the session $_SESSION["TFU_LOGIN"]. you can set
 *   this in the tfu_config.php or implement your own way!
 */
define('_VALID_TWG', '42');

session_start();

include "tfu_helper.php";
if (isset($_SESSION["TFU_LOGIN"])){ // 
	$dir = getCurrentDir(); 
	// if you have more complex filenames you can use the index
	if (isset($_GET['index'])){
		$file = getFileName($dir);
		if (file_exists($file)){
			$action = $_GET['action'];
			if ($action == "rename"){ // rename a file
				$newName = $dir . "/" . utf8_decode($_GET['newfilename']);
				if (!file_exists($newName)){
					if (is_writeable($file)){
						$result = @rename($file, $newName);
						if ($result){
							echo "&result=true";
						}else{
							echo "&result=false";
						}
					}else{
						echo "&result=perm";
					}
				}else{
					echo "&result=exists";
				}
			}else if ($action == "delete"){ // delete a file
				if (is_deletable($file)){
					set_error_handler("on_error_no_output");
					@chmod($file , 0777);
					set_error_handler("on_error");
					$result = @unlink($file);
					if ($result){
						echo "&result=true";
					}else{
						echo "&result=false";
					}
				}else{
					echo "&result=perm";
				}
			}else if ($action == "preview"){ // preview image 
				// we store the rul of the last preview image in the session - use it if you need it ;).
				$_SESSION["TFU_LAST_PREVIEW"] = fixUrl(getRootUrl() . $file); 
				// we generate thumbs for jpge,png and gif!
				if (preg_match("/.*\.(j|J)(p|P)(e|E){0,1}(g|G)$/", $file) ||
						preg_match("/.*\.(p|P)(n|N)(g|G)$/", $file) ||
						preg_match("/.*\.(g|G)(i|I)(f|F)$/", $file)){
					if (isset($_GET['big'])){
						send_thumb($file, 90, 400, 275); // big preview 4x bigger!
					}else{
						send_thumb($file, 90, 80, 55); // small preview
					}
				}else{
					return; // we return nothing if no image.
				}
			}else if ($action == "info"){ // get infos about a file
				echo "&size=" . filesize($file);
			}else if ($action == "download"){ // download a file
				$fp = fopen($file, "rb");
				while ($content = fread($fp, 8192 * 128)){ // 
					print $content;
				}
				fclose($fp);
			}
		}
	}else{
		echo "&result=index";
	}
}else{
	echo "Not logged in!";
}

?>