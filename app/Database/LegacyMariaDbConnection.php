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

    /**
     * Run a SQL statement and log the query.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function run($query, $bindings, \Closure $callback)
    {
        $local_db = config('database.connections.mysql.database');
        if ($local_db && $local_db !== 'hrims') {
            $query = preg_replace('/\bhrims\./i', $local_db . '.', $query);
        }

        return parent::run($query, $bindings, $callback);
    }
}
