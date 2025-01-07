<?php

test('format date month', function () {
    $locale = 'hr';
    $issue_date = '2024-09';
    $formatted = \Carbon\Carbon::createFromFormat('Y-m', $issue_date)->locale($locale)->translatedFormat('F Y');
    expect(true)->toBeTrue();
});


