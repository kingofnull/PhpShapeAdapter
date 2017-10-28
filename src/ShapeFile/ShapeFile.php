<?php

// include "vendor/autoload.php";
namespace ShpAdapter\ShapeFile;

// use ShpAdapter\XBase\Table;

class ShapeFile
{
    var $FileName;
    var $envelope;
    var $SHPFile;
    var $SHXFile;
    var $DBFFile;

    var $DBFHeader;

    var $lastError = "";

    var $boundingBox = array("xmin" => 0.0, "ymin" => 0.0, "xmax" => 0.0, "ymax" => 0.0);
    var $fileLength = 0;
    var $shapeType = 0;

    var $records;
    var $encoding;

    var $readPrj;
    var $writePrj;


    const NULL_TYPE = 0;
    const POINT_TYPE = 1;
    const POLYLINE_TYPE = 3;
    const POLYGON_TYPE = 5;
    const MULTIPOINT_TYPE = 8;
    const POINTZ_TYPE = 11;
    const POLYLINEZ_TYPE = 13;
    const POLYGONZ_TYPE = 15;
    const MULTIPOINTZ_TYPE = 18;
    const POINTM_TYPE = 21;
    const POLYLINEM_TYPE = 23;
    const POLYGONM_TYPE = 25;
    const MULTIPOINTM_TYPE = 28;
    const MULTIPATCH_TYPE = 31;

    function __construct($shapeType,$dbfHeader=null, $boundingBox = null, $FileName = NULL)
    {
        $this->shapeType = $shapeType;
        $this->boundingBox = $boundingBox;
        $this->FileName = $FileName;
        $this->fileLength = 50;

        $outerPrj='EPSG:4326'; //Other Export Projection Are Not Supported Now. TODO
        $encoding='windows-1256'; //Other Export Encodings Are Not Supported Now. TODO

        $this->prj4 = new \ShpAdapter\Proj4\Proj4php();
        $this->writePrj=new \ShpAdapter\Proj4\Proj($outerPrj,$this->prj4);
        $this->encoding = $encoding;

        if($dbfHeader){
            $this->setDBFHeader($dbfHeader);
        }
    }


    function loadFromFile($FileName)
    {
        $this->FileName = $FileName;

        if (($this->_openSHPFile()) && ($this->_openDBFFile())) {
            $this->_loadHeaders();
            $this->_loadRecords();
            $this->_closeSHPFile();
            $this->_closeDBFFile();
        } else {
            return false;
        }
    }

    function openFile($FileName,$projection=null)
    {

        $this->FileName = $FileName;

        $this->dbfFileName = str_replace('.*', '.dbf', $this->FileName);
        if (!file_exists($this->dbfFileName)) {
            throw new \Exception("File of `{$this->dbfFileName}` not found!");
        }
        if(!$projection){
            $this->prjFileName = str_replace('.*', '.prj', $this->FileName);
            if ( !file_exists($this->prjFileName)) {
                throw new \Exception("File of `{$this->prjFileName}` not found!");
            }
            $projection=file_get_contents($this->prjFileName);
        }


        $this->readPrj=new \ShpAdapter\Proj4\Proj($projection,$this->prj4);


        if (($this->_openSHPFile()) && ($this->_openDBFFile())) {
            $this->_loadHeaders();
            $this->recordNum = 0;
            fseek($this->SHPFile, 100);
        } else {
            return false;
        }
    }


    function nextRecord()
    {
        if (!feof($this->SHPFile)) {
            $bByte = ftell($this->SHPFile);
            $record = new ShapeRecord(-1,$this);
//            $record->parent=&$this;
            $record->loadFromFile($this->SHPFile, $this->DBFFile);
            $eByte = ftell($this->SHPFile);
            if (($eByte <= $bByte) || ($record->lastError != "")) {
                return false;
            }

            return $record;
        } else {
            return null;
        }

    }


    function saveToFile($FileName = NULL)
    {
        if (!$this->boundingBox) {
            $this->boundingBox = $this->getBBox();
//           var_dump($this->boundingBox);
        }

        if ($FileName != NULL) $this->FileName = $FileName;

        if (($this->_openSHPFile(TRUE)) && ($this->_openSHXFile(TRUE)) && ($this->_openDBFFile(TRUE))) {
            $this->_saveHeaders();
            $this->_saveRecords();
            $this->_closeSHPFile();
            $this->_closeSHXFile();
            $this->_closeDBFFile();

            $this->prjFileName = str_replace('.*', '.prj', $this->FileName);
//            $arcGisProjectionString=$this->shpPrj->getDefData() FIXME // don't get in correct format
            //EPSG:4326 fixed Esri Wkt Projection String
            $arcGisProjectionString="GEOGCS[\"GCS_WGS_1984\",DATUM[\"D_WGS_1984\",SPHEROID[\"WGS_1984\",6378137,298.257223563]],PRIMEM[\"Greenwich\",0],UNIT[\"Degree\",0.0174532925199433]]";
            file_put_contents($this->prjFileName,$arcGisProjectionString);
        } else {
            return false;
        }
    }

    function addRecord($record)
    {
        if ($record->envelope) {
            // $record->envelope->
        }

        if ((isset($this->DBFHeader)) && (is_array($this->DBFHeader))) {
            $record->updateDBFInfo($this->DBFHeader);
        }

        $this->fileLength += ($record->getContentLength() + 4);
        $record->recordNumber = count($this->records) + 1;
//        $record->parent=&$this;

        $this->records[] = $record;
//      $this->records[count($this->records) - 1]->recordNumber = count($this->records);


        return (count($this->records) - 1);
    }

    function deleteRecord($index)
    {
        if (isset($this->records[$index])) {
            $this->fileLength -= ($this->records[$index]->getContentLength() + 4);
            for ($i = $index; $i < (count($this->records) - 1); $i++) {
                $this->records[$i] = $this->records[$i + 1];
            }
            unset($this->records[count($this->records) - 1]);
            $this->_deleteRecordFromDBF($index);
        }
    }

    function getDBFHeader()
    {
        return $this->DBFHeader;
    }

    function setDBFHeader($header)
    {
        $this->DBFHeader = $header;
        $this->DBFHeader =array_column($this->DBFHeader,null,0);
//        var_dump($this->DBFHeader);
        for ($i = 0; $i < count($this->records); $i++) {
            $this->records[$i]->updateDBFInfo($header);
        }
    }

    function getIndexFromDBFData($field, $value)
    {
        $result = -1;
        for ($i = 0; $i < (count($this->records) - 1); $i++) {
            if (isset($this->records[$i]->DBFData[$field]) && (strtoupper($this->records[$i]->DBFData[$field]) == strtoupper($value))) {
                $result = $i;
            }
        }

        return $result;
    }

    function _loadDBFHeader()
    {
        $DBFFile = fopen(str_replace('.*', '.dbf', $this->FileName), 'r');

        $result = array();
        $buff32 = array();
        $i = 1;
        $inHeader = true;

        while ($inHeader) {
            if (!feof($DBFFile)) {
                $buff32 = fread($DBFFile, 32);
                if ($i > 1) {
                    if (substr($buff32, 0, 1) == chr(13)) {
                        $inHeader = false;
                    } else {
                        $pos = strpos(substr($buff32, 0, 10), chr(0));
                        $pos = ($pos == 0 ? 10 : $pos);

                        $fieldName = substr($buff32, 0, $pos);
                        $fieldType = substr($buff32, 11, 1);
                        $fieldLen = ord(substr($buff32, 16, 1));
                        $fieldDec = ord(substr($buff32, 17, 1));

                        array_push($result, array($fieldName, $fieldType, $fieldLen, $fieldDec));
                    }
                }
                $i++;
            } else {
                $inHeader = false;
            }
        }

        fclose($DBFFile);
        return ($result);
    }

    function _deleteRecordFromDBF($index)
    {
        if (dbase_delete_record($this->DBFFile, $index)) {
            dbase_pack($this->DBFFile);
        }
    }

    function _loadHeaders()
    {
        fseek($this->SHPFile, 24, SEEK_SET);
        $this->fileLength = Util::loadData("N", fread($this->SHPFile, 4));

        fseek($this->SHPFile, 32, SEEK_SET);
        $this->shapeType = Util::loadData("V", fread($this->SHPFile, 4));
        //var_dump( $this->shapeType);die;

        $this->boundingBox = array();
        $this->boundingBox["xmin"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->boundingBox["ymin"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->boundingBox["xmax"] = Util::loadData("d", fread($this->SHPFile, 8));
        $this->boundingBox["ymax"] = Util::loadData("d", fread($this->SHPFile, 8));

        $this->DBFHeader = $this->_loadDBFHeader();
    }

    function _saveHeaders()
    {
        fwrite($this->SHPFile, pack("NNNNNN", 9994, 0, 0, 0, 0, 0));
        fwrite($this->SHPFile, pack("N", $this->fileLength));
        fwrite($this->SHPFile, pack("V", 1000));
        fwrite($this->SHPFile, pack("V", $this->shapeType));
        fwrite($this->SHPFile, Util::packDouble($this->boundingBox['xmin']));
        fwrite($this->SHPFile, Util::packDouble($this->boundingBox['ymin']));
        fwrite($this->SHPFile, Util::packDouble($this->boundingBox['xmax']));
        fwrite($this->SHPFile, Util::packDouble($this->boundingBox['ymax']));
        fwrite($this->SHPFile, pack("dddd", 0, 0, 0, 0));

        fwrite($this->SHXFile, pack("NNNNNN", 9994, 0, 0, 0, 0, 0));
        fwrite($this->SHXFile, pack("N", 50 + 4 * count($this->records)));
        fwrite($this->SHXFile, pack("V", 1000));
        fwrite($this->SHXFile, pack("V", $this->shapeType));
        fwrite($this->SHXFile, Util::packDouble($this->boundingBox['xmin']));
        fwrite($this->SHXFile, Util::packDouble($this->boundingBox['ymin']));
        fwrite($this->SHXFile, Util::packDouble($this->boundingBox['xmax']));
        fwrite($this->SHXFile, Util::packDouble($this->boundingBox['ymax']));
        fwrite($this->SHXFile, pack("dddd", 0, 0, 0, 0));
    }

    function _loadRecords()
    {
        fseek($this->SHPFile, 100);
        while (!feof($this->SHPFile)) {
            $bByte = ftell($this->SHPFile);
            $record = new ShapeRecord(-1);
//            $record->parent=&$this;
            $record->loadFromFile($this->SHPFile, $this->DBFFile);
            $eByte = ftell($this->SHPFile);
            if (($eByte <= $bByte) || ($record->lastError != "")) {
                return false;
            }

            $this->records[] = $record;
        }
    }

    function _saveRecords()
    {
        // if (file_exists(str_replace('.*', '.dbf', $this->FileName))) {
        // unlink(str_replace('.*', '.dbf', $this->FileName));
        // }
        // if (!($this->DBFFile = dbase_create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader))) {
        $dbfFile = str_replace('.*', '.dbf', $this->FileName);


        $r = $this->DBFFile = \ShpAdapter\XBase\WritableTable::create($dbfFile, $this->DBFHeader);

        if (!($r)) {
            return $this->setError(sprintf("It wasn't possible to create the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
        }

        $offset = 50;
        if (is_array($this->records) && (count($this->records) > 0)) {
            reset($this->records);
            while (list($index, $record) = each($this->records)) {
                //Save the record to the .shp file
                $record->saveToFile($this->SHPFile, $this->DBFFile, $index + 1);

                //Save the record to the .shx file
                fwrite($this->SHXFile, pack("N", $offset));
                fwrite($this->SHXFile, pack("N", $record->getContentLength()));
                $offset += (4 + $record->getContentLength());
            }
        }
        // dbase_pack($this->DBFFile);
    }

    function _openSHPFile($toWrite = false)
    {
        $this->SHPFile = fopen(str_replace('.*', '.shp', $this->FileName), ($toWrite ? "wb+" : "rb"));
        if (!$this->SHPFile) {
            return $this->setError(sprintf("It wasn't possible to open the Shape file '%s'", str_replace('.*', '.shp', $this->FileName)));
        }

        return TRUE;
    }

    function _closeSHPFile()
    {
        if ($this->SHPFile) {
            fclose($this->SHPFile);
            $this->SHPFile = NULL;
        }
    }

    function _openSHXFile($toWrite = false)
    {
        $this->SHXFile = fopen(str_replace('.*', '.shx', $this->FileName), ($toWrite ? "wb+" : "rb"));
        if (!$this->SHXFile) {
            return $this->setError(sprintf("It wasn't possible to open the Index file '%s'", str_replace('.*', '.shx', $this->FileName)));
        }

        return TRUE;
    }

    function _closeSHXFile()
    {
        if ($this->SHXFile) {
            fclose($this->SHXFile);
            $this->SHXFile = NULL;
        }
    }

    function _openDBFFile($toWrite = false)
    {
        $checkFunction = $toWrite ? "is_writable" : "is_readable";
        if (($toWrite) && (!file_exists(str_replace('.*', '.dbf', $this->FileName)))) {
            // if (!dbase_create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader)) {
            // var_dump($this->DBFHeader);
            $r = $this->DBFFile = \ShpAdapter\XBase\WritableTable::create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader);
            if (!$r) {
                return $this->setError(sprintf("It wasn't possible to create the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
            }
        }
        if ($checkFunction(str_replace('.*', '.dbf', $this->FileName))) {

            // $this->DBFFile = dbase_open(str_replace('.*', '.dbf', $this->FileName), ($toWrite ? 2 : 0));
            $this->DBFFile = new \ShpAdapter\XBase\Table(str_replace('.*', '.dbf', $this->FileName));
            if (!$this->DBFFile) {
                return $this->setError(sprintf("It wasn't possible to open the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
            }
        } else {
            return $this->setError(sprintf("It wasn't possible to find the DBase file '%s'", str_replace('.*', '.dbf', $this->FileName)));
        }
        return TRUE;
    }

    function _closeDBFFile()
    {
        if ($this->DBFFile) {
            // dbase_close($this->DBFFile);
            $this->DBFFile->close();
            $this->DBFFile = NULL;
        }
    }

    function setError($error)
    {
        $this->lastError = $error;
        return false;
    }


    function getBBox()
    {
        // Go through each component and get the max and min x and y
        $i = 0;
        foreach ($this->records as $record) {
            $component_bbox = $record->extend;

            // On the first run through, set the bbox to the component bbox
            if ($i == 0) {
                $maxx = $component_bbox['maxx'];
                $maxy = $component_bbox['maxy'];
                $minx = $component_bbox['minx'];
                $miny = $component_bbox['miny'];
            }

            // Do a check and replace on each boundary, slowly growing the bbox
            $maxx = $component_bbox['maxx'] > $maxx ? $component_bbox['maxx'] : $maxx;
            $maxy = $component_bbox['maxy'] > $maxy ? $component_bbox['maxy'] : $maxy;
            $minx = $component_bbox['minx'] < $minx ? $component_bbox['minx'] : $minx;
            $miny = $component_bbox['miny'] < $miny ? $component_bbox['miny'] : $miny;
            $i++;
        }

        return array(
            'ymax' => $maxy,
            'ymin' => $miny,
            'xmax' => $maxx,
            'xmin' => $minx,
        );
    }
}


?>