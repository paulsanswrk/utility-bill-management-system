<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Household;
use App\Models\UtilityCompany;
use App\Services\UBMS_Security_Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    private function getBillsOfCurrentUser(int $household_id)
    {
        $bills = Bill::all()
            ->where('user_id', '=', Auth::id())
            ->where('household_id', '=', $household_id)
            ->whereNotNull('company_id');

        return $bills
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'household_id' => $bill->household_id,
                    'utility_company_id' => $bill->company_id,
                    'utility_company_name' => $bill->company?->name,
                    'amount' => $bill->amount,
                    'paid' => $bill->paid,
                    'bill_date' => $bill->bill_date,
                    'payment_date' => $bill->payment_date,
                    'has_bill_pdf' => !empty($bill->bill_pdf_path),
                    'has_payment_pdf' => !empty($bill->payment_confirmation_pdf_path),
                ];
            })
            ->sortByDesc('bill_date')->values();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $household_id = $request->household_id;
        if (!$household_id) {
            $first_household = DB::table('households')
                ->where('user_id', '=', Auth::id())
                ->orderBy('name')
                ->limit(1)
                ->get()->first();
            $household_id = $first_household?->id ?? 0;
        }
//        return Bill::all();
        return [
            'user_bills' => $this->getBillsOfCurrentUser($household_id),
            'user_companies' => UtilityCompany::getCompaniesOfUser(Auth::id()),
            'user_households' => Household::getHouseholdsOfUser(Auth::id()),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $bill = new Bill();
        $bill->user_id = Auth::id();
        $bill->household_id = $request->household_id;
        $bill->cipher_key_encrypted = bin2hex($this->ubms_security_service->gen_key_4_bill());

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

            $bill->company_id = $request->utility_company_id;
            $bill->bill_date = $request->bill_date;
            $bill->payment_date = $request->payment_date;
            $bill->amount = $request->amount;
            $bill->paid = $request->paid;

            //move PDFs to the permanent location if still in Temp
            $permanent_bill_pdf_path = $bill->get_bill_pdf_path('bill');
            if (!empty($bill->bill_pdf_path) && $permanent_bill_pdf_path !== $bill->bill_pdf_path) {
                //TODO: log unsuccessful attempts
                $success = Storage::move($bill->bill_pdf_path, $permanent_bill_pdf_path);
                if (!$success)
                    Log::error("failed to move file $bill->bill_pdf_path to $permanent_bill_pdf_path");
                $bill->bill_pdf_path = $permanent_bill_pdf_path;
            }

            $permanent_bill_pdf_path = $bill->get_bill_pdf_path('payment_confirmation');
            if (!empty($bill->payment_confirmation_pdf_path) && $permanent_bill_pdf_path !== $bill->payment_confirmation_pdf_path) {
                $success = Storage::move($bill->payment_confirmation_pdf_path, $permanent_bill_pdf_path);
                Log::error("failed to move file $bill->payment_confirmation_pdf_path to $permanent_bill_pdf_path");
                $bill->payment_confirmation_pdf_path = $permanent_bill_pdf_path;
            }

            $bill->save();

            $household_id = $request->household_id;

            return ['success' => true, 'user_bills' => $this->getBillsOfCurrentUser($household_id), 'user_companies' => UtilityCompany::getCompaniesOfUser(Auth::id())];
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

        if ($bill->user_id != Auth::id())
            return ['success' => false, 'message' => 'Bill not found'];

        $household_id = $request->household_id;

        if (!empty($bill->bill_pdf_path))
            Storage::delete($bill->bill_pdf_path);

        if (!empty($bill->payment_confirmation_pdf_path))
            Storage::delete($bill->payment_confirmation_pdf_path);

        Bill::destroy($request->id);

        return ['success' => true, 'user_bills' => $this->getBillsOfCurrentUser($household_id), 'user_companies' => UtilityCompany::getCompaniesOfUser(Auth::id())];
    }

    public function delete_pdf(Request $request)
    {
        $bill = Bill::find($request->id);

        if ($bill->user_id != Auth::id())
            return ['success' => false, 'message' => 'Bill not found'];

        $household_id = $request->household_id;
        $doc_type = $request->doctype;

        switch ($doc_type) {
            case 'bill':
                $path = $bill->bill_pdf_path;
                $bill->bill_pdf_path = null;
                break;
            case 'payment_confirmation':
                $path = $bill->payment_confirmation_pdf_path;
                $bill->payment_confirmation_pdf_path = null;
                break;
        }

        if (!empty($path))
            Storage::delete($path);

        $bill->save();

        return ['success' => true, 'user_bills' => $this->getBillsOfCurrentUser($household_id)];
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
        $bytes = $this->ubms_security_service->decrypt_with_user_key($bytes, $bill->cipher_key_encrypted);

        return response()->streamDownload(function () use ($bytes) {
            echo $bytes;
        }, $doc_name, $headers);
    }

    public function zip()
    {
        $user_id = Auth::id();

        $bills = Bill::all()->where('user_id', '=', $user_id)->whereNotNull('company_id')->values();

        $zip = Zip::create("package.zip");

        $suffix_4_dup_zip_paths = [];

        foreach ($bills as $bill) {
            foreach (['bill_pdf_path' => 'bill', 'payment_confirmation_pdf_path' => 'payment_confirmation'] as $doc_type => $fn) {
                if ($bill->$doc_type) {
                    $zip_path = "{$bill->household->name}/{$bill->bill_date}/{$bill->company->name}/{$fn}.pdf";

                    $suffix = $suffix_4_dup_zip_paths[$zip_path] ?? 0;
                    if ($suffix)
                        $zip_path = "{$bill->household->name}/{$bill->bill_date}/{$bill->company->name}/{$fn}-{$suffix}.pdf";
                    $suffix_4_dup_zip_paths[$zip_path] = $suffix + 1;

                    $full_fn = storage_path("app/{$bill->$doc_type}");
                    $cipher_bytes = file_get_contents($full_fn);
                    $plain_bytes = $this->ubms_security_service->decrypt_with_user_key($cipher_bytes, $bill->cipher_key_encrypted);
                    $zip->addRaw($plain_bytes, $zip_path);
                }
            }
        }

        return $zip;

    }
}
