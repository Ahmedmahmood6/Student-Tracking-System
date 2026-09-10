<?php

namespace App\Filament\Resources\ClassSessions;

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Filament\Resources\ClassSessions\Pages\CreateClassSession;
use App\Filament\Resources\ClassSessions\Pages\EditClassSession;
use App\Filament\Resources\ClassSessions\Pages\ListClassSessions;
use App\Filament\Resources\ClassSessions\Pages\ViewClassSession;
use App\Filament\Resources\ClassSessions\RelationManagers\AssessmentsRelationManager;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\Subject;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ClassSessionResource extends Resource
{
    protected static ?string $model = ClassSession::class;

    protected static ?string $modelLabel = 'Class Session';

    protected static ?string $pluralModelLabel = 'Classes & Sessions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static UnitEnum|string|null $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Class Session Information')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('student_id')
                                ->label('Student')
                                ->relationship('student', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('subject_id')
                                ->label('Subject')
                                ->relationship('subject', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            DatePicker::make('date')
                                ->label('Session Date')
                                ->default(now())
                                ->required(),

                            Select::make('status')
                                ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()]))
                                ->default(ClassSessionStatus::Scheduled->value)
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TimePicker::make('start_time')
                                ->label('Start Time')
                                ->seconds(false)
                                ->default('10:00')
                                ->required(),

                            TimePicker::make('end_time')
                                ->label('End Time')
                                ->seconds(false)
                                ->default('11:30')
                                ->required()
                                ->after('start_time'),
                        ]),

                        Textarea::make('general_notes')
                            ->label('Class Topics & General Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session Summary')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('student.name')
                                ->label('Student')
                                ->weight('bold'),

                            TextEntry::make('subject.name')
                                ->label('Subject')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('date')
                                ->label('Date')
                                ->date('M d, Y'),

                            TextEntry::make('time')
                                ->label('Time Window')
                                ->state(fn (ClassSession $record): string => substr((string) $record->start_time, 0, 5).' - '.substr((string) $record->end_time, 0, 5)),

                            TextEntry::make('status')
                                ->badge()
                                ->color(fn (ClassSessionStatus|string|null $state): string => match ($state instanceof ClassSessionStatus ? $state : ClassSessionStatus::tryFrom((string) $state)) {
                                    ClassSessionStatus::Scheduled => 'warning',
                                    ClassSessionStatus::Completed => 'success',
                                    ClassSessionStatus::Cancelled => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('attendance.status')
                                ->label('Attendance')
                                ->badge()
                                ->placeholder('Not recorded')
                                ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                                    'present' => 'success',
                                    'late' => 'warning',
                                    'absent' => 'danger',
                                    default => 'gray',
                                }),
                        ]),

                        TextEntry::make('attendance.notes')
                            ->label('Attendance Remarks')
                            ->placeholder('No notes')
                            ->columnSpanFull(),

                        TextEntry::make('general_notes')
                            ->label('Lesson Notes / Outline')
                            ->placeholder('No notes recorded')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->date('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (ClassSession $record): string => $record->date?->isToday() ? 'primary' : 'gray'),

                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->badge()
                    ->color('info')
                    ->searchable()
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
                SelectFilter::make('student_id')
                    ->label('Student')
                    ->options(fn () => Student::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () => Subject::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()])),

                Filter::make('today')
                    ->label('Today\'s Classes')
                    ->query(fn (Builder $query): Builder => $query->whereDate('date', now()->toDateString())),
            ])
            ->recordActions([
                Action::make('recordAttendance')
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

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AssessmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassSessions::route('/'),
            'create' => CreateClassSession::route('/create'),
            'view' => ViewClassSession::route('/{record}'),
            'edit' => EditClassSession::route('/{record}/edit'),
        ];
    }
}
