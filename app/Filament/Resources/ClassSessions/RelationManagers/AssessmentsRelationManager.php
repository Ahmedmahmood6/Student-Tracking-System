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

    protected static ?string $title = 'Assessments & Homework';

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
                        ->label('Assessment Type')
                        ->options(collect(AssessmentType::cases())->mapWithKeys(fn (AssessmentType $type) => [$type->value => $type->label()]))
                        ->default(AssessmentType::Homework->value)
                        ->required(),

                    TextInput::make('title')
                        ->label('Title / Topic')
                        ->required()
                        ->placeholder('e.g. Chapter 2 Quiz'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('score')
                        ->label('Score Earned')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->lte('max_score'),

                    TextInput::make('max_score')
                        ->label('Maximum Score')
                        ->numeric()
                        ->default(100)
                        ->required()
                        ->gt(0),
                ]),

                Textarea::make('description')
                    ->label('Description / Questions')
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Teacher Feedback / Notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state instanceof AssessmentType ? $state->label() : ucfirst((string) $state)),

                TextColumn::make('title')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('score_display')
                    ->label('Score / Max')
                    ->state(fn (Assessment $record): string => $record->score.' / '.$record->max_score),

                TextColumn::make('percentage')
                    ->label('Percentage')
                    ->badge()
                    ->state(fn (Assessment $record): string => $record->percentage.'%')
                    ->color(fn (Assessment $record): string => match (true) {
                        $record->percentage >= 85 => 'success',
                        $record->percentage >= 65 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('notes')
                    ->label('Feedback')
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(AssessmentType::cases())->mapWithKeys(fn (AssessmentType $type) => [$type->value => $type->label()])),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
