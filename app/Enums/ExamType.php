<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExamType: string implements HasColor, HasLabel
{
    case Qudurat = 'qudurat';
    case Tahsili = 'tahsili';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Qudurat => 'قدرات',
            self::Tahsili => 'تحصيلي',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Qudurat => 'warning',
            self::Tahsili => 'success',
        };
    }

    public function label(): string
    {
        return $this->getLabel() ?? $this->value;
    }
}
