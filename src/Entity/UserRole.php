<?php

namespace CodedMonkey\Dirigent\Entity;

enum UserRole: string
{
    case Owner = 'ROLE_SUPER_ADMIN';
    case Admin = 'ROLE_ADMIN';
    case User = 'ROLE_USER';

    public function isAdmin(): bool
    {
        return self::Admin === $this || $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return self::Owner === $this;
    }
}
