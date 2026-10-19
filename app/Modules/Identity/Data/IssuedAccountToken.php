<?php

namespace App\Modules\Identity\Data;

use App\Modules\Identity\Models\AccountToken;

/**
 * Returned once when a token is issued. The plaintext token exists only here:
 * show it to the recipient and let this object go out of scope.
 */
final class IssuedAccountToken
{
    public function __construct(
        public readonly AccountToken $token,
        public readonly string $plainToken,
    ) {}
}