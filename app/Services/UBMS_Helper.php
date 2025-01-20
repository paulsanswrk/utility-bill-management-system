<?php

namespace App\Services;

class UBMS_Helper
{
    public function strToSortedIntArray(string $str): array
    {

        $res = collect(explode(',', $str))
            ->map(fn($val) => trim($val))
            ->filter(fn($val) => is_numeric($val))
            ->map(fn($val) => intval($val))
            ->sort()
            ->values()
            ->toArray();

        return $res;
    }

    public function intArrayToSortedStr(array $arr): string
    {
        $arr = collect($arr)->sort()->values()->toArray();
        return implode(',', $arr);
    }

    public function detectChanges(array $old, array $new): array
    {

        $removed = array_diff($old, $new);
        $added = array_diff($new, $old);

        return [
            'added' => array_values($added),
            'removed' => array_values($removed),
        ];
    }
}
