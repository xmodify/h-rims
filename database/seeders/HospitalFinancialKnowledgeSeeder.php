<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\Ai\Knowledge\HospitalFinancialKnowledgeService;

class HospitalFinancialKnowledgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expertService = app(HospitalFinancialKnowledgeService::class);

        $title = 'คู่มือและมาตรฐานการบริหารการเงินการคลังโรงพยาบาล สธ. (PlanFin, EBITDA 20%, 13 ดัชนีวิกฤต, และ TPS Score)';
        $filename = 'MOPH_Hospital_Financial_Management_Master_Guide.pdf';

        $doc = DB::table('rag_documents')->where('title', $title)->first();
        if (!$doc) {
            $docId = DB::table('rag_documents')->insertGetId([
                'title' => $title,
                'filename' => $filename,
                'file_path' => 'rag_documents/' . $filename,
                'file_type' => 'pdf',
                'file_size' => 102400,
                'chunk_count' => 4,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $docId = $doc->id;
            DB::table('rag_chunks')->where('document_id', $docId)->delete();
        }

        $chunks = [
            [
                'page' => 1,
                'content' => $expertService->getPlanfinKnowledge() . "\n\n" . $expertService->getFy70PreparationGuide()
            ],
            [
                'page' => 2,
                'content' => $expertService->getFinancialDistressKnowledge()
            ],
            [
                'page' => 3,
                'content' => $expertService->getCostingAndTpsKnowledge()
            ],
            [
                'page' => 4,
                'content' => $expertService->getWorkingCapitalKnowledge()
            ]
        ];

        foreach ($chunks as $idx => $c) {
            DB::table('rag_chunks')->insert([
                'document_id' => $docId,
                'chunk_index' => $idx + 1,
                'content' => $c['content'],
                'page_number' => $c['page'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
