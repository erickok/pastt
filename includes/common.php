<?php

	// PASTT: PHP Android Translation Tool
	// http://code.google.com/p/android-php-translator/
	// Licensed Apache License 2.0
	// http://www.apache.org/licenses/LICENSE-2.0
	
	error_reporting(E_ALL);
	@set_magic_quotes_runtime(FALSE);
	
	// Load settings
	include('settings.php');
	
	// Some global vars
	$basedir = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . '/res';
	$arraySeparator = '|';
	
	// Check settings
	if ($sendmail == '' && $_SERVER['SERVER_NAME'] != 'localhost')
		die('Please set \'$sendmail\' in \'includes/settings.php\' to the e-mail address where change notifications can be mailed. Optionally, you can set \'$frommail\' to provide a sender address.');
		
	// Read which translations already exist
	if ($basehandle = opendir($basedir)) {
		while (false !== ($dirname = readdir($basehandle))) {
		
			// If it is a 'values-' directory, assume it is a translation resource
			if (substr($dirname, 0, 7) == 'values-') {
				$languages[] = substr($dirname, 7);
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
