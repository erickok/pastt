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
	
		// Insert all the translations into the original file text
		$lines = file($basedir . '/values/strings.xml');
		$outfile = "";
		foreach ($lines as $line) {
			
			// <string> lines
			$line = trim($line);
			if (substr(trim($line), 0, 8) == '<string ' && substr($line, -9) == '</string>') {
				$namePos = strpos($line, 'name="') + 6;
				$stringPos = strPos($line, '>', $namePos) + 1;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$newValue = stripslashes($_POST[$name]);
				if (trim($newValue) != '') {
					$outfile .= substr($line, 0, $stringPos) . $newValue . substr($line, strrpos($line, '<')) . "\n";
				}
				
			// <string-array> lines
			} else if (substr($line, 0, 13) == '<string-array') {
				$namePos = strpos($line, 'name="') + 6;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$newValues = explode($arraySeparator, stripslashes($_POST[$name]));
				$n = 0;
				$outfile .= $line . "\n";
				
			// <item> lines
			} else if (substr($line, 0, 6) == '<item>') {
				$outfile .= substr($line, 0, 6) . $newValues[$n] . substr($line, strrpos($line, '<')) . "\n";
				$n++;
				
			} else {
				$outfile .= $line . "\n";
				
			}
		}
				
		// Save the new translation with a new unique number (to prevent incorrect overwriting)
		$newfilename = 'strings.' . time() . '.xml';
		if (!is_dir($basedir . '/values-' . $lang)) {
			mkdir($basedir . '/values-' . $lang);
		}
		$newfilepath = $basedir . '/values-' . $lang . '/' . $newfilename;
		file_put_contents($newfilepath, $outfile);
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
	
	function parseStrings($file) {
		$lines = file($file);
		foreach ($lines as $line) {
			
			// Empty lines
			$line = trim($line);
			if ($line == '') {
				//$out[]['type'] = 'empty';
				
			// <string> lines
			} else if (substr($line, 0, 8) == '<string ' && substr($line, -9) == '</string>') {
				$out[]['type'] = 'string';
				$namePos = strpos($line, 'name="') + 6;
				$stringPos = strPos($line, '>', $namePos) + 1;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$string = substr($line, $stringPos, strrpos($line, '<') - $stringPos);
				$out[count($out)-1]['name'] = $name;
				$out[count($out)-1]['value'] = $string;
				
			// <string-array> lines
			} else if (substr($line, 0, 13) == '<string-array') {
				$out[]['type'] = 'stringarray';
				$namePos = strpos($line, 'name="') + 6;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$out[count($out)-1]['name'] = $name;
				
			// <item> lines
			} else if (substr($line, 0, 6) == '<item>') {
				$val = substr($line, 6, strrpos($line, '<') - 6);
				$out[count($out)-1]['values'][] = $val;
				
			}
		}
		return $out;
	}
	
	function findTranslation($translations, $name) {
		foreach ($translations as $translation) {
			if ($translation['name'] == $name) {
				return $translation;
			}
		}
	}
	
	function dp($in) {
		echo '<pre>';
		print_r($in);
		echo '</pre><br />';
	}
	
	// Load the XML files
	$strings = parseStrings($basedir . '/values/strings.xml');
	//dp($strings);
	
	// Load the translation XML file, if it exists
	if (isset($newesttranslation)) {
		echo '
	<p>You are working with the last-saved translation \'' . $newesttranslation . '\' (saved ' . date('d F Y H:i', filemtime($basedir . '/values-' . $lang . '/' . $newesttranslation)) . '). When you save your updates it will not override it but make a new copy.</p>';
		$translations = parseStrings($basedir . '/values-' . $lang . '/' . $newesttranslation);
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
	
	function encodeForHtml($in) {
		return htmlspecialchars($in);
	}
	function encodeForInput($in) {
		return str_replace(array('&','"'), array('&amp;','&quot;'), $in);
	}
	
	// For every string in the original (English) file
	foreach ($strings as $string) {
		
		$name = $string['name'];
		$transtext = '';
		
		if ($string['type'] == 'string') {
			$value = $string['value'];
			if (isset($translations)) {
				$trans = findTranslation($translations, $name);
				$transtext = $trans['value'];
			}
		} else if ($string['type'] == 'stringarray') {
			$value = implode($arraySeparator, $string['values']);
			if (isset($translations)) {
				$trans = findTranslation($translations, $name);
				$transtext = implode($arraySeparator, $trans['values']);
			}
		}
		//dp($trans);

		// Show a table row that has the key, the original English text and a input box with the translation text that is editable
		echo '
		<tr' . ($isuneven? $classuneven: '') . '>
			<td>' . $name . '</td>
			<td>' . encodeForHtml($value) . '</td>
			<td><input type="text" id="' . $name . '" name="' . $name . '" value="' . encodeForInput($transtext) . '" /></td>
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

