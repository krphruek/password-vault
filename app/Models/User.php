<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['username', 'password', 'name', 'role', 'is_active'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['is_active' => 'boolean'];

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function stores()
    {
        return $this->hasMany(Store::class, 'am_user_id', 'id');
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }

    public function storesByBU(): array
    {
        $stores = $this->isAdmin()
            ? Store::with(['credentials', 'amUser'])
                   ->orderBy('BU')->orderBy('Name')->get()
            : Store::with(['credentials', 'amUser'])
                   ->where('am_user_id', $this->id)
                   ->orderBy('BU')->orderBy('Name')->get();

        return $stores
            ->groupBy(fn($s) => $s->BU ?? 'ไม่ระบุสาขา')
            ->map(fn($group, $bu) => [
                'bu'     => $bu,
                'stores' => $group->map(fn($s) => [
                    'id'          => $s->ID,
                    'name'        => $s->Name,
                    'bu'          => $s->BU,
                    'rm'          => $s->RM,
                    'am'          => $s->AM,
                    'am_username' => $s->amUser?->username,
                    'channel'     => $s->ช่องทางการขาย,
                    'credentials' => $s->credentials
                        ->keyBy('system')
                        ->map(fn($c) => [
                            'username' => $c->username,
                            'password' => $c->password,
                            'note'     => $c->note,
                        ]),
                ])->values(),
            ])
            ->values()
            ->toArray();
    }
}