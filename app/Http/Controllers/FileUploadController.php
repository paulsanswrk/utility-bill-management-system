<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
//            'bill_id' => ['required', 'integer', ], //'exists:bills,id'
            'bill_pdf' => 'required|mimes:pdf|max:50000000',
        ]);

        $bill_pdf = $request->file('bill_pdf');
        $path = $bill_pdf->store('uploads', 'private');

        // Additional logic (e.g., storing file information in the database)

        return ['success' => true, 'path' => $path, 'bill_id' => $request->bill_id];
    }
}
