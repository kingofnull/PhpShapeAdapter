# PhpShapeAdapter
A portable shape file reader and writer with complete support of dbf data and prj file and shx.
This project coded in pure php (ver5.6) and has no dependency on any special extension.
 

This library can read and write from Arc Gis shape files.

All exports translated to `EPSG:4326` because of problems of generating .prj files.
<br/>
<br/>
#####Using examples

read from new_shape.shp
```php
include "../vendor/autoload.php";

$shp = new ShpAdapter\ShapeFile\ShapeFile(); 
$shp->openFile('shp/new_shape.*');
while($r=$shp->nextRecord()){
	print_r([json_encode($r->isValid),$r->DBFData['desc'],$r->wkt]);
	echo("\n");
}
	
```

reading without `.prj` just set projection on openFile:
```
$shp->openFile('shp/new_shape.*','EPSG:4326');
```

writing a point :
```
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

```

more examples are available in `examples` directory.

<center>
<sub>@2017 MSS</sub>
</center>