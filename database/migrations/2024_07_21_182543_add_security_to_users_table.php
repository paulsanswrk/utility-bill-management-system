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
        Schema::table('users', function (Blueprint $table) {
            $table->string('security_question')->nullable();
            $table->string('security_answer_hash')->nullable(); //salt + hash
            $table->string('key_salt')->nullable();
            $table->string('work_key_encrypted')->nullable();
            $table->string('pwd_key_encrypted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('security_question');
            $table->dropColumn('security_answer_hash');
            $table->dropColumn('key_salt');
            $table->dropColumn('work_key_encrypted');
            $table->dropColumn('pwd_key_encrypted');
        });
    }
};
