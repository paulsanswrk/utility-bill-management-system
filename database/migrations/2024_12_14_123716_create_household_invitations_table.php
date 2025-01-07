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
        Schema::create('household_invitations', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('invitee_id')->nullable();
            $table->string('invitee_email');
            $table->unsignedBigInteger('invited_by');
            $table->string('household_ids')->nullable(false);
            $table->string('invitation_status')->nullable();

            $table->foreign('invitee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_invitations');
    }
};
