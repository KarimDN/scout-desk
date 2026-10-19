<?php

namespace App\Modules\Identity\Enums;

enum AccountTokenPurpose: string
{
    case Invitation = 'invitation';
    case PasswordReset = 'password_reset';
}