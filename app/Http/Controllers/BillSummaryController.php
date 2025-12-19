<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillSummaryController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->is_admin !== 1) {
            return [
                'success' => false,
                'message' => 'You have no access to this page',
            ];
        }

        $page = $request->get('page', 1);
        $rows = $request->get('rows', 20);
        $sortField = $request->get('sortField', 'bill_date');
        $sortOrder = $request->get('sortOrder', 'desc');

        $allowedSortFields = ['bill_date', 'utility_company_name', 'user_name', 'amount', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'bill_date';
        }
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        $query = DB::table('bills as b')
            ->join('users as u', 'u.id', '=', 'b.user_id')
            ->leftJoin('utility_companies as c', 'c.id', '=', 'b.company_id')
            ->leftJoin('households as h', 'h.id', '=', 'b.household_id')
            ->whereNotNull('b.bill_summary')
            ->select([
                'b.id',
                'b.bill_date',
                'b.amount',
                'b.bill_summary',
                'b.created_at',
                'u.name as user_name',
                'u.email as user_email',
                'c.name as utility_company_name',
                'h.name as household_name',
            ]);

        $total = $query->count();

        $data = $query
            ->orderBy($sortField, $sortOrder)
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->get();

        return [
            'success' => true,
            'summaries' => [
                'data' => $data,
                'total' => $total,
                'current_page' => (int) $page,
            ],
        ];
    }
}
