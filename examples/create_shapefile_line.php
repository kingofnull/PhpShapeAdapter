<pre><?php
include "../vendor/autoload.php";
use ShpAdapter\ShapeFile\ShapeFile;
use ShpAdapter\ShapeFile\ShapeRecord;


$shp = new ShapeFile(ShapeFile::POLYLINE_TYPE ,array(
    array('ID', 'N', 8, 0),
    array('DESC', 'C', 50, 0)
));

$record0 = new ShapeRecord(ShapeRecord::POLYLINE_TYPE,$shp);
$record0->setFromWkt("LINESTRING (30 10, 10 30, 40 40)");
$record0->setData(['ID'=>1,'DESC'=>'dfsdfsdf saسیسشیگگلهسلام']);
//$record0->addPoint(array("x" => 54.00793, "y" => 32.24872));
$shp->addRecord($record0);




$shp->saveToFile('shp/new_shape.*');

echo "The ShapeFile was created.<br />\n";
echo "Return to the <a href='index.php'>index</a>.";
?>