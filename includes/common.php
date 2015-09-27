<?php

	// PASTT: PHP Android Translation Tool
	// http://code.google.com/p/android-php-translator/
	// Licensed Apache License 2.0
	// http://www.apache.org/licenses/LICENSE-2.0
	
	error_reporting(E_ALL);
	@set_magic_quotes_runtime(FALSE);
	
	// Check access
	if (!defined('DIRECT_ACCESSIBLE')) { die('Do not access the include files directly, but go to <a href="../">the script root</a> instead.'); }

	// Load settings
	include('settings.php');
	
	// Some global vars
	$basedir = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/')) . '/res';
	$arraySeparator = '|';
	
	// Check settings
	if ($sendmail == '' && $_SERVER['SERVER_NAME'] != 'localhost')
		die('Please set \'$sendmail\' in \'includes/settings.php\' (which might first be created by copying \'includes/settings.example.php\' if you haven\'t done so yet) to the e-mail address where change notifications can be mailed. Optionally, you can set \'$frommail\' to provide a sender address.');
		
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

	function render_captcha($site_key) {
		return '<div class="g-recaptcha" data-sitekey="' . $site_key . '" style="display: inline-block"></div>' .
		'<noscript>' .
		'  <div style="width: 302px; height: 352px; display: inline-block">' .
		'    <div style="width: 302px; height: 352px; position: relative;">' .
		'      <div style="width: 302px; height: 352px; position: absolute;">' .
		'        <iframe src="https://www.google.com/recaptcha/api/fallback?k="' . $site_key .
		'          frameborder="0" scrolling="no" style="width: 302px; height:352px; border-style: none;">' .
		'        </iframe>' .
		'      </div>' .
		'      <div style="width: 250px; height: 80px; position: absolute; border-style: none;' .
		'        bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">' .
		'        <textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response"' .
		'          style="width: 250px; height: 80px; border: 1px solid #c1c1c1; margin: 0px; padding: 0px; resize: none;" value="">' .
		'        </textarea>' .
		'      </div>' .
		'    </div>' .
		'  </div>' .
		'</noscript>';
	}

	function confirm_captcha_response($secret_key, $response) {
		// do not check invalid responses
		if ($response == null || strlen($response) == 0) {
			return false;
		}

		$response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key .
			'&remoteip=' . $_SERVER["REMOTE_ADDR"] .
			'&response=' . $response);

		$answer = json_decode($response);
		return $answer->success == 'true';
	}
?>
