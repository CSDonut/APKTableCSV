<?php
header("Access-Control-Allow-Origin: *");
// Database configuration
include 'config.php';

// Open a connection to a MySQL server
$connection = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($connection->connect_error) {
  exit('Error connecting to database');
}

// Select all the rows in the markers table
$striqrID = htmlspecialchars($_GET["ID"]); //Grabs ID from URL 

//Prepare and bind to prevent sql injection 
$query = $connection->prepare("SELECT n.net_id, s.netcode,s.sta_code, s.stationname, s.latitude, s.longitude, a.epidist, a.fdist, a.grdacc,a.apkv2,a.vpk, a.dpk, a.sa03, a.sa1, a.sa3,a.stracc FROM stations s,apk_table a, network n where a.iqrid=? and a.sta_code=s.sta_code and s.netcode=n.netcode order by epidist");
$query->bind_param("s", $striqrID);

$query->execute();
$result = $query->get_result();
if($result->num_rows === 0)
  exit("Results is empty");


if($result->num_rows > 0){
    $delimiter = ",";
    $filename = $striqrID ."_recordsummary_" . date('Y-m-d') . ".csv";
    
    //create a file pointer
    $f = fopen('php://memory', 'w');
    
    //set column headers
    $fields = array('Network ID', 'Network Name', 'Station Number', 'Station Name', 'Lat', 'Long', 'Epic Dist', 'Fault Dist', 'PGAv1(g)', 'PGAv2(g)', 'PGV(cm/s)', 'PGD(cm)', 'Sa (g) .3sec', 'Sa (g) 1sec', 'Sa (g) 3sec', 'Struct Apk(g)');
    fputcsv($f, $fields, $delimiter); // Format line as CSV and write to file pointer
    
    //output each row of the data, format line as csv and write to file pointer
    while($row = $result->fetch_assoc()){
      if($row['stracc'] == -9999)
        $straccVar = "             -  -";
    
      else
        $straccVar = $row['stracc'];
        
      $lineData = array($row['net_id'], $row['netcode'], $row['sta_code'], $row['stationname'], $row['latitude'], $row['longitude'], $row['epidist'], $row['fdist'], $row['grdacc'], $row['apkv2'], $row['vpk'], $row['dpk'], $row['sa03'], $row['sa1'], $row['sa3'], $straccVar);
      fputcsv($f, $lineData, $delimiter);
    }
    
    //move back to beginning of file
    fseek($f, 0);
    
    //set headers to download file rather than displayed
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    //output all remaining data on a file pointer
    fpassthru($f);
}
exit;
?>


