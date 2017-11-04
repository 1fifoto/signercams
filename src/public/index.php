<?php

// ==============================================================
// Utility functions
function vector($v1, $v2)
{
    return array(
        $v2[0] - $v1[0],
        $v2[1] - $v1[1],
        $v2[2] - $v1[2]
    );
}

function dot($v1, $v2)
{
    return $v1[0] * $v2[0] + $v1[1] * $v2[1] + $v1[2] * $v2[2];
}

function cross($v1, $v2)
{
    return array(
        $v1[1] * $v2[2] - $v1[2] * $v2[1],
        $v1[2] * $v2[0] - $v1[0] * $v2[2],
        $v1[0] * $v2[1] - $v1[1] * $v2[0]
    );
}

function length($v1)
{
    return sqrt(dot($v1, $v1));
}

function unitize($v1)
{
    $length = length($v1);
    return array(
        $v1[0] / $length,
        $v1[1] / $length,
        $v1[2] / $length
    );
}

function normal($v1, $v2, $v3)
{
    $d1 = vector($v1, $v2);
    $d2 = vector($v1, $v3);
    $d3 = cross($d1, $d2);
    $d4 = unitize($d3);
    // echo "d1=" . implode(',',$d1) . " d2=" . implode(',',$d2) . " d3=" . implode(',',$d3) . " d4=" . implode(',',$d4) . "<br />";
    return $d4;
}

function order_solid_keys($i)
{
    switch ($i) {
        case "title":
            return 1;
        case "facets":
            return 2;
    }
}

function cmp_solid_keys($a, $b)
{
    return order_solid_keys($a) - order_solid_keys($b);
}

function order_facet_keys($i)
{
    switch ($i) {
        case "normal":
            return 1;
        case "vertex1":
            return 2;
        case "vertex2":
            return 3;
        case "vertex3":
            return 4;
        case "pad":
            return 5;
    }
}

function cmp_facet_keys($a, $b)
{
    return order_facet_keys($a) - order_facet_keys($b);
}

/**
 * Read CSV file containing X, Y and Z values into a PHP array
 *
 * @param string $file_name
 * @return array|NULL
 */
function read_csv_xyz($file_name)
{
//     echo "file_name="; print_r($file_name); echo "<br />";
    
    $csv = NULL;
    if (($handle = fopen($file_name, 'r')) !== FALSE) {
        
        $csv = array();
        $row_count = 0;
        
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
            
            if (is_numeric($data[0]) && is_numeric($data[1]) && is_numeric($data[2])) {
                
                // get the values from the csv
                $csv[$row_count]['x'] = $data[0];
                $csv[$row_count]['y'] = $data[1];
                $csv[$row_count]['z'] = $data[2];
                
                // inc to the next row
                $row_count ++;
            }
        }
        
        fclose($handle);
        
//         echo "csv="; print_r($csv); echo "<br />";
//         echo "row_count=" . $row_count . "<br />";
        
    }
    
    return $csv;
}

/**
 * Read STL file into a PHP STL associative array
 *
 * @param string $file_name
 * @return array|NULL
 */
function read_stl($file_name)
{
    
//     echo "file_name="; print_r($file_name); echo "<br />";
    
    $stl = NULL;
    if (($handle = fopen($file_name, 'r')) !== FALSE) {
        
        $stl = [];
        
        $title_bin = fread($handle, 80);
        $title = unpack("a*title", $title_bin);
        // echo "title="; print_r($title); echo PHP_EOL;
        $stl["title"] = $title["title"];
        
        $count_bin = fread($handle, 4);
        $count = unpack("V1count", $count_bin);
        // echo "count="; print_r($count); echo PHP_EOL;
        $stl["facets"] = [];
        
        for ($f = 0; $f < $count["count"]; $f ++) {
            
            $facet_bin = fread($handle, 50);
            $facet[] = unpack("f3normal/f3vertex1/f3vertex2/f3vertex3/v1pad", $facet_bin);
            // echo "facet[$i]="; print_r($facet); echo PHP_EOL;
            $stl["facets"][$f]["normal"] = [
                $facet[$f]["normal1"],
                $facet[$f]["normal2"],
                $facet[$f]["normal3"]
            ];
            $stl["facets"][$f]["vertex1"] = [
                $facet[$f]["vertex11"],
                $facet[$f]["vertex12"],
                $facet[$f]["vertex13"]
            ];
            $stl["facets"][$f]["vertex2"] = [
                $facet[$f]["vertex21"],
                $facet[$f]["vertex22"],
                $facet[$f]["vertex23"]
            ];
            $stl["facets"][$f]["vertex3"] = [
                $facet[$f]["vertex31"],
                $facet[$f]["vertex32"],
                $facet[$f]["vertex33"]
            ];
            $stl["facets"][$f]["pad"] = $facet[$f]["pad"];
            uksort($stl["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
        }
        uksort($stl, "cmp_solid_keys"); // order: title, facets
        
        fclose($handle);
        
//         echo "stl="; print_r($csv); echo "<br />";
        
    }
    
    return $stl;
}

/**
 * Convert CSV file to a PHP associative array of CAM STL
 *
 * @param string $file_name
 * @param array $stl
 * @param bool $generate_normals
 */
function convert_from_csv_xyz_to_stl_cam($csv, $cam, $radius, $height, $title)
{
    
//     echo "csv="; print_r($csv); echo "<br />";
//     echo "cam="; print_r($cam); echo "<br />";
//     echo "radius="; print_r($radius); echo "<br />";
//     echo "height="; print_r($height); echo "<br />";
//     echo "title="; print_r($title); echo "<br />";
    
    // Initialize PHP STL associative array with title and empty facets
    $stl = [
        "title" => $title,
        "facets" => []
    ];
    
    // For each angle around cam and each CSV row
    $r = 0; // CSV row index
    $f = 0; // Facet index
    $csv_count = count($csv);
    $deltaAngle = 360.0 / $csv_count; // angle (degrees) between each CSV row
    for ($angle1 = 0.0; $angle1 < 360.0; $angle1 += $deltaAngle) {
        $angle2 = ($angle1 + $deltaAngle) % 360.0;
        // echo "angle1=" . $angle1 . " angle2=" . $angle2 . "<br />";
        $radian1 = $angle1 * pi() / 180.0;
        $radian2 = $angle2 * pi() / 180.0;
        // echo "radian1=" . $radian1 . " radian2=" . $radian2 . "<br />";
        
        // Compute cam radius given CSV row x, y or z
        $xRadius1 = $radius + $csv[$r][$cam];
        $xRadius2 = $radius + $csv[($r + 1) % $csv_count][$cam];
        // echo "xRadius1=" . $xRadius1 . " xRadius2=" . $xRadius2 . "<br />";
        
        // Convert cam radii to cam Facet 1 - top facet
        $stl["facets"][$f]['vertex1'][0] = 0.0;
        $stl["facets"][$f]['vertex1'][1] = 0.0;
        $stl["facets"][$f]['vertex1'][2] = $height;
        $stl["facets"][$f]['vertex2'][0] = $xRadius1 * cos($radian1);
        $stl["facets"][$f]['vertex2'][1] = $xRadius1 * sin($radian1);
        $stl["facets"][$f]['vertex2'][2] = $height;
        $stl["facets"][$f]['vertex3'][0] = $xRadius2 * cos($radian2);
        $stl["facets"][$f]['vertex3'][1] = $xRadius2 * sin($radian2);
        $stl["facets"][$f]['vertex3'][2] = $height;
        $stl["facets"][$f]['normal'] = normal($stl["facets"][$f]['vertex1'], $stl["facets"][$f]['vertex2'], $stl["facets"][$f]['vertex3']);
        $stl["facets"][$f]['pad'] = 0;
        uksort($stl["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
        $f ++; // next facet
        
        // Convert cam radii to cam Facet 2 - side left
        $stl["facets"][$f]['vertex1'][0] = $xRadius1 * cos($radian1);
        $stl["facets"][$f]['vertex1'][1] = $xRadius1 * sin($radian1);
        $stl["facets"][$f]['vertex1'][2] = $height;
        $stl["facets"][$f]['vertex2'][0] = $xRadius1 * cos($radian1);
        $stl["facets"][$f]['vertex2'][1] = $xRadius1 * sin($radian1);
        $stl["facets"][$f]['vertex2'][2] = 0.0;
        $stl["facets"][$f]['vertex3'][0] = $xRadius2 * cos($radian2);
        $stl["facets"][$f]['vertex3'][1] = $xRadius2 * sin($radian2);
        $stl["facets"][$f]['vertex3'][2] = 0.0;
        $stl["facets"][$f]['normal'] = normal($stl["facets"][$f]['vertex1'], $stl["facets"][$f]['vertex2'], $stl["facets"][$f]['vertex3']);
        $stl["facets"][$f]['pad'] = 0;
        uksort($stl["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
        $f ++; // next facet
        
        // Convert cam radii to cam Facet 3 - side right
        $stl["facets"][$f]['vertex1'][0] = $xRadius1 * cos($radian1);
        $stl["facets"][$f]['vertex1'][1] = $xRadius1 * sin($radian1);
        $stl["facets"][$f]['vertex1'][2] = $height;
        $stl["facets"][$f]['vertex2'][0] = $xRadius2 * cos($radian2);
        $stl["facets"][$f]['vertex2'][1] = $xRadius2 * sin($radian2);
        $stl["facets"][$f]['vertex2'][2] = 0.0;
        $stl["facets"][$f]['vertex3'][0] = $xRadius2 * cos($radian2);
        $stl["facets"][$f]['vertex3'][1] = $xRadius2 * sin($radian2);
        $stl["facets"][$f]['vertex3'][2] = $height;
        $stl["facets"][$f]['normal'] = normal($stl["facets"][$f]['vertex1'], $stl["facets"][$f]['vertex2'], $stl["facets"][$f]['vertex3']);
        $stl["facets"][$f]['pad'] = 0;
        uksort($stl["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
        $f ++; // next facet
        
        // Convert cam radii to cam Facet 4 - bottom
        $stl["facets"][$f]['vertex1'][0] = 0.0;
        $stl["facets"][$f]['vertex1'][1] = 0.0;
        $stl["facets"][$f]['vertex1'][2] = 0.0;
        $stl["facets"][$f]['vertex2'][0] = $xRadius2 * cos($radian2);
        $stl["facets"][$f]['vertex2'][1] = $xRadius2 * sin($radian2);
        $stl["facets"][$f]['vertex2'][2] = 0.0;
        $stl["facets"][$f]['vertex3'][0] = $xRadius1 * cos($radian1);
        $stl["facets"][$f]['vertex3'][1] = $xRadius1 * sin($radian1);
        $stl["facets"][$f]['vertex3'][2] = 0.0;
        $stl["facets"][$f]['normal'] = normal($stl["facets"][$f]['vertex1'], $stl["facets"][$f]['vertex2'], $stl["facets"][$f]['vertex3']);
        $stl["facets"][$f]['pad'] = 0;
        uksort($stl["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
        $f ++; // next facet
        
        $r ++; // next CSV row
    }
    uksort($stl, "cmp_solid_keys"); // order: title, facets
    
//     echo "stl="; print_r($stl); echo PHP_EOL;
//     echo "facet count="; print_r(count($stl['facets'])); echo PHP_EOL;
    
    return $stl;
}

/**
 * Write STL file from a PHP STL associative array
 *
 * @param string $file_name
 * @param array $stl
 * @param bool $generate_normals
 */
function write_stl($file_name, $stl, $generate_normals = false)
{
//     echo "file_name="; print_r($file_name); echo "<br />";
//     echo "stl="; print_r($stl); echo "<br />";
//     echo "facet count="; print_r(count($stl['facets'])); echo PHP_EOL;
//     echo "generate_normals=" . $generate_normals . "<br />";
    
    if (($handle = fopen($file_name, 'w')) !== FALSE) {
        
        if (! isset($stl["title"])) { // If title does not exist, then create a blank one
            $stl["title"] = '';
        }
        fwrite($handle, pack("a80", $stl["title"]), 80);
        
        if (isset($stl["facets"])) { // If facets exist, then output their count and them
            
            $facet_count = count($stl['facets']);
            fwrite($handle, pack("V1", $facet_count), 4);
            
            for ($f = 0; $f < $facet_count; $f ++) {
                if ($generate_normals) {
                    $stl["facets"][$f]["normal"] = normal($stl["facets"][$f]["vertex1"], $stl["facets"][$f]["vertex2"], $stl["facets"][$f]["vertex3"]);
                }
                if (! isset($stl["facets"][$f]["pad"])) { // If pad does not exist, then create one
                    $stl["facets"][$f]["pad"] = 0;
                }
                fwrite($handle, pack("f3f3f3f3v1", $stl["facets"][$f]["normal"][0], $stl["facets"][$f]["normal"][1], $stl["facets"][$f]["normal"][2], $stl["facets"][$f]["vertex1"][0], $stl["facets"][$f]["vertex1"][1], $stl["facets"][$f]["vertex1"][2], $stl["facets"][$f]["vertex2"][0], $stl["facets"][$f]["vertex2"][1], $stl["facets"][$f]["vertex2"][2], $stl["facets"][$f]["vertex3"][0], $stl["facets"][$f]["vertex3"][1], $stl["facets"][$f]["vertex3"][2], $stl["facets"][$f]["pad"]), 50);
            }
        } else { // If facets do not exist, then output a count of zero
            
            $facet_count = 0;
            fwrite($handle, pack("V1", $facet_count), 4);
        }
        
        fclose($handle);
        
        return true;
        
    } else {
        
        return false;
        
    }
}

// ==============================================================

if (! empty($_POST["radius"])) {
    $radius = $_POST["radius"];
} else {
    $radius = 75;
}
// echo "radius=". $radius . "<br />";

if (! empty($_POST["height"])) {
    $height = $_POST["height"];
} else {
    $height = 10;
}
// echo "height=". $height . "<br />";

// echo "_FILES="; print_r($_FILES); echo "<br />";
if (isset($_FILES["file"]) && $_FILES['file']['error'] != UPLOAD_ERR_NO_FILE) {
    $infileerror = $_FILES['file']['error'];
    $infilename = basename($_FILES['file']['name']);
    $infiletype = $_FILES['file']['type'];
    $infilesize = $_FILES["file"]["size"];
    $infiletmpname = $_FILES['file']['tmp_name'];
    move_uploaded_file($infiletmpname, "../../upload/$infilename");
} elseif (! empty($_POST["infilename"])) {
    $infileerror = 0;
    $infilename = $_POST["infilename"];
    $infiletype = $_POST["infiletype"];
    $infilesize = $_POST["infilesize"];
    $infiletmpname = $_POST["infiletmpname"];
} else {
    $infileerror = UPLOAD_ERR_NO_FILE;
    $infilename = "";
    $infiletype = "";
    $infilesize = "";
    $infiletmpname = "";
}
// echo "infileerror=" . $infileerror . "<br />";
// echo "infilename=" . $infilename . "<br />";
// echo "infiletype=" . $infiletype . "<br />";
// echo "infilesize=" . $infilesize . " bytes<br />";
// echo "infiletmpname=" . $infiletmpname . "<br />";

$download = false; // download not generated
$message = "&nbsp;"; // no output message
$outfilename = ""; // no ouput file name
                   
// echo "_POST="; print_r($_POST); echo "<br />";
if (isset($_POST["cam-x"]) || isset($_POST["cam-y"]) || isset($_POST["cam-z"])) {
    
    if (! empty($_POST["cam-x"])) {
        $cam = "x";
    } elseif (! empty($_POST["cam-y"])) {
        $cam = "y";
    } elseif (! empty($_POST["cam-z"])) {
        $cam = "z";
    }
    
    // if there was an error uploading the file, or there is a no file error, or there is a previous input file name
    if ($infileerror > 0) {
        
        if ($infileerror == UPLOAD_ERR_FORM_SIZE) {
            $message = "Error: CSV file size too big.<br />";
        } else if ($infileerror == UPLOAD_ERR_NO_FILE) {
            $message = "Error: No CSV file specified.<br />";
        } else {
            $message = "Error: Unknown error '$infileerror'.<br />";
        }
    } else {
        
        // check the file is a csv
        $pathinfo = pathinfo($infilename);
        $outfilename = $pathinfo['filename'] . "_" . $cam . "_cam.stl";
        $extension = strtolower($pathinfo['extension']);
        $title = $pathinfo['filename'] . " " . $cam . " cam";
        if ($extension === 'csv' && $infiletype == 'text/csv' && $infilesize < 32768) {
            
            // Read in and parse CSV file
            if (($csv = read_csv_xyz("../../upload/" . $infilename)) != NULL) {
                
                // Verify there is some input
                if (count($csv) > 0) {
                    
                    // Convert from CSV XYZ to STL CAM
                    $stl = convert_from_csv_xyz_to_stl_cam($csv, $cam, $radius, $height, $title);
                    
                    // Write STL file from a PHP STL associative array
                    if (write_stl(__DIR__ . "/../../download/" . $outfilename, $stl)) {
                        
                        $download = true; // download generated
                        $message = "Success: Input CSV file: '$infilename' and output STL file: '$outfilename'.stl. <br />";
                        
                    } else {
                        $message = "Error: Invalid download STL file, '$infiletmpname'.<br />";
                    }
                } else {
                    $message = "Error: Empty upload CSV file, '$infiletmpname', or invalid format.<br />";
                }
            } else {
                $message = "Error: upload CSV file, '$infiletmpname', failed to open.<br />";
            }
        } else {
            $message = "Error: Invalid upload CSV file extension, '$extension', type, '$infiletype', or size, '$infilesize'.<br />";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<title>Signer Cams</title>
<!-- Required meta tags -->
<meta charset="utf-8">
<meta name="viewport"
	content="width=device-width, initial-scale=1, shrink-to-fit=no">
<?php if ($download): ?>
    <meta http-equiv="refresh"
	content="2; URL=download/<?php echo $outfilename ?>">
<?php endif; ?>
    <!-- Bootstrap CSS -->
<link rel="stylesheet"
	href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css"
	integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb"
	crossorigin="anonymous">
</head>
<body>
	<!-- Optional JavaScript -->
	<!-- jQuery first, then Popper.js, then Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
		integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
		crossorigin="anonymous"></script>
	<script
		src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js"
		integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh"
		crossorigin="anonymous"></script>
	<script
		src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js"
		integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ"
		crossorigin="anonymous"></script>
	<!-- Custom styles for this template -->
	<link href="css/sticky-footer-navbar.css" rel="stylesheet">
	<div class="container">
		<h1 class="mt-1 border border-primary rounded">
			<a href="<?php echo $_SERVER["PHP_SELF"]; ?>"><img
				src="images/SignerCamsIcon.png" alt="Signer Cams Thumbnail"
				class="img-thumbnail"></a> Signer Cams
		</h1>
		<div class="row small">
			<div class="col-sm">
				Instructions to generate Signer X, Y, and Z Cam STL files.
				<ol>
					<li>Enter Radius in mm (default 75).</li>
					<li>Enter Height in mm (default 10).</li>
					<li>Next
						<ol>
							<li>Select the "Choose File" button.</li>
							<li>In the file pop-up, select a local CSV file to upload which
								contains your initials or signature as X, Y and Z columns in mm
								and no headers.</li>
							<li>Select the "Open" button.</li>
						</ol>
					
					<li>Select either the "Generate X Cam" button, the "Generate Y Cam"
						button, or the "Generate Z Cam" button to create and download your
						Signer Cam STL file.</li>
				</ol>
			</div>
		</div>
		<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post"
			enctype="multipart/form-data">
			<fieldset>
				<legend>Signer Cams Input</legend>
				<input type="hidden" name="MAX_FILE_SIZE" value="32768" /> <input
					type="hidden" name="infilename" value="<?php echo $infilename; ?>" />
				<input type="hidden" name="infiletype"
					value="<?php echo $infiletype; ?>" /> <input type="hidden"
					name="infilesize" value="<?php echo $infilesize; ?>" /> <input
					type="hidden" name="infiletmpname"
					value="<?php echo $infiletmpname; ?>" />
				<div class="row">
					<div class="col-sm">
						<label for="radius">Radius:</label> <input type="text"
							name="radius" id="radius" value="<?php echo $radius ?>" /> mm
					</div>
				</div>
				<div class="row">
					<div class="col-sm">
						<label for="height">Height:</label> <input type="text"
							name="height" id="height" value="<?php echo $height ?>" /> mm
					</div>
				</div>
				<div class="row">
					<div class="col-sm">
						<label for="file">CSV File:</label> <input type="file" name="file"
							id="file" accept="text/csv" or accept=".csv" />
					</div>
				</div>
				<div class="row mt-3">
					<div class="col-sm">
						<input type="submit" name="cam-x" value="Generate X Cam"
							class="btn btn-outline-primary" />
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-sm">
						<input type="submit" name="cam-y" value="Generate Y Cam"
							class="btn btn-outline-primary" />
					</div>
				</div>
				<div class="row mt-1">
					<div class="col-sm">
						<input type="submit" name="cam-z" value="Generate Z Cam"
							class="btn btn-outline-primary" />
					</div>
				</div>
			</fieldset>
		</form>
		<div class="row mt-3">
			<div class="col-sm">
                <?php if (substr($message, 0, 5) == "Error") { ?>
                <div class="alert alert-danger" role="alert">
                <?php } else { ?>
                <div class="alert alert-light" role="alert">
                <?php } ?>
                    <?php echo $message ?>
                </div>
				</div>
			</div>
		</div>

</body>
<footer class="footer">
	<div class="container">
		<a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img
			alt="Creative Commons License" style="border-width: 0"
			src="https://i.creativecommons.org/l/by/4.0/88x31.png" /></a>&nbsp;This
		work is licensed under a <a rel="license"
			href="http://creativecommons.org/licenses/by/4.0/">Creative Commons
			Attribution 4.0 International License</a>.
	</div>
</footer>
</html>
