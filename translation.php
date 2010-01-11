<?php

	include('includes/common.php');
	
	if (!isset($_GET['lang'])) {
		die('No language specified; this should be in the query string.');
	}
	$lang = addslashes(htmlspecialchars(strip_tags($_GET['lang'])));
	
	// Get the last translation file, if it exists
	if ($transdir = @opendir($basedir . '/values-' . $lang)) {
	
		while (false !== ($stringsfile = readdir($transdir))) {
			// If it is a strings XML file, save it in the array
			if (substr($stringsfile, 0, 8) == 'strings.') {
				$existingfiles[] = $stringsfile;
			}
		}
		closedir($transdir);
		
		// Fiels should have been found here
		if (!isset($existingfiles) || count($existingfiles) <= 0) {
			die ('Every resource directory \'values-{lang}\' should at least have a single translation file.');
		}
		
		// Sort the list of all strings[.timestamp].xml files
		rsort($existingfiles);
		// Use the newest custom translation or strings.xml as a fallback
		$newesttranslation = $existingfiles[0];
		if ($newesttranslation == 'strings.xml' && count($existingfiles) > 1) {
			$newesttranslation = $existingfiles[1];
		}
		
	}
	
	// Save the updated translation?
	if (isset($_POST['submit'])) {
	
		// Load the English original
		$original = simplexml_load_file($basedir . '/values/strings.xml');
		
		// Insert all the translations
		for ($i = 0; $i < count($original->string); $i++) {
			$new = str_replace('\'', '\\\'', stripslashes(htmlspecialchars($_POST[(string)$original->string[$i]['name']])));
			if ($new == '') {
				// Remove the string form the translation file if it was empty (not specified; so the app can use the original (English) version)
				unset($original->string[$i]);
			} else {
				$original->string[$i] = $new;
			}
		}
		for ($i = 0; $i < count($original->{'string-array'}); $i++) {
			$newitems = explode(';', str_replace('\'', '\\\'', stripslashes(htmlspecialchars($_POST[(string)$original->{'string-array'}[$i]['name']]))));
			//print_r(count($original->{'string-array'}[$i]->item));
			for ($j = 0; $j < count($original->{'string-array'}[$i]->item); $j++) {
				$original->{'string-array'}[$i]->item[$j] = $newitems[$j];
			}
		}
		
		// Save the new translation with a new unique number (to prevent incorrect overwriting)
		$newfilename = 'strings.' . time() . '.xml';
		if (!is_dir($basedir . '/values-' . $lang)) {
			mkdir($basedir . '/values-' . $lang);
		}
		$newfilepath = $basedir . '/values-' . $lang . '/' . $newfilename;
		file_put_contents($newfilepath, $original->asXML());
		$newesttranslation = $newfilename;
		
		// Send an e-mail to notify of the new translation
		if (isset($sendmail) && $sendmail != "") {
			mail(
				$sendmail,
				$iso639[$lang] . ' (' . $lang . ') translation updated',
				'The ' . $iso639[$lang] . ' (' . $lang . ') translation of your Android string resource file has been ' . 
				'updated.\n\nThe new file was stored at ' . $newfilepath,
				'From: ' . $sendmail);
		}
		
	}
	
	$pageTitle = 'Edit a translation - ' . $appname;
	include('includes/header.php');

	echo '
	<h1>Translating to ' . $iso639[$lang] . ' (' . $lang . ')</h1>';
	
	// Load the XML files
	$original = simplexml_load_file($basedir . '/values/strings.xml');
	if (isset($newesttranslation)) {
		echo '
	<p>You are working with the last-saved translation \'' . $newesttranslation . '\' (saved ' . date('d F Y H:i', filemtime($basedir . '/values-' . $lang . '/' . $newesttranslation)) . '). When you save your updates it will not override it but make a new copy.</p>';
		$translation = simplexml_load_file($basedir . '/values-' . $lang . '/' . $newesttranslation);
	} else {
		echo '
	<p>No translation for this language currently exists. When saving for the first time, it will create a directory and the first strings.{timestamp}.xml for this new language.</p>';
	}
	
	echo '
	<form id="translationform" name="translationform" method="post" action="translation.php?lang=' . $lang . '">
	<table id="translationtable">
		<tr>
			<th id="key">Key</th>
			<th id="org">English</th>
			<th id="trans">' . $iso639[$lang] . ' (' . $lang . ')</th>
		</tr>';
	
	$isuneven = false;
	$classuneven = ' class="uneven"';
	
	// For every string in the original (English) file
	foreach ($original->string as $string) {
		
		// Use an xpath query to get the translated text
		$transtext = (isset($translation)? $translation->xpath('//string[@name=\'' . $string['name'] . '\']'): null);

		// Show a table row that has the key, the original English text and a input box with the translation text that is editable
		echo '
		<tr' . ($isuneven? $classuneven: '') . '>
			<td>' . $string['name'] . '</td>
			<td>' . str_replace('\\\'', '\'', $string) . '</td>
			<td><input type="text" id="' . $string['name'] . '" name="' . $string['name'] . '" value="' . (isset($transtext) && isset($transtext[0])? str_replace('\\\'', '\'', $transtext[0]): '') . '" /></td>
		</tr>';
		
		$isuneven = !$isuneven;
		
	}
	
	// For every string array in the original (English) file
	foreach ($original->{'string-array'} as $stringarray) {
		
		// Use an xpath query to get the translated text
		$transtextarray = (isset($translation)? $translation->xpath('//string-array[@name=\'' . $stringarray['name'] . '\']'): null);

		// Show a table row that has the array key, the original English text values and an input box with the translation text that is editable
		echo '
		<tr' . ($isuneven? $classuneven: '') . '>
			<td>' . $stringarray['name'] . '</td>
			<td>';
		$transitems = '';
		for ($i=0; $i<count($stringarray->item); $i++) {
			echo ($i > 0? ';': '') . str_replace('\\\'', '\'', $stringarray->item[$i]);
			$transitems .= ($i > 0? ';': '') . (isset($transtextarray)? (isset($transtextarray[0])? str_replace('\\\'', '\'', $transtextarray[0]->item[$i]): ''): '');
		}
		echo '</td>
			<td><input type="text" id="' . $stringarray['name'] . '" name="' . $stringarray['name'] . '" value="' . $transitems . '" /></td>
		</tr>';
		
		$isuneven = !$isuneven;
		
	}
	
	echo '
		<tr>
			<td colspan="3"><input type="submit" id="submit" name="submit" value="Save updated translation" /></td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: center;"><a href="./">or go back without saving</a></td>
		</tr>
	</table>
	</form>';
	
	include('includes/footer.php');

?>

