<?php

namespace App\Enum;

enum DeliveryStatus: string
{
    case Pending = 'Pending';
    case Assigned = 'Assigned';
    case Picked_up = 'Picked_up';
    case Delivered = 'Delivered';
}
