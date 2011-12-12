<?php

	// PASTT: PHP Android Translation Tool
	// http://code.google.com/p/android-php-translator/
	// Licensed Apache License 2.0
	// http://www.apache.org/licenses/LICENSE-2.0
	
	define('DIRECT_ACCESSIBLE', TRUE);
	include('includes/common.php');
	
	// Default language to select?
	include('includes/checklanguage.php');
	$defaultLanguage = getDefaultLanguage('aa');
	if (strlen($defaultLanguage) > 2) {
		// Remove regional identifier
		$defaultLanguage = substr($defaultLanguage, 0, 2);
	}

	$pageTitle = $appname;
	include('includes/header.php');

	echo '
	<h1>' . $appname . '</h1>';
	
	echo '
	<table>
		<tr>
			<th colspan="3">Existing translations</td>
		</tr>';
	
	$isuneven = false;
	$classuneven = ' class="uneven"';
	
	if (isset($languages)) {
		sort($languages);
		foreach ($languages as $language) {
			
			// Show the language and an edit link
			$langgroup = substr($language, 0, 2);
			echo '
		<tr' . ($isuneven? $classuneven: '') . '>
			<td>' . $language . '</td>
			<td>' . $iso639[$langgroup] . '</td>
			<td><a href="translation.php?lang=' . $language . '">Edit translation</a></td>
		</tr>';
		
			$isuneven = !$isuneven;
		}
	}
	
	echo '
		<tr>
			<td colspan="3">
				<form id="addtranslation" name="addtranslation" method="GET" action="translation.php">
					Add a new translation for: 
					<select id="lang" name="lang">';
	foreach ($iso639 as $langcode => $langname) {
		$selected = '';
		if ($defaultLanguage == $langcode) {
			$selected = ' selected="selected"';
		}
		echo '
						<option value="' . $langcode . '"' . $selected . '>' . $langcode . ' - ' . $langname . '</option>';
	}
	echo '
					</select>
					<strong>-r</strong><input type="text" id="region" name="region" /> (optional region code)
					<input type="submit" id="submit" name="submit" value="Add" />
				</form>
			</td>
		</tr>
	</table>';

	include('includes/footer.php');

?>

