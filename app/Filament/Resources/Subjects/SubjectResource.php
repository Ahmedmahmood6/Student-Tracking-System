<?php

namespace App\Filament\Resources\Subjects;

use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Filament\Resources\Subjects\Pages\EditSubject;
use App\Filament\Resources\Subjects\Pages\ListSubjects;
use App\Filament\Resources\Subjects\Pages\ViewSubject;
use App\Filament\Resources\Subjects\RelationManagers\StudentsRelationManager;
use App\Models\Subject;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $modelLabel = 'مادة دراسية';

    protected static ?string $pluralModelLabel = 'المواد الدراسية';

    protected static ?string $navigationLabel = 'المواد الدراسية';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static UnitEnum|string|null $navigationGroup = 'الإدارة الأكاديمية';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المادة الدراسية')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المادة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: الرياضيات، الفيزياء، اللغة الإنجليزية'),

                        Textarea::make('description')
                            ->label('الوصف أو المنهج الدراسي')
                            ->rows(3)
                            ->placeholder('تفاصيل المنهج أو المقرر الدراسي...'),

                        Toggle::make('active')
                            ->label('مادة نشطة')
                            ->default(true),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل المادة الدراسية')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->label('اسم المادة')
                                ->weight('bold')
                                ->size('lg'),

                            IconEntry::make('active')
                                ->label('نشطة')
                                ->boolean(),
                        ]),

                        TextEntry::make('description')
                            ->label('الوصف')
                            ->placeholder('لا يوجد وصف مسجل')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المادة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('students_count')
                    ->counts('students')
                    ->label('عدد الطلاب المسجلين')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->placeholder('—'),

                ToggleColumn::make('active')
                    ->label('نشطة'),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('حالة النشاط')
                    ->placeholder('جميع المواد')
                    ->trueLabel('النشطة فقط')
                    ->falseLabel('غير النشطة فقط'),
            ])
            ->recordActions([
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
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubjects::route('/'),
            'create' => CreateSubject::route('/create'),
            'view' => ViewSubject::route('/{record}'),
            'edit' => EditSubject::route('/{record}/edit'),
        ];
    }
}
