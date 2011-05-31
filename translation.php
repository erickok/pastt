<?php

	// PASTT: PHP Android Translation Tool
	// http://code.google.com/p/android-php-translator/
	// Licensed Apache License 2.0
	// http://www.apache.org/licenses/LICENSE-2.0
	
	define('DIRECT_ACCESSIBLE', TRUE);
	include('includes/common.php');
	
	if (!isset($_GET['lang'])) {
		die('No language specified; this should be in the query string.');
	}
	$lang = addslashes(htmlspecialchars(strip_tags($_GET['lang'])));
	if (preg_match('/^[a-z][a-z](-r[A-Z][A-Z])?$/', $lang) == 0) {
		die($lang . ' is not a valid language/locale code; should either be two letters (e.g. nl) or two letters dash two capitals (e.g. pt-BR)');
	}
	$langname = $iso639[substr($lang, 0, 2)];
	
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
	
		// Traverse through the lines of the original strings file
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
			
			if (isset($_POST['name'])) {
				$byname = htmlspecialchars(strip_tags($_POST['name']));
				$byemail = htmlspecialchars(strip_tags($_POST['email']));
			}
			
			# Anti-header-injection
			# By Victor Benincasa <vbenincasa(AT)gmail.com>
			foreach($_REQUEST as $fields => $value) if(@eregi("TO:", $value) || @eregi("CC:", $value) || 
				@eregi("CCO:", $value) || @eregi("Content-Type", $value)) exit("ERROR: Code injection attempt denied! " .
				"Please don't use the following sequences in your message: 'TO:', 'CC:', 'CCO:' or 'Content-Type'.");

			mail(
				$sendmail,
				"$langname ($lang) translation updated",
				"The $langname ($lang) translation of your Android string resource file " . 
				"has been updated." . (isset($byname)? "\n\nTranslator: $byname ($byemail)": "") . 
				"\n\nThe new file was stored at $newfilepath",
				(isset($frommail) && $frommail == '')? null: "From: $frommail");
				
		}
		
	}
	
	$pageTitle = 'Editing ' . $langname . ' translation - ' . $appname;
	include('includes/header.php');

	echo '
	<script type="text/javascript">
		var showOnlyMissing = false;
		function flipShowOnlyMissing() {
			showOnlyMissing = !showOnlyMissing;
			var tbl = document.getElementById(\'translationtable\');
			// Traverse through the table rows (skipping the first header row)
			for (var i = 1; i < tbl.rows.length; i++) {
				if (tbl.rows[i].cells.length > 2 && tbl.rows[i].cells[2].children != null) {
					// Get the input field, which is in the third cell
					var input = tbl.rows[i].cells[2].children[0];
					if (input.value != \'\' && !showOnlyMissing) {
						// Show this row (again)
						tbl.rows[i].style.display = \'table-row\';
					} else if (input.value != \'\') {
						// Hide this row
						tbl.rows[i].style.display = \'none\';
					}
				}
			}
			// Change the button text
			var but = document.getElementById(\'showmissing\');
			but.value = showOnlyMissing? \'Show all rows\': \'Show only missing translations\';
		}
	</script>

	<h1>Translating to ' . $langname . ' (' . $lang . ')</h1>';
	
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
	<p>You are working with the last-saved translation \'' . $newesttranslation . '\' (saved ' . date('d F Y H:i', filemtime($basedir . '/values-' . $lang . '/' . $newesttranslation)) . '). When you save your updates it will not override it but make a new copy.</p>
	<p id="showmissingrow"><input type="button" id="showmissing" name="showmissing" value="Show only missing translations" onclick="javascript:flipShowOnlyMissing();" /></p>';
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
			<th id="trans">' . $langname . ' (' . $lang . ')</th>
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
	
	if ($askforemail) {
		echo '
		<tr>
			<td colspan="3">Please fill in your name and e-mail address:</td>
		</tr>
		<tr>
			<td>Name:</td>
			<td colspan="2"><input type="input" id="name" name="name" value="" /></td>
		</tr>
		<tr>
			<td>E-mail:</td>
			<td colspan="2"><input type="input" id="email" name="email" value="" /></td>
		</tr>';
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

