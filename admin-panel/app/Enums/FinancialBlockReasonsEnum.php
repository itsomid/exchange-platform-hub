<?php

namespace App\Enums;

enum FinancialBlockReasonsEnum :string
{
    case ADMIN = 'admin';
    case GROUP = 'group';
    case PASSWORD_CHANGED = 'password_changed';
}
