<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource("/bills","App\Http\Controllers\BillController");
Route::resource("/bills/store","App\Http\Controllers\BillController");
Route::resource("/companies","App\Http\Controllers\UtilityCompanyController");
Route::resource("/companies/store","App\Http\Controllers\UtilityCompanyController");

Route::post('/upload/{bill_id}', [FileUploadController::class, 'upload'])->name('upload');


