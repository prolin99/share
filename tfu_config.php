<?php
/**
 * TWG Flash uploader 2.3
 * 
 * Copyright (c) 2004-2006 TinyWebGallery
 * written by Michael Dempfle
 * 
 *   This file is the main configuration file of the flash.
 * 
 *   Please read the documentation found in this file!
 * 
 * There are 2 interesting settings you should look at first:
 *   - $login  - you can implement your own autentification by setting this flag! 
 *               If you use "auth" a login screen appears.
 *   - $folder - The folder where your uploads will be saved!
 * 
 *   Have fun using TWG Flash Uploader
 */
 
define('_VALID_TWG', '42');
session_start();


include "tfu_helper.php";

/*
    CONFIGURATION
*/
$timezone= "Asia/Taipei" ;
$login = "auth"; // The login flag - has to set by yourself below "true" is logged in, "auth" shows the login form, "reauth" should be set if the authentification has failed. "false" if the flash should be disabled.  
$folder = "save_data"; // this is the root upload folder. 

$maxfilesize = getMaximumUploadSize(); // The max files size limit of the server
$resize_show = is_gd_version_min_20(); // Show the resize box! Valid is "true" and "false" (Strings!) - the function is_gd_version_min_20 checks if the minimum requirements for resizing images are there! 
$resize_data = "100000,1280,1024,800"; // The data for the resize dropdown
$resize_label = "Original,1280,1024,800"; // The labels for the resize dropdown

$resize_default = "0";                 // The preselected entry in the dropdown (1st = 0)
$allowed_file_extensions = "all";  // Allowed file extensions! jpg,jpeg,gif,png are allowed by default. "all" allowes all types - this list is the supported files in the browse dropdown!
$forbidden_file_extensions = "php";    // Forbidden file extensions! - only usefull if you use "all" and you want to skip some exensions!

// Enhanced features - this are only defaults! if TFU detects that this is not possible this functions are disabled!
$hide_remote_view = "";                 // If you want to disable the remote view add "&hide_remote_view=true" as value!
$show_preview = is_gd_version_min_20(); // Show the small preview. Valid is "true" and "false" (Strings!) - the function is_gd_version_min_20 checks if the minimum requirements for resizing images are there! 
$show_big_preview = "true";             // Show the big preview - clicking on the preview image shows a bigger preview 
$show_delete = "true";                  // Shows the delete button
$enable_folder_browsing = "true";       // Without browsing creation and deletion is disabled by default!
$enable_folder_creation = "true";       // Show the menu item to create folders
$enable_folder_deletion = "true";       // Show the menu item to delete folders - this works recursive!
$enable_folder_rename = "true";         // Show the menu item to rename folders
$enable_file_rename = "true";          // Show the menu item to rename files - default is false because this is a securiy issue!
$keep_file_extension = "true";          // You can disalow to change the file extension! - only available in the unlimited version! 
$enable_file_download = "true";         // You can enable the download of a single file! - only available in the unlimited version!

// some optional things - can be removed as well - the defaults are entered below!
$login_text = "&login_text=Please login";  // Login Text
$relogin_text = "&relogin_text=Wrong Username/Password. Please retry"; // Retry login text
$upload_file = "&upload_file=tfu_upload.php?session_id=". session_id() ; // Upload php file - this is relative to he flash
$readdir_file = "&readdir_file=tfu_readDir.php"; // readDir php file - this dir is relative to the caller html file
$file_file = "&file_file=tfu_file.php";    // file php file - creates the preview images, downloads images, deletes and rename files - this dir is relative to the caller html file
$sort_files_by_date = false;               // sort files that last uploaded files are shown on top
$warning_setting = "&warning_setting=all"; // the warning is shown if remote files do already exist - can be set to all,once,none

// the text of the email is stored in the tfu_upload.php if you like to change it :) 
$upload_notification_email = ""; // you can get an email everytime a fileupload was initiated! The mail is sent at the first file of an upload queue! "" = no emails - php mail has to be configured properly!
$upload_notification_email_from = ""; // the sender of the notification email!
$upload_notification_email_subject ="A file was uploaded by the TWG Flash Uploader"; // Subject of the email - you should set a nicer one after the login or in tfu_upload.php
$upload_notification_email_text ="A file was uploaded by the TWG Flash Uploader";    // Text of the email - you should set a nicer one after the login or in tfu_upload.php

/**
 * Extra settings for the unlimied version
 */
$titel = "&titel=新營國小檔案分享空間";      // This is the title of the flash - can not be set in the freeware version!
$remote_label = "";                        // "&remote_label=Remote" This is a optional setting - you can change the display string above the file list if you want to use a different header - can only be changed in the unlimited version! - if you want to have a ? you have to urlencode the & !
$preview_label = "";                       // "&preview_label=Preview" This is a optional setting - you can change the display string of the header if you don't use the preview but maybe this function to determine the selection in the remote file list - can only be changed in the unlimited version!  - if you want to have a ? you have to urlencode the & !
$upload_finished_js_url = "";              // "&upload_finished_js_url=status.???" - You can specify an url that is called by the flash in the js function uploadFinished(param) This makes it possible e.g. to show a kind of result in a iframe below the flash. - only available in the unlimited version!
$preview_select_js_url = "";               // "&preview_select_js_url=preview.???" - You can specify an url that is called by the flash in the js function previewSelect(param) This makes it possible e.g. to show a kind of result in a iframe below the flash. this is only executed if show_preview=true - only available in the unlimited version!
$delete_js_url = "";                       // "&delete_js_url=preview.???" - You can specify an url that is called by the flash in the js function deleteFile(param) This makes it possible e.g. to show a kind of result in a iframe below the flash is someone deletes a file. - only available in the unlimited version!
$show_full_url_for_selected_file = "";     // "&show_full_url_for_selected_file=true" - if you use this parameter the link to the selected file is shown - can be used for direct links - only available in the unlimited version!  
$directory_file_limit = "&directory_file_limit=100000"; // you can specify a maximum number of files someone is allowed to have in a folder to limit the upload someone can make! - only available in the unlimited version!  
$queue_file_limit = "&queue_file_limit=100000"; // you can specify a maximum number of files someone can upload at once! - only available in the unlimited version!  
$queue_file_limit_size = "&queue_file_limit_size=100000"; // you can specify the limit of the upload queue in MB! - only available in the unlimited version!  

/*
     AUTHENTIFICATION

This part is interesting if you want to use the login!
*/
if (isset($_POST['twg_user'])){ // twg_user and twg_pass are always sent by the flash!
	$user = $_POST['twg_user'];
	$pass = $_POST['twg_pass'];
	$in= true ;
}	
 /*
if (isset($_GET['twg_user'])){ // twg_user and twg_pass are always sent by the flash!
	$user = $_GET['twg_user'];
	$pass = $_GET['twg_pass'];
		$in= true ;
}	
 */
/*
  TFU has a very simply user managment included - 
  add users/folders/paths at .htusers.php.
  The password is not encrypted - please add this to enhance security!
*/
 
if  ($in) {    
  if (($login == "auth" || $login == "reauth") && $user != "") {
    include (".htusers.php");

    foreach ($GLOBALS["users"] as $userarray){
    	if ($user == $userarray[0] && $pass == $userarray[1]){
				$login = "true";
				$folder = $userarray[2]; 
				$enable_folder_browsing ="true";    
				if ($userarray[3] != "") {
				  $show_delete = $userarray[3];  
				}
				 
				if ($userarray[4] != "") {
				  $enable_folder_browsing = $userarray[4];    
				  $enable_folder_creation = $userarray[4]; 
				  $enable_folder_deletion = $userarray[4];   
				  $enable_folder_rename = $userarray[4];
				  $enable_file_rename = $userarray[4];
				}
				 
				break;
			} else {
				$login = "reauth";
			}
	  }
	}
	
	if ($login == "true"){
		$_SESSION["TFU_LOGIN"] = "true";
	}
	
	// this setting are needed in the other php files too!
	$_SESSION["TFU_ROOT_DIR"] = $_SESSION["TFU_DIR"] = $folder;
	$enable_folder_browsing= "true" ;
	$_SESSION["TFU_BROWSE_FOLDER"] = $enable_folder_browsing;
	
	$_SESSION["TFU_CREATE_FOLDER"] = $enable_folder_creation;
	$_SESSION["TFU_DELETE_FOLDER"] = $enable_folder_deletion;
	$_SESSION["TFU_SORT_FILES_BY_DATE"] = $sort_files_by_date; 
	$_SESSION["TFU_NOT_EMAIL"] = $upload_notification_email;
	$_SESSION["TFU_NOT_EMAIL_FROM"] = $upload_notification_email_from;
	$_SESSION["TFU_NOT_EMAIL_SUBJECT"] = $upload_notification_email_subject;
	$_SESSION["TFU_NOT_EMAIL_TEXT"] = $upload_notification_email_text;
	$_SESSION["TFU_PATH"] = "/var/www/html/share/";

	// sending and checking the registration infos - check is done in the flash therefore
	// we have to send all the registration infos to the flash!
	if (file_exists("twg.lic.php")){
		include "twg.lic.php";
		$reg_infos = "&d=" . $d . "&s=" . $s . "&m=" . $m . "&l=" . $l;
	}else{
		$reg_infos = ""; // means freeware version!
	}
	// the sessionid is mandatory because upload in flash and Firefox would create a new session otherwise - sessionhandled login would fail then!
	echo "&session_id=" . session_id() . "&login=" . $login . "&maxfilesize=" . $maxfilesize . "&dir=" . $folder;
	echo "&resize_show=" . $resize_show . "&resize_data=" . $resize_data . "&resize_label=" . $resize_label . " &resize_default=" . $resize_default;
	echo "&allowed_file_extensions=" . $allowed_file_extensions . "&forbidden_file_extensions=" . $forbidden_file_extensions;
	echo "&show_delete=" . $show_delete . "&enable_folder_browsing=" . $enable_folder_browsing . "&enable_folder_creation=" . $enable_folder_creation . "&enable_folder_deletion=" . $enable_folder_deletion . "&enable_file_download=" . $enable_file_download;
	//echo "&show_delete=true&enable_folder_browsing=true&enable_folder_creation=" . $enable_folder_creation . "&enable_folder_deletion=" . $enable_folder_deletion . "&enable_file_download=" . $enable_file_download;
	echo "&show_preview=" . $show_preview . "&show_big_preview=" . $show_big_preview . "&enable_file_rename=" . $enable_file_rename . "&enable_folder_rename=" . $enable_folder_rename . "&keep_file_extension=" . $keep_file_extension;
	// optional settings!
	echo $login_text . $relogin_text . $upload_file . $readdir_file . $titel . $file_file . $warning_setting . $hide_remote_view . $directory_file_limit;
	echo $remote_label . $preview_label . $reg_infos . $show_full_url_for_selected_file . $upload_finished_js_url . $preview_select_js_url;
	echo $queue_file_limit . $queue_file_limit_size;
}else{
	echo "Direct calls are not allowed!";
}

?>