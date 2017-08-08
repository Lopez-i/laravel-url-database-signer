<?php

namespace lopez_i\UrlSigner\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SignedUrls
 * @package App
 * @property string $request_type
 * @property string $expire_at
 * @property string $url_signature
 * @property int $id
 */
class SignedUrls extends Model
{

    /**
     * remove default use of laravel timestamps
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_type', 'url_signature', 'user_id', 'requested_at', 'expire_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'requested_at', 'expire_at', 'user_id', 'url_signature'
    ];
}
