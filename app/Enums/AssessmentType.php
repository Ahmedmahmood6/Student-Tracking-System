<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Homework = 'homework';
    case Quiz = 'quiz';
    case Exam = 'exam';
    case Assignment = 'assignment';
    case Activity = 'activity';

    public function label(): string
    {
        return match ($this) {
            self::Homework => 'Homework',
            self::Quiz => 'Quiz',
            self::Exam => 'Exam',
            self::Assignment => 'Assignment',
            self::Activity => 'Activity',
        };
    }
}
