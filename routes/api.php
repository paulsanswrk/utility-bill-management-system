<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManageUsersController;
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
Route::post('/profile/set_notifications', [ProfileController::class, 'set_notifications'])->name('set_notifications');

Route::get("/users", [ManageUsersController::class, 'index'])->middleware('auth:sanctum');
Route::post("/update_user", [ManageUsersController::class, 'update_user'])->middleware('auth:sanctum');
Route::post("/change_pwd", [ManageUsersController::class, 'change_pwd'])->middleware('auth:sanctum');
Route::post("/users/send_pwd_reset_link", [ManageUsersController::class, 'send_pwd_reset_link'])->middleware('auth:sanctum');
Route::post("/users/impersonate", [ManageUsersController::class, 'impersonate'])->middleware('auth:sanctum');
Route::post("/users/exit_impersonation", [ManageUsersController::class, 'exit_impersonation'])->middleware('auth:sanctum');

