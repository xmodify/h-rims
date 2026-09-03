<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HosfinTruncateGl extends Command
{
    protected $signature = 'hosfin:truncate-gl';
    protected $description = 'Cleanly truncate all hosfin_gl_* tables and remove GL_SYNC preview rows';

    public function handle()
    {
        $this->info('Starting GL data truncation...');

        Schema::disableForeignKeyConstraints();

        $tables = [
            'hosfin_gl_journal_items',
            'hosfin_gl_journals',
            'hosfin_gl_ap_bills',
            'hosfin_gl_ar_debtors',
            'hosfin_gl_cost_centers',
            'hosfin_gl_cost_summaries',
            'hosfin_gl_monthly_summaries',
            'hosfin_gl_sync_logs',
            'hosfin_gl_subledgers',
            'hosfin_gl_accounts',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line(" - Truncated: <comment>{$table}</comment>");
            }
        }

        if (Schema::hasTable('hosfin_trial_balance')) {
            $deleted = DB::table('hosfin_trial_balance')
                ->where('import_filename', 'GL_SYNC')
                ->delete();
            $this->line(" - Removed {$deleted} GL_SYNC rows from <comment>hosfin_trial_balance</comment>");
        }

        Schema::enableForeignKeyConstraints();

        $this->info('✅ All GL data cleanly truncated! Ready for fresh import via Rims-GL-Sync.exe.');
        return Command::SUCCESS;
    }
}
