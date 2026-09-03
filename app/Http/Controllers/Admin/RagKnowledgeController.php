<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RagDocument;
use App\Models\RagChunk;
use App\Models\RagCategory;
use App\Services\Ai\AiService;
use App\Services\Ai\DocumentParserService;
use App\Services\Ai\RagSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RagKnowledgeController extends Controller
{
    protected AiService $aiService;
    protected DocumentParserService $parserService;
    protected RagSearchService $searchService;

    public function __construct(
        AiService $aiService,
        DocumentParserService $parserService,
        RagSearchService $searchService
    ) {
        $this->aiService = $aiService;
        $this->parserService = $parserService;
        $this->searchService = $searchService;
    }

    /**
     * Display RAG Knowledge Dashboard & Documents List
     */
    public function index(Request $request)
    {
        $selectedCategory = $request->input('category_id');
        $query = RagDocument::with(['category', 'chunks'])->withCount('chunks');
        if (!empty($selectedCategory)) {
            $query->where('category_id', $selectedCategory);
        }

        $documents = $query->orderBy('id', 'desc')->paginate(10);
        $totalDocs = RagDocument::count();
        $totalChunks = RagChunk::count();
        $categories = RagCategory::withCount('documents')->orderBy('sort_order', 'asc')->get();

        $aiConfig = [
            'is_active' => AiService::isActive(),
            'provider' => AiService::getProvider(),
            'api_key' => AiService::getApiKey(),
            'model' => AiService::getModelName(),
            'embed_model' => AiService::getEmbedModel(),
            'api_url' => AiService::getApiUrl(),
            'has_key' => !empty(AiService::getApiKey())
        ];

        return view('admin.rag.index', compact('documents', 'totalDocs', 'totalChunks', 'categories', 'selectedCategory', 'aiConfig'));
    }

    /**
     * Save AI & LLM Settings from Modal
     */
    public function saveSettings(Request $request)
    {
        $settings = [
            'ai_active' => ($request->has('ai_active') && in_array($request->input('ai_active'), ['Y', '1', 'on', true], true)) ? 'Y' : 'N',
            'ai_provider' => $request->input('ai_provider', 'gemini'),
            'ai_api_key' => $request->input('ai_api_key', ''),
            'ai_api_url' => $request->input('ai_api_url', 'http://localhost:11434'),
            'ai_model_name' => $request->input('ai_model_name', 'gemini-1.5-flash'),
            'ai_embed_model' => $request->input('ai_embed_model', 'text-embedding-004'),
        ];

        foreach ($settings as $name => $val) {
            DB::table('main_setting')->updateOrInsert(
                ['name' => $name],
                ['value' => $val !== null ? trim($val) : '']
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าระบบ AI เรียบร้อยแล้ว'
        ]);
    }

    /**
     * Handle Document Upload and Processing
     */
    public function upload(Request $request)
    {
        $request->validate([
            'document' => 'required|file|max:51200', // max 50MB
            'title' => 'nullable|string|max:255'
        ], [
            'document.required' => 'กรุณาเลือกไฟล์เอกสาร',
            'document.max' => 'ขนาดไฟล์ต้องไม่เกิน 50MB'
        ]);

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $fileSize = $file->getSize();

        $allowedExtensions = ['pdf', 'docx', 'txt', 'md', 'json', 'csv'];
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => "ไม่รองรับไฟล์นามสกุล .{$extension} (รองรับเฉพาะ: " . implode(', ', $allowedExtensions) . ")"
            ], 422);
        }

        $title = $request->input('title');
        if (empty($title)) {
            $title = pathinfo($originalName, PATHINFO_FILENAME);
        }

        // Store file
        $storedPath = $file->storeAs('rag_documents', time() . '_' . $originalName);
        $fullPath = storage_path('app/' . $storedPath);

        $categoryId = $request->input('category_id');

        // Create document record
        $document = RagDocument::create([
            'category_id' => !empty($categoryId) ? $categoryId : null,
            'title' => $title,
            'filename' => $originalName,
            'file_path' => $storedPath,
            'file_type' => $extension,
            'file_size' => $fileSize,
            'chunk_count' => 0,
            'status' => 'processing',
        ]);

        try {
            // 1. Extract text
            $extractedPages = $this->parserService->extractText($fullPath, $extension);
            if (empty($extractedPages)) {
                throw new \Exception("ไม่สามารถสกัดข้อความจากไฟล์ได้ หรือเอกสารเป็นหน้าว่าง");
            }

            // 2. Chunk text
            $chunks = $this->parserService->chunkText($extractedPages);
            if (empty($chunks)) {
                throw new \Exception("ไม่พบเนื้อหาที่สามารถแบ่งเป็นย่อหน้าความรู้ได้");
            }

            // 3. Process Vector Embedding & Save Chunks
            DB::beginTransaction();
            $savedCount = 0;

            foreach ($chunks as $chunkData) {
                $embedding = [];
                try {
                    $embedding = $this->aiService->getEmbedding($chunkData['content']);
                } catch (\Throwable $e) {
                    Log::warning("Embedding failed for chunk {$chunkData['chunk_index']}: " . $e->getMessage());
                }

                RagChunk::create([
                    'document_id' => $document->id,
                    'chunk_index' => $chunkData['chunk_index'],
                    'content' => $chunkData['content'],
                    'embedding' => $embedding,
                    'page_number' => $chunkData['page_number']
                ]);
                $savedCount++;
            }

            $document->update([
                'status' => 'completed',
                'chunk_count' => $savedCount
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "ประมวลผลเอกสาร '{$title}' สำเร็จ (แยกได้ {$savedCount} ย่อหน้าความรู้)",
                'document' => $document
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Document upload processing failed: " . $e->getMessage());

            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => "ประมวลผลเอกสารไม่สำเร็จ: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete document and all associated chunks
     */
    public function destroy($id)
    {
        $document = RagDocument::findOrFail($id);
        $title = $document->title;
        $document->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ลบเอกสาร '{$title}' และความรู้ที่เกี่ยวข้องเรียบร้อยแล้ว"
            ]);
        }

        return redirect()->back()->with('success', "ลบเอกสาร '{$title}' เรียบร้อยแล้ว");
    }

    /**
     * Reindex / Reprocess existing document
     */
    public function reindex($id)
    {
        $document = RagDocument::findOrFail($id);
        $fullPath = storage_path('app/' . $document->file_path);

        if (!file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์ต้นฉบับบนเซิร์ฟเวอร์'
            ], 404);
        }

        try {
            $document->update(['status' => 'processing']);
            $document->chunks()->delete();

            $extractedPages = $this->parserService->extractText($fullPath, $document->file_type);
            $chunks = $this->parserService->chunkText($extractedPages);

            DB::beginTransaction();
            $savedCount = 0;
            foreach ($chunks as $chunkData) {
                $embedding = [];
                try {
                    $embedding = $this->aiService->getEmbedding($chunkData['content']);
                } catch (\Throwable $e) {
                    Log::warning("Re-embedding chunk {$chunkData['chunk_index']} warning: " . $e->getMessage());
                }

                RagChunk::create([
                    'document_id' => $document->id,
                    'chunk_index' => $chunkData['chunk_index'],
                    'content' => $chunkData['content'],
                    'embedding' => $embedding,
                    'page_number' => $chunkData['page_number']
                ]);
                $savedCount++;
            }

            $document->update([
                'status' => 'completed',
                'chunk_count' => $savedCount,
                'error_message' => null
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "ประมวลผลเอกสาร '{$document->title}' ใหม่สำเร็จ ({$savedCount} ย่อหน้า)"
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => "Re-index ล้มเหลว: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View chunks of a document (JSON for modal inspection)
     */
    public function chunks($id)
    {
        $document = RagDocument::with('chunks')->findOrFail($id);
        return response()->json([
            'success' => true,
            'document' => $document,
            'chunks' => $document->chunks
        ]);
    }

    /**
     * Ask AI (Q&A using RAG)
     */
    public function ask(Request $request)
    {
        $user = auth()->user();
        if (!$user || ($user->status !== 'admin' && ($user->allow_ai_copilot ?? 'N') !== 'Y')) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่ได้รับสิทธิ์ใช้งาน RiMS Copilot กรุณาติดต่อผู้ดูแลระบบเพื่อขอเปิดสิทธิ์'
            ], 403);
        }

        $request->validate([
            'question' => 'required|string|min:1'
        ], [
            'question.required' => 'กรุณาระบุคำถาม',
            'question.min' => 'คำถามสั้นเกินไป'
        ]);

        $question = $request->input('question');
        $history = $request->input('history', []);
        $result = $this->searchService->ask($question, 4, is_array($history) ? $history : []);

        return response()->json([
            'success' => true,
            'question' => $question,
            'answer' => $result['answer'],
            'sources' => $result['sources']
        ]);
    }

    /**
     * List all categories with document count (JSON)
     */
    public function listCategories()
    {
        $categories = RagCategory::withCount('documents')->orderBy('sort_order', 'asc')->get();
        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Store or update a category
     */
    public function saveCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer'
        ]);

        $id = $request->input('id');
        $data = [
            'name' => trim($request->input('name')),
            'description' => $request->input('description'),
            'color' => $request->input('color', '#198754') ?: '#198754',
            'sort_order' => (int) $request->input('sort_order', 0)
        ];

        if ($id) {
            $category = RagCategory::findOrFail($id);
            $category->update($data);
            $msg = 'อัปเดตข้อมูลหมวดหมู่เรียบร้อยแล้ว';
        } else {
            $category = RagCategory::create($data);
            $msg = 'สร้างหมวดหมู่ใหม่เรียบร้อยแล้ว';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'category' => $category
        ]);
    }

    /**
     * Delete a category
     */
    public function deleteCategory($id)
    {
        $category = RagCategory::findOrFail($id);
        // Untie documents
        RagDocument::where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "ลบหมวดหมู่ '{$category->name}' เรียบร้อยแล้ว"
        ]);
    }
}
