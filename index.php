<?php

	include('includes/common.php');
	
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
		foreach ($languages as $language) {
			
			// Show the language and an edit link
			echo '
		<tr' . ($isuneven? $classuneven: '') . '>
			<td>' . $language . '</td>
			<td>' . $iso639[$language] . '</td>
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
		echo '
						<option value="' . $langcode . '">' . $langcode . ' - ' . $langname . '</option>';
	}
	echo '
					</select>
					<input type="submit" id="submit" name="submit" value="Add" />
				</form>
			</td>
		</tr>
	</table>';
	
	include('includes/footer.php');

?>

