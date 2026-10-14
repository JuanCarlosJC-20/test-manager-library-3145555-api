<?php

namespace App\Domain\Users;

enum Role: string
{
    case Admin = 'admin';
    case Librarian = 'librarian';
}
