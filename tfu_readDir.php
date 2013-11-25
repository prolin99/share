<?php
/**
 * TWG Flash uploader 2.3
 * 
 * Copyright (c) 2004-2006 TinyWebGallery
 * written by Michael Dempfle
 * 
 * 
 *   This file does the following:
 *     - Returns the file list to the flash.
 *     - Create dirs
 *     - Rename dirs
 *     - Delete dirs
 *     - Change dirs
 *     - Check what is possible in the current directory (permissions ...)
 * 
 *   All files from a directory are read and added to the return parameter
 *   &files. The first parameter is the size of the listing! The format is up to you
 *   The current format is e.g. "3 files (234k)" The dirtext parameter is added to 
 *   the title bar of the flash
 * 
 * 
 *   Authentification is done by the session $_SESSION["TFU_LOGIN"]. you can set
 *   this in the tfu_config.php or implement your own way!
 */
define('_VALID_TWG', '42');
session_start();

include "tfu_helper.php";

if (isset($_SESSION["TFU_LOGIN"])){
	$dir = getCurrentDir();

	$status = ""; // this is the status flag I use to check if the actions where sucessfull!
	if (isset($_GET['createdir'])){ // creates a directory
		$newdir = $_GET['newdir'];
		$createdir = $dir . "/" . $newdir;
		if (file_exists($createdir)){
			$status = "&create_dir=exists";
		}else{
			//$result = mkdir ($dir . "/" . utf8_decode($newdir));
			$result = mkdir ($dir . "/" . $newdir );
			if ($result){
				$status = "&create_dir=true";
			}else{
				$status = "&create_dir=false";
			}
		}
	}

	if (isset($_GET['renamedir'])){ // Rename a directory
		$upperdir = substr($dir, 0, strrpos ($dir, "/"));
		$newdir = $_GET['newdir'];
		if ($dir == $_SESSION["TFU_ROOT_DIR"]){
			$status = "&rename_dir=main";
		}else{
			$createdir = $upperdir . "/" . $newdir;
			if (file_exists($createdir)){
				$status = "&rename_dir=exists";
			}else{
				//$result = rename ($dir, $upperdir . "/"  . utf8_decode($newdir));
				$result = rename ($dir, $upperdir . "/"  . $newdir);
				if ($result){
					//$dir = utf8_decode($createdir);
					$dir =  $createdir ;
					$_SESSION["TFU_DIR"] = $dir;
					$status = "&rename_dir=true";
				}else{
					$status = "&rename_dir=false";
				}
			}
		}
	}

	if (isset($_GET['deletedir'])){ // the check if the file can be deleted is done before - if it is not possible we never get here!
		$upperdir = substr($dir, 0, strrpos ($dir, "/"));
		$result = remove($dir);
		if ($result){
			$status = "&delete_dir=true";
			$dir = $upperdir;
			$_SESSION["TFU_DIR"] = $dir;
		}else{
			$status = "&delete_dir=false";
		}
	} 
	// needed for browsing - we check if a [..] is possible
	if (isset($_SESSION["TFU_ROOT_DIR"])){
		if ($dir == $_SESSION["TFU_ROOT_DIR"]){
			$show_root = false;
		}else{
			$show_root = true;
		}
	}else{
		$show_root = false;
	}

	if (isset($_GET['changedir'])){ // Change a directory
		$index = $_GET['index'];
		if ($index == 0 && $show_root){ // we go up!
			$dir = substr($_SESSION["TFU_DIR"], 0, strrpos ($_SESSION["TFU_DIR"], "/"));
		}else{ // we go deeper
			if($show_root){
				$index--;
			}
			$dirhandle = opendir($dir);
			$myDirs = array();
			while($filed = readdir($dirhandle)){ // only read dirs
				if($filed != "." && $filed != ".."){
					if(is_dir($dir . '/' . $filed)){
						array_push($myDirs, $filed);
					}
				}
			}
			usort ($myDirs, "mycmp");
			$dir = $dir . "/" . $myDirs[$index];
			}
		$_SESSION["TFU_DIR"] = $dir;
	} 
	// needed for browsing
	if (isset($_SESSION["TFU_ROOT_DIR"])){
		if ($dir == $_SESSION["TFU_ROOT_DIR"]){
			$show_root = false;
		}else{
			$show_root = true;
		}
	}else{
		$show_root = false;
	} 
	// All files are sored in the array $myFiles
	$sort_by_date = $_SESSION["TFU_SORT_FILES_BY_DATE"];

	$dirhandle = opendir($dir);
	$myFiles = array();
	$myDirs = array();
	$size = 0;
	while($file = readdir($dirhandle)){
		if($file != "." && $file != ".."){
			if(is_dir($dir . '/' . $file)){
				//array_push($myDirs, "[" . urlencode(utf8_encode($file)) . "]");
				array_push($myDirs, "[" . urlencode($file) . "]");
			}else{
				set_error_handler("on_error_no_output");
				@$size += filesize($dir . '/' . $file);
				if ($sort_by_date){
					$file = filemtime(($dir . '/' . $file)) . $file;
				}
				set_error_handler("on_error");
				//array_push($myFiles, urlencode(utf8_encode($file)));

				array_push($myFiles,urlencode( $file));
				
			}
		}
	}
	closedir ($dirhandle);
	if ($sort_by_date){
		usort ($myFiles, "cmp_date_dec");
		$i = 0;
		foreach ($myFiles as $fieldName){
			$myFiles[$i] = substr($myFiles[$i], 10);
			$i++;
		}
	}else{
		usort ($myFiles, "cmp_dec");
	}	
	reset($myFiles);

	if ($show_root){
		array_push($myDirs, "[..]");
	}
	usort ($myDirs, "cmp_dir_dec");
	// now we check if we can delete the current folder - root folder cannot be deleted!
	if (is_deletable($dir) && $show_root){
		$status .= "&dir_delete=true";
	}else{
		$status .= "&dir_delete=false";
	} 
	// new we check if we can create folders - we have to check safemode too!
	set_error_handler("on_error_no_output");
	if (@mkdir($dir . "/__TWG_TEST__")){ 
		// now we try to create a subdir to check if it is usefull to create a folder - it does not make sense to make a folder where nothing can be stored!
		$subdir = $dir . "/__TWG_TEST__" . "/__TWG_TEST_SUB_";
		if (@mkdir($subdir)){
			rmdir($subdir);
			$status .= "&dir_create=true";
		}else{
			$status .= "&dir_create=subdir";
		}
		rmdir($dir . "/__TWG_TEST__");
	}else{
		if (has_safemode_problem_global() && runsNotAsCgi()){
			$status .= "&dir_create=safemode";
		}else{
			$status .= "&dir_create=false";
		}
	}
	set_error_handler("on_error");

	$nrFiles = count($myFiles); 
	// now we check if can delete files - we only check the 1st file!
	if ($nrFiles > 0){
		if (is_deletable($dir . "/" . urldecode($myFiles[0]))){
			$status .= "&file_delete=true";
		}else{
			$status .= "&file_delete=false";
		}
	}
	// we check if we have an error in the upload!
	if (isset($_SESSION["upload_memory_limit"]) && isset($_GET['check_upload'])){
		$mem_errors = "&upload_errors=" . $_SESSION["upload_memory_limit"];
		unset($_SESSION["upload_memory_limit"]);
	}else{
		$mem_errors = "";
	}

	if (isset($_SESSION["TFU_LAST_UPLOADS"])){
		$upload_ok = "&upload_ok=" . count($_SESSION["TFU_LAST_UPLOADS"]);
	}else{
		$upload_ok = "&upload_ok=0"; // normal when no check is done!
	}

	$files = implode("|", $myFiles);
	if ($_SESSION["TFU_BROWSE_FOLDER"] ){ // we check if we are allowed to browse!
		$dirs = implode("|", $myDirs);
	}else{
		$dirs = "";
	}
	// we only show the path - relative path is not shown!
	//$dirsub = utf8_encode(str_replace("../", "", $dir));
	$dirsub = (str_replace("../", "", $dir));


	$baseurl = "&baseurl=" . getRootUrl() . $dir . "/"; // the baseurl 
	$size = $nrFiles . " files (" . ceil($size / 1024) . "k)"; // formating of the display can be done here!
	echo "&files=" . $size . "|" . $files . "&dirs=" . $dirs . $status . "&dirtext= - Upload Folder: " . $dirsub . $mem_errors . $upload_ok . $baseurl;
	
	store_temp_session() ;
}else{
	echo "Not logged in!";
}

?>
