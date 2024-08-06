<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Services\UBMS_Security_Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileUploadController extends Controller
{
    private UBMS_Security_Service $ubms_security_service;

    function __construct(UBMS_Security_Service $ubms_security_service)
    {
        $this->ubms_security_service = $ubms_security_service;
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

        $this->ubms_security_service->encrypt_file_with_user_key($bill_pdf->path(), $bill->cipher_key_encrypted);
        $path = $bill_pdf->store(Bill::pdf_tmp_upload_path, 'private');


        $bill = Bill::find($bill_id);

        switch ($doc_type) {
            case 'bill':
                $bill->bill_pdf_path = "private/$path";
                break;
            case 'payment_confirmation':
                $bill->payment_confirmation_pdf_path = "private/$path";
//                $bill->paid = true;
                break;
        }

        $bill->save();


        return ['success' => true, 'path' => $path, 'bill_id' => $bill_id];
    }
}
