<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ClassSessionStatus: string implements HasColor, HasLabel
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Scheduled => 'مجدولة',
            self::Completed => 'مكتملة',
            self::Cancelled => 'ملغاة',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Scheduled => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function label(): string
    {
        return $this->getLabel() ?? $this->value;
    }
}
