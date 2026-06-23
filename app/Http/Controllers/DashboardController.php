<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Imports\CredentialsImport;
use App\Imports\StoresImport;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\SystemType;
use App\Models\Bu;
use App\Models\Rm;

class DashboardController extends Controller
{
    
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');
        $bu = $request->input('bu', '');
        $busQuery = Store::query();

        $driver = DB::connection()->getDriverName();
        $likeOperator = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
 
        if ($user->role === 'am') {
            $busQuery->where('am_user_id', $user->id);
        }

        $query = Store::with(['amUser:id,name'])
        ->select('ID', 'Name', 'BU', 'RM', 'ช่องทางการขาย', 'am_user_id');
    
        if ($user->role === 'am') {
            $query->where('am_user_id', $user->id);
        }
         
        //ค้นหาจาก DB จริง
        if ($search) {
            $amIds = User::query()
                ->where('role', 'am')
                ->where('name', $likeOperator, "%{$search}%")
                ->pluck('id');

            $query->where(function($q) use ($search, $amIds, $likeOperator) {
                $q->where('ID', $likeOperator, "%{$search}%")
                ->orWhere('Name', $likeOperator, "%{$search}%")
                ->orWhere('BU', $likeOperator, "%{$search}%")
                ->orWhere('RM', $likeOperator, "%{$search}%")
                ->orWhere('ช่องทางการขาย', $likeOperator, "%{$search}%");

                if ($amIds->isNotEmpty()) {
                    $q->orWhereIn('am_user_id', $amIds);
                }
            });
        }

        if ($bu) {
            $query->where('BU', $bu);
        }

        $stores = $query->orderBy('BU')->orderBy('Name')->paginate(10);

        $storesList = $stores->getCollection()->map(fn($s) => [
            'id'          => $s->ID,
            'name'        => $s->Name,
            'bu'          => $s->BU ?? 'ไม่ระบุ',
            'rm'          => $s->RM,
            'am'          => $s->amUser?->name,
            'am_user_id'  => $s->am_user_id,
            'channel'     => $s->{'ช่องทางการขาย'},
        ])->values();

    if ($request->ajax()) {
            return response()->json([
                'data' => $storesList,
                'pagination' => [
                    'current_page' => $stores->currentPage(),
                    'last_page'    => $stores->lastPage(),
                    'per_page'     => $stores->perPage(),
                    'total'        => $stores->total(),
                ],
            ]);
    }

    $query = Store::query();

    if ($user->role === 'am') {
        $query->where('am_user_id', $user->id);
    }
    $buCount = Store::query()
    ->when($user->role === 'am', fn($q) => $q->where('am_user_id', $user->id))
    ->whereNotNull('BU')
    ->where('BU', '<>', '')
    ->distinct()
    ->count('BU');

    $bus = Bu::orderBy('name')->pluck('name');  

    $ams = User::where('role','am')
    ->where('is_active',true)
    ->orderBy('name')
    ->get(['id','name']);

    $rms = Rm::orderBy('name')->pluck('name');
    $systems = SystemType::orderBy('name')-> get(['name','color']);
    
    return view('dashboard', [
    'storesByBU'  => [],
    'bus'         => $bus,
    'totalPages'  => $stores->lastPage(),
    'currentPage' => 1,
    'total'       => $stores->total(),
    'ams'         => $ams,
    'rms'         => $rms,
    'systems'     => $systems,
    'buCount'     => $buCount,
    ]);
    }

public function createStore(Request $request)
    {
        $data = $request->validate([
            'ID'            => ['required', 'string', 'max:20', 'unique:stores,ID'],
            'Name'          => ['required', 'string', 'max:200'],
            'BU'            => ['nullable', 'string', 'max:100'],
            'RM'            => ['nullable', 'string', 'max:100'],
            'am_user_id'    => ['nullable', 'uuid'],
            'ช่องทางการขาย' => ['nullable', 'string', 'max:100'],
        ], [
            'ID.unique' => 'รหัสร้านนี้มีอยู่แล้ว',
        ]);

        if (isset($data['BU']))$data['BU']= strtoupper($data['BU']);
        if (isset($data['ช่องทางการขาย'])) $data['ช่องทางการขาย'] = strtoupper($data['ช่องทางการขาย']);

        Store::create($data);
       $store = Store::create($data);
        Log::info('STORE_CREATED', [
            'user'     => Auth::user()?->username,
            'store_id' => $store->ID,
            'data'     => $store->toArray(),
        ]);
        return response()->json(['success' => true]);
    }

public function updateCredential(Request $request, string $storeId)
    {
        $request->validate([
            'credentials'            => ['required', 'array'],
            'credentials.*.system'   => ['required', 'string'],
            'credentials.*.username' => ['required', 'string'],
            'credentials.*.password' => ['required', 'string'],
            'credentials.*.note'     => ['nullable', 'string'],
        ]);

        $store = Store::findOrFail($storeId);

        foreach ($request->credentials as $cred) {
            $store->credentials()->updateOrCreate(
                ['store_id' => $storeId, 'system' => $cred['system'], 'username' => $cred['username']],
                [
                    'password' => $cred['password'],
                    'note'     => $cred['note'] ?? null,
                ]
            );
        }
        Log::info('CREDENTIAL_UPDATED', [
            'user' => Auth::user()?->username,
            'store_id' => $storeId,
            'credentials' => $request->credentials,
        ]);
        return response()->json(['success' => true]);
    }

public function updateStore(Request $request, string $storeId)
    {
        $store = Store::findOrFail($storeId);

        $data = $request->validate([
            'Name'          => ['required', 'string'],
            'BU'            => ['nullable', 'string'],
            'RM'            => ['nullable', 'string'],
            'am_user_id'    => ['nullable', 'string'],
            // 'AM'            => ['nullable', 'string'],
            'ช่องทางการขาย' => ['nullable', 'string'],
        ]);

        if (isset($data['BU']))$data['BU'] = strtoupper($data['BU']);
        if (isset($data['ช่องทางการขาย'])) $data['ช่องทางการขาย'] = strtoupper($data['ช่องทางการขาย']);

        $oldData =$store->toArray();
        $store->update($data);
        Log::info('STORE_CREATED', [
            'user' => Auth::user()?->username,
            'store_id' => $store->ID,
            'data' => $store->toArray(),
        ]);
        return response()->json(['success' => true]);
    }

public function destroyStore(string $storeId)
    {   
        $store = Store::findOrFail($storeId);

        Log::warning('STORE_DELETED', [
            'user' => Auth::user()?->username,
            'store_id' => $store->ID,
            'data' => $store->toArray(),
        ]);

        $store->delete();
        // Store::findOrFail($storeId)->delete();
        return response()->json(['success' => true]);
    }

public function getCredentials(string $storeId)
{
    $store = Store::with('credentials')->findOrFail($storeId);

    $creds = $store->credentials
        ->groupBy('system')
        ->map(fn($group) => $group->map(fn($c) => [
            'username' => $c->username,
            'password' => $c->password,
            'note'     => $c->note,
        ])->values())
        ->toArray();

    return response()->json($creds);
}

public function getStore(string $storeId)
{
    $store = Store::findOrFail($storeId);
    return response()->json([
        'id'      => $store->ID,
        'name'    => $store->Name,
        'bu'      => $store->BU,
        'rm'      => $store->RM,
        'channel' => $store->{'ช่องทางการขาย'},
        
    ]);
}

public function export(): StreamedResponse //บันทึกเป็น CSV แล้วส่งให้ดาวน์โหลด
    {
        $stores = Store::orderBy('BU')->orderBy('Name')->get();

        return response()->stream(function () use ($stores) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, ['ID','Name','BU','RM','ช่องทางการขาย']);
            foreach ($stores as $s) {
                fputcsv($f, [
                    $s->ID, $s->Name, $s->BU, $s->RM,
                    $s->{'ช่องทางการขาย'}
                ]);
            }
            fclose($f);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stores_'.now()->format('Ymd').'.csv"',
        ]);
    }

public function exportCredentials(): StreamedResponse
    {
        $user  = Auth::user();
        $query = Store::with('credentials', 'amUser');

    if ($user->role === 'staff') {
    } elseif ($user->role === 'am') {
        $query->where('am_user_id', $user->id);
    }
        $stores = $query->get();
    return response()->stream(function () use ($stores) {
        $f = fopen('php://output', 'w');
        fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($f, ['store_id', 'store_name', 'system', 'username', 'password', 'note']);
    foreach ($stores as $store) {
            foreach ($store->credentials as $c) {
                fputcsv($f, [
                    $store->ID,
                    $store->Name,
                    $c->system,
                    $c->username,
                    $c->password,
                    $c->note,
                ]);
            }
        }
        fclose($f);
    }, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="credentials_'.now()->format('Ymd').'.csv"',
    ]);
    
    }
    // ── Staff: Import ─────────────────────────────
public function importCredentials(Request $request)
    {
    $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

    $imported = 0;
    $errors   = [];

    (new FastExcel)->import($request->file('file'), function($row) use (&$imported, &$errors) {
        $storeId  = trim($row['store_id']  ?? '');
        $system   = trim($row['system']    ?? '');
        $username = trim($row['username']  ?? '');
        $password = trim($row['password']  ?? '');

        if (!$storeId || !$system || !$username || !$password) return;

        if (! Store::where('ID', $storeId)->exists()) {
            $errors[] = "store_id '{$storeId}' ไม่พบในระบบ";
            return;
        };
    \App\Models\UserPassword::updateOrCreate(
            ['store_id' => $storeId, 'system' => $system, 'username' => $username],
            ['password' => $password, 'note'   => trim($row['note'] ?? '')]
        );
        $imported++;
    });
        
        return response()->json(['success' => true, 'imported' => $imported, 'errors' => $errors]);
    }
    // ── Admin: Import ─────────────────────────────
public function importStores(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $imported = 0;
        $errors   = [];

        (new FastExcel)->import($request->file('file'), function($row) use (&$imported, &$touchFilters) {
        $id   = trim($row['ID']   ?? $row['id']   ?? '');
        $name = trim($row['Name'] ?? $row['name'] ?? '');
        if (!$id || !$name) return;

        //หาam_user_id จากชื่อAM ที่ให้มาในไฟล์
        $amName = trim($row['AM'] ?? $row['am'] ?? '');
        $amUserId = null;
         if ($amName) {
            $amUser = \App\Models\User::where('role', 'am')
                                  ->where('name', $amName)
                                  ->first();
            if ($amUser) {
                $amUserId = $amUser->id;
            }
        }

        Store::updateOrCreate(
            ['ID' => $id],
            [
                'Name'          => $name,
                'BU'            => strtoupper(trim($row['BU'] ?? $row['bu'] ?? '')),
                'RM'            => trim($row['RM'] ?? $row['rm'] ?? ''),
                'am_user_id'    => $amUserId,  // ← บันทึก am_user_id แทน AM
                'ช่องทางการขาย' => strtoupper(trim($row['ช่องทางการขาย'] ?? '')),
            ]
        );
        $imported++;
        });

        return response()->json(['success' => true, 'imported' => $imported, 'errors' => $errors]);
    }

}