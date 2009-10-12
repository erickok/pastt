<?php

	include('includes/common.php');
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $appname; ?></title>
	<style type="text/css">
		@import url("includes/default.css");
	</style>
</head>

<body>

<?php

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
					Add a new translation for <a href="http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes">ISO-639</a> code: 
					<input type="text" id="lang" name="lang" value="" />
					<input type="submit" id="submit" name="submit" value="Add" />
				</form>
			</td>
		</tr>
	</table>';
?>

</body>
</html>

