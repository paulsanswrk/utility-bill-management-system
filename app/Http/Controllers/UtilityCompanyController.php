<?php

namespace App\Http\Controllers;

use App\Models\UtilityCompany;
use Illuminate\Http\Request;

class UtilityCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UtilityCompany::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $utilityCompany = new UtilityCompany();
        $utilityCompany->name = $request->name;
        try {
            $utilityCompany->save();
            return ['success' => true, 'companies' => UtilityCompany::all()];
        } catch (\Exception $e) {

            $message = str_contains($e->getMessage(), 'ix_utility_companies_name_unique')? 'This company already exists!': 'There was an error while processing your request';
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
