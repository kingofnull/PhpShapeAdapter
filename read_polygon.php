<pre><?php
// require_once('ShapeFile.lib.php');
include "vendor/autoload.php";

 
// $page1 = new ShpAdapter\Proj4\Common();
// die;

// use \lib\shapeFile\ShapeFile;

$shp = new ShpAdapter\ShapeFile\ShapeFile(1); 
$shp->openFile('shp/new_shape.*');
while($r=$shp->nextRecord()){
	print_r([json_encode($r->isValid),$r->DBFData,$r->wkt]);
	echo("\n");
	// break;
	// break;
}
	


die;
$i = 1;
foreach($shp->records as $record){
   echo "<pre>";
   echo "Record No. $i:\n\n\n";
   echo "SHP Data = ";
   print_r($record->SHPData);   //All the data related to the poligon
   print_r("\n\n\n");
   echo "DBF Data = ";
   print_r($record->DBFData);   //All the information related to each poligon
   print_r("\n\n\n");
   echo "</pre>";
   $i++;
   break;
}

echo "The ShapeFile was completely readed.<br />\n";
echo "Return to the <a href='index.php'>index</a>.";
?>