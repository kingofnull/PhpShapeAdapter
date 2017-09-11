<?php 

// include "vendor/autoload.php";
namespace ShpAdapter\ShapeFile;

// use ShpAdapter\XBase\Table;

class ShapeFile {
    var $FileName;

    var $SHPFile;
    var $SHXFile;
    var $DBFFile;

    var $DBFHeader;
        
    var $lastError = "";
        
    var $boundingBox = array("xmin" => 0.0, "ymin" => 0.0, "xmax" => 0.0, "ymax" => 0.0);
    var $fileLength = 0;
    var $shapeType = 0;
        
    var $records;
	
	
	const NULL_TYPE=0;
	const POINT_TYPE=1;
	const POLYLINE_TYPE=3;
	const POLYGON_TYPE=5;
	const MULTIPOINT_TYPE=8;
	const POINTZ_TYPE=11;
	const POLYLINEZ_TYPE=13;
	const POLYGONZ_TYPE=15;
	const MULTIPOINTZ_TYPE=18;
	const POINTM_TYPE=21;
	const POLYLINEM_TYPE=23;
	const POLYGONM_TYPE=25;
	const MULTIPOINTM_TYPE=28;
	const MULTIPATCH_TYPE=31;
			
    function __construct($shapeType, $boundingBox = array("xmin" => 0.0, "ymin" => 0.0, "xmax" => 0.0, "ymax" => 0.0), $FileName = NULL) {
      $this->shapeType = $shapeType;
      $this->boundingBox = $boundingBox;
      $this->FileName = $FileName;
      $this->fileLength = 50;
    }
	
	
	function loadFromFile($FileName) {
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
	
	function openFile($FileName) {
		
      $this->FileName = $FileName;
      
	  
	  
      if (($this->_openSHPFile()) && ($this->_openDBFFile())) {
        $this->_loadHeaders();
		$this->recordNum=0;   
		fseek($this->SHPFile, 100);		
      } else {
        return false;
      }
    }
	
	
	function nextRecord() {
		if(!feof($this->SHPFile)){
			$bByte = ftell($this->SHPFile);
			$record = new ShapeRecord(-1);
			$record->loadFromFile($this->SHPFile, $this->DBFFile);
			$eByte = ftell($this->SHPFile);
			if (($eByte <= $bByte) || ($record->lastError != "")) {
			  return false;
			}

			return $record;
		}else{
			return null;
		}
		
	}
	
	
        
  
        
    function saveToFile($FileName = NULL) {
      if ($FileName != NULL) $this->FileName = $FileName;

      if (($this->_openSHPFile(TRUE)) && ($this->_openSHXFile(TRUE)) && ($this->_openDBFFile(TRUE))) {
        $this->_saveHeaders();
        $this->_saveRecords();
        $this->_closeSHPFile();
        $this->_closeSHXFile();
        $this->_closeDBFFile();
      } else {
        return false;
      }
    }
        
    function addRecord($record) {
      if ((isset($this->DBFHeader)) && (is_array($this->DBFHeader))) {
        $record->updateDBFInfo($this->DBFHeader);
      }

      $this->fileLength += ($record->getContentLength() + 4);
      $this->records[] = $record;
      $this->records[count($this->records) - 1]->recordNumber = count($this->records);

      return (count($this->records) - 1);
    }
        
    function deleteRecord($index) {
      if (isset($this->records[$index])) {
        $this->fileLength -= ($this->records[$index]->getContentLength() + 4);
        for ($i = $index; $i < (count($this->records) - 1); $i++) {
          $this->records[$i] = $this->records[$i + 1];
        }
        unset($this->records[count($this->records) - 1]);
        $this->_deleteRecordFromDBF($index);
      }
    }
        
    function getDBFHeader() {
      return $this->DBFHeader;
    }
        
    function setDBFHeader($header) {
      $this->DBFHeader = $header;

      for ($i = 0; $i < count($this->records); $i++) {
        $this->records[$i]->updateDBFInfo($header);
      }
    }

    function getIndexFromDBFData($field, $value) {
      $result = -1;
      for ($i = 0; $i < (count($this->records) - 1); $i++) {
        if (isset($this->records[$i]->DBFData[$field]) && (strtoupper($this->records[$i]->DBFData[$field]) == strtoupper($value))) {
          $result = $i;
        }
      }

      return $result;
    }

    function _loadDBFHeader() {
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
      return($result);
    }
        
    function _deleteRecordFromDBF($index) {
      if (dbase_delete_record($this->DBFFile, $index)) {
        dbase_pack($this->DBFFile);
      }
    }

    function _loadHeaders() {
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

    function _saveHeaders() {
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
      fwrite($this->SHXFile, pack("N", 50 + 4*count($this->records)));
      fwrite($this->SHXFile, pack("V", 1000));
      fwrite($this->SHXFile, pack("V", $this->shapeType));
      fwrite($this->SHXFile, Util::packDouble($this->boundingBox['xmin']));
      fwrite($this->SHXFile, Util::packDouble($this->boundingBox['ymin']));
      fwrite($this->SHXFile, Util::packDouble($this->boundingBox['xmax']));
      fwrite($this->SHXFile, Util::packDouble($this->boundingBox['ymax']));
      fwrite($this->SHXFile, pack("dddd", 0, 0, 0, 0));
    }

    function _loadRecords() {
      fseek($this->SHPFile, 100);
      while (!feof($this->SHPFile)) {
        $bByte = ftell($this->SHPFile);
        $record = new ShapeRecord(-1);
        $record->loadFromFile($this->SHPFile, $this->DBFFile);
        $eByte = ftell($this->SHPFile);
        if (($eByte <= $bByte) || ($record->lastError != "")) {
          return false;
        }

        $this->records[] = $record;
      }
    }

    function _saveRecords() {
      // if (file_exists(str_replace('.*', '.dbf', $this->FileName))) {
        // unlink(str_replace('.*', '.dbf', $this->FileName));
      // }
      // if (!($this->DBFFile = dbase_create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader))) {
	  $r=$this->DBFFile =\ShpAdapter\XBase\WritableTable::create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader);
	  
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
    
    function _openSHPFile($toWrite = false) {
      $this->SHPFile = fopen(str_replace('.*', '.shp', $this->FileName), ($toWrite ? "wb+" : "rb"));
      if (!$this->SHPFile) {
        return $this->setError(sprintf("It wasn't possible to open the Shape file '%s'", str_replace('.*', '.shp', $this->FileName)));
      }

      return TRUE;
    }
    
    function _closeSHPFile() {
      if ($this->SHPFile) {
        fclose($this->SHPFile);
        $this->SHPFile = NULL;
      }
    }
    
    function _openSHXFile($toWrite = false) {
      $this->SHXFile = fopen(str_replace('.*', '.shx', $this->FileName), ($toWrite ? "wb+" : "rb"));
      if (!$this->SHXFile) {
        return $this->setError(sprintf("It wasn't possible to open the Index file '%s'", str_replace('.*', '.shx', $this->FileName)));
      }

      return TRUE;
    }
    
    function _closeSHXFile() {
      if ($this->SHXFile) {
        fclose($this->SHXFile);
        $this->SHXFile = NULL;
      }
    }
        
    function _openDBFFile($toWrite = false) {
      $checkFunction = $toWrite ? "is_writable" : "is_readable";
      if (($toWrite) && (!file_exists(str_replace('.*', '.dbf', $this->FileName)))) {
        // if (!dbase_create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader)) {
			// var_dump($this->DBFHeader);
		$r=$this->DBFFile =\ShpAdapter\XBase\WritableTable::create(str_replace('.*', '.dbf', $this->FileName), $this->DBFHeader);
        if (!$r ) {
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
        
    function _closeDBFFile() {
      if ($this->DBFFile) {
        // dbase_close($this->DBFFile);
		$this->DBFFile->close();
        $this->DBFFile = NULL;
      }
    }
        
    function setError($error) {
      $this->lastError = $error;
      return false;
    }
  }
  
  

?>