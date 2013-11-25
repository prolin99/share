<?php
/**
 * TWG Flash uploader 2.3 
 * 
 * Copyright (c) 2004-2006 TinyWebGallery
 * written by Michael Dempfle
 * 
 * 
 *   This file uploads the images to your webspace.
 * 
 *   This file gives a basic example how this can be done. The flash calles
 *   this file with 2 parameters:
 *   &dir = the upload directory
 *   &size = the size selected in the dropdown
 *   &PHPSESSID - The sessionid is always sent to this file because otherwise the 
 *   session is lost in Firefox and Opera!
 * 
 *   The uploaded files are resized if this is possible (jpg,png,gif).
 *   Right now there is no feedback to the flash if the upload was succesfull.
 * 
 *   The current build can write debug information to the file tfu.log. The number of
 *   files that are uploaded and the filenames! You can uncomment the debug lines if 
 *   you have a problem.
 * 
 *   Authentification is done by the session $_SESSION["TFU_LOGIN"]. You can set
 *   this in the tfu_config.php or implement your own way!
 */
define('_VALID_TWG', '42');
session_start();

include "tfu_helper.php";
firefox_fix() ;

if (isset($_SESSION["TFU_LOGIN"])){ // 
	$dir = getCurrentDir();

	if (isset($_GET['size'])){
		$size = $_GET['size'];
	}else{
		$size = 100000; // no resize
	}
	if (!isset($_SESSION["TFU_LAST_UPLOADS"]) || isset($_GET['firstStart'])){
		$_SESSION["TFU_LAST_UPLOADS"] = array();
		// we only send an email for the first email of an upload cycle
		if (isset ($_SESSION["TFU_NOT_EMAIL"]) && $_SESSION["TFU_NOT_EMAIL"] != "") {	    
		    $youremail = $_SESSION["TFU_NOT_EMAIL_FROM"]; 
		    $email = $_SESSION["TFU_NOT_EMAIL"];
				$submailheaders = "From: $youremail\n";
				$submailheaders .= "Reply-To: $youremail\n";
				$subject=$_SESSION["TFU_NOT_EMAIL_SUBJECT"];
				$mailtext = $_SESSION["TFU_NOT_EMAIL_TEXT"];			
				@mail ($email, html_entity_decode ($subject), html_entity_decode ($mailtext), $submailheaders);		    	  			  
	   	}	
	}

	foreach ($_FILES as $fieldName => $file){
		$store = true;
		if (preg_match("/.*\.(j|J)(p|P)(e|E){0,1}(g|G)$/", $file['name'])){
			$store = resize_file($file['tmp_name'], $size, 80);
		}
		if ($store){
		  //$filename = $dir . "/" . utf8_decode(str_replace("\\'", "'", $file['name']));  // fix for special characters like öäüÖÄÜñé...
		  //$filename = $dir . "/" . (str_replace("\\'", "'", $file['name']));
		       //debug ('--------------------') ;
		 	$fn = (str_replace("\\'", "'", $file['name'])); 
		 	//debug ($_SESSION["TFU_PATH"] .  $dir . "/" .$fn)  ;
 
		  	if  (file_exists( $_SESSION["TFU_PATH"]  .  $dir . "/" .$fn) )  {
				//debug ($dir . "/" .$fn .'---in' ) ;
			    	$fn_arr = preg_split ('/[.]/' , $fn );
			    	
			    	$fn_arr[0] = $fn_arr[0]  . '-' . date("mdHis"); 
 			  	$fn = $fn_arr[0]  .  '.'  .$fn_arr[ (count ($fn_arr)-1)] ;
			}    
 
			$filename = $dir . "/" .$fn ; 
			if (move_uploaded_file($file['tmp_name'], $filename)){ 
				chmod($filename,0777); // we change the file to 777 - if you like more restrictions - change the mode here! 
				array_push($_SESSION["TFU_LAST_UPLOADS"], fixUrl(getRootUrl() . $filename));	
			}
		}
	}
}else{
	echo "Not logged in!";
}
echo " "; // important - solves bug for Mac!
flush();

?>
