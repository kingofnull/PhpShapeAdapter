<pre><?php
include "../vendor/autoload.php";
use ShpAdapter\ShapeFile\ShapeFile;
use ShpAdapter\ShapeFile\ShapeRecord;


$shp = new ShapeFile(ShapeFile::POINT_TYPE ,array(
    array('ID', 'N', 8, 0),
    array('DESC', 'C', 50, 0)
));

$record0 = new ShapeRecord(ShapeRecord::POINT_TYPE,$shp);
$record0->setFromWkt("POINT (30 10)");
$record0->setData(['ID'=>1,'DESC'=>'dfsdfsdf saسیسشیگگلهسلام']);
$shp->addRecord($record0);



$shp->saveToFile('shp/new_shape.*');

echo "The ShapeFile was created.<br />\n";
echo "Return to the <a href='index.php'>index</a>.";
?>