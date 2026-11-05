<?php

namespace App\Support;

use App\Models\Status;

class CatalogStatus
{
    public static function id(string $group, string $code, string $name, int $order = 0): int
    {
        return Status::query()->firstOrCreate(
            ['status_group' => $group, 'status_code' => $code],
            ['status_name' => $name, 'display_order' => $order],
        )->id;
    }

    public static function findId(string $group, string $code): ?int
    {
        return Status::query()
            ->where('status_group', $group)
            ->where('status_code', $code)
            ->value('id');
    }
}
