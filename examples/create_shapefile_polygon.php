<pre><?php
include "../vendor/autoload.php";
use ShpAdapter\ShapeFile\ShapeFile;
use ShpAdapter\GeoPhp\GeoPHP;
use ShpAdapter\GeoPhp\WKT;
use ShpAdapter\ShapeFile\ShapeRecord;
// use ShpAdapter\GeoPhp\WKT;
// use ShpAdapter\GeoPhp\GeoPHP;;
// $g=new GeoPHP();
// die;
// $a=new WKT();
// $r=$a->read("POLYGON((1 1,2 2,3 3,1 1))");
// print_r($r->asArray());
// die;
//$a=new WKT();
//$poly1 = GeoPHP::load('POLYGON((30 10,10 20,20 40,40 40,30 10))','wkt');
//$poly2 = GeoPHP::load('POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30, 35 35, 30 20, 20 30))','wkt');
//$combined_poly = $poly1->union($poly2);
//var_dump($combined_poly);die;
//$kml = $combined_poly->out('kml');
//
//die;



// $shp = new ShapeFile(ShapeFile::POINT_TYPE , array("xmin" => 464079.002268, "ymin" => 2120153.74792, "xmax" => 505213.52849, "ymax" => 2163205.70036));
$shp = new ShapeFile(ShapeFile::POLYGON_TYPE,array(
    array('ID', 'N', 8, 0),
    array('DESC', 'C', 80, 0)
));

// $record0 = new ShapeRecord(ShapeRecord::POINT_TYPE);
for($i=1;$i<100;$i++){
	$record0 = new ShapeRecord(ShapeRecord::POLYGON_TYPE,$shp);
	$record0->setData(['ID'=>1,'DESC'=>'dfsdfsdf saسیسشیگگلهسلام']);
	$record0->setFromWkt("POLYGON((54.0169840732894 32.2727693636786,54.016971818238 32.2726065671812,54.0169628094251 32.2726068915776,54.0169468972644 32.2723955270693,54.015736155128 32.2725365263926,54.0145254091182 32.272677513836,54.0133146613565 32.2728184894495,54.0133155617864 32.2728256587419,54.0127556208849 32.2728985687483,54.0127888042291 32.2737277011383,54.0146589435287 32.273512483627,54.014657157252 32.2735008664659,54.015833648926 32.2733682338751,54.0170101369679 32.2732355909733,54.0169750644603 32.2727696880749,54.0169840732894 32.2727693636786))");
	$shp->addRecord($record0);
}

// $shp->addRecord($record1);
// $shp->addRecord($record2);


$shp->saveToFile('shp/new_shape.*');

echo "The ShapeFile was created.<br />\n";
echo "Return to the <a href='index.php'>index</a>.";




?>