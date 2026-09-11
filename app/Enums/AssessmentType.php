<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssessmentType: string implements HasColor, HasLabel
{
    case Homework = 'homework';
    case Quiz = 'quiz';
    case Exam = 'exam';
    case Assignment = 'assignment';
    case Activity = 'activity';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Homework => 'واجب منزلي',
            self::Quiz => 'اختبار قصير',
            self::Exam => 'امتحان',
            self::Assignment => 'تكليف',
            self::Activity => 'نشاط',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Homework => 'info',
            self::Quiz => 'warning',
            self::Exam => 'danger',
            self::Assignment => 'primary',
            self::Activity => 'success',
        };
    }

    public function label(): string
    {
        return $this->getLabel() ?? $this->value;
    }
}
