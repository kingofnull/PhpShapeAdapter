<?php

namespace ShpAdapter\ShapeFile;

use ShpAdapter\GeoPhp\WKT;
use \ShpAdapter\XBase\Record;

/**
 * Class ShapeRecord
 * @package ShpAdapter\ShapeFile
 * @property ShapeFile $parent
 */
class ShapeRecord
{
    var $SHPFile = NULL;
    var $DBFFile = NULL;
    var $parent;

    var $recordNumber = NULL;
    var $shapeType = NULL;

    var $lastError = "";

    var $SHPData = array();
    var $DBFData = array();

    function __construct($shapeType,&$parent)
    {
        $this->shapeType = $shapeType;
        $this->parent=$parent;
    }


    const NULL_TYPE = 0;
    const POINT_TYPE = 1;
    const POLYLINE_TYPE = 3;
    const POLYGON_TYPE = 5;
    const MULTIPOINT_TYPE = 8;

    function loadFromFile(&$SHPFile, &$DBFFile)
    {
        $this->SHPFile = $SHPFile;
        $this->DBFFile = $DBFFile;
        $this->_loadHeaders();
        // die(json_encode($this->shapeType));
        switch ($this->shapeType) {
            case 0:
                $this->_loadNullRecord();
                break;
            case 1:
                $this->_loadPointRecord();
                break;
            case 3:
                $this->_loadPolyLineRecord();
                break;
            case 5:
                $this->_loadPolygonRecord();
                break;
            case 8:
                $this->_loadMultiPointRecord();
                break;
            default:
                $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
                break;
        }
        $this->_loadDBFData();
    }

    function saveToFile(&$SHPFile, &$DBFFile, $recordNumber)
    {
        $this->SHPFile = $SHPFile;
        $this->DBFFile = $DBFFile;
        $this->recordNumber = $recordNumber;
        $this->_saveHeaders();

        switch ($this->shapeType) {
            case 0:
                $this->_saveNullRecord();
                break;
            case 1:
                $this->_savePointRecord();
                break;
            case 3:
                $this->_savePolyLineRecord();
                break;
            case 5:
                $this->_savePolygonRecord();
                break;
            case 8:
                $this->_saveMultiPointRecord();
                break;
            default:
                $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
                break;
        }
        $this->_saveDBFData();
    }

    function updateDBFInfo($header)
    {
        $tmp = $this->DBFData;
        unset($this->DBFData);
        $this->DBFData = array();
        reset($header);
        while (list($key, $value) = each($header)) {
            $this->DBFData[$value[0]] = (isset($tmp[$value[0]])) ? $tmp[$value[0]] : "";
        }
    }

    function _loadHeaders()
    {
        $this->recordNumber = Util::loadData("N", fread($this->SHPFile, 4));
        $tmp = Util::loadData("N", fread($this->SHPFile, 4)); //We read the length of the record
        $this->shapeType = Util::loadData("V", fread($this->SHPFile, 4));
    }

    function _saveHeaders()
    {
        fwrite($this->SHPFile, pack("N", $this->recordNumber));
        fwrite($this->SHPFile, pack("N", $this->getContentLength()));
        // var_dump($this->shapeType);die;
        fwrite($this->SHPFile, pack("V", $this->shapeType));
    }

    function _loadPoint()
    {
        $data = array();

        $data["x"] = Util::loadData("d", fread($this->SHPFile, 8));
        $data["y"] = Util::loadData("d", fread($this->SHPFile, 8));
//        var_export($this->parent->readPrj->getDefData());

        $pointSrc = new \ShpAdapter\Proj4\Point($data["x"], $data["y"], $this->parent->readPrj);
        $pointDest = $this->parent->prj4->transform($this->parent->writePrj,$pointSrc);
        $coords=($pointDest->toArray());

        $data["x"] =$coords[0];
        $data["y"] =$coords[1];

        $this->isValid = true;
        $this->wkt = "POINT({$data["x"]} {$data["y"]})";


        return $data;
    }

    function _savePoint($data)
    {
        if (isset($data['x'])) {
            fwrite($this->SHPFile, Util::packDouble($data["x"]));
            fwrite($this->SHPFile, Util::packDouble($data["y"]));
        } else {
            fwrite($this->SHPFile, Util::packDouble($data[0]));
            fwrite($this->SHPFile, Util::packDouble($data[1]));
        }

    }

    function _loadNullRecord()
    {
        $this->SHPData = array();
    }

    function _saveNullRecord()
    {
        //Don't save anything
    }

    function _loadPointRecord()
    {
        $this->SHPData = $this->_loadPoint();
    }

    function _savePointRecord()
    {
        $this->_savePoint($this->SHPData);
    }

    function _loadMultiPointRecord()
    {
        $this->SHPData = array();
        $this->SHPData["xmin"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->SHPData["ymin"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->SHPData["xmax"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->SHPData["ymax"] = Util::loadData("d", fread($this->SHPFile, 8));

        $this->SHPData["numpoints"] = Util::loadData("V", fread($this->SHPFile, 4));

        for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
            $this->SHPData["points"][] = $this->_loadPoint();
        }
    }

    function _saveMultiPointRecord()
    {
        fwrite($this->SHPFile, pack("dddd", $this->SHPData["xmin"], $this->SHPData["ymin"], $this->SHPData["xmax"], $this->SHPData["ymax"]));

        fwrite($this->SHPFile, pack("V", $this->SHPData["numpoints"]));

        for ($i = 0; $i <= $this->SHPData["numpoints"]; $i++) {
            $this->_savePoint($this->SHPData["points"][$i]);
        }
    }

    function _loadPolyLineRecord()
    {
        $this->SHPData = array();
        $this->SHPData["xmin"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->SHPData["ymin"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->SHPData["xmax"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->SHPData["ymax"] = Util::loadData("d", fread($this->SHPFile, 8));

        $this->SHPData["numparts"] = Util::loadData("V", fread($this->SHPFile, 4));
        $this->SHPData["numpoints"] = Util::loadData("V", fread($this->SHPFile, 4));

        for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
            $this->SHPData["parts"][$i] = Util::loadData("V", fread($this->SHPFile, 4));
        }

        $firstIndex = ftell($this->SHPFile);
        $readPoints = 0;
        reset($this->SHPData["parts"]);
        while (list($partIndex, $partData) = each($this->SHPData["parts"])) {
            if (!isset($this->SHPData["parts"][$partIndex]["points"]) || !is_array($this->SHPData["parts"][$partIndex]["points"])) {
                $this->SHPData["parts"][$partIndex] = array();
                $this->SHPData["parts"][$partIndex]["points"] = array();
            }
            while (!in_array($readPoints, $this->SHPData["parts"]) && ($readPoints < ($this->SHPData["numpoints"])) && !feof($this->SHPFile)) {
                $this->SHPData["parts"][$partIndex]["points"][] = $this->_loadPoint();
                $readPoints++;
            }
        }


        $this->isMulti = $this->SHPData["numparts"] > 1;


        if ($this->isMulti) {
            $rr = [];
            foreach ($this->SHPData["parts"] as $part) {
                $r = [];
                // print_r($this->SHPData["parts"]);
                foreach ($this->SHPData["parts"][0]['points'] as $p) {

                    $r[] = "{$p['x']} {$p['y']}";
                }

                $this->isValid = count($r) > 4 && $r[0] == $r[count($r) - 1];
                $rr[] = '(' . implode(', ', $r) . ')';
            }

            $this->wkt = 'MultiLineString(' . implode(',', $rr) . ')';
        } else {
//            print_r($this->SHPData["parts"]);
            $r = [];
            // print_r($this->SHPData["parts"]);
            foreach ($this->SHPData["parts"][0]['points'] as $p) {

                $r[] = "{$p['x']} {$p['y']}";
            }

            $this->isValid = count($r) > 4 && $r[0] == $r[count($r) - 1];
            $this->wkt = 'LineString(' . implode(',', $r) . ')';
        }

        fseek($this->SHPFile, $firstIndex + ($readPoints * 16));
    }

    function _savePolyLineRecord()
    {
        fwrite($this->SHPFile, pack("dddd", $this->SHPData["xmin"], $this->SHPData["ymin"], $this->SHPData["xmax"], $this->SHPData["ymax"]));

        fwrite($this->SHPFile, pack("VV", $this->SHPData["numparts"], $this->SHPData["numpoints"]));

        for ($i = 0; $i < $this->SHPData["numparts"]; $i++) {
            fwrite($this->SHPFile, pack("V", count($this->SHPData["parts"][$i])));
        }

        reset($this->SHPData["parts"]);
        foreach ($this->SHPData["parts"] as $partData) {
            reset($partData["points"]);
            while (list($pointIndex, $pointData) = each($partData["points"])) {
                $this->_savePoint($pointData);
            }
        }
    }


    function _loadPolygonRecord()
    {
        $this->_loadPolyLineRecord();
        // var_dump($this->SHPData["parts"]);die;


        $this->isMulti = $this->SHPData["numparts"] > 1;

        if ($this->isMulti) {
            $rr = [];
            foreach ($this->SHPData["parts"] as $part) {
                $r = [];
                // print_r($this->SHPData["parts"]);
                foreach ($this->SHPData["parts"][0]['points'] as $p) {

                    $r[] = "{$p['x']} {$p['y']}";
                }

                $this->isValid = count($r) > 4 && $r[0] == $r[count($r) - 1];
                $rr[] = '(' . implode(', ', $r) . ')';
            }

            $this->wkt = 'MULTIPOLYGON(' . implode(',', $rr) . ')';
        } else {
            $r = [];
            // print_r($this->SHPData["parts"]);
            foreach ($this->SHPData["parts"][0]['points'] as $p) {

                $r[] = "{$p['x']} {$p['y']}";
            }

            $this->isValid = count($r) > 4 && $r[0] == $r[count($r) - 1];
            $this->wkt = 'POLYGON((' . implode(',', $r) . '))';
        }
    }

    function _savePolygonRecord()
    {
        $this->_savePolyLineRecord();
    }


    function setFromWkt($wkt,$sourceProjection='EPSG:4326')
    {
        $a = new WKT();
        $this->geometry = $a->read($wkt);
        $this->envelope = $this->geometry->envelope();
        $this->extend = $this->envelope->getBBox();
        if($srid=$this->geometry->getSRID()){
            $sourceProjection='EPSG:'.$srid;
        }

        $srcPrj    = new \ShpAdapter\Proj4\Proj($sourceProjection, $this->parent->prj4);

        $eMinPoint = new \ShpAdapter\Proj4\Point($this->extend['minx'],  $this->extend['miny'], $srcPrj);
        $eMinPoint = $this->parent->prj4->transform($this->parent->writePrj,$eMinPoint)->toArray();
        $this->SHPData["xmin"] = $eMinPoint[0];
        $this->SHPData["ymin"] = $eMinPoint[0];

        $eMaxPoint = new \ShpAdapter\Proj4\Point($this->extend['maxx'],  $this->extend['maxy'], $srcPrj);
        $eMaxPoint = $this->parent->prj4->transform($this->parent->writePrj,$eMaxPoint)->toArray();
        $this->SHPData["xmax"] = $eMaxPoint[0];
        $this->SHPData["ymax"] = $eMaxPoint[1];

        //var_dump($extend);die;

        switch ($this->shapeType) {

            case self::POINT_TYPE:
                $this->addPoint($this->geometry->asArray());
                break;

            //line string simbol!
            case self::POLYLINE_TYPE:
//                var_dump($g->asArray());die;
                $this->SHPData["parts"][0]['points'] = $this->geometry->asArray();
                $this->SHPData["numpoints"] = count($this->SHPData["parts"][0]['points']);
                $this->SHPData["numparts"] = 1;
                break;

            case self::POLYGON_TYPE:
                $this->SHPData["parts"][0]['points'] = $this->geometry->asArray()[0];
                $this->SHPData["numpoints"] = count($this->SHPData["parts"][0]['points']);
                $this->SHPData["numparts"] = 1;
                // var_dump($wkt);
                break;
            default:
                throw new Exception("invalid shape type");
        }

//        var_dump($this->parent->prj4);


        if(!empty($this->SHPData["parts"])){
            foreach ( $this->SHPData["parts"][0]['points'] as &$point){
                $pointSrc = new \ShpAdapter\Proj4\Point($point[0], $point[1], $srcPrj);
//            var_dump($pointSrc->getProjection());
// Transform the point between datums.
                $pointDest = $this->parent->prj4->transform($this->parent->writePrj,$pointSrc);
                $coords=($pointDest->toArray());
                unset($coords[2]);
                $point=$coords;
            }
        }



//        $pointSrc = new \ShpAdapter\Proj4\Point(652709.401, 6859290.946, $projL93);
// Transform the point between datums.
//        $pointDest = $p->transform($p2, $pointSrc);
    }

    function addPoint($point, $partIndex = 0)
    {
        switch ($this->shapeType) {
            case 0:
                //Don't add anything
                break;
            case 1:
                //Substitutes the value of the current point
                $this->SHPData = $point;
                break;
            case 3:
            case 5:
                //Adds a new point to the selected part
                if (!isset($this->SHPData["xmin"]) || ($this->SHPData["xmin"] > $point["x"])) $this->SHPData["xmin"] = $point["x"];
                if (!isset($this->SHPData["ymin"]) || ($this->SHPData["ymin"] > $point["y"])) $this->SHPData["ymin"] = $point["y"];
                if (!isset($this->SHPData["xmax"]) || ($this->SHPData["xmax"] < $point["x"])) $this->SHPData["xmax"] = $point["x"];
                if (!isset($this->SHPData["ymax"]) || ($this->SHPData["ymax"] < $point["y"])) $this->SHPData["ymax"] = $point["y"];

                $this->SHPData["parts"][$partIndex]["points"][] = $point;

                $this->SHPData["numparts"] = count($this->SHPData["parts"]);
                $this->SHPData["numpoints"]++;
                break;
            case 8:
                //Adds a new point
                if (!isset($this->SHPData["xmin"]) || ($this->SHPData["xmin"] > $point["x"])) $this->SHPData["xmin"] = $point["x"];
                if (!isset($this->SHPData["ymin"]) || ($this->SHPData["ymin"] > $point["y"])) $this->SHPData["ymin"] = $point["y"];
                if (!isset($this->SHPData["xmax"]) || ($this->SHPData["xmax"] < $point["x"])) $this->SHPData["xmax"] = $point["x"];
                if (!isset($this->SHPData["ymax"]) || ($this->SHPData["ymax"] < $point["y"])) $this->SHPData["ymax"] = $point["y"];

                $this->SHPData["points"][] = $point;
                $this->SHPData["numpoints"]++;
                break;
            default:
                $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
                break;
        }
    }

    function deletePoint($pointIndex = 0, $partIndex = 0)
    {
        switch ($this->shapeType) {
            case 0:
                //Don't delete anything
                break;
            case 1:
                //Sets the value of the point to zero
                $this->SHPData["x"] = 0.0;
                $this->SHPData["y"] = 0.0;
                break;
            case 3:
            case 5:
                //Deletes the point from the selected part, if exists
                if (isset($this->SHPData["parts"][$partIndex]) && isset($this->SHPData["parts"][$partIndex]["points"][$pointIndex])) {
                    for ($i = $pointIndex; $i < (count($this->SHPData["parts"][$partIndex]["points"]) - 1); $i++) {
                        $this->SHPData["parts"][$partIndex]["points"][$i] = $this->SHPData["parts"][$partIndex]["points"][$i + 1];
                    }
                    unset($this->SHPData["parts"][$partIndex]["points"][count($this->SHPData["parts"][$partIndex]["points"]) - 1]);

                    $this->SHPData["numparts"] = count($this->SHPData["parts"]);
                    $this->SHPData["numpoints"]--;
                }
                break;
            case 8:
                //Deletes the point, if exists
                if (isset($this->SHPData["points"][$pointIndex])) {
                    for ($i = $pointIndex; $i < (count($this->SHPData["points"]) - 1); $i++) {
                        $this->SHPData["points"][$i] = $this->SHPData["points"][$i + 1];
                    }
                    unset($this->SHPData["points"][count($this->SHPData["points"]) - 1]);

                    $this->SHPData["numpoints"]--;
                }
                break;
            default:
                $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
                break;
        }
    }

    function getContentLength()
    {
        switch ($this->shapeType) {
            case 0:
                $result = 0;
                break;
            case 1:
                $result = 10;
                break;
            case 3:
            case 5:
                $result = 22 + 2 * count($this->SHPData["parts"]);
                for ($i = 0; $i < count($this->SHPData["parts"]); $i++) {
                    $result += 8 * count($this->SHPData["parts"][$i]["points"]);
                }
                break;
            case 8:
                $result = 20 + 8 * count($this->SHPData["points"]);
                break;
            default:
                $result = false;
                $this->setError(sprintf("The Shape Type '%s' is not supported.", $this->shapeType));
                break;
        }
        return $result;
    }

    function _loadDBFData()
    {


        // $this->DBFData = @dbase_get_record_with_names($this->DBFFile, $this->recordNumber);
        // while ($record = $table->nextRecord()) {
        $data = [];
        $record = $this->DBFFile->nextRecord();
        // var_dump($record->getString);
        if ($record) {
            foreach ($record->getColumns() as $c) {
                // $data[$c->name]=$record>$($c->name);
                $data[$c->name] = iconv('WINDOWS-1256', 'UTF-8',$record->getObject($c));
            }

            // var_dump($data);die;
            $this->DBFData = $data;
            // }
        }


        unset($this->DBFData["deleted"]);
    }

    function _saveDBFData()
    {
        unset($this->DBFData["deleted"]);
        if ($this->recordNumber <= ($this->DBFFile->getRecordCount())) {
            if (!dbase_replace_record($this->DBFFile, array_values($this->DBFData), $this->recordNumber)) {
                $this->setError("I wasn't possible to update the information in the DBF file.");
            }
        } else {


            $record = $this->DBFFile->nextRecord();
            foreach ($this->DBFData as $name => $data) {
                // var_dump($record,$name,$data);
                if ($this->parent->encoding == 'windows-1256' && $this->parent->DBFHeader[$name][1] == Record::DBFFIELD_TYPE_CHAR) {
                    $data = Util::utf8to1256($data);
//                    var_dump('ada');
                }
                $record->$name = $data;
            }

            $r = $this->DBFFile->writeRecord();


            // if (!dbase_add_record($this->DBFFile, array_values($this->DBFData))) {
            if (!$r) {
                $this->setError("I wasn't possible to add the information to the DBF file.");
            }
        }
    }

    function setError($error)
    {
        $this->lastError = $error;
        return false;
    }

    public function getData($data)
    {
        if ($this->parent) {
            return $this->DBFData = $this->parent->records[$this->recordNumber];
        } else {
            return $this->DBFData;
        }
    }

    public function setData($data)
    {
        $this->DBFData = $data;
        /*if ($this->parent) {
            $this->parent->records[$this->recordNumber] = $data;
        }*/
    }
}