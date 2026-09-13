<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Teacher = 'teacher';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Admin => 'مدير النظام',
            self::Teacher => 'معلم / معلمة',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Teacher => 'success',
        };
    }

    public function label(): string
    {
        return $this->getLabel() ?? $this->value;
    }
}
