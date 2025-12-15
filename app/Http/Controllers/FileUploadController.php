<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Services\GeminiPdfAnalyzerService;
use App\Services\UBMS_Security_Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    private UBMS_Security_Service $ubms_security_service;
    private GeminiPdfAnalyzerService $geminiAnalyzer;

    function __construct(UBMS_Security_Service $ubms_security_service, GeminiPdfAnalyzerService $geminiAnalyzer)
    {
        $this->ubms_security_service = $ubms_security_service;
        $this->geminiAnalyzer = $geminiAnalyzer;
    }

    public function upload(Request $request)
    {
        $request->validate([
//            'bill_id' => ['required', 'integer', ], //'exists:bills,id'
            'pdf' => 'required|mimes:pdf|max:50000000',
        ]);

        $bill_id = $request->bill_id;
        $doc_type = $request->doc_type;

        $bill_pdf = $request->file('pdf');

        $bill = Bill::find($bill_id);

        // Analyze the PDF with Gemini BEFORE encryption (only for bill type)
        $bill_summary = null;
        if ($doc_type === 'bill') {
            try {
                $bill_summary = $this->geminiAnalyzer->analyze($bill_pdf->path());
            } catch (\Throwable $e) {
                Log::error('PDF analysis failed during upload: ' . $e->getMessage());
            }
        }

        $this->ubms_security_service->encrypt_file_with_user_key($bill_pdf->path(), $bill->cipher_key_encrypted);
        $path = $bill_pdf->store(Bill::pdf_tmp_upload_path, 'private');


        $bill = Bill::find($bill_id);

        switch ($doc_type) {
            case 'bill':
                $bill->bill_pdf_path = "private/$path";
                if ($bill_summary) {
                    $bill->bill_summary = $bill_summary;
                }
                break;
            case 'payment_confirmation':
                $bill->payment_confirmation_pdf_path = "private/$path";
//                $bill->paid = true;
                break;
        }

        $bill->save();


        return ['success' => true, 'path' => $path, 'bill_id' => $bill_id, 'bill_summary' => $bill_summary];
    }
}
