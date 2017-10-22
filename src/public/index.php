<?php
if (isset($_POST["submit"])) {
    if (isset($_FILES["file"])) {
        //if there was an error uploading the file
        if ($_FILES["file"]["error"] > 0) {
            echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
        }
        else {
            //Print file details
            echo "Upload: " . $_FILES["file"]["name"] . "<br />";
            echo "Type: " . $_FILES["file"]["type"] . "<br />";
            echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
            echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
            
//             //if file already exists
//             if (file_exists("upload/" . $_FILES["file"]["name"])) {
//                 echo $_FILES["file"]["name"] . " already exists. ";
//             }
//             else {
//                 //Store file in directory "upload" with the name of "uploaded_file.txt"
//                 $storagename = "uploaded_file.txt";
//                 move_uploaded_file($_FILES["file"]["tmp_name"], "../../upload/" . $storagename);
//                 echo "Stored in: " . "upload/" . $_FILES["file"]["name"] . "<br />";
//             }

            $csv = array();
            $name = $_FILES['file']['name'];
            $ext = strtolower(end(explode('.', $_FILES['file']['name'])));
            $type = $_FILES['file']['type'];
            $tmpName = $_FILES['file']['tmp_name'];
            // check the file is a csv
            if($ext === 'csv'){
                if(($handle = fopen($tmpName, 'r')) !== FALSE) {
                    $row = 0;
                    while(($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
//                         // number of fields in the csv
//                         $col_count = count($data);
                        // get the values from the csv
                        $csv[$row]['x'] = $data[0];
                        $csv[$row]['y'] = $data[1];
                        $csv[$row]['z'] = $data[2];
                        // inc the row
                        $row++;
                    }
                    fclose($handle);
                }
            }
            print_r($csv);
        }
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
