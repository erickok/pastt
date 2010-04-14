<?php

	// PASTT: PHP Android Translation Tool
	// http://code.google.com/p/android-php-translator/
	// Licensed Apache License 2.0
	// http://www.apache.org/licenses/LICENSE-2.0
	
	include('includes/common.php');
	
	if (!isset($_GET['lang'])) {
		die('No language specified; this should be in the query string.');
	}
	$lang = addslashes(htmlspecialchars(strip_tags($_GET['lang'])));
	
	function DOMinnerHTML($element) {
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ($children as $child) {
			$tmp_dom = new DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$innerHTML.=' '.trim($tmp_dom->saveHTML()).' ';
		}
		return trim($innerHTML);
	}
	
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
		$original = new DOMDocument();
		$original->load($basedir . '/values/strings.xml');
		$resources = $original->getElementsByTagName('resources')->item(0);
		$strings = $original->getElementsByTagName('string');
		$stringarrays = $original->getElementsByTagName('string-array');
		
		// Insert all the translations
		for ($i = 0; $i < $strings->length; $i++) {
			$string = $strings->item($i);
			$name = $string->getAttribute('name');
			// Make sure the new string is free of incorrect slashes and ampersands
			$new = stripslashes($_POST[$name]);
			if ($new == '') {
				// Remove the <string> node if it was empty (not specified; so the app can use the original (English) version)
				$resources->removeChild($string);
				$i--;
			} else {
				// Remove the 'text content children' and add a new 'text node' again to this <string> node
				while ($string->hasChildNodes()) {
					$string->removeChild($string->firstChild);
				}
				$string->appendChild(new DOMText($new));
			}
		}
		
		for ($i = 0; $i < $stringarrays->length; $i++) {
			$stringarray = $stringarrays->item($i);
			$name = $stringarray->getAttribute('name');
			// Make sure the new string is free of incorrect slashes and ampersands
			$new = stripslashes($_POST[$name]);
			$newitems = explode($arraySeparator, $new);
			$items = $stringarray->getElementsByTagName('item');
			for ($j = 0; $j < $items->length; $j++) {
				$item = $items->item($j);
				// Remove the 'text content children' and add a new 'text node' again to this <item> node
				while ($item->hasChildNodes()) {
					$item->removeChild($item->firstChild);
				}
				$item->appendChild(new DOMText($newitems[$j]));
			}
		}
		
		// Save the new translation with a new unique number (to prevent incorrect overwriting)
		$newfilename = 'strings.' . time() . '.xml';
		if (!is_dir($basedir . '/values-' . $lang)) {
			mkdir($basedir . '/values-' . $lang);
		}
		$newfilepath = $basedir . '/values-' . $lang . '/' . $newfilename;
		file_put_contents($newfilepath, $original->saveXML());
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
	$original = new DOMDocument();
	$original->load($basedir . '/values/strings.xml');
	$strings = $original->getElementsByTagName('string');
	$stringarrays = $original->getElementsByTagName('string-array');
	
	// Load the translation XML file, if it exists
	if (isset($newesttranslation)) {
		echo '
	<p>You are working with the last-saved translation \'' . $newesttranslation . '\' (saved ' . date('d F Y H:i', filemtime($basedir . '/values-' . $lang . '/' . $newesttranslation)) . '). When you save your updates it will not override it but make a new copy.</p>';
		$translation = new DOMDocument();
		$translation->load($basedir . '/values-' . $lang . '/' . $newesttranslation);
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
	foreach ($strings as $string) {
		
		// Use an xpath query to get the translated text
		$name = $string->getAttribute('name');
		$value = DOMinnerHTML($string);
		$transtext = '';
		if (isset($newesttranslation)) {
			$trquery = new DOMXPath($translation);
			$trnodes = $trquery->query('//string[@name=\'' . $name . '\']');
			if ($trnodes->length > 0) {
				$transtext = DOMinnerHTML($trnodes->item(0));
			}
		}
		//var_dump($transtext);

		// Show a table row that has the key, the original English text and a input box with the translation text that is editable
		echo '
		<tr' . ($isuneven? $classuneven: '') . '>
			<td>' . $name . '</td>
			<td>' . htmlspecialchars(str_replace('\\\'', '\'', $value), ENT_NOQUOTES) . '</td>
			<td><input type="text" id="' . $name . '" name="' . $name . '" value="' . str_replace('"', '&quot;', $transtext) . '" /></td>
		</tr>';
		
		$isuneven = !$isuneven;
		
	}
	
	// For every string array in the original (English) file
	
	foreach ($stringarrays as $stringarray) {
		
		// Use an xpath query to get the translated text
		$name = $stringarray->getAttribute('name');
		$values = $stringarray->getElementsByTagName('item');
		if (isset($newesttranslation)) {
			$trquery = new DOMXPath($translation);
			$trnodes = $trquery->query('//string-array[@name=\'' . $name . '\']');
			if ($trnodes->length > 0) {
				$transtexts = $trnodes->item(0)->getElementsByTagName('item');
			}
		}

		// Show a table row that has the array key, the original English text values and an input box with the translation text that is editable
		echo '
		<tr' . ($isuneven? $classuneven: '') . '>
			<td>' . $name . '</td>
			<td>';
		$transitems = '';
		for ($i=0; $i<$values->length; $i++) {
			echo ($i > 0? $arraySeparator: '') . htmlspecialchars(str_replace('\\\'', '\'', DOMinnerHTML($values->item($i))), ENT_NOQUOTES);
			$transitems .= ($i > 0? $arraySeparator: '') . (isset($transtexts)? DOMinnerHTML($transtexts->item($i)): '');
		}
		echo '</td>
			<td><input type="text" id="' . $name . '" name="' . $name . '" value="' . str_replace('"', '&quot;', $transitems) . '" /></td>
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

