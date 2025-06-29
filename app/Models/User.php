<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasUuids, HasFactory, Notifiable, HasApiTokens, HasRolesAndPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $primaryKey = 'user_id';
    protected $guarded = [];
    protected $appends = ['login_terakhir'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        //'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id', 'sekolah_id');
    }
    public function access_token()
    {
        return $this->hasOne(PersonalAccessToken::class, 'tokenable_id')->orderBy('last_used_at', 'DESC');
    }
    public function getLoginTerakhirAttribute()
	{
        $last_login_at = NULL;
        if($this->access_token){
            $last_login_at = Carbon::parse($this->access_token->last_used_at)->format('d/m/Y H:i:s');
        } elseif($this->attributes['last_login_at']){
            return Carbon::parse($this->attributes['last_login_at'])->format('d/m/Y H:i:s');
        }
        return $last_login_at;
	}
    protected function profilePhotoPath(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value) ? "/storage/images/$value" : NULL,
        );
    }
}
