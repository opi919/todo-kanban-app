<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::User => 'User',
        };
    }

    public function canCreateTasks(): bool
    {
        return $this === self::Admin;
    }

    public function canViewAllTasks(): bool
    {
        return $this === self::Admin;
    }
}
