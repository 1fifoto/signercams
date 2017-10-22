<?php
if (isset($_POST["submit"])) {
    if (isset($_FILES["file"])) {
        $file=$_FILES["file"]
        echo "File $file selected <br />";
    } else {
        echo "No file selected <br />";
    }
}
?>

<html>
<body>
	<h1>Signer Cams</h1>
	<table width="600">
		<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post"
			enctype="multipart/form-data">
			<tr>
				<td width="20%">Select file</td>
				<td width="80%"><input type="file" name="file" id="file" /></td>
			</tr>
			<tr>
				<td>Submit</td>
				<td><input type="submit" name="submit" /></td>
			</tr>
		</form>
	</table>
</body>
<footer>
	<a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img
		alt="Creative Commons License" style="border-width: 0"
		src="https://i.creativecommons.org/l/by/4.0/88x31.png" /></a><br />This
	work is licensed under a <a rel="license"
		href="http://creativecommons.org/licenses/by/4.0/">Creative Commons
		Attribution 4.0 International License</a>.
</footer>
</html>
