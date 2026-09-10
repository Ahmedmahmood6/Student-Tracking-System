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

    protected static ?string $title = 'Enrolled Students';

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
                    ->label('Student Name')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->placeholder('—'),

                TextColumn::make('pivot.weekly_day')
                    ->label('Schedule Day')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst((string) $state) : '—'),

                TextColumn::make('schedule_time')
                    ->label('Time')
                    ->state(fn (Student $record): string => $record->pivot?->weekly_start_time
                        ? substr((string) $record->pivot->weekly_start_time, 0, 5).' - '.substr((string) $record->pivot->weekly_end_time, 0, 5)
                        : '—'),

                IconColumn::make('active')
                    ->boolean(),
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
                    ->label('Student Classes')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->color('primary')
                    ->url(fn (Student $record): string => ClassSessionResource::getUrl('index', [
                        'tableFilters' => [
                            'student_id' => ['value' => $record->id],
                            'subject_id' => ['value' => $this->getOwnerRecord()->getKey()],
                        ],
                    ])),

                Action::make('studentProfile')
                    ->label('Profile')
                    ->icon(Heroicon::OutlinedUser)
                    ->url(fn (Student $record): string => StudentResource::getUrl('view', ['record' => $record])),

                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
