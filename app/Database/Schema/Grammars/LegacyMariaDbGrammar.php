<?php

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\MariaDbGrammar;

class LegacyMariaDbGrammar extends MariaDbGrammar
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
        return sprintf(
            'select column_name as `name`, data_type as `type_name`, column_type as `type`, '
            . 'collation_name as `collation`, is_nullable as `nullable`, '
            . 'column_default as `default`, column_comment as `comment`, '
            . 'extra as `extra` ' // Removed generation_expression
            . 'from information_schema.columns where table_schema = %s and table_name = %s '
            . 'order by ordinal_position asc',
            $schema ? $this->quoteString($schema) : 'schema()',
            $this->quoteString($table)
        );
    }
}
