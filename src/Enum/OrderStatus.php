<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Unpaid = 'Unpaid';
    case Paid = 'Paid';
    case Preparing = 'Preparing';
    case ReadyForDelivery = 'ReadyForDelivery';
    case Active = 'Active';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}