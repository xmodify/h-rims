<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'status',
        'cid',
        'allow_home',
        'allow_import',
        'allow_check',
        'allow_emr',
        'allow_claim_op',
        'allow_claim_ip',
        'allow_mishos',
        'allow_debtor',
        'allow_debtor_lock',
        'allow_debtor_acc',
        'allow_receipt',
        'allow_nhso_endpoint',
        'allow_aopod_death',
        'allow_check_right',
        'allow_hosfin',
        'allow_ai_copilot',
        'allow_export_f16_eclaim',
        'allow_export_f16_fdh',
        'allow_export_ssop',
        'allow_export_aipn',
        'allow_export_csop',
        'allow_export_cipn',
        'provider_id',
        'fdh_user',
        'fdh_pass',
        'fdh_secretKey',
        'eclaim_user',
        'eclaim_pass',
        'moph_token',
        'moph_token_expire',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
