<?php

namespace App\Enums;

enum DonorType: string
{
    case Member = 'member';
    case Individual = 'individual';
    case Organization = 'organization';
}
