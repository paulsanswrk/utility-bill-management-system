<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;

it('', function () {
    config(['database.default' => 'mysql']);

    $user_id = 1;
    $household_id = 1;
    $user = User::find($user_id); // Replace 1 with the actual user ID
    $bills = $user->bills;

    $mapped =  DB::table('household_user')
        ->where('user_id', $user_id)
        ->where('household_id', $household_id)
        ->exists();

    $user = User::find($user_id);

//    $sql = $user->bills()->toSql();

    expect(true)->toBeTrue();

});

it('household_invitations', function () {
    config(['database.default' => 'mysql']);

//    Schema::dropIfExists('household_user');

    Schema::dropIfExists('household_invitations');

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


    /*    Schema::create('household_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('household_id')->constrained()->onDelete('cascade');
            $table->primary(['user_id', 'household_id']);
            $table->timestamps();
        });*/

    expect(true)->toBeTrue();

});

it('like', function () {
    config(['database.default' => 'mysql']);

    $data = DB::select("select * FROM household_invitations
            WHERE invited_by = ?
            AND invitee_email = ?
            AND ? like CONCAT('%', household_ids, '%')", [
        108,
        'test@test.com',
        '20,21'
    ]);

    expect(true)->toBeTrue();

});


