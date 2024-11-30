<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_change_requests', function (Blueprint $table) {

            $table->unsignedBigInteger('id')->primary();
            $table->string('new_email')->nullable(false);
            $table->timestamp('expires_at')->nullable(false);
            $table->uuid('uuid')->nullable(false)->unique();
            $table->foreign('id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
