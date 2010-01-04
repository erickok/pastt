<?php

	error_reporting(E_ALL);
	set_magic_quotes_runtime(FALSE);
	
	// Some global vars
	$appname = 'PASTT: PHP Android String Translation Tool';
	$basedir = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . '/res';
	$sendmail = "erickok@gmail.com";
	
	// Read which translations already exist
	if ($basehandle = opendir($basedir)) {
		while (false !== ($dirname = readdir($basehandle))) {
		
			// If it is a 'values-' directory, assume it is a translation resource
			if (substr($dirname, 0, 7) == 'values-') {
				$languages[] = substr($dirname, 7, 2);
			}
			
		}
		closedir($basehandle);
	} else {
		die('Cannot read directory ' . $basedir . '. Is it read-protected?');
	}
	
	// Some languages that we know of, to show the name instead of the localization code
	// Android locale codes are defined in the ISO-639 standard: http://en.wikipedia.org/wiki/ISO-639
	$raw639 = file_get_contents('includes/iso-639.txt');
	$handle = fopen('includes/iso-639.txt', 'r');
	while (($data = fgetcsv($handle, 100, ";")) !== FALSE) {
		$iso639[$data[0]] = $data[1];
	}
	fclose($handle);
	
?>
