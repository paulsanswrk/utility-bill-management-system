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
        Schema::create('household_user', function (Blueprint $table) {
//            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('household_id')->constrained()->onDelete('cascade');

            $table->primary(['user_id', 'household_id']);
//            $table->boolean('is_creator')->default(false)->nullable(false);
            $table->timestamps();
        });
        /*Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('households', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('utility_companies', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('ix_utility_companies_name_unique_per_user');
            $table->dropColumn('user_id');

            $table->foreignId('household_id')->constrained()->onDelete('cascade');
            $table->unique(['household_id', 'name'], 'ix_utility_companies_name_unique_per_hh');
        });*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_user');
    }
};
