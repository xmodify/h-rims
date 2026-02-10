<?php

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\MariaDbGrammar;

class MariaDbLegacyGrammar extends MariaDbGrammar
{
    /**
     * Compile the query to determine the columns.
     *
     * @param  string|null  $schema
     * @param  string  $table
     * @return string
     */
    public function compileColumns($schema, $table)
    {
        return str_replace(
            'generation_expression as `expression`, ',
            "'' as `expression`, ",
            parent::compileColumns($schema, $table)
        );
    }
}
