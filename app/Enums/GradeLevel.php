<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GradeLevel: string implements HasColor, HasLabel
{
    // المرحلة الابتدائية
    case Primary1 = 'primary_1';
    case Primary2 = 'primary_2';
    case Primary3 = 'primary_3';
    case Primary4 = 'primary_4';
    case Primary5 = 'primary_5';
    case Primary6 = 'primary_6';

    // المرحلة المتوسطة / الإعدادية
    case Prep1 = 'prep_1';
    case Prep2 = 'prep_2';
    case Prep3 = 'prep_3';

    // المرحلة الثانوية
    case Secondary1 = 'secondary_1';
    case Secondary2 = 'secondary_2';
    case Secondary3 = 'secondary_3';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Primary1 => 'أول ابتدائي',
            self::Primary2 => 'ثاني ابتدائي',
            self::Primary3 => 'ثالث ابتدائي',
            self::Primary4 => 'رابع ابتدائي',
            self::Primary5 => 'خامس ابتدائي',
            self::Primary6 => 'سادس ابتدائي',
            self::Prep1 => 'أول متوسط / إعدادي',
            self::Prep2 => 'ثاني متوسط / إعدادي',
            self::Prep3 => 'ثالث متوسط / إعدادي',
            self::Secondary1 => 'أول ثانوي',
            self::Secondary2 => 'ثاني ثانوي',
            self::Secondary3 => 'ثالث ثانوي',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Primary1, self::Primary2, self::Primary3, self::Primary4, self::Primary5, self::Primary6 => 'info',
            self::Prep1, self::Prep2, self::Prep3 => 'warning',
            self::Secondary1, self::Secondary2, self::Secondary3 => 'success',
        };
    }

    public function label(): string
    {
        return $this->getLabel() ?? $this->value;
    }
}
