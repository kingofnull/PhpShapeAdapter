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
    array('DESC', 'C', 50, 0)
));

// $record0 = new ShapeRecord(ShapeRecord::POINT_TYPE);
$record0 = new ShapeRecord(ShapeRecord::POLYGON_TYPE,$shp);
$record0->setData(['ID'=>1,'DESC'=>'dfsdfsdf saسیسشیگگلهسلام']);
$record0->setFromWkt("SRID=4326;POLYGON((-71.1031880899493 42.3152774590236,
-71.1031627617667 42.3152960829043,-71.102923838298 42.3149156848307,
-71.1023097974109 42.3151969047397,-71.1019285062273 42.3147384934248,
-71.102505233663 42.3144722937587,-71.10277487471 42.3141658254797,
-71.103113945163 42.3142739188902,-71.10324876416 42.31402489987,
-71.1033002961013 42.3140393340215,-71.1033488797549 42.3139495090772,
-71.103396240451 42.3138632439557,-71.1041521907712 42.3141153348029,
-71.1041411411543 42.3141545014533,-71.1041287795912 42.3142114839058,
-71.1041188134329 42.3142693656241,-71.1041112482575 42.3143272556118,
-71.1041072845732 42.3143851580048,-71.1041057218871 42.3144430686681,
-71.1041065602059 42.3145009876017,-71.1041097995362 42.3145589148055,
-71.1041166403905 42.3146168544148,-71.1041258822717 42.3146748022936,
-71.1041375307579 42.3147318674446,-71.1041492906949 42.3147711126569,
-71.1041598612795 42.314808571739,-71.1042515013869 42.3151287620809,
-71.1041173835118 42.3150739481917,-71.1040809891419 42.3151344119048,
-71.1040438678912 42.3151191367447,-71.1040194562988 42.3151832057859,
-71.1038734225584 42.3151140942995,-71.1038446938243 42.3151006300338,
-71.1038315271889 42.315094347535,-71.1037393329282 42.315054824985,
-71.1035447555574 42.3152608696313,-71.1033436658644 42.3151648370544,
-71.1032580383161 42.3152269126061,-71.103223066939 42.3152517403219,
-71.1031880899493 42.3152774590236))");
// die;

// $record1 = new ShpAdapter\ShapeFile\ShapeRecord(1);
// $record1->addPoint(array("x" => 472131.764567, "y" => 2143634.39608));

// $record2 = new ShpAdapter\ShapeFile\ShapeRecord(1);
// $record2->addPoint(array("x" => 492131.764567, "y" => 2143634.39608));

$shp->addRecord($record0);
// $shp->addRecord($record1);
// $shp->addRecord($record2);

$shp->setDBFHeader(
                array(
						array('ID', 'N', 8, 0),
						array('DESC', 'C', 50, 0)
					));


$shp->saveToFile('shp/new_shape.*');

echo "The ShapeFile was created.<br />\n";
echo "Return to the <a href='index.php'>index</a>.";




?>