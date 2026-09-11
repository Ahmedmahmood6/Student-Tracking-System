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

    protected static ?string $modelLabel = 'حصة دراسية';

    protected static ?string $pluralModelLabel = 'الحصص والجلسات';

    protected static ?string $navigationLabel = 'الحصص والجلسات';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static UnitEnum|string|null $navigationGroup = 'الإدارة الأكاديمية';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الحصة الدراسية')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('student_id')
                                ->label('الطالب')
                                ->relationship('student', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('subject_id')
                                ->label('المادة الدراسية')
                                ->relationship('subject', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            DatePicker::make('date')
                                ->label('تاريخ الحصة')
                                ->default(now())
                                ->required(),

                            Select::make('status')
                                ->label('حالة الحصة')
                                ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()]))
                                ->default(ClassSessionStatus::Scheduled->value)
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TimePicker::make('start_time')
                                ->label('وقت البدء')
                                ->seconds(false)
                                ->default('10:00')
                                ->required(),

                            TimePicker::make('end_time')
                                ->label('وقت الانتهاء')
                                ->seconds(false)
                                ->default('11:30')
                                ->required()
                                ->after('start_time'),
                        ]),

                        Select::make('rating')
                            ->label('تقييم أداء الطالب في الحصة (نجوم)')
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ (5/5) ممتاز جداً',
                                4 => '⭐⭐⭐⭐ (4/5) جيد جداً',
                                3 => '⭐⭐⭐ (3/5) جيد',
                                2 => '⭐⭐ (2/5) مقبول',
                                1 => '⭐ (1/5) يحتاج لمتابعة',
                            ])
                            ->nullable()
                            ->placeholder('لم يتم التقييم بعد (اختياري)'),

                        Textarea::make('general_notes')
                            ->label('موضوعات الدرس والملاحظات العامة')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ملخص الحصة')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('student.name')
                                ->label('الطالب')
                                ->weight('bold'),

                            TextEntry::make('subject.name')
                                ->label('المادة')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('date')
                                ->label('التاريخ')
                                ->date('M d, Y'),

                            TextEntry::make('time')
                                ->label('الوقت')
                                ->state(fn (ClassSession $record): string => substr((string) $record->start_time, 0, 5).' - '.substr((string) $record->end_time, 0, 5)),

                            TextEntry::make('status')
                                ->label('الحالة')
                                ->badge()
                                ->color(fn (ClassSessionStatus|string|null $state): string => match ($state instanceof ClassSessionStatus ? $state : ClassSessionStatus::tryFrom((string) $state)) {
                                    ClassSessionStatus::Scheduled => 'warning',
                                    ClassSessionStatus::Completed => 'success',
                                    ClassSessionStatus::Cancelled => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('attendance.status')
                                ->label('الحضور')
                                ->badge()
                                ->placeholder('لم يُسجل')
                                ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                                    'present' => 'success',
                                    'late' => 'warning',
                                    'absent' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('rating')
                                ->label('تقييم الأداء في الحصة')
                                ->state(fn (ClassSession $record): string => $record->rating ? str_repeat('⭐', $record->rating)." ({$record->rating}/5)" : 'لم يتم التقييم بعد')
                                ->columnSpanFull(),
                        ]),

                        TextEntry::make('attendance.notes')
                            ->label('ملاحظات الحضور')
                            ->placeholder('لا توجد ملاحظات')
                            ->columnSpanFull(),

                        TextEntry::make('general_notes')
                            ->label('ملاحظات محتوى الدرس')
                            ->placeholder('لا توجد ملاحظات مسجلة')
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
                    ->label('التاريخ')
                    ->date('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (ClassSession $record): string => $record->date?->isToday() ? 'primary' : 'gray'),

                TextColumn::make('student.name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subject.name')
                    ->label('المادة')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('time')
                    ->label('الوقت')
                    ->state(fn (ClassSession $record): string => substr((string) $record->start_time, 0, 5).' - '.substr((string) $record->end_time, 0, 5)),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (ClassSessionStatus|string|null $state): string => match ($state instanceof ClassSessionStatus ? $state : ClassSessionStatus::tryFrom((string) $state)) {
                        ClassSessionStatus::Scheduled => 'warning',
                        ClassSessionStatus::Completed => 'success',
                        ClassSessionStatus::Cancelled => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('attendance.status')
                    ->label('الحضور')
                    ->badge()
                    ->placeholder('لم يُسجل')
                    ->color(fn ($state): string => match ((string) ($state?->value ?? $state)) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('rating')
                    ->label('تقييم الأداء')
                    ->state(fn (ClassSession $record): string => $record->rating ? str_repeat('⭐', $record->rating) : '—')
                    ->placeholder('—'),

                TextColumn::make('assessments_count')
                    ->counts('assessments')
                    ->label('التقييمات')
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label('تصفية حسب الطالب')
                    ->options(fn () => Student::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('subject_id')
                    ->label('تصفية حسب المادة')
                    ->options(fn () => Subject::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('تصفية حسب الحالة')
                    ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()])),

                Filter::make('today')
                    ->label('حصص اليوم فقط')
                    ->query(fn (Builder $query): Builder => $query->whereDate('date', now()->toDateString())),
            ])
            ->recordActions([
                Action::make('recordAttendance')
                    ->label('الحضور')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->modalHeading('تسجيل الحضور')
                    ->form([
                        Select::make('status')
                            ->label('حالة الحضور')
                            ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn (AttendanceStatus $status) => [$status->value => $status->label()]))
                            ->default(fn (ClassSession $record) => $record->attendance?->status?->value ?? AttendanceStatus::Present->value)
                            ->required(),
                        Textarea::make('notes')
                            ->label('ملاحظات الحضور')
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

                Action::make('rateSession')
                    ->label('تقييم الأداء')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->modalHeading('تقييم أداء ومستوى الطالب في الحصة')
                    ->form([
                        Select::make('rating')
                            ->label('تقييم الحصة (من 5 نجوم)')
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ (5/5) ممتاز جداً',
                                4 => '⭐⭐⭐⭐ (4/5) جيد جداً',
                                3 => '⭐⭐⭐ (3/5) جيد',
                                2 => '⭐⭐ (2/5) مقبول',
                                1 => '⭐ (1/5) يحتاج لمتابعة',
                            ])
                            ->default(fn (ClassSession $record) => $record->rating)
                            ->nullable()
                            ->placeholder('اختر التقييم (اختياري)'),
                    ])
                    ->action(function (ClassSession $record, array $data): void {
                        $record->update([
                            'rating' => $data['rating'] ?? null,
                        ]);
                    }),

                Action::make('updateStatus')
                    ->label('الحالة')
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('gray')
                    ->modalHeading('تحديث حالة الحصة')
                    ->form([
                        Select::make('status')
                            ->label('حالة الحصة')
                            ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()]))
                            ->default(fn (ClassSession $record) => $record->status instanceof ClassSessionStatus ? $record->status->value : $record->status)
                            ->required(),
                    ])
                    ->action(function (ClassSession $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),

                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
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
