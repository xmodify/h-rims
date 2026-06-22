<?php
$jsonPath = __DIR__ . '/../docs/lookup/extracted_schemas.json';
$schemas = json_decode(file_get_contents($jsonPath), true);

$schemas['labcat_nhso'] = [
    'columns' => [
        'lccode' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'billgroup' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'cscode' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'tmlt' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'loinc' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'panel' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'name' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'sflag' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'chargecat' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'unitprice' => ['type' => 'double(15,2)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'benefitplan' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'reimbprice' => ['type' => 'double(15,2)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'updateflag' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'updatebeg' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'updateend' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'rpdatebeg' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'rpdateend' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'dateupd' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'hcode' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'message' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'stm_filename' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
    ],
    'indexes' => []
];

file_put_contents($jsonPath, json_encode($schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Successfully added labcat_nhso schema definition to extracted_schemas.json\n";
