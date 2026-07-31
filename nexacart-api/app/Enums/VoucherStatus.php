<?php

namespace App\Enums;

enum VoucherStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}