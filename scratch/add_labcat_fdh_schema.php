<?php
$jsonPath = __DIR__ . '/../docs/lookup/extracted_schemas.json';
$schemas = json_decode(file_get_contents($jsonPath), true);

$schemas['labcat_fdh'] = [
    'columns' => [
        'id' => ['type' => 'int(11) unsigned', 'nullable' => false, 'default' => null, 'extra' => 'auto_increment'],
        'benefitplan' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'cscode' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'name' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'unit' => ['type' => 'varchar(100)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'unitprice' => ['type' => 'double(15,2)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'gyear' => ['type' => 'varchar(50)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'updatebeg' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'updateend' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'updateflag' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'tmlt' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'tmlt_name' => ['type' => 'text', 'nullable' => true, 'default' => null, 'extra' => ''],
        'lccode' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'loinc' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'exception' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'stm_filename' => ['type' => 'varchar(255)', 'nullable' => true, 'default' => null, 'extra' => ''],
        'created_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null, 'extra' => ''],
        'updated_at' => ['type' => 'timestamp', 'nullable' => true, 'default' => null, 'extra' => ''],
    ],
    'indexes' => [
        'PRIMARY' => [
            ['column' => 'id', 'unique' => true]
        ],
        'lccode' => [
            ['column' => 'lccode', 'unique' => false]
        ]
    ]
];

file_put_contents($jsonPath, json_encode($schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Successfully added labcat_fdh schema definition to extracted_schemas.json\n";
