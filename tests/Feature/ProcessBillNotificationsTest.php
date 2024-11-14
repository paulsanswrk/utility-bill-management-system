<?php

use App\Jobs\ProcessBillNotifications;

it('process bill notification', function () {

    config(['database.default' => 'mysql']);

    $ProcessBillNotifications = new ProcessBillNotifications();
    $ProcessBillNotifications->handle();
    expect(true)->toBeTrue();

});
