<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Storage;


class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'role' => 'boolean',
            'status' => 'boolean',
        ];
    }

    // リレーション
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }
    public function member()
    {
        return $this->hasOne(Member::class);
    }
    // クエリスコープ
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function scopeAdmin($query)
    {
        return $query->where('role', 1);
    }
    public function scopeMember($query)
    {
        return $query->where('role', 0);
    }
    public function settings()
    {
        return $this->hasMany(Setting::class);
    }
    
    // アクセサ
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            0 => '退会',
            1 => '在籍中',
        };
    }
    public function getRoleTextAttribute()
    {
        return match($this->role) {
            0 => '一般会員',
            1 => '管理者',
        };
    }
    public function getUserNameAttribute()
    {
        // 管理人か一般会員かによって場合分け
        if ($this->role == 1) {
            return "管理人";
        } else {
            return $this->member->nickname ?? "名無し";
        }
    }
    public function getUserIconUrlAttribute()
    {
        if ($this->role == 1) {
            return asset('images/front/admin_icon.png');
        }else{
            return $this->member->member_icon ? Storage::url($this->member->member_icon) : asset('images/front/default_member_icon.png');
        }
    }


    // 属性
    public function attributes()
    {
        return [
            'name' => '名前',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
            'role' => '役割',
            'status' => '状態',
        ];
    }
        // ビジネスメソッド
    // 
        /**
     * 未読メッセージ件数を取得
     */
    public function countUnreadMessages()
    {
        //
    }
    /**
     * 未読レビュー件数を取得
     */
    public function countUnreadReviews()
    {
        //
    }

}
