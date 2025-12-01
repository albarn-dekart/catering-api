<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Unpaid = 'Unpaid';
    case Paid = 'Paid';
    case Active = 'Active';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}