<?php

namespace App\Tenancy;

use RuntimeException;

class NoTenantException extends RuntimeException
{
    public function __construct(string $message = 'Konteks sekolah tidak valid atau belum dipilih.')
    {
        parent::__construct($message);
    }
}
