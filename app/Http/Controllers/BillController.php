<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Household;
use App\Models\UtilityCompany;
use App\Repositories\UBMS_Repo;
use App\Services\UBMS_Helper;
use App\Services\UBMS_Security_Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use STS\ZipStream\Facades\Zip;

class BillController extends Controller
{
    private UBMS_Security_Service $ubms_security_service;
    private UBMS_Helper $ubms_helper;
    private UBMS_Repo $ubms_repo;

    function __construct(UBMS_Security_Service $ubms_security_service, UBMS_Helper $ubms_helper, UBMS_Repo $ubms_repo)
    {
        $this->ubms_security_service = $ubms_security_service;
        $this->ubms_helper = $ubms_helper;
        $this->ubms_repo = $ubms_repo;
    }

    /**
     * @return mixed[]
     */
    private function getBillsOfCurrentUser(int $household_id = 0)
    {
        /*if (!HouseholdController::has_access(Auth::id(), $household_id) || !($household = Household::find($household_id))) {
            return [];
        }

        $bills = $household->bills()->where('household_id', $household_id)
            ->whereNotNull('company_id')
            ->get();*/


        /*        $bills = Bill::all()
                    ->where('user_id', '=', $user_id)
                    ->where('household_id', '=', $household_id)
                    ->whereNotNull('company_id');*/
        $user_id = Auth::id();

        $bills = DB::select("SELECT b.id,
       b.household_id,
       b.company_id                                          'utility_company_id',
       c.name                                                'utility_company_name',
       b.amount,
       b.paid,
       b.bill_date,
       b.payment_date,
       if(b.bill_pdf_path is not null, 1, 0)                 has_bill_pdf,
       if(b.payment_confirmation_pdf_path is not null, 1, 0) has_payment_pdf
FROM `bills` b
         INNER JOIN `households` hh ON hh.`id` = b.`household_id`
         LEFT JOIN `utility_companies` c ON c.`id` = b.`company_id`
         INNER JOIN `household_user` hu ON hu.`household_id` = hh.`id`
         INNER JOIN `users` u ON u.`id` = hu.`user_id`
WHERE u.`id` = $user_id
  and b.company_id is not null
  # and b.bill_pdf_path is not null
and ($household_id = 0 or $household_id = b.household_id)
order by b.bill_date desc");

        foreach ($bills as &$bill) {
            $bill->paid = !!$bill->paid;
        }

        return $bills;

    }

    public static function check_access_to_bill(int $user_id, int $bill_id): bool
    {
        $result = DB::select("select exists(select 1
                        from bills b
                        join household_user hu on hu.household_id = b.household_id
                        where b.id=$bill_id and hu.user_id=$user_id)");

        return !!$result;
    }

    /**
     * Display a listing of the resource.
     */
    public function get_bills(Request $request)
    {

        $household_id = $request->household_id ?? 0;
        $hh_bills = $hh_companies = [];
        if ($household_id) {
            $household = Household::find($household_id);
            $hh_companies = $household->utility_companies()->select('utility_companies.id', 'utility_companies.name')->orderBy('name')->get();
            $hh_bills = $this->getBillsOfCurrentUser($household_id);
        }

        $user_households = Household::get_user_households();

        return [
            'user_households' => $user_households,
            'hh_bills' => $hh_bills,
            'hh_companies' => $hh_companies,
            'invitations' => (new HouseholdController($this->ubms_helper))->get_invitations_for_invitee(Auth::id()),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $bill = new Bill();
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

            return ['success' => true,
                'user_bills' => $this->getBillsOfCurrentUser($household_id),
                'user_companies' => UtilityCompany::getCompaniesForHH($household_id)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $bill_id = $request->id;
        $household_id = $request->household_id;
        $user_id = Auth::id();

        if (!self::check_access_to_bill($user_id, $bill_id)) {
            return ['success' => false, 'message' => "You don't have permission to delete this bill"];
        }

        $bill = Bill::find($bill_id);

        if (!empty($bill->bill_pdf_path))
            Storage::delete($bill->bill_pdf_path);

        if (!empty($bill->payment_confirmation_pdf_path))
            Storage::delete($bill->payment_confirmation_pdf_path);

        Bill::destroy($bill_id);

        return ['success' => true,
            'user_bills' => $this->getBillsOfCurrentUser($household_id),
            'companies' => UtilityCompany::getCompaniesForHH($household_id)];
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

//        $bills = Bill::all()->where('user_id', '=', $user_id)->whereNotNull('company_id')->values();

        $bills = $this->ubms_repo->get_user_bills($user_id);

        $zip = Zip::create("package.zip");

        $suffix_4_dup_zip_paths = [];

        foreach ($bills as $bill) {
            foreach (['bill_pdf_path' => 'bill', 'payment_confirmation_pdf_path' => 'payment_confirmation'] as $doc_type => $fn) {
                if ($bill->$doc_type) {
                    $zip_path = "{$bill->household_name}/{$bill->bill_date}/{$bill->utility_company_name}/{$fn}.pdf";

                    $suffix = $suffix_4_dup_zip_paths[$zip_path] ?? 0;
                    if ($suffix)
                        $zip_path = "{$bill->household_name}/{$bill->bill_date}/{$bill->utility_company_name}/{$fn}-{$suffix}.pdf";
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
