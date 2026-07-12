<?php

namespace App\Enums;

enum CommunityRole: string
{
    case President = 'president';
    case Admin = 'admin';
    case Member = 'member';
}
