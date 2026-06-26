<?php
namespace App\Http\Controllers;

use App\Models\Bu;
use App\Models\Rm;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\SystemType;
use App\Models\UserPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ManageController extends Controller
{
    public function index()
    {   
        $buCounts = Store::select('BU', DB::raw('COUNT(*) as count'))
            ->whereNotNull('BU')->where('BU', '<>', '')
            ->groupBy('BU')->pluck('count', 'BU');

        $bus = Bu::orderBy('name')->get()->map(fn($b) => [
            'name'  => $b->name,
            'count' => $buCounts[$b->name] ?? 0,
        ]);
        
        $rmCounts = Store::select('RM', DB::raw('COUNT(*) as count'))
            ->whereNotNull('RM')->where('RM', '<>', '')
            ->groupBy('RM')->pluck('count', 'RM');

        $rms = Rm::orderBy('name')->get()->map(fn($r) => [
            'name'  => $r->name,
            'count' => $rmCounts[$r->name] ?? 0,
        ]);

        $ams = User::where('role', 'am')->orderBy('name')
            ->get(['id', 'username', 'name', 'is_active']);

        $systems = SystemType::orderBy('name')->get();

        $bus_count = Store::select('BU')->whereNotNull('BU')->where('BU','<>','')
            ->distinct()->count('BU');

        return view('manage', [
            'bus'      => $bus,
            'rms'      => $rms,
            'ams'      => $ams,
            'systems'  => $systems,
        ]);
    }

    // BU
    public function addBU(Request $request)
    {
        $data = $request->validate([
        'name' => ['required', 'string', 'max:100', 'unique:bus,name']
        ], ['name.unique' => 'BU นี้มีอยู่แล้ว']);
        Bu::create(['name' => strtoupper(trim($data['name']))]);
        Log::info('BU_CREATED', ['user' => Auth::user()?->username, 'name' => $data['name']]);
        return response()->json(['success' => true]);
    }

    public function renameBU(Request $request)
    {
       $data = $request->validate(['old' => ['required'], 'new' => ['required', 'string', 'max:100']]);
        $newName = strtoupper(trim($data['new']));

        Bu::where('name', $data['old'])->update(['name' => $newName]);
        Store::where('BU', $data['old'])->update(['BU' => $newName]);
        Log::info('BU_RENAMED', ['user' => Auth::user()?->username, 'old' => $data['old'], 'new' => $data['new']]);
        return response()->json(['success' => true]);
    }

    public function deleteBU(Request $request)
    {
        $data = $request->validate(['value' => ['required', 'string']]);
        Bu::where('name', $data['value'])->delete();
        Store::where('BU', $data['value'])->update(['BU' => null]);
        Log::warning('BU_DELETED', ['user' => Auth::user()?->username, 'name' => $data['value']]);
        return response()->json(['success' => true]);
    }

    // RM
    public function addRM(Request $request)
    {
        $data = $request->validate([
        'name' => ['required', 'string', 'max:100', 'unique:rms,name']],
         ['name.unique' => 'RM นี้มีอยู่แล้ว']);
        Rm::create(['name' => trim($data['name'])]);
        Log::info('RM_CREATED', ['user' => Auth::user()?->username, 'name' => $data['name']]);
        return response()->json(['success' => true]);
    }

    public function renameRM(Request $request)
    {
        $data = $request->validate(['old' => ['required'], 'new' => ['required', 'string', 'max:100']]);
        $newName = trim($data['new']);

        Rm::where('name', $data['old'])->update(['name' => $newName]);
        Store::where('RM', $data['old'])->update(['RM' => $newName]);
        Log::info('RM_RENAMED', ['user' => Auth::user()?->username, 'old' => $data['old'], 'new' => $data['new']]);
        return response()->json(['success' => true]);
    }

    public function deleteRM(Request $request)
    {
        $data = $request->validate(['value' => ['required', 'string']
        ]);
        Rm::where('name', $data['value'])->delete();
        Store::where('RM', $data['value'])->update(['RM' => null]);
        Log::warning('RM_DELETED', ['user' => Auth::user()?->username, 'name' => $data['value']]);
        return response()->json(['success' => true]);
    }

    // AM 
    public function createAM(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:10', 'unique:users,username'],
            'name'     => ['required', 'string', 'max:100'],
            'pin'      => ['required', 'string', 'min:4', 'max:10'],
        ], ['username.unique' => 'รหัสนี้มีอยู่แล้ว']);

        User::create([
            'username'  => $data['username'],
            'name'      => $data['name'],
            'password'  => Hash::make($data['pin']),
            'role'      => 'am',
            'is_active' => true,
        ]);
        Log::info('AM_CREATED', ['user' => Auth::user()?->username, 'username' => $data['username'], 'name' => $data['name']]);
        return response()->json(['success' => true]);
    }

    public function updateAM(Request $request, string $id)
{
    $user = User::where('role', 'am')->findOrFail($id);

    $rules = [
        'name'      => ['required', 'string', 'max:100'],
        'is_active' => ['required'],
        'username'  => ['nullable', 'string', 'max:7', 'unique:users,username,' . $id . ',id'],
        'pin'       => ['nullable', 'string', 'max:6'],
    ];

    $data = $request->validate($rules, [
        'username.unique' => 'รหัสนี้มีคนใช้แล้ว',
    ]);

    $update = [
        'name'      => $data['name'],
        'is_active' => filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN),
    ];

    if (!empty($data['username'])) $update['username'] = $data['username'];
    if (!empty($data['pin']))      $update['password'] = Hash::make($data['pin']);

    $user->update($update);

    Log::info('AM_UPDATED', [
        'user'    => Auth::user()?->username,
        'am_id'   => $id,
        'changed' => array_keys($update),
    ]);

    return response()->json(['success' => true]);
}

    public function deleteAM(string $id)
    {
        $user = User::where('role', 'am')->findOrFail($id);
        $user->delete(); // stores.am_user_id เป็น nullOnDelete อยู่แล้ว
        Log::warning('AM_DELETED', ['user' => Auth::user()?->username, 'am_id' => $id, 'name' => $user->name]);
        $user->delete();
        return response()->json(['success' => true]);
    }

    // Systems
    public function storeSystem(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:50', 'unique:systems,name'],
            'color' => ['nullable', 'string', 'max:20'],
        ], ['name.unique' => 'ระบบนี้มีอยู่แล้ว']);

        SystemType::create([
            'name'  => trim($data['name']),
            'color' => $data['color'] ?? '#888888',
        ]);
         Log::info('SYSTEM_CREATED', ['user' => Auth::user()?->username, 'name' => $data['name']]);
        return response()->json(['success' => true]);
    }

    public function updateSystem(Request $request, int $id)
    {
        $system = SystemType::findOrFail($id);
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:50', 'unique:systems,name,' . $id],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        // ถ้าเปลี่ยนชื่อ ต้องอัปเดต userpassword.system ทุก row ที่ใช้ชื่อเดิมด้วย
        if ($system->name !== $data['name']) {
            UserPassword::where('system', $system->name)->update(['system' => $data['name']]);
        }

        $system->update([
            'name'  => $data['name'],
            'color' => $data['color'] ?? $system->color,
        ]);
        Log::info('SYSTEM_UPDATED', ['user' => Auth::user()?->username, 'id' => $id, 'name' => $data['name']]);
        return response()->json(['success' => true]);
    }

    public function destroySystem(int $id)
    {
        $system = SystemType::findOrFail($id);

        $inUse = UserPassword::where('system', $system->name)->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'ระบบนี้มี Username/Password ใช้งานอยู่ ลบไม่ได้',
            ], 422);
        }
        Log::warning('SYSTEM_DELETED', ['user' => Auth::user()?->username, 'id' => $id, 'name' => $system->name]);
        $system->delete();
        return response()->json(['success' => true]);
    }

}