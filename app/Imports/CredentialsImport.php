<?php
namespace App\Imports;

use App\Models\Store;
use App\Models\UserPassword;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class CredentialsImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int   $imported = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $storeId = trim($row['store_id'] ?? '');
            $system  = trim($row['system']   ?? '');
            $username= trim($row['username'] ?? '');
            $password= trim($row['password'] ?? '');

            if (!$storeId || !$system || !$username || !$password) continue;

            // เช็คว่า store มีอยู่จริง
            if (!Store::where('ID', $storeId)->exists()) {
                $this->errors[] = "store_id '{$storeId}' ไม่พบในระบบ";
                continue;
            }

            UserPassword::updateOrCreate(
                ['store_id' => $storeId, 'system' => $system, 'username' => $username],
                ['password' => $password, 'note' => trim($row['note'] ?? '')]
            );
            $this->imported++;
        }
    }
}