<?php

namespace App\Database\Query\Processors;

use Illuminate\Database\Query\Processors\MySqlProcessor;

class LegacyMariaDbProcessor extends MySqlProcessor
{
    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumns($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->name,
                'type_name' => $result->type_name,
                'type' => $result->type,
                'collation' => $result->collation,
                'nullable' => $result->nullable === 'YES',
                'default' => $result->default,
                'auto_increment' => $result->extra === 'auto_increment',
                'comment' => $result->comment ?: null,
                'generation' => isset($result->expression) && $result->expression ? [
                    'type' => match ($result->extra) {
                        'STORED GENERATED' => 'stored',
                        'VIRTUAL GENERATED' => 'virtual',
                        default => null,
                    },
                    'expression' => $result->expression,
                ] : null,
            ];
        }, $results);
    }
}
