<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Models\ClassSession;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

    protected static ?string $title = 'الحصص والجلسات الدراسية';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('المادة الدراسية')
                    ->relationship('subject', 'name')
                    ->options(fn () => $this->getOwnerRecord()->subjects->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                DatePicker::make('date')
                    ->label('تاريخ الحصة')
                    ->required()
                    ->default(now()),

                Grid::make(2)->schema([
                    TimePicker::make('start_time')
                        ->label('وقت البدء')
                        ->required()
                        ->seconds(false)
                        ->default('10:00'),

                    TimePicker::make('end_time')
                        ->label('وقت الانتهاء')
                        ->required()
                        ->seconds(false)
                        ->default('11:30')
                        ->after('start_time'),
                ]),

                Grid::make(2)->schema([
                    Select::make('status')
                        ->label('حالة الحصة')
                        ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()]))
                        ->default(ClassSessionStatus::Scheduled->value)
                        ->required(),

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
                ]),

                Textarea::make('general_notes')
                    ->label('ملاحظات الحصة ومحتوى الدرس')
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
                    ->label('التاريخ')
                    ->date('M d, Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subject.name')
                    ->label('المادة')
                    ->badge()
                    ->color('info')
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
                SelectFilter::make('subject_id')
                    ->label('تصفية حسب المادة')
                    ->options(fn () => Subject::pluck('name', 'id')),

                SelectFilter::make('status')
                    ->label('تصفية حسب الحالة')
                    ->options(collect(ClassSessionStatus::cases())->mapWithKeys(fn (ClassSessionStatus $status) => [$status->value => $status->label()])),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة حصة جديدة'),
            ])
            ->recordActions([
                Action::make('manageAttendance')
                    ->label('تسجيل الحضور')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->modalHeading('تسجيل حضور الطالب في الحصة')
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

                Action::make('viewClass')
                    ->label('تفاصيل الحصة')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (ClassSession $record): string => ClassSessionResource::getUrl('view', ['record' => $record])),
                ActionGroup::make([
                    EditAction::make()->label('تعديل'),
                    DeleteAction::make()->label('حذف'),
                ]),
            ]);
    }
}
