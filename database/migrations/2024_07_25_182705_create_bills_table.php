<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('household_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('bill_date')->nullable();
            $table->string('payment_date')->nullable();
            $table->decimal('amount')->default(0);
            $table->boolean('paid')->default(false);
            $table->string('bill_pdf_path')->nullable();
            $table->string('payment_confirmation_pdf_path')->nullable();
            $table->string('cipher_key_encrypted')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('utility_companies');
            $table->foreign('household_id', 'fk_bills_households')->references('id')->on('households')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
