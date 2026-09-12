<?php

namespace App\Filament\Resources\Students;

use App\Enums\ExamType;
use App\Enums\GradeLevel;
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
use Filament\Forms\Components\Select;
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

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $navigationLabel = 'الطلاب';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static UnitEnum|string|null $navigationGroup = 'الإدارة الأكاديمية';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الطالب')
                    ->description('الملف الشخصي والحالة الأكاديمية')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('اسم الطالب')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('مثال: عمر خالد'),

                            TextInput::make('phone')
                                ->label('رقم الهاتف')
                                ->tel()
                                ->maxLength(30)
                                ->placeholder('مثال: 01012345678'),

                            Select::make('grade_level')
                                ->label('الصف الدراسي')
                                ->options(GradeLevel::class)
                                ->searchable()
                                ->preload(),

                            Select::make('exam_type')
                                ->label('نوع الاختبار')
                                ->options(ExamType::class),

                            DatePicker::make('date_of_birth')
                                ->label('تاريخ الميلاد')
                                ->native(false),

                            Toggle::make('active')
                                ->label('طالب نشط')
                                ->default(true)
                                ->inline(false),
                        ]),

                        Textarea::make('address')
                            ->label('العنوان')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('بيانات ولي الأمر')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('parent_name')
                                ->label('اسم ولي الأمر')
                                ->maxLength(255)
                                ->placeholder('مثال: خالد محمد'),

                            TextInput::make('parent_phone')
                                ->label('هاتف ولي الأمر')
                                ->tel()
                                ->maxLength(30)
                                ->placeholder('مثال: 01098765432'),
                        ]),
                    ]),

                Section::make('ملاحظات داخلية')
                    ->schema([
                        Textarea::make('notes')
                            ->label('ملاحظات المعلم / الإدارة')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الملف التعريفي للطالب')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name')
                                ->label('اسم الطالب')
                                ->weight('bold')
                                ->size('lg'),

                            TextEntry::make('phone')
                                ->label('رقم الهاتف')
                                ->placeholder('غير متوفر'),

                            IconEntry::make('active')
                                ->label('نشط')
                                ->boolean(),

                            TextEntry::make('grade_level')
                                ->label('الصف الدراسي')
                                ->badge()
                                ->placeholder('غير محدد'),

                            TextEntry::make('exam_type')
                                ->label('نوع الاختبار')
                                ->badge()
                                ->placeholder('غير محدد'),

                            TextEntry::make('parent_name')
                                ->label('ولي الأمر')
                                ->placeholder('غير متوفر'),

                            TextEntry::make('parent_phone')
                                ->label('هاتف ولي الأمر')
                                ->placeholder('غير متوفر'),

                            TextEntry::make('date_of_birth')
                                ->label('تاريخ الميلاد')
                                ->date('M d, Y')
                                ->placeholder('غير متوفر'),
                        ]),

                        TextEntry::make('address')
                            ->label('العنوان')
                            ->placeholder('لا يوجد عنوان محدد')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->placeholder('لا توجد ملاحظات مسجلة')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('parent_name')
                    ->label('ولي الأمر')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('parent_phone')
                    ->label('هاتف ولي الأمر')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('subjects.name')
                    ->label('المواد المسجلة')
                    ->badge()
                    ->color('info')
                    ->separator(', '),

                TextColumn::make('grade_level')
                    ->label('الصف الدراسي')
                    ->badge()
                    ->sortable(),

                TextColumn::make('exam_type')
                    ->label('نوع الاختبار')
                    ->badge()
                    ->sortable(),

                ToggleColumn::make('active')
                    ->label('نشط'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('M d, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('حالة النشاط')
                    ->placeholder('جميع الطلاب')
                    ->trueLabel('النشطين فقط')
                    ->falseLabel('غير النشطين فقط'),
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
