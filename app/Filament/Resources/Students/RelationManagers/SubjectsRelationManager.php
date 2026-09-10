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

    protected static ?string $title = 'Enrolled Subjects & Schedules';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('weekly_day')
                    ->label('Weekly Day')
                    ->options(collect(WeeklyDay::cases())->mapWithKeys(fn (WeeklyDay $day) => [$day->value => $day->label()]))
                    ->nullable(),

                TimePicker::make('weekly_start_time')
                    ->label('Weekly Start Time')
                    ->seconds(false),

                TimePicker::make('weekly_end_time')
                    ->label('Weekly End Time')
                    ->seconds(false)
                    ->after('weekly_start_time'),

                Textarea::make('teacher_notes')
                    ->label('Teacher / Plan Notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Subject')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('pivot.weekly_day')
                    ->label('Schedule Day')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst((string) $state) : '—'),

                TextColumn::make('schedule_time')
                    ->label('Weekly Time')
                    ->state(fn (Subject $record): string => $record->pivot?->weekly_start_time
                        ? substr((string) $record->pivot->weekly_start_time, 0, 5).' - '.substr((string) $record->pivot->weekly_end_time, 0, 5)
                        : '—'),

                TextColumn::make('pivot.teacher_notes')
                    ->label('Notes')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('weekly_day')
                            ->label('Weekly Day')
                            ->options(collect(WeeklyDay::cases())->mapWithKeys(fn (WeeklyDay $day) => [$day->value => $day->label()]))
                            ->nullable(),
                        TimePicker::make('weekly_start_time')
                            ->label('Weekly Start Time')
                            ->seconds(false),
                        TimePicker::make('weekly_end_time')
                            ->label('Weekly End Time')
                            ->seconds(false)
                            ->after('weekly_start_time'),
                        Textarea::make('teacher_notes')
                            ->label('Teacher / Plan Notes'),
                    ]),
            ])
            ->recordActions([
                Action::make('viewClasses')
                    ->label('Subject Classes')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->color('primary')
                    ->url(fn (Subject $record): string => ClassSessionResource::getUrl('index', [
                        'tableFilters' => [
                            'student_id' => ['value' => $this->getOwnerRecord()->getKey()],
                            'subject_id' => ['value' => $record->id],
                        ],
                    ])),

                Action::make('scheduleClass')
                    ->label('New Class')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('success')
                    ->url(fn (Subject $record): string => ClassSessionResource::getUrl('create', [
                        'student_id' => $this->getOwnerRecord()->getKey(),
                        'subject_id' => $record->id,
                    ])),

                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
