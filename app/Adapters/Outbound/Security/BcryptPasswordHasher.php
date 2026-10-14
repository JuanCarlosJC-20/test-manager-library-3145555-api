<?php

namespace App\Adapters\Outbound\Security;

use App\Application\Contracts\PasswordHasher;
use Illuminate\Support\Facades\Hash;

final class BcryptPasswordHasher implements PasswordHasher
{
    public function hash(string $plainText): string
    {
        return Hash::driver('bcrypt')->make($plainText);
    }

    public function verify(string $plainText, string $hash): bool
    {
        return Hash::driver('bcrypt')->check($plainText, $hash);
    }
}
