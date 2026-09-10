<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Models\ClassSession;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClassesRelationManager extends RelationManager
{
    protected static string $relationship = 'classSessions';

    protected static ?string $title = 'Classes & Sessions';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->options(fn () => $this->getOwnerRecord()->subjects->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                DatePicker::make('date')
                    ->required()
                    ->default(now()),

                Grid::make(2)->schema([
                    TimePicker::make('start_time')
                        ->required()
                        ->seconds(false)
                        ->default('10:00'),

                    TimePicker::make('end_time')
                        ->required()
                        ->seconds(false)
                        ->default('11:30')
                        ->after('start_time'),
                ]),

                Select::make('status')
                    ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()]))
                    ->default(ClassSessionStatus::Scheduled->value)
                    ->required(),

                Textarea::make('general_notes')
                    ->label('General Class Notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('time')
                    ->label('Time')
                    ->state(fn (ClassSession $record): string => substr((string) $record->start_time, 0, 5).' - '.substr((string) $record->end_time, 0, 5)),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ClassSessionStatus|string|null $state): string => match ($state instanceof ClassSessionStatus ? $state : ClassSessionStatus::tryFrom((string) $state)) {
                        ClassSessionStatus::Scheduled => 'warning',
                        ClassSessionStatus::Completed => 'success',
                        ClassSessionStatus::Cancelled => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('attendance.status')
                    ->label('Attendance')
                    ->badge()
                    ->placeholder('Not Recorded')
                    ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('assessments_count')
                    ->counts('assessments')
                    ->label('Assessments')
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Filter by Subject')
                    ->options(fn () => Subject::pluck('name', 'id')),

                SelectFilter::make('status')
                    ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()])),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('manageAttendance')
                    ->label('Attendance')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->form([
                        Select::make('status')
                            ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn (AttendanceStatus $status) => [$status->value => $status->label()]))
                            ->default(fn (ClassSession $record) => $record->attendance?->status?->value ?? AttendanceStatus::Present->value)
                            ->required(),
                        Textarea::make('notes')
                            ->default(fn (ClassSession $record) => $record->attendance?->notes),
                    ])
                    ->action(function (ClassSession $record, array $data): void {
                        $record->attendance()->updateOrCreate(
                            ['class_id' => $record->id],
                            [
                                'status' => $data['status'],
                                'notes' => $data['notes'] ?? null,
                            ]
                        );
                    }),

                Action::make('updateStatus')
                    ->label('Status')
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('warning')
                    ->modalHeading('Update Class Status')
                    ->form([
                        Select::make('status')
                            ->label('Session Status')
                            ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()]))
                            ->default(fn (ClassSession $record) => $record->status instanceof ClassSessionStatus ? $record->status->value : $record->status)
                            ->required(),
                    ])
                    ->action(function (ClassSession $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),

                Action::make('viewClass')
                    ->label('Open Class Details')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (ClassSession $record): string => ClassSessionResource::getUrl('view', ['record' => $record])),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
