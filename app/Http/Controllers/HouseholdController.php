<?php

namespace App\Http\Controllers;

use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseholdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $household = new Household();
        $user_id = \Auth::id();
        $household->name = $request->name;
        try {
            $household->save();
            $household->users()->attach([
                $user_id => ['is_creator' => true, 'created_at' => now()],
            ]);

            /*DB::table('household_user')->insert([
                'user_id' => $household->user_id,
                'household_id' => $household->id,
                'is_creator' => true, // Assuming you want to track the creator in this table
            ]);*/
            return ['success' => true, 'new_id' => $household->id];
        } catch (\Exception $e) {
            $message = str_contains($e->getMessage(), 'ix_households_name_unique_per_user') ? 'This household already exists!' : 'There was an error while processing your request';
            return ['success' => false, 'message' => $message];
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Household $household)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Household $household)
    {
        $household = Household::find($request->id);
        if (empty($household) || $household->user_id != \Auth::id()) {
            return ['success' => false, 'message' => 'Household not found'];
        }

        $household->name = $request->name;
        $household->save();
        return ['success' => true];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $household_id = $request->id;

        DB::table('bills')
            ->where('household_id', '=', $household_id)
            ->whereNull('company_id')
            ->delete();

        try {
            DB::table('households')->where('id', '=', $household_id)->delete();
            return ['success' => true];
        } catch (\Exception $e) {
            $message = str_contains($e->getMessage(), 'fk_bills_households') ? 'Cannot delete a non-empty household' : 'There was an error while processing your request';
            return ['success' => false, 'message' => $message];
        }
    }

    public static function has_access($user_id, $household_id)
    {
        return DB::table('household_user')
            ->where('user_id', $user_id)
            ->where('household_id', $household_id)
            ->exists();
    }
}
