<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Append-only: update/delete ditolak di level model dan (MariaDB) di level trigger database. */
class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('audit_logs bersifat append-only.'));
        static::deleting(fn () => throw new LogicException('audit_logs bersifat append-only.'));
    }
}
