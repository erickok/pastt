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
	<div class="container">
		<h3>' . $appname . '</h3>
		<h4>Existing translations</h4>
		<table class="table table-striped">';
	
	if (isset($languages)) {
		sort($languages);
		foreach ($languages as $language) {
			
			// Show the language and an edit link
			$langgroup = substr($language, 0, 2);
			echo '
			<tr>
				<td>' . $language . '</td>
				<td>' . $iso639[$langgroup] . '</td>
				<td><a class="btn btn-sm btn-primary" href="translation.php?lang=' . $language . '">Edit</a></td>
			</tr>';
		}
	}
	
	echo '
		</table>
		<div class="well">
			<form class="form-inline" id="addtranslation" name="addtranslation" method="GET" action="translation.php">
				<div class="form-group">
					<label for="lang">Add a new translation</label>
					<select class="form-control" id="lang" name="lang">';
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
				</div>
				<div class="form-group">
					<label for="region">-r</label>
					<input class="form-control" type="text" id="region" name="region" placeholder="Region code (optional)" />
				</div>
				<input class="btn btn-primary" type="submit" id="submit" name="submit" value="Add" />
			</form>
		</div>
	</div>';

	include('includes/footer.php');

?>

