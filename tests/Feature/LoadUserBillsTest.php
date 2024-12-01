<?php

use App\Models\User;

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
