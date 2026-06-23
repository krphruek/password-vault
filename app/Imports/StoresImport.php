<?php
namespace App\Imports;

use App\Models\Store;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class StoresImport implements ToCollection, WithHeadingRow
{
    public array $errors   = [];
    public int   $imported = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $id   = trim($row['id']   ?? '');
            $name = trim($row['name'] ?? '');
            if (!$id || !$name) continue;

            Store::updateOrCreate(
                ['ID' => $id],
                [
                    'Name'          => $name,
                    'BU'            => strtoupper(trim($row['bu']            ?? '')),
                    'RM'            => trim($row['rm']            ?? ''),
                    'AM'            => trim($row['am']            ?? ''),
                    'ช่องทางการขาย' => strtoupper(trim($row['chongthangarkhay'] ?? $row['ช่องทางการขาย'] ?? '')),
                ]
            );
            $this->imported++;
        }
    }
}