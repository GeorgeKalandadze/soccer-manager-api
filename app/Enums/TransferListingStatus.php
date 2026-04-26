<?php

namespace App\Enums;

enum TransferListingStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
