<?php
namespace ShpAdapter\ShapeFile;

class Util{
	  static function loadData($type, $data) {
		if (!$data) return $data;
		$tmp = unpack($type, $data);
		return current($tmp);
	  }

	  static function swap($binValue) {
		$result = $binValue{strlen($binValue) - 1};
		for($i = strlen($binValue) - 2; $i >= 0 ; $i--) {
		  $result .= $binValue{$i};
		}

		return $result;
	  }

	  static function packDouble($value, $mode = 'LE') {
		$value = (double)$value;
		$bin = pack("d", $value);

		//We test if the conversion of an integer (1) is done as LE or BE by default
		switch (pack ('L', 1)) {
		  case pack ('V', 1): //Little Endian
			$result = ($mode == 'LE') ? $bin : swap($bin);
		  break;
		  case pack ('N', 1): //Big Endian
			$result = ($mode == 'BE') ? $bin : swap($bin);
		  break;
		  default: //Some other thing, we just return false
			$result = FALSE;
		}

		return $result;
	  }
 }