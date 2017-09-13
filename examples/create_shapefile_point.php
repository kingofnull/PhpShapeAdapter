<pre><?php
include "../vendor/autoload.php";
use ShpAdapter\ShapeFile\ShapeFile;
use ShpAdapter\ShapeFile\ShapeRecord;


$shp = new ShapeFile(ShapeFile::POINT_TYPE , array("xmin" => 464079.002268, "ymin" => 2120153.74792, "xmax" => 505213.52849, "ymax" => 2163205.70036));

$record0 = new ShapeRecord(ShapeRecord::POINT_TYPE);
$record0->addPoint(array("x" => 54.00793, "y" => 32.24872));
$shp->addRecord($record0);

$shp->setDBFHeader(array(
						array('ID', 'N', 8, 0),
						array('DESC', 'C', 50, 0)
					));

$shp->records[0]->DBFData['ID'] = '1';
$shp->records[0]->DBFData['DESC'] = 'سلام';


$shp->saveToFile('shp/new_shape.*');

echo "The ShapeFile was created.<br />\n";
echo "Return to the <a href='index.php'>index</a>.";
?>