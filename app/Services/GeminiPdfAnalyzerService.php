<?php

namespace App\Services;

use GeminiAPI\Client;
use GeminiAPI\Enums\MimeType;
use GeminiAPI\Resources\Parts\FilePart;
use GeminiAPI\Resources\Parts\TextPart;
use Illuminate\Support\Facades\Log;

class GeminiPdfAnalyzerService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Analyze a PDF file using Gemini and return a structured summary.
     *
     * @param string $filePath Absolute path to the raw (unencrypted) PDF file
     * @return string|null The generated summary, or null on failure
     */
    public function analyze(string $filePath): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('GeminiPdfAnalyzerService: GEMINI_API_KEY is not set. Skipping PDF analysis.');
            return null;
        }

        try {
            $pdfBytes = file_get_contents($filePath);
            if ($pdfBytes === false) {
                Log::error("GeminiPdfAnalyzerService: Could not read file at {$filePath}");
                return null;
            }

            $base64Pdf = base64_encode($pdfBytes);

            $client = (new Client($this->apiKey))->withV1BetaVersion();
            $model = $client->generativeModel('gemini-2.5-flash');

            $response = $model->generateContent(
                new TextPart(
                    'Analyze this utility bill PDF and provide a concise summary including: ' .
                    '1) Company/Provider name, ' .
                    '2) Billing period, ' .
                    '3) Account number (if visible), ' .
                    '4) Total amount due, ' .
                    '5) Due date (if visible), ' .
                    '6) Key line items or charges. ' .
                    'Keep the summary brief and well-structured.'
                ),
                new FilePart(MimeType::FILE_PDF, $base64Pdf),
            );

            $summary = $response->text();

            return !empty($summary) ? $summary : null;
        } catch (\Throwable $e) {
            Log::error('GeminiPdfAnalyzerService: PDF analysis failed - ' . $e->getMessage());
            return null;
        }
    }
}
