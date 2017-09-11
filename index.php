<?php
include "vendor/autoload.php";

use XBase\Table;

$table = new Table(dirname(__FILE__).'/shp/e3.dbf');

while ($record = $table->nextRecord()) {
    var_dump( (string)$record->code);
}