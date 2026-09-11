<?php

namespace App\Filament\Resources\ClassSessions\RelationManagers;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assessments';

    protected static ?string $title = 'التقييمات والواجبات المدرسية';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('type')
                        ->label('نوع التقييم')
                        ->options(collect(AssessmentType::cases())->mapWithKeys(fn (AssessmentType $type) => [$type->value => $type->label()]))
                        ->default(AssessmentType::Homework->value)
                        ->required(),

                    TextInput::make('title')
                        ->label('عنوان التقييم / موضوع الاختبار')
                        ->required()
                        ->placeholder('مثال: اختبار الباب الثاني، حل واجب صـ 45'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('score')
                        ->label('الدرجة المحرزة')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->lte('max_score'),

                    TextInput::make('max_score')
                        ->label('الدرجة العظمى')
                        ->numeric()
                        ->default(100)
                        ->required()
                        ->gt(0),
                ]),

                Textarea::make('description')
                    ->label('الأسئلة أو تفاصيل التكليف')
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('ملاحظات المعلم وتوجيهاته')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state instanceof AssessmentType ? $state->label() : (AssessmentType::tryFrom((string) $state)?->label() ?? $state)),

                TextColumn::make('title')
                    ->label('عنوان التقييم')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('score_display')
                    ->label('الدرجة / العظمى')
                    ->state(fn (Assessment $record): string => $record->score.' / '.$record->max_score),

                TextColumn::make('percentage')
                    ->label('النسبة المئوية')
                    ->badge()
                    ->state(fn (Assessment $record): string => $record->percentage.'%')
                    ->color(fn (Assessment $record): string => match (true) {
                        $record->percentage >= 85 => 'success',
                        $record->percentage >= 65 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('notes')
                    ->label('ملاحظات المعلم')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('تصفية حسب نوع التقييم')
                    ->options(collect(AssessmentType::cases())->mapWithKeys(fn (AssessmentType $type) => [$type->value => $type->label()])),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة تقييم / واجب'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
