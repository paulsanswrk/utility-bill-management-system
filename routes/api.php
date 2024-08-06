<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource("/companies", "App\Http\Controllers\UtilityCompanyController");
Route::resource("/companies/store", "App\Http\Controllers\UtilityCompanyController");

Route::post("/households/store", [HouseholdController::class, 'store'])->middleware('auth:sanctum');
Route::post("/households/update", [HouseholdController::class, 'update'])->middleware('auth:sanctum');
Route::post("/households/destroy", [HouseholdController::class, 'destroy'])->middleware('auth:sanctum');

Route::resource("/bills", "App\Http\Controllers\BillController")->middleware('auth:sanctum');
Route::resource('/bills/store', "App\Http\Controllers\BillController")->middleware('auth:sanctum');
Route::post('/bills/save', [BillController::class, 'update'])->middleware('auth:sanctum');
Route::post('/bills/destroy', [BillController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/bills/pdf/{bill_id}/{doc_type}/{download?}', [BillController::class, 'pdf'])->middleware('auth:sanctum');
Route::post('/bills/delete_pdf', [BillController::class, 'delete_pdf'])->middleware('auth:sanctum');

Route::post('/upload/{bill_id}/{doc_type}', [FileUploadController::class, 'upload'])->name('upload')->middleware('auth:sanctum');

Route::post('/profile/set_locale/{language}', [ProfileController::class, 'set_locale']);


