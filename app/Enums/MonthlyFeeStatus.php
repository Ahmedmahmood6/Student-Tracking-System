<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MonthlyFeeStatus: string implements HasColor, HasLabel
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Unpaid => 'غير مدفوع',
            self::PartiallyPaid => 'مدفوع جزئياً',
            self::Paid => 'مدفوع',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Unpaid => 'danger',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
        };
    }

    public function label(): string
    {
        return $this->getLabel() ?? $this->value;
    }
}
