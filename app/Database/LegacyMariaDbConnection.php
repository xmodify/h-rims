<?php

namespace App\Database;

use Illuminate\Database\MariaDbConnection;
use App\Database\Schema\Grammars\LegacyMariaDbGrammar;

class LegacyMariaDbConnection extends MariaDbConnection
{
    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        $grammar = new LegacyMariaDbGrammar($this);
        $grammar->setTablePrefix($this->tablePrefix);
        return $grammar;
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new \App\Database\Query\Processors\LegacyMariaDbProcessor;
    }
}
