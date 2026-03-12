<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}

