<?php

namespace App\Models;

enum Role: string
{
    case User = 'user';
    case Admin = 'admin';
}
