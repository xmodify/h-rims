<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use ZipArchive;

class DocumentParserService
{
    /**
     * Parse document and return plain text
     *
     * @param string $filePath Full absolute path to file
     * @param string $extension File extension (pdf, docx, txt, md, json, xlsx)
     * @return array Array of pages or text segments: [['text' => '...', 'page' => 1]]
     */
    public function extractText(string $filePath, string $extension): array
    {
        $ext = strtolower($extension);

        switch ($ext) {
            case 'txt':
            case 'md':
            case 'csv':
                return $this->parsePlainText($filePath);

            case 'json':
                return $this->parseJsonText($filePath);

            case 'docx':
                return $this->parseDocx($filePath);

            case 'pdf':
                return $this->parsePdf($filePath);

            default:
                return $this->parsePlainText($filePath);
        }
    }

    /**
     * Chunk text segments into manageable vector chunks with overlap
     *
     * @param array $extractedPages [['text' => '...', 'page' => 1]]
     * @param int $chunkSize Target character size per chunk (default ~800)
     * @param int $chunkOverlap Overlap characters (default ~150)
     * @return array Array of chunks: [['chunk_index' => 0, 'content' => '...', 'page_number' => 1]]
     */
    public function chunkText(array $extractedPages, int $chunkSize = 800, int $chunkOverlap = 150): array
    {
        $chunks = [];
        $chunkIndex = 0;

        foreach ($extractedPages as $pageData) {
            $text = trim($pageData['text'] ?? '');
            $pageNumber = $pageData['page'] ?? null;

            if (empty($text)) {
                continue;
            }

            // Clean multiple whitespaces but preserve paragraphs
            $text = preg_replace("/[ \t]+/", " ", $text);
            $text = preg_replace("/\n{3,}/", "\n\n", $text);

            $length = mb_strlen($text);

            if ($length <= $chunkSize) {
                $chunks[] = [
                    'chunk_index' => $chunkIndex++,
                    'content' => $text,
                    'page_number' => $pageNumber
                ];
                continue;
            }

            $offset = 0;
            while ($offset < $length) {
                $chunkText = mb_substr($text, $offset, $chunkSize);

                // Try to break cleanly at sentence or paragraph boundary
                if ($offset + $chunkSize < $length) {
                    $lastBreak = -1;
                    $delimiters = ["\n\n", "\n", ". ", "। ", " ", "  "];
                    foreach ($delimiters as $delim) {
                        $pos = mb_strrpos($chunkText, $delim);
                        if ($pos !== false && $pos > ($chunkSize * 0.6)) {
                            $lastBreak = $pos + mb_strlen($delim);
                            break;
                        }
                    }

                    if ($lastBreak > 0) {
                        $chunkText = mb_substr($chunkText, 0, $lastBreak);
                        $offset += $lastBreak - $chunkOverlap;
                    } else {
                        $offset += $chunkSize - $chunkOverlap;
                    }
                } else {
                    $offset += $chunkSize;
                }

                $cleanChunk = trim($chunkText);
                if (mb_strlen($cleanChunk) > 20) { // Ignore tiny fragmented artifacts
                    $chunks[] = [
                        'chunk_index' => $chunkIndex++,
                        'content' => $cleanChunk,
                        'page_number' => $pageNumber
                    ];
                }
            }
        }

        return $chunks;
    }

    /**
     * Parse plain text / Markdown / CSV
     */
    protected function parsePlainText(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }
        $content = file_get_contents($filePath);
        // Ensure UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'TIS-620, ISO-8859-1, Windows-874');
        }
        return [['text' => $content, 'page' => 1]];
    }

    /**
     * Parse JSON file
     */
    protected function parseJsonText(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }
        $data = json_decode(file_get_contents($filePath), true);
        if (empty($data)) {
            return [];
        }

        // Format JSON into readable key-value sentences
        $lines = [];
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    $lines[] = json_encode($val, JSON_UNESCAPED_UNICODE);
                } else {
                    $lines[] = "{$key}: {$val}";
                }
            }
        }

        return [['text' => implode("\n", $lines), 'page' => 1]];
    }

    /**
     * Parse DOCX using ZipArchive
     */
    protected function parseDocx(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xmlContent) {
                // Convert <w:p> paragraph tags to newlines
                $xmlContent = str_replace('</w:p>', "\n", $xmlContent);
                $xmlContent = str_replace('</w:tr>', "\n", $xmlContent);
                $xmlContent = str_replace('</w:tc>', "\t", $xmlContent);

                // Strip all other XML tags
                $text = strip_tags($xmlContent);
                return [['text' => html_entity_decode($text, ENT_QUOTES, 'UTF-8'), 'page' => 1]];
            }
        }

        return [];
    }

    /**
     * Parse PDF text
     */
    protected function parsePdf(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        // 1. High-accuracy Python extractor (pdfplumber / pypdf)
        $scriptPath = base_path('app/Services/Ai/extract_pdf.py');
        if (file_exists($scriptPath)) {
            try {
                $cmd = 'python "' . $scriptPath . '" "' . $filePath . '" 2>&1';
                $output = @shell_exec($cmd);
                if ($output) {
                    $decoded = json_decode($output, true);
                    if (is_array($decoded) && !empty($decoded)) {
                        return $decoded;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Python PDF extraction warning: " . $e->getMessage());
            }
        }

        // 2. Fallback to PHP native stream parsing
        $rawContent = @file_get_contents($filePath);
        if (empty($rawContent)) {
            return [];
        }

        $pages = [];
        // Split by /Page or /Contents markers if available
        $pageChunks = preg_split('/\/Type\s*\/Page[^s]/i', $rawContent);

        if (count($pageChunks) > 1) {
            $pageNo = 1;
            foreach (array_slice($pageChunks, 1) as $pageChunk) {
                $pageText = $this->extractTextFromPdfStream($pageChunk);
                if (!empty(trim($pageText))) {
                    $pages[] = [
                        'text' => $pageText,
                        'page' => $pageNo
                    ];
                }
                $pageNo++;
            }
        }

        // Fallback: If page splitting failed or gave empty text, parse whole stream
        if (empty($pages)) {
            $allText = $this->extractTextFromPdfStream($rawContent);
            if (!empty(trim($allText))) {
                $pages[] = [
                    'text' => $allText,
                    'page' => 1
                ];
            }
        }

        return $pages;
    }

    /**
     * Helper to extract uncompressed streams and text tokens from PDF
     */
    protected function extractTextFromPdfStream(string $stream): string
    {
        $text = '';

        // Extract and decompress flate streams
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $stream, $matches)) {
            foreach ($matches[1] as $compressed) {
                $decompressed = @gzuncompress($compressed);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($compressed);
                }
                if ($decompressed !== false) {
                    $text .= "\n" . $this->extractTextTokens($decompressed);
                }
            }
        }

        if (empty(trim($text))) {
            $text = $this->extractTextTokens($stream);
        }

        return trim($text);
    }

    /**
     * Extract PDF text operators: (text) Tj, [(t)(e)(x)(t)] TJ
     */
    protected function extractTextTokens(string $content): string
    {
        $result = '';

        // Match BT ... ET blocks
        if (preg_match_all('/BT[\r\n]+(.*?)[\r\n]+ET/s', $content, $btMatches)) {
            foreach ($btMatches[1] as $btBlock) {
                // Match (Text) Tj
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $btBlock, $tjMatches)) {
                    foreach ($tjMatches[1] as $m) {
                        $result .= $this->unescapePdfString($m) . " ";
                    }
                }
                // Match [(t)(e)(x)(t)] TJ
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $btBlock, $tjArrayMatches)) {
                    foreach ($tjArrayMatches[1] as $arr) {
                        if (preg_match_all('/\((.*?)\)/s', $arr, $subMatches)) {
                            foreach ($subMatches[1] as $sub) {
                                $result .= $this->unescapePdfString($sub);
                            }
                            $result .= " ";
                        }
                    }
                }
                $result .= "\n";
            }
        }

        return $result;
    }

    protected function unescapePdfString(string $str): string
    {
        $str = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
        // Handle Octal escapes \000
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);
        return $str;
    }
}
