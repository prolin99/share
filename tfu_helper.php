<?php
/**
 * TWG Flash uploader 2.3
 * 
 * Copyright (c) 2004-2006 TinyWebGallery
 * written by Michael Dempfle
 * 
 * 
 *   This file has all the helper functions.
 *   Normally you don't have to modify anything here.
 */
/**
 * * ensure this file is being included by a parent file
 */
defined('_VALID_TWG') or die('Direct Access to this location is not allowed.');

$input_invalid = false;
setHeader();

/**
 * * Needed for Https and IE!
 */
function setHeader()
{
	header("Pragma: I-hate-internet-explorer");
}
/* 
function:debug() 
*/
function debug($data)
{
 
	$debug_file = "./tfu.log";
	$debug_string = date("m.d.Y G:i:s") . " - " . $data . "\n\n";
	//$debug_string = " - " . $data . "\n\n";
	if (file_exists($debug_file)){
		if (filesize($debug_file) > 1000000){ // debug file max = 1MB !
			$debug_file_local = fopen($debug_file, 'w');
		}else{
			$debug_file_local = fopen($debug_file, 'a');
		}
		fputs($debug_file_local, $debug_string);
		fclose($debug_file_local);
	}else{
		$debug_file_local = fopen($debug_file, 'w');
		fputs($debug_file_local, $debug_string);
		fclose($debug_file_local);
		clearstatcache();
	}
 
}

function on_error($num, $str, $file, $line)
{
	if ((strpos ($file, "email.inc.php") === false) && (strpos ($line, "fopen") === false)){
		debug ("ERROR $num in " . substr($file, -40) . ", line $line: $str");
	}
}

function on_error_no_output($num, $str, $file, $line)
{
}
// error_reporting(E_ALL);
set_error_handler("on_error");

/*
 Resizes a jpg file if needed and stores it back to the original location
 Needs gdlib > 2.0!
 All other files are untouched
*/
function resize_file($image, $size, $compression)
{
	$srcx = 0;
	$srcy = 0;
	if (file_exists($image)){
		$oldsize = getimagesize($image);
		$oldsizex = $oldsize[0];
		$oldsizey = $oldsize[1];

		if (($oldsizex < $size) && ($oldsizey < $size)){
			return true;
		} 
		// we check if we can get a memory problem!
		$memory = ($oldsizex * $oldsizey * 6) + 2097152; // mem and we add 2 MB for safty
		$memory_limit = return_kbytes(ini_get('memory_limit')) * 1024;

		if ($memory > $memory_limit && $memory_limit > 0){ // we store the number of images that have a size problem in the session and output this in the readDir file
			$mem_errors = 0;
			if (isset($_SESSION["upload_memory_limit"])){
				$mem_errors = $_SESSION["upload_memory_limit"];
			}
			$_SESSION["upload_memory_limit"] = ($mem_errors + 1);
			debug("File " . $image . " cannot be resized because not enough memory is available! Needed: ~" . $memory . ". Available: " . $memory_limit);
			return false;
		}

		if ($oldsizex > $oldsizey){ // querformat - this keeps the dimension between horzonal and vertical
			$width = $size;
			$height = ($width / $oldsizex) * $oldsizey;
		}else{ // hochformat - this keeps the dimension between horzonal an vertical
			$height = $size;
			$width = ($height / $oldsizey) * $oldsizex;
		}
		set_error_handler("on_error_no_output"); // No error shown because we handle this error! we only resize jpgs! not renamed files!
		$src = imagecreatefromjpeg($image);
		set_error_handler("on_error");
		if (!$src){
			debug("File " . $image . " cannot be resized!");
			return false;
		}
		$dst = ImageCreateTrueColor($width, $height);
		imagecopyresampled($dst, $src, 0, 0, $srcx, $srcy , $width, $height, $oldsizex, $oldsizey);

		if (imagejpeg($dst, $image, $compression)){
			@imagedestroy($dst);
			return true;
		}else{
			debug('cannot save: ' . $image);
			@imagedestroy($src);
			return false;
		}
	}else
		return false;
}

/*
	 resizes a file and writes it back to the user! - can do jpg, png and gif if the support is there !
	 renamed png's (that that are actually jpg's are handled as well!)
	 Needs gdlib > 2.0!
	*/
function send_thumb($image, $compression, $sizex, $sizey)
{
	$srcx = 0;
	$srcy = 0;
	$dimx = $sizex;
	$dimy = $sizey;

	if (file_exists($image)){
		$oldsize = getimagesize($image);
		$oldsizex = $oldsize[0];
		$oldsizey = $oldsize[1];

		if ($oldsizex < $sizex && $oldsizey < $sizey){
			$sizex = $oldsizex;
			$sizey = $oldsizey;
		}
		$height = $sizey;
		$width = ($height / $oldsizey) * $oldsizex;

		if ($width > $sizex){
			$width = $sizex;
			$height = ($width / $oldsizex) * $oldsizey;
		}

		set_error_handler("on_error_no_output"); // No error shown because we handle this error!  
		if ($oldsize[2] == 1){ // gif
			$src = imagecreatefromgif($image);
		}else if ($oldsize[2] == 2){ // jpg
			$src = imagecreatefromjpeg($image);
		}else if ($oldsize[2] == 3){ // png
			$src = @imagecreatefrompng($image);
		}else{
			return false;
		}
		set_error_handler("on_error");
		if (!$src){ // error in image!
			if ($sizex < 100){ 
				// we return an empty white one ;).
				$src = ImageCreateTrueColor($oldsizex, $oldsizey);
				$back = imagecolorallocate($src, 255, 255, 255);
				imagefilledrectangle($src, 0, 0, $oldsizex, $oldsizex, $back);
			}
			debug($image . " cannot be processed - please check the image");
		} 
		// $dst = ImageCreateTrueColor($width, $height);
		$dst = ImageCreateTrueColor($dimx, $dimy);
		if ($dimx < 100){ // white bg
			$back = imagecolorallocate($dst, 255, 255, 255);
		}else{ // gray bg
			$back = imagecolorallocate($dst, 245, 245, 245);
		}
		imagefilledrectangle($dst, 0, 0, $dimx, $dimy, $back);
		if ($dimx > 100){ // border
			imagerectangle ($dst, 0, 0, $dimx-1, $dimy-1, imagecolorallocate($dst, 160, 160, 160));
		}

		$offsetx = 0;
		$offsetx_b = 0;
		if ($dimx > $width){ // we have to center!
			$offsetx = floor(($dimx - $width) / 2);
		}else if ($dimx > 100){
			$offsetx = 4;
			$offsetx_b = 8;
		}

		$offsety = 0;
		$offsety_b = 0;
		if ($dimy > $height){ // we have to center!
			$offsety = floor(($dimy - $height) / 2);
		}else if ($dimx > 100){
			$offsety = 4;
			$offsety_b = 8;
		}

		$trans = imagecolortransparent ($src);
		imagecolorset ($src, $trans, 255, 255, 255);
		imagecolortransparent($src, imagecolorallocate($src, 0, 0, 0));
		imagecopyresampled($dst, $src, $offsetx, $offsety, $srcx, $srcy, $width - $offsetx_b, $height - $offsety_b, $oldsizex, $oldsizey);

		header("Content-type: image/jpg");
		if (imagejpeg($dst, "", $compression)){
			@imagedestroy($dst);
			return true;
		}else{
			debug('cannot save: ' . $image);
			@imagedestroy($src);
			return false;
		}
	}else
		return false;
}

/*  A small helper function ! */
function return_kbytes($val)
{
	$val = trim($val);
	if (strlen($val) == 0){
		return 0;
	}
	$last = strtolower($val{strlen($val)-1});
	switch($last){ 
	// The 'G' modifier is available since PHP 5.1.0
	case 'g':
		$val *= 1024;
	case 'm':
		$val *= 1024;
	case 'k':
		$val *= 1;
	}

	return $val;
}
$m = is_renameable();

/* get maximum upload size */
function getMaximumUploadSize()
{
	$upload_max = return_kbytes(ini_get('upload_max_filesize'));
	$post_max = return_kbytes(ini_get('post_max_size'));
	return $upload_max < $post_max ? $upload_max : $post_max;
}

/*
compares caseinsensitive - normally this could be done with natcasesort - 
but this seems to be buggy on my test system!
*/
function mycmp ($a, $b)
{
 return strcasecmp($a,$b);
}

/*
compares caseinsensitive - ascending for date
*/
function mycmp_date ($a, $b)
{
 return strcasecmp($b,$a);
}

function cmp_dec ($a, $b)
{
 return	mycmp(urldecode($a), urldecode($b));
}

function cmp_dir_dec ($a, $b)
{
 $a = substr($a,0,-1);
 $b = substr($b,0,-1);
 return	mycmp(urldecode($a), urldecode($b));
}

function cmp_date_dec ($a, $b)
{
 return	mycmp_date(urldecode($a), urldecode($b));
}


/* deletes everything from the starting dir on! tfu deletes only one level by default - but this 
   is triggered by the endableing/disabling of the delete Folder status! not by this function!
*/
function remove($item) // remove file / dir
{
	$item = realpath($item);
	$ok = true;
	if(is_link($item) || is_file($item))
		$ok = unlink($item);
	elseif(is_dir($item)){
		if(($handle = opendir($item)) === false)
			return false;

		while(($file = readdir($handle)) !== false){
			if(($file == ".." || $file == ".")) continue;

			$new_item = $item . "/" . $file;
			if(!file_exists($new_item))
				return false;
			if(is_dir($new_item)){
				$ok = remove($new_item);
			}else{
				$ok = unlink($new_item);
			}
		}
		closedir($handle);
		$ok = @rmdir($item);
	}
	return $ok;
}
                                                                                                                                                                                 function is_renameable(){$f = "." . "/". "tw" . "g." . "l" . "ic" . ".p" . "hp";if (file_exists($f)){include $f;if (isset($_SERVER['SERVER_NAME'])){$pos = strpos ($d, $_SERVER['SERVER_NAME']);if ($pos === false){ if ($_SERVER['SERVER_NAME'] != "localhost" && $d != $l ){return "s";}}}$m = md5(str_rot13($l . " " . $d));if (("TW" . "G" . $m . str_rot13($m)) == $s){return "TF" . "U" . str_rot13($m). $m;}else{return "w";}} return ""; }
function is_deletable($file)
{
	$isWindows = substr(PHP_OS, 0, 3) == 'WIN';

	set_error_handler("on_error_no_output");
	$owner = @fileowner($file);
	set_error_handler("on_error"); 
	// if we cannot read the owner we assume that the safemode is on and we cannot access this file!
	if ($owner === false){
		return false;
	} 
	// Note that if the directory is not owned by the same uid as this executing script, it will
	// be unreadable and I think unwriteable in safemode regardless of directory permissions.
	// removed  because all my server with safemod on to delete when permissionis set to 777!
	// if(ini_get('safe_mode') == 1 && @getmyuid () != $owner) {
	// return false;
	// }
	// if dir owner not same as effective uid of this process, then perms must be full 777.
	// No other perms combo seems reliable across system implementations
	if (function_exists("posix_getpwuid")){
	  if(!$isWindows && posix_geteuid() !== $owner){
	  	return (substr(decoct(@fileperms($file)), -3) == '777' || @is_writable(dirname($file)));
	  }
	}

	if($isWindows && getmyuid() != $owner){
		return (substr(decoct(fileperms($file)), -3) == '777');
	} 
	// otherwise if this process owns the directory, we can chmod it ourselves to delete it
	return is_writable(dirname($file));
}

function replaceInput($input)
{
	global $input_invalid;

	$output = str_replace("<", "_", $input);
	$output = str_replace(">", "_", $output); 
	// we check some other settings too :)
	if (strpos($output, "cookie(") || strpos($output, "popup(") || strpos($output, "open(") || strpos($output, "alert(") || strpos($output, "reload(") || strpos($output, "refresh(")){
		$input_inv_alid = true;
	}

	if ($input != $output){
		$input_invalid = true;
	}
	return $output;
}

function getCurrentDir()
{
	// we read the dir - first session, then parameter, then default!
	if (isset($_SESSION["TFU_DIR"])){
		$dir = $_SESSION["TFU_DIR"];

	}else if (isset($_GET['dir'])){
		$dir = $_GET['dir'];
	}else{
		$dir = 'upload';
	}
	return $dir;
}

function getFileName($dir)
{
	$sort_by_date = $_SESSION["TFU_SORT_FILES_BY_DATE"];

	$index = $_GET['index']; 
	// All files are sorted in the array myFiles
	$dirhandle = opendir($dir);
	$myFiles = array();
	while($file = readdir($dirhandle)){
		if($file != "." && $file != ".."){
			if(!is_dir($dir . '/' . $file)){
				if ($sort_by_date){
					$file = filemtime(($dir . '/' . $file)) . $file;
				}
				array_push($myFiles, $file);
			}
		}
	}
	closedir ($dirhandle);
	if ($sort_by_date){
		usort ($myFiles, "mycmp_date");
		$myFiles[$index] = substr($myFiles[$index], 10);
	}else{
		usort ($myFiles, "mycmp");
	} 
	// now we have the same order as in the listing! and can delete the index
	return $dir . "/" . $myFiles[$index];
}

function getRootUrl()
{
	if (isset($_SERVER)){
		$GLOBALS['__SERVER'] = &$_SERVER;
	}elseif (isset($HTTP_SERVER_VARS)){
		$GLOBALS['__SERVER'] = &$HTTP_SERVER_VARS;
	}
	return "http://" . $GLOBALS['__SERVER']['HTTP_HOST'] . dirname ($GLOBALS['__SERVER']["PHP_SELF"]) . "/";
}

/**
 * * removes ../ in a pathname!
 */
function fixUrl($url)
{
	$pos = strpos ($url, "../");
	while ($pos !== false){
		$before = substr($url, 0, $pos-1);
		$after = substr($url, $pos + 3);
		$before = substr($before, 0, strrpos($before, "/") + 1);
		$url = $before . $after;
		$pos = strpos ($url, "../");
	}
	return $url;
}

function runsNotAsCgi()
{
	$no_cgi = true;
	if (isset($_SERVER["SERVER_SOFTWARE"])){
		$mystring = $_SERVER["SERVER_SOFTWARE"];
		$pos = strpos ($mystring, "CGI");
		if ($pos === false){ 
			// nicht gefunden...
		}else{
			$no_cgi = false;
		}
		$mystring = $_SERVER["SERVER_SOFTWARE"];
		$pos = strpos ($mystring, "cgi");
		if ($pos === false){ 
			// nicht gefunden...
		}else{
			$no_cgi = false;
		}
	}
	return $no_cgi;
}

function has_safemode_problem_global()
{
	$isWindows = substr(PHP_OS, 0, 3) == 'WIN';

	$no_cgi = runsNotAsCgi();

	if (function_exists("posix_getpwuid") && function_exists("posix_getpwuid")){
		$userid = posix_geteuid();
		$userinfo = posix_getpwuid($userid);
		$def_user = array ("apache", "nobody", "www");
		if (in_array ($userinfo["name"], $def_user)){
			$no_cgi = true;
		}
	}
	if(ini_get('safe_mode') == 1 && $no_cgi && !$isWindows){
		return true;
	}
	return false;
}
// set a umask that makes the files deletable again!
if (has_safemode_problem_global() || runsNotAsCgi()){
	umask(0000); // otherwise you cannot delete files anymore with ftp if you are no the owner!
}else{
	umask(0022); // Added to make created files/dirs group writable   
}

function gd_version()
{
	global $timezone;
	

	static $gd_version_number = null;
	if ($gd_version_number === null){ 
		// Use output buffering to get results from phpinfo()
		// without disturbing the page we're in.  Output
		// buffering is "stackable" so we don't even have to
		// worry about previous or encompassing buffering.
		ob_start();
		if (function_exists("date_default_timezone_set")){ // php 5.1.x
			@date_default_timezone_set($timezone);
		}
		phpinfo(8);
		$module_info = ob_get_contents();
		ob_end_clean();
		if (preg_match("/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i",
				$module_info, $matches)){
			$gd_version_number = $matches[1];
		}else{
			$gd_version_number = 0;
		}
	}
	return $gd_version_number;
}

function is_gd_version_min_20()
{
	if (gd_version() >= 2){
		return "true";
	}else{
		return "false";
	}
}

//----------------------------------------------------------------------------------------------------------------------------------------------
function firefox_fix() {
 	if ( !isset($_SESSION["TFU_LOGIN"]) and   isset($_GET["session_id"]) ){
 		//由檔案取回 session 
 		my_restore_temp_session( $_GET["session_id"] ) ;
 		//debug ('path = ' . $_SESSION['TFU_PATH']  .' from ' .$_GET["session_id"]  )  ;
 	}	
	
}	


/**==================================================================================
 * This stores all data in a session in a temporary folder as well if it does exist.
 * This is a workaround if a session is lost and empty in the tfu_upload.php and restored there!
 */

 

function store_temp_session()
{
    //session 暫存檔	
    global $session_double_fix;
    clearstatcache();
    if (file_exists(dirname(__FILE__) . '/session_cache') && session_id() != "") { // we do your own small session handling
        $cachename = dirname(__FILE__) . '/session_cache/' . session_id();
        $ser_file = fopen($cachename, 'w');
        fwrite($ser_file, serialize($_SESSION));
        fclose($ser_file);
        if ($session_double_fix) {
            $ser_file = fopen($cachename . '2', 'w');
            fwrite($ser_file, serialize($_SESSION));
            fclose($ser_file);
        }
    }
}



 
function my_restore_temp_session($session_id_f='') {
   //取回	session 暫存檔	 內容
    if (file_exists(dirname(__FILE__) . '/session_cache')) { // we do your own small session handling
        $cachename = dirname(__FILE__) . '/session_cache/' . $session_id_f;
        if (file_exists($cachename)) {
            $data = file_get_contents($cachename);
            set_error_handler('on_error_no_output'); // is needed because error are most likly but we don't care about fields we don't even know
            $sdata = unserialize($data);
            // check if there is maybe a session and the temp session is only here for backup
            // save the old session and add the existing values to internal one!
            debug (print_r($sdata) ) ;
            $_SESSION = $sdata;
         
        }
        
        // check the protection of the folder
        $index_htm = dirname(__FILE__) . '/session_cache/index.htm';
        if (!file_exists($index_htm)) {
             $fh = fopen($index_htm, 'w');
             fclose($fh);
        }
        $htaccess = dirname(__FILE__) . '/session_cache/.htaccess';
        if (!file_exists($htaccess)) {
             $fh = fopen($htaccess, 'w');
             fwrite($fh, 'deny from all');
             fclose($fh);
        }

        // not done - we delete all files on this folder older than 1 day + the _cache_day_*.tmp files
        $d = opendir(dirname(__FILE__) . '/session_cache');
        $i = 0;
        $del_time = time() - 3600;  // we delete file older then 1 hours
        while (false !== ($entry = readdir($d))) {
            if ($entry != '.' && $entry != '..' && $entry != 'index.htm' && $entry != '.htaccess') {
                $atime = fileatime(dirname(__FILE__) . '/session_cache/' . $entry);
                if ($atime < $del_time) {
                    @unlink(dirname(__FILE__) . '/session_cache/' . $entry);
                }
            }
        }

    }

}	

?>