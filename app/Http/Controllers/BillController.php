<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\UtilityCompany;
use App\Services\UBMS_Security_Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use STS\ZipStream\Facades\Zip;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillController extends Controller
{
    private UBMS_Security_Service $ubms_security_service;

    function __construct(UBMS_Security_Service $ubms_security_service)
    {
        $this->ubms_security_service = $ubms_security_service;
    }

    /**
     * @return mixed[]
     */
    private function getBillsOfCurrentUser()
    {
        //TODO: use FK instead
        $companies = UtilityCompany::all()->where('user_id', '=', Auth::id())->groupBy('id');

        return Bill::all()->where('user_id', '=', Auth::id())->whereNotNull('data')->values()
            ->map(function ($bill) use ($companies) {
                $data = json_decode($bill->data);
                return [
                    'id' => $bill->id,
                    'utility_company_id' => $data->utility_company_id,
                    'utility_company_name' => $companies[$data->utility_company_id][0]?->name,
                    'amount' => $data->amount,
                    'bill_date' => $data->bill_date,
                    'payment_date' => $data->payment_date,
                    'paid' => $data->paid,
                    'has_bill_pdf' => !empty($bill->bill_pdf_path),
                    'has_payment_pdf' => !empty($bill->payment_confirmation_pdf_path),
                ];
            })
            ->sortByDesc('bill_date')->values();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
//        return Bill::all();
        return ['user_bills' => $this->getBillsOfCurrentUser(), 'user_companies' => UtilityCompany::getCompaniesOfUser(Auth::id())];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $bill = new Bill();
        $bill->user_id = Auth::id();
        $bill->household_id = 1; //TODO: define household_id in frontend
//        $bill->data = 'test data';
        $bill->save();

        return ['success' => true, 'new_id' => $bill->id];
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $bill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        try {
            $request->validate([
                'id' => ['required', 'integer', 'exists:bills,id'], //
            ]);
            $bill_id = $request->id;
            $bill = Bill::find($bill_id);


            $bill->data = json_encode([
                'utility_company_id' => $request->utility_company_id,
                'amount' => $request->amount,
                'bill_date' => $request->bill_date,
                'payment_date' => $request->payment_date,
                'paid' => $request->paid,
            ]);

            //move PDFs to the permanent location if still in Temp
            $permanent_bill_pdf_path = $bill->get_bill_pdf_path($request->utility_company_id, $request->bill_date, 'bill');
            if (!empty($bill->bill_pdf_path) && $permanent_bill_pdf_path !== $bill->bill_pdf_path) {
                //TODO: log unsuccessful attempts
                $success = Storage::move($bill->bill_pdf_path, $permanent_bill_pdf_path);
                if (!$success)
                    Log::error("failed to move file $bill->bill_pdf_path to $permanent_bill_pdf_path");
                $bill->bill_pdf_path = $permanent_bill_pdf_path;
            }

            $permanent_bill_pdf_path = $bill->get_bill_pdf_path($request->utility_company_id, $request->bill_date, 'payment_confirmation');
            if (!empty($bill->payment_confirmation_pdf_path) && $permanent_bill_pdf_path !== $bill->payment_confirmation_pdf_path) {
                $success = Storage::move($bill->payment_confirmation_pdf_path, $permanent_bill_pdf_path);
                Log::error("failed to move file $bill->payment_confirmation_pdf_path to $permanent_bill_pdf_path");
                $bill->payment_confirmation_pdf_path = $permanent_bill_pdf_path;
            }

            $bill->save();
            return ['success' => true, 'user_bills' => $this->getBillsOfCurrentUser(), 'user_companies' => UtilityCompany::getCompaniesOfUser(Auth::id())];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $bill = Bill::find($request->id);

        if (!empty($bill->bill_pdf_path))
            Storage::delete($bill->bill_pdf_path);

        if (!empty($bill->payment_confirmation_pdf_path))
            Storage::delete($bill->payment_confirmation_pdf_path);

        Bill::destroy($request->id);

        return ['success' => true, 'user_bills' => $this->getBillsOfCurrentUser(), 'user_companies' => UtilityCompany::getCompaniesOfUser(Auth::id())];
    }

    public function pdf(Request $request)
    {
        $bill_id = $request->bill_id;
        $doc_type = $request->doc_type;
        $download = $request->download;

        $bill = Bill::find($bill_id);

        switch ($doc_type) {
            case 'bill':
                $path = $bill->bill_pdf_path;
                $doc_name = "bill.pdf";
                break;
            case 'payment_confirmation':
                $path = $bill->payment_confirmation_pdf_path;
                $doc_name = "payment_confirmation.pdf";
                break;
        }

        $storage_path = storage_path("app/$path");

        $headers = [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Content-Type' => 'application/pdf',
        ];

        $bytes = file_get_contents($storage_path);
        $bytes = $this->ubms_security_service->decrypt_with_user_key($bytes, Auth::user()->work_key_encrypted);

        return response()->streamDownload(function () use ($bytes) {
            echo $bytes;
        }, $doc_name, $headers);
    }

    public function zip()
    {
        $user_id = Auth::id();

        $files = Storage::disk('private')->allFiles("bills/user_{$user_id}");

        $zip = Zip::create("package.zip");

        foreach ($files as $f) {
            $zip->add(storage_path("app/private/$f"), substr($f, strlen("bills/user_{$user_id}")));
        }

        return $zip;

    }
}
