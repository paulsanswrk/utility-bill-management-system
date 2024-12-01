<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\UtilityCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UtilityCompanyController extends Controller
{

    /**
 * Display a listing of the resource.
 */
/*    public function index()
    {
        return UtilityCompany::getCompaniesForHH(Auth::id());
    }*/

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $utilityCompany = new UtilityCompany();
        $utilityCompany->name = $request->name;
        $household_id = $request->hh_id;
        $utilityCompany->household_id = $household_id;
        try {
            $utilityCompany->save();
            return ['success' => true, 'companies' => UtilityCompany::getCompaniesForHH($household_id)];
        } catch (\Exception $e) {
            $message = str_contains($e->getMessage(), 'ix_utility_companies_name_unique_per_user')? 'This company already exists!': 'There was an error while processing your request';
            return ['success' => false, 'message' => $message];
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UtilityCompany $utilityCompany)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UtilityCompany $utilityCompany)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UtilityCompany $utilityCompany)
    {
        //
    }
}
