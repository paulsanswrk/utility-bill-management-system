<?php

use App\Services\GeminiPdfAnalyzerService;

it('analyzes a real PDF bill via Gemini API', function () {
    $apiKey = config('services.gemini.api_key');

    if (empty($apiKey)) {
        $this->markTestSkipped('GEMINI_API_KEY is not set — skipping live API test.');
    }

    $pdfPath = base_path('tests/fixtures/Sample_Utility_Bill.pdf');
    expect(file_exists($pdfPath))->toBeTrue('Test fixture PDF not found');

    $service = new GeminiPdfAnalyzerService();
    $summary = $service->analyze($pdfPath);

    // Dump the summary so we can see it in test output
    dump($summary);

    expect($summary)
        ->not->toBeNull('Gemini should return a summary')
        ->and($summary)->toBeString()
        ->and(strlen($summary))->toBeGreaterThan(20, 'Summary should be a meaningful length');
});
