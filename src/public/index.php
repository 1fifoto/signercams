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

function length($v1) {
    return sqrt(dot($v1,$v1));
}

function unitize($v1)
{
    $length = length($v1);
    return array($v1[0]/$length, $v1[1]/$length, $v1[2]/$length);
}

function normal($v1, $v2, $v3)
{
    $d1 = vector($v1, $v2);
    $d2 = vector($v1, $v3);
    $d3 = cross($d1, $d2);
    $d4 = unitize($d3);
//     echo "d1=" . implode(',',$d1) . " d2=" . implode(',',$d2) . " d3=" . implode(',',$d3) . " d4=" . implode(',',$d4) . "<br />";
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

// ==============================================================
$download=false; // download not generated
$message = ""; // no output message
$filename = ""; // no file name

if (!empty($_POST["radius"])) {
    $radius = $_POST["radius"];
} else {
    $radius = 75;
}
// echo "radius=". $radius . "<br />";

if (!empty($_POST["height"])) {
    $height = $_POST["height"];
} else {
    $height = 10;
}
// echo "height=". $height . "<br />";

// echo "_POST="; print_r($_POST); echo "<br />";
if (isset($_POST["submit"])) {
    
//     echo "_FILES="; print_r($_FILES); echo "<br />";
    if (isset($_FILES["file"])) {
        
        //if there was an error uploading the file
        if ($_FILES["file"]["error"] > 0) {
            
            if ($_FILES["file"]["error"] == UPLOAD_ERR_FORM_SIZE) {
                $message = "File too big. <br />";
            } else if ($_FILES["file"]["error"] == UPLOAD_ERR_NO_FILE) {
                $message = "No file specified. <br />";
            } else {
                $message = "Return Code: " . $_FILES["file"]["error"] . "<br />";
            }
            
        } else {
            
            // Print file details
//             echo "Upload: " . $_FILES["file"]["name"] . "<br />";
//             echo "Type: " . $_FILES["file"]["type"] . "<br />";
//             echo "Size: " . ($_FILES["file"]["size"]) . " bytes<br />";
//             echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
            
            $pathinfo = pathinfo($_FILES['file']['name']);
            $filename = $pathinfo['filename'];
            $extension = strtolower($pathinfo['extension']);
            $type = $_FILES['file']['type'];
            $size = $_FILES["file"]["size"];
            $tmpName = $_FILES['file']['tmp_name'];
            
            // check the file is a csv
            if($extension === 'csv' && $type == 'text/csv' && $size < 32768){
                
                // Read in and parse CSV file
                $csv = array();
                if(($handle = fopen($tmpName, 'r')) !== FALSE) {
                    $row_count = 0;
                    while(($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                        // get the values from the csv
                        $csv[$row_count]['x'] = $data[0];
                        $csv[$row_count]['y'] = $data[1];
                        $csv[$row_count]['z'] = $data[2];
                        // inc the row
                        $row_count++;
                    }
                    fclose($handle);
                }
//                 echo "csv="; print_r($csv); echo "<br />";
//                 echo "row_count=" . $row_count . "<br />";
                
                // Inialized PHP associative array
                $solid = [
                    "title" => "Cam X\0",
                    "facets" => []
                ];
                
                // For x, then y then z
                
                // For each angle around cam and each CSV row
                $r = 0; // CSV row index
                $f = 0; // Facet index
                $deltaAngle = 360.0/$row_count; // angle (degrees) between each CSV row
                for ($angle1 = 0.0; $angle1 < 360.0; $angle1 += $deltaAngle) {
                    $angle2 = ($angle1+$deltaAngle) % 360.0;
//                     echo "angle1=" . $angle1 . " angle2=" . $angle2 . "<br />";
                    $radian1 = $angle1*pi()/180.0;
                    $radian2 = $angle2*pi()/180.0;
//                     echo "radian1=" . $radian1 . " radian2=" . $radian2 . "<br />";
                    
                    // Compute cam radius given CSV row x, y or z
                    $xRadius1 = $radius + $csv[$r]['x'];
                    $xRadius2 = $radius + $csv[($r+1) % $row_count]['x'];
//                     echo "xRadius1=" . $xRadius1 . " xRadius2=" . $xRadius2 . "<br />";
                
                    // Convert cam radii to cam Facet 1 - top facet
                    $solid["facets"][$f]['vertex1'][0] = 0.0;
                    $solid["facets"][$f]['vertex1'][1] = 0.0;
                    $solid["facets"][$f]['vertex1'][2] = $height;
                    $solid["facets"][$f]['vertex2'][0] = $xRadius1 * cos($radian1);
                    $solid["facets"][$f]['vertex2'][1] = $xRadius1 * sin($radian1);
                    $solid["facets"][$f]['vertex2'][2] = $height;
                    $solid["facets"][$f]['vertex3'][0] = $xRadius2 * cos($radian2);
                    $solid["facets"][$f]['vertex3'][1] = $xRadius2 * sin($radian2);
                    $solid["facets"][$f]['vertex3'][2] = $height;
                    $solid["facets"][$f]['normal'] = normal($solid["facets"][$f]['vertex1'], $solid["facets"][$f]['vertex2'], $solid["facets"][$f]['vertex3']);
                    $solid["facets"][$f]['pad'] = 0;
                    uksort($solid["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
                    $f++; // next facet
                    
                    // Convert cam radii to cam Facet 2 - side left
                    $solid["facets"][$f]['vertex1'][0] = $xRadius1 * cos($radian1);
                    $solid["facets"][$f]['vertex1'][1] = $xRadius1 * sin($radian1);
                    $solid["facets"][$f]['vertex1'][2] = $height;
                    $solid["facets"][$f]['vertex2'][0] = $xRadius1 * cos($radian1);
                    $solid["facets"][$f]['vertex2'][1] = $xRadius1 * sin($radian1);
                    $solid["facets"][$f]['vertex2'][2] = 0.0;
                    $solid["facets"][$f]['vertex3'][0] = $xRadius2 * cos($radian2);
                    $solid["facets"][$f]['vertex3'][1] = $xRadius2 * sin($radian2);
                    $solid["facets"][$f]['vertex3'][2] = 0.0;
                    $solid["facets"][$f]['normal'] = normal($solid["facets"][$f]['vertex1'], $solid["facets"][$f]['vertex2'], $solid["facets"][$f]['vertex3']);
                    $solid["facets"][$f]['pad'] = 0;
                    uksort($solid["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
                    $f++; // next facet
                    
                    // Convert cam radii to cam Facet 3 - side right
                    $solid["facets"][$f]['vertex1'][0] = $xRadius1 * cos($radian1);
                    $solid["facets"][$f]['vertex1'][1] = $xRadius1 * sin($radian1);
                    $solid["facets"][$f]['vertex1'][2] = $height;
                    $solid["facets"][$f]['vertex2'][0] = $xRadius2 * cos($radian2);
                    $solid["facets"][$f]['vertex2'][1] = $xRadius2 * sin($radian2);
                    $solid["facets"][$f]['vertex2'][2] = 0.0;
                    $solid["facets"][$f]['vertex3'][0] = $xRadius2 * cos($radian2);
                    $solid["facets"][$f]['vertex3'][1] = $xRadius2 * sin($radian2);
                    $solid["facets"][$f]['vertex3'][2] = $height;
                    $solid["facets"][$f]['normal'] = normal($solid["facets"][$f]['vertex1'], $solid["facets"][$f]['vertex2'], $solid["facets"][$f]['vertex3']);
                    $solid["facets"][$f]['pad'] = 0;
                    uksort($solid["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
                    $f++; // next facet
                    
                    // Convert cam radii to cam Facet 4 - bottom
                    $solid["facets"][$f]['vertex1'][0] = 0.0;
                    $solid["facets"][$f]['vertex1'][1] = 0.0;
                    $solid["facets"][$f]['vertex1'][2] = 0.0;
                    $solid["facets"][$f]['vertex2'][0] = $xRadius2 * cos($radian2);
                    $solid["facets"][$f]['vertex2'][1] = $xRadius2 * sin($radian2);
                    $solid["facets"][$f]['vertex2'][2] = 0.0;
                    $solid["facets"][$f]['vertex3'][0] = $xRadius1 * cos($radian1);
                    $solid["facets"][$f]['vertex3'][1] = $xRadius1 * sin($radian1);
                    $solid["facets"][$f]['vertex3'][2] = 0.0;
                    $solid["facets"][$f]['normal'] = normal($solid["facets"][$f]['vertex1'], $solid["facets"][$f]['vertex2'], $solid["facets"][$f]['vertex3']);
                    $solid["facets"][$f]['pad'] = 0;
                    uksort($solid["facets"][$f], "cmp_facet_keys"); // order: normal, vertex1, vertex2, vertex3, pad
                    $f++; // next facet
                    
                    $r++; // next CSV row
                }
                uksort($solid, "cmp_solid_keys"); // order: title, facets
                
//                 echo "solid="; print_r($solid); echo PHP_EOL;
//                 echo "facet count="; print_r(count($solid['facets'])); echo PHP_EOL;
                
                // End for x, then y then z
                
                // Write STL file from a PHP associative array
                
                $file = fopen("../../download/".$filename.".stl", "w+");
                
                fwrite($file, pack("a80", $solid["title"]), 80);
                $facet_count = count($solid['facets']);
                fwrite($file, pack("V1", $facet_count), 4);
                
                for ($f = 0; $f < $facet_count; $f ++) {
                    
                    fwrite($file, pack("f3f3f3f3v1", $solid["facets"][$f]["normal"][0], $solid["facets"][$f]["normal"][1], $solid["facets"][$f]["normal"][2], $solid["facets"][$f]["vertex1"][0], $solid["facets"][$f]["vertex1"][1], $solid["facets"][$f]["vertex1"][2], $solid["facets"][$f]["vertex2"][0], $solid["facets"][$f]["vertex2"][1], $solid["facets"][$f]["vertex2"][2], $solid["facets"][$f]["vertex3"][0], $solid["facets"][$f]["vertex3"][1], $solid["facets"][$f]["vertex3"][2], $solid["facets"][$f]["pad"]), 50);
                }
                
                fclose($file);
                
                $download = true; // download generated
                
            } else {
                $message = "Invalid file extension, type or size. <br />";
            }
            
        }
    } else {
        $message = "No file selected. <br />";
    }
}
?>
    
<!-- Output webpage or output download -->
<html>
<?php if ($download): ?>
<head>
    <meta http-equiv="refresh" content="2; URL=../../download/<?php echo $filename ?>.stl">
</head>
<?php endif; ?>
<body>
	<h1>Signer Cams</h1>
	<p><?php echo $message ?><p>
	<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post"
		enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="32768" />
		<table>
			<tr>
				<td>Radius</td>
				<td><input type="text" name="radius" id="radius" value="<?php echo $radius ?>"/></td>
			</tr>
			<tr>
				<td>Height</td>
				<td><input type="text" name="height" id="height" value="<?php echo $height ?>"/></td>
			</tr>
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
