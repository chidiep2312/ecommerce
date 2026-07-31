<?php

namespace App\Enums;

enum SellerRequestStatus: string
{
    case Approved = 'aproved';
    case Pending ='pending';
    case Rejected = 'rejected';
}