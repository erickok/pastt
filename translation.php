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
    $region = '';
    if ( isset( $_GET['region'] ) ) {
	$region = addslashes(htmlspecialchars(strip_tags($_GET['region'])));
    }
	if (strlen($region) > 0) {
		$lang .= '-r' . strtoupper($region);
	}
	if (preg_match('/^[a-z][a-z](-r[A-Z][A-Z])?$/', $lang) == 0) {
		die($lang . ' is not a valid language/locale code; should either be two letters (e.g. nl) or two letters dash two capitals (e.g. pt-rBR)');
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
	
		// Require the input of a name and email address?
		if ($requireemail) {
			$byname = htmlspecialchars(strip_tags($_POST['pastt_translator_name']));
			$byemail = htmlspecialchars(strip_tags($_POST['pastt_translator_email']));
			if(strlen($byname) <= 0 || strlen($byemail) <= 0 || !preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $byemail)) {
				die('Please enter a name and valid email address. You can use the back button of the browser to recover your translations.');
			}
		}

		// Check captcha
		if ($requirecaptcha && !confirm_captcha_response($recaptcha_secret_key, $_POST["g-recaptcha-response"])) {
			die('Error validating the security response. You can use the back button of the browser to recover your translations.');
		}

		// Traverse through the lines of the original strings file
		$lines = file($basedir . '/values/strings.xml');
		$outfile = "";
		$multiline = "";
		foreach ($lines as $line) {
			
			// Avoid looking up untranslatable strings
			if (strpos($line, 'translatable="false"') !== false) {
				continue;
			}
			
			// <string> lines
			$line = trim($line);
			if (substr(trim($line), 0, 8) == '<string ' && substr($line, -9) == '</string>') {
				$namePos = strpos($line, 'name="') + 6;
				$stringPos = strPos($line, '>', $namePos) + 1;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$newValue = str_replace("\n","\\n",$_POST[$name]);
				if (trim($newValue) != '') {
					$outfile .= $indentation . substr($line, 0, $stringPos) . $newValue . substr($line, strrpos($line, '<')) . "\n";
				}
				$multiline = "";
				
			} else if (substr(trim($line), 0, 8) == '<string ') {
				// Support multi line strings
				$multiline = $line;
			
			} else if (substr($line, -9) == '</string>') {
				$multiline .= $line;
				$namePos = strpos($multiline, 'name="') + 6;
				$stringPos = strPos($multiline, '>', $namePos) + 1;
				$name = substr($multiline, $namePos, strpos($multiline, '"', $namePos) - $namePos);
				$newValue = str_replace("\n","\\n",stripslashes($_POST[$name]));
				if (trim($newValue) != '') {
					$outfile .= substr($multiline, 0, $stringPos) . $newValue . substr($multiline, strrpos($multiline, '<')) . "\n";
				}
				$multiline = "";
			// <string-array> lines
			} else if (substr($line, 0, 13) == '<string-array') {
				$namePos = strpos($line, 'name="') + 6;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$skipStringArray = stripslashes($_POST[$name]) == '';
				if ($skipStringArray) {
					$multiline = "";
					continue;
				}
				$newValues = explode($arraySeparator, str_replace("\n","\\n",stripslashes($_POST[$name])));
				$n = 0;
				$outfile .= $line . "\n";
				$multiline = "";
			// <item> lines
			} else if (substr($line, 0, 6) == '<item>') {
				if ($skipStringArray) {
					$multiline = "";
					continue;
				}
				$outfile .= substr($line, 0, 6) . $newValues[$n] . substr($line, strrpos($line, '<')) . "\n";
				$n++;
				$multiline = "";
			// </string-array> lines
			} else if (substr($line, 0, 15) == '</string-array>') {
				if ($skipStringArray) {
					$multiline = "";
					continue;
				}
				$outfile .= $line . "\n";
			// <plurals lines
			} else if (substr($line, 0, 8) == '<plurals') {
				$namePos = strpos($line, 'name="') + 6;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$quantityTypes = getQuantitiesInLang($langPlurals, $lang);
				// Check if there is at least one translation in this plural, otherwise we don't output it at all
				$hasAtLeastOneTranslation = false;
				foreach ($quantityTypes as $type) {
					$quantityName = $name . "_quantity_" . $type;
					if (!empty($_POST[$quantityName])) $hasAtLeastOneTranslation = true;
				}
				if ($hasAtLeastOneTranslation) {
					$outfile .= $indentation . $line . "\n";
					foreach ($quantityTypes as $type) {
						$quantityName = $name . "_quantity_" . $type;
						$newValue = str_replace("\n","\\n", stripslashes($_POST[$quantityName]));
						if (!empty($newValue)) { // Don't print untranslated plurals (they will override the default English text)
							$outfile .= $indentation . $indentation . "<item quantity=\"" . $type . "\">" . $newValue . "</item>" . "\n";
						}
					}
					$outfile .= $indentation . "</plurals>\n";
				}
			// <item quantity= lines
			} else if (substr($line, 0, 15) == '<item quantity=') {
				continue;
			// </plurals> lines
			} else if (substr($line, 0, 10) == '</plurals>') {
				continue; // Already printed as part of the plural
			} else {
				if ($multiline != "") {
					$multiline .= $line;
				} else {
					$outfile .= $line . "\n";
				}
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
			
			if (isset($_POST['pastt_translator_name'])) {
				$byname = htmlspecialchars(strip_tags($_POST['pastt_translator_name']));
				$byemail = htmlspecialchars(strip_tags($_POST['pastt_translator_email']));
			}
			
			# Anti-header-injection
			# By Victor Benincasa <vbenincasa(AT)gmail.com>
			foreach($_REQUEST as $fields => $value) if(@preg_match("TO:", $value) || @eregi("CC:", $value) || 
				@preg_match("CCO:", $value) || @preg_match("Content-Type", $value)) exit("ERROR: Code injection attempt denied! " .
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
		var unloadOk = false;
		window.onbeforeunload = function() {
			if (unloadOk === true) {
				return null;
			} else {
				return \'Please make sure you don\\\'t have unsaved translations!\';
			}
		}

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
		function requireNameAndEmail() {
			var name = document.getElementById(\'pastt_translator_name\');
			var email = document.getElementById(\'pastt_translator_email\');
			// Regex from http://www.regular-expressions.info/email.html
			var emailRegex = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i;
			if (emailRegex.test(email.value)) {
				if (name.value.trim().length > 0) {
					return true;
				}
			}
			alert(\'Please enter a name and valid e-mail address.\');
			return false;
		}
		function requireCaptcha() {
			if (!grecaptcha.getResponse()) {
				alert("Please confirm the captcha first.");
				return false;
			}
			return true;
		}
	</script>

	<div class="container">
	<h3>' . $langname . ' (' . $lang . ')</h3>
		<div class="container">';
	
	function parseStrings($file) {
		$lines = file($file);
		$multiline = "";
		foreach ($lines as $line) {
			
			// Empty lines
			$line = trim($line);
			if ($line == '') {
				//$out[]['type'] = 'empty';
				
			// <string> lines
			} else if (substr($line, 0, 8) == '<string ' && substr($line, -9) == '</string>') {
				$out[]['type'] = 'string';
				$translatable = (strpos($line, 'translatable="false"') !== false);
				$namePos = strpos($line, 'name="') + 6;
				$stringPos = strPos($line, '>', $namePos) + 1;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$string = substr($line, $stringPos, strrpos($line, '<') - $stringPos);
				$out[count($out)-1]['name'] = $name;
				$out[count($out)-1]['value'] = $string;
				$out[count($out)-1]['translatable'] = $translatable;
				$multiline = "";
				
			} else if ((substr(trim($line), 0, 8) == '<string ') && ($multiline == "")) {
				// Support multi line strings
				$multiline = $line;
			
			} else if (substr($line, -9) == '</string>') {
				$multiline .= $line;
				$out[]['type'] = 'string';
				$translatable = (strpos($multiline, 'translatable="false"') !== false);
				$namePos = strpos($multiline, 'name="') + 6;
				$stringPos = strPos($multiline, '>', $namePos) + 1;
				$name = substr($multiline, $namePos, strpos($multiline, '"', $namePos) - $namePos);
				$string = substr($multiline, $stringPos, strrpos($multiline, '<') - $stringPos);
				$out[count($out)-1]['name'] = $name;
				$out[count($out)-1]['value'] = $string;
				$out[count($out)-1]['translatable'] = $translatable;
				$multiline = "";
				
			// <string-array> lines
			} else if (substr($line, 0, 13) == '<string-array') {
				$out[]['type'] = 'stringarray';
				$translatable = (strpos($line, 'translatable="false"') !== false);
				$namePos = strpos($line, 'name="') + 6;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$out[count($out)-1]['name'] = $name;
				$out[count($out)-1]['translatable'] = $translatable;
				$multiline = "";
				
			// <item> lines
			} else if (substr($line, 0, 6) == '<item>') {
				$val = substr($line, 6, strrpos($line, '<') - 6);
				$out[count($out)-1]['values'][] = $val;
				$multiline = "";

			// <plurals> lines
			} else if (substr($line, 0, 8) == '<plurals') {
				$out[]['type'] = 'plurals';
				$translatable = (strpos($line, 'translatable="false"') !== false);
				$namePos = strpos($line, 'name="') + 6;
				$name = substr($line, $namePos, strpos($line, '"', $namePos) - $namePos);
				$out[count($out)-1]['name'] = $name;
				$out[count($out)-1]['translatable'] = $translatable;
				$multiline = "";

			// <item quantity= lines
			} else if (substr($line, 0, 15) == '<item quantity=') {
				$quantityPos = strpos($line, 'quantity="') + 10;
				$quantity = substr($line, $quantityPos, strrpos($line, '"', $quantityPos) - $quantityPos);
				$valPos = strPos($line, '>') + 1;
				$val = substr($line, $valPos, strrpos($line, '<') - $valPos);
				$out[count($out)-1]['values'][$quantity] = $val;
				$multiline = "";

			} else if ($multiline != ""){
				$multiline .= $line;
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
	
	// Load the base XML file
	$strings = parseStrings($basedir . '/values/strings.xml');
	//dp($strings);
	
	echo '
			<div class="row">';
	// Load the translation XML file, if it exists
	if (isset($newesttranslation)) {
		echo '
				<p>You are working with the last-saved translation \'<a href="res/values-' . $lang . '/' . $newesttranslation . '">' . $newesttranslation . '</a>\' (saved ' . date('d F Y H:i', filemtime($basedir . '/values-' . $lang . '/' . $newesttranslation)) . '). When you save your updates it will not override it but make a new copy.</p>
				<p id="showmissingrow"><input class="btn btn-large btn-primary" type="button" id="showmissing" name="showmissing" value="Show only missing translations" onclick="javascript:flipShowOnlyMissing();" /></p>';
		$translations = parseStrings($basedir . '/values-' . $lang . '/' . $newesttranslation);
	} else {
		echo '
				<p>No translation for this language currently exists. When saving for the first time, it will create a directory and the first strings.{timestamp}.xml for this new language.</p>';
	}
	echo '
			</div>';
	
	// Require the input of a name and email address and/or captcha?
	if ($requireemail && $requireCaptcha) {
		$requirehtml = 'return(requireNameAndEmail() && requireCaptcha());';
	} else if ($requireemail) {
		$requirehtml = 'return(requireNameAndEmail());';
	} else if ($requirecaptcha) {
		$requirehtml = 'return(requireCaptcha());';
	} else {
		$requirehtml = '';
	}

	echo '
			<form id="translationform" name="translationform" method="post" action="translation.php?lang=' . $lang . '" onsubmit="javascript:unloadOk=true;' . $requirehtml . '">
			<div class="row">
				<div class="table-responsive">
				<table class="table table-striped" id="translationtable">
					<thead>
						<tr>
							<th class="col-xs-1 col-sm-2" id="key">Key</th>
							<th class="col-xs-1 col-sm-2" id="org">English</th>
							<th class="col-xs-10 col-sm-8" id="trans">' . $langname . ' (' . $lang . ')</th>
						</tr>
					</thead>
					<tbody>';
	
	function encodeForHtml($in) {
		return htmlspecialchars($in);
	}
	function encodeForInput($in) {
		return str_replace(array('&','"'), array('&amp;','&quot;'), $in);
	}
	
	// For every string in the original (English) file
	foreach ($strings as $string) {
		
		if ($string['translatable']) {
			continue;
		}
		
		$name = $string['name'];
		$transtext = '';
		
		if ($string['type'] == 'string') {
			$value = $string['value'];
			if (isset($translations)) {
				$trans = findTranslation($translations, $name);
				$transtext = $trans['value'];
			}
			showRow($name, $value, $transtext);
		} else if ($string['type'] == 'stringarray') {
			$value = implode($arraySeparator, $string['values']);
			if (isset($translations)) {
				$trans = findTranslation($translations, $name);
				$transtext = implode($arraySeparator, $trans['values']);
			}
			showRow($name, $value, $transtext);
		} else if ($string['type'] == 'plurals') {
			$quantityTypes = getQuantitiesInLang($langPlurals, $lang);
			foreach ($quantityTypes as $type) {
				$quantityName = $name . "_quantity_" . $type;
				if (isset($translations)) {
					$trans = findTranslation($translations, $name);
					$transtext = $trans['values'][$type];
				}
				if ($type == 'one') {
					$value = $string['values']['one'];
				} else {
					$value = $string['values']['other'];
				}
				showRow($quantityName, $value, $transtext);
			}
		}
	}

	function showRow($name, $value, $transtext) {
		// Show a table row that has the key, the original English text and a input box with the translation text that is editable
		echo '
						<tr>
							<td class="col-sm-2">' . $name . '</td>
							<td class="col-sm-2">' . str_replace("\\n","<br/>",encodeForHtml($value)) . '</td>
							<td class="col-sm-8">';

		if (strpos(encodeForInput($value),"\\n") !== FALSE) echo '<textarea class="form-control" id="' . $name . '" name="' . $name . '">' . str_replace("\\n","\n",encodeForInput($transtext)) . '</textarea>';
		else echo '<input class="form-control" type="text" id="' . $name . '" name="' . $name . '" value="' . encodeForInput($transtext) . '" /></td>';
		echo '
						</tr>';
	}

	function getQuantitiesInLang($langPlurals, $lang) {
		if ($langPlurals[$lang]) {
			return $langPlurals[$lang];
		}
		// Language is not set up for plurals, fallback to enable all possible plurals
		return array('zero', 'one', 'two', 'few', 'many', 'other');
	}
	
	echo '
					</tbody>
				</table>
				</div>
			</div>
			<div class="row">
				<div class="well">';
	if ($askforemail) {
		$requiredfieldhtml = $requireemail? ' <strong>Your are required to fill in your name and e-mail address:</strong>': 'Please fill in your name and e-mail address:';
		echo '
					<p>' . $requiredfieldhtml . '</p>
					<div class="form-group">
						<label for="pastt_translator_name">Name</label>
						<input type="input" class="form-control" id="pastt_translator_name" name="pastt_translator_name" value="" />
					</div>
					<div class="form-group">
						<label for="pastt_translator_email">E-mail:</label>
						<input type="input" class="form-control" id="pastt_translator_email" name="pastt_translator_email" value="" />
					</div>';
	}

	if($requirecaptcha) {
		echo '
					<div style="text-align: center">' . render_captcha($recaptcha_site_key) . '</div>';
	}

	echo '
					<input type="submit" class="btn btn-primary" id="submit" name="submit" value="Save updated translation" />
				</div>
			</div>
			</form>
		</div>
	</div>';
	
	include('includes/footer.php');

?>
