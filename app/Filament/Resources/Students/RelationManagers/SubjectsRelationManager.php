<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\WeeklyDay;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Models\Subject;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'subjects';

    protected static ?string $title = 'المواد المسجلة والمواعيد الأسبوعية';

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
                    ->label('المادة الدراسية')
                    ->weight('bold')
                    ->searchable(),

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
                    ->state(fn (Subject $record): string => $record->pivot?->weekly_start_time
                        ? substr((string) $record->pivot->weekly_start_time, 0, 5).' - '.substr((string) $record->pivot->weekly_end_time, 0, 5)
                        : '—'),

                TextColumn::make('pivot.teacher_notes')
                    ->label('ملاحظات')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('ربط مادة دراسية')
                    ->modalHeading('ربط مادة دراسية وتحديد الموعد')
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()->label('المادة الدراسية'),
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
                // Action::make('viewClasses')
                //     ->label('حصص المادة')
                //     ->icon(Heroicon::OutlinedCalendar)
                //     ->color('primary')
                //     ->url(fn (Subject $record): string => ClassSessionResource::getUrl('index', [
                //         'tableFilters' => [
                //             'student_id' => ['value' => $this->getOwnerRecord()->getKey()],
                //             'subject_id' => ['value' => $record->id],
                //         ],
                //     ])),

                Action::make('scheduleClass')
                    ->label('جدولة حصة')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('success')
                    ->url(fn (Subject $record): string => ClassSessionResource::getUrl('create', [
                        'student_id' => $this->getOwnerRecord()->getKey(),
                        'subject_id' => $record->id,
                    ])),

                EditAction::make()->label('تعديل الموعد'),
                DetachAction::make()->label('إلغاء الربط'),
            ]);
    }
}
