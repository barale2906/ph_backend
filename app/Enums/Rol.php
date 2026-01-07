<?php

namespace App\Enums;

enum Rol: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN_PH = 'ADMIN_PH';
    case LOGISTICA = 'LOGISTICA';
    case LECTURA = 'LECTURA';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrador',
            self::ADMIN_PH => 'Administrador PH',
            self::LOGISTICA => 'Logística',
            self::LECTURA => 'Solo Lectura',
        };
    }
}

