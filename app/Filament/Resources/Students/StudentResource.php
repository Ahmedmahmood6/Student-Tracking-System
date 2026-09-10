<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\RelationManagers\ClassesRelationManager;
use App\Filament\Resources\Students\RelationManagers\MonthlyFeesRelationManager;
use App\Filament\Resources\Students\RelationManagers\ReportsRelationManager;
use App\Filament\Resources\Students\RelationManagers\SubjectsRelationManager;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static UnitEnum|string|null $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student Information')
                    ->description('Personal profile and status')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g. Omar Khalid'),

                            TextInput::make('phone')
                                ->tel()
                                ->maxLength(30)
                                ->placeholder('e.g. +201012345678'),

                            DatePicker::make('date_of_birth')
                                ->label('Date of Birth')
                                ->native(false),

                            Toggle::make('active')
                                ->label('Active Student')
                                ->default(true)
                                ->inline(false),
                        ]),

                        Textarea::make('address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Parent / Guardian Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('parent_name')
                                ->label('Parent / Guardian Name')
                                ->maxLength(255),

                            TextInput::make('parent_phone')
                                ->label('Parent Phone')
                                ->tel()
                                ->maxLength(30)
                                ->placeholder('e.g. +201098765432'),
                        ]),
                    ]),

                Section::make('Internal Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Teacher / Admin Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student Profile')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name')
                                ->weight('bold')
                                ->size('lg'),

                            TextEntry::make('phone')
                                ->placeholder('Not Provided'),

                            IconEntry::make('active')
                                ->boolean(),

                            TextEntry::make('parent_name')
                                ->label('Parent / Guardian')
                                ->placeholder('Not Provided'),

                            TextEntry::make('parent_phone')
                                ->label('Parent Phone')
                                ->placeholder('Not Provided'),

                            TextEntry::make('date_of_birth')
                                ->date('M d, Y')
                                ->placeholder('Not Provided'),
                        ]),

                        TextEntry::make('address')
                            ->placeholder('No address specified')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->placeholder('No notes available')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('parent_name')
                    ->label('Parent')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('parent_phone')
                    ->label('Parent Phone')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('subjects.name')
                    ->label('Enrolled Subjects')
                    ->badge()
                    ->color('info')
                    ->separator(', '),

                ToggleColumn::make('active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Active Status')
                    ->placeholder('All Students')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->recordActions([
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
            SubjectsRelationManager::class,
            ClassesRelationManager::class,
            ReportsRelationManager::class,
            MonthlyFeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'view' => ViewStudent::route('/{record}'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
