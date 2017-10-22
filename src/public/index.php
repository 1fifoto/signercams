<?php
print_r($_POST) . "<br />";
if (isset($_POST["submit"])) {
    print_r($_FILES) . "<br />";
    if (isset($_FILES["file"])) {
        $file = $_FILES["file"]["name"];
        echo "File $file selected <br />";
    } else {
        echo "No file selected <br />";
    }
}
?>

<html>
<body>
	<h1>Signer Cams</h1>
	<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post"
		enctype="multipart/form-data">
		<table>
			<tr>
				<td>Select file</td>
				<td><input type="file" name="file" id="file" /></td>
			</tr>
			<tr>
				<td>Submit</td>
				<td><input type="submit" name="submit" /></td>
			</tr>
		</table>
	</form>
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
