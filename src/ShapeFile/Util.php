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

    static function utf8to1256($str) {
        $trans=[
            'ی'=>'ي',
            '۱'=>'1',
            '۲'=>'2',
            '۳'=>'3',
            '۴'=>'4',
            '۵'=>'5',
            '۶'=>'6',
            '۷'=>'7',
            '۸'=>'8',
            '۹'=>'9',
            '۰'=>'0',
        ];
        $str=str_replace(array_keys($trans),array_values($trans) , $str);
        $str=iconv("UTF-8", 'WINDOWS-1256//TRANSLIT//IGNORE',$str);
        return $str;
    }


    static function _1256toUtf8($str) {
        $trans=[
            'ی'=>'ي',
            '۱'=>'1',
            '۲'=>'2',
            '۳'=>'3',
            '۴'=>'4',
            '۵'=>'5',
            '۶'=>'6',
            '۷'=>'7',
            '۸'=>'8',
            '۹'=>'9',
            '۰'=>'0',
        ];
        $str=str_replace(array_values($trans),array_keys($trans) , $str);
        $str=iconv('WINDOWS-1256',"UTF-8//TRANSLIT//IGNORE" ,$str);
        return $str;
    }
 }