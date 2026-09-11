<?php

namespace App\Filament\Resources\Subjects\RelationManagers;

use App\Enums\WeeklyDay;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'الطلاب المسجلون في المادة والمواعيد';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('weekly_day')
                    ->label('يوم الحصة الأسبوعي')
                    ->options(collect(WeeklyDay::cases())->mapWithKeys(fn (WeeklyDay $day) => [$day->value => $day->label()]))
                    ->nullable(),

                TimePicker::make('weekly_start_time')
                    ->label('وقت البدء الأسبوعي')
                    ->seconds(false),

                TimePicker::make('weekly_end_time')
                    ->label('وقت الانتهاء الأسبوعي')
                    ->seconds(false)
                    ->after('weekly_start_time'),

                Textarea::make('teacher_notes')
                    ->label('ملاحظات الخطة / المعلم')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الطالب')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('—'),

                TextColumn::make('pivot.weekly_day')
                    ->label('اليوم الأسبوعي')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state) {
                        $enum = WeeklyDay::tryFrom((string) $state);

                        return $enum ? $enum->label() : ($state ?: '—');
                    }),

                TextColumn::make('schedule_time')
                    ->label('الموعد الأسبوعي')
                    ->state(fn (Student $record): string => $record->pivot?->weekly_start_time
                        ? substr((string) $record->pivot->weekly_start_time, 0, 5).' - '.substr((string) $record->pivot->weekly_end_time, 0, 5)
                        : '—'),

                IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('تسجيل طالب بالمادة')
                    ->modalHeading('تسجيل طالب في المادة وتحديد الموعد الأسبوعي')
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()->label('اسم الطالب'),
                        Select::make('weekly_day')
                            ->label('يوم الحصة الأسبوعي')
                            ->options(collect(WeeklyDay::cases())->mapWithKeys(fn (WeeklyDay $day) => [$day->value => $day->label()]))
                            ->nullable(),
                        TimePicker::make('weekly_start_time')
                            ->label('وقت البدء الأسبوعي')
                            ->seconds(false),
                        TimePicker::make('weekly_end_time')
                            ->label('وقت الانتهاء الأسبوعي')
                            ->seconds(false)
                            ->after('weekly_start_time'),
                        Textarea::make('teacher_notes')
                            ->label('ملاحظات الخطة / المعلم'),
                    ]),
            ])
            ->recordActions([
                Action::make('viewClasses')
                    ->label('حصص الطالب')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->color('primary')
                    ->url(fn (Student $record): string => ClassSessionResource::getUrl('index', [
                        'tableFilters' => [
                            'student_id' => ['value' => $record->id],
                            'subject_id' => ['value' => $this->getOwnerRecord()->getKey()],
                        ],
                    ])),

                Action::make('studentProfile')
                    ->label('الملف التعريفي')
                    ->icon(Heroicon::OutlinedUser)
                    ->url(fn (Student $record): string => StudentResource::getUrl('view', ['record' => $record])),

                EditAction::make()->label('تعديل الموعد'),
                DetachAction::make()->label('إلغاء التسجيل'),
            ]);
    }
}
