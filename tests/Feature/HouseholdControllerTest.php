<?php

namespace Tests\Http\Controllers;

use App\Http\Controllers\HouseholdController;
use App\Services\UBMS_Helper;


it('get invitations for invitee', function () {
    config(['database.default' => 'mysql']);

    $household_controller = new HouseholdController(new UBMS_Helper());

    $invitations = $household_controller->get_invitations_for_invitee(106);

    echo json_encode($invitations);

    expect(true)->toBeTrue();

});
