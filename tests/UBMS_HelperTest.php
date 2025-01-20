<?php

namespace Tests\Services;

use App\Services\UBMS_Helper;

test('str to sorted int array', function () {
    $ubms_helper = new UBMS_Helper();

    expect($ubms_helper->strToSortedIntArray(''))->toBe([]);
    expect($ubms_helper->strToSortedIntArray('qq,3,1,2'))->toBe([1, 2, 3]);
    expect($ubms_helper->strToSortedIntArray('qq,3 ,1 ,  2'))->toBe([1, 2, 3]);
    expect($ubms_helper->strToSortedIntArray('3,1,2'))->toBe([1, 2, 3]);
    expect($ubms_helper->strToSortedIntArray('10,5,7,5'))->toBe([5, 5, 7, 10]);
    expect($ubms_helper->strToSortedIntArray('3'))->toBe([3]);
    expect($ubms_helper->strToSortedIntArray('6,6,6'))->toBe([6, 6, 6]);
});


test('int array to sorted str', function () {
    $ubms_helper = new UBMS_Helper();

    expect($ubms_helper->intArrayToSortedStr([]))->toBe('');
    expect($ubms_helper->intArrayToSortedStr([3, 1, 2]))->toBe('1,2,3');
    expect($ubms_helper->intArrayToSortedStr([10, 5, 7, 5]))->toBe('5,5,7,10');
    expect($ubms_helper->intArrayToSortedStr([3]))->toBe('3');
    expect($ubms_helper->intArrayToSortedStr([6, 6, 6]))->toBe('6,6,6');
});

test('detect_changes', function () {
    $ubms_helper = new UBMS_Helper();


    expect($ubms_helper->detectChanges([], [1, 2, 3]))->toBe([
        'added' => [1, 2, 3],
        'removed' => [],
    ]);

    expect($ubms_helper->detectChanges([1, 2, 3], []))->toBe([
        'added' => [],
        'removed' => [1, 2, 3],
    ]);

    expect($ubms_helper->detectChanges([1, 2, 3], [2, 3, 4]))->toBe([
        'added' => [4],
        'removed' => [1],
    ]);

    expect($ubms_helper->detectChanges([1, 2, 3], [1, 2, 3]))->toBe([
        'added' => [],
        'removed' => [],
    ]);

    expect($ubms_helper->detectChanges([5, 6], [6, 7, 8]))->toBe([
        'added' => [7, 8],
        'removed' => [5],
    ]);
});
