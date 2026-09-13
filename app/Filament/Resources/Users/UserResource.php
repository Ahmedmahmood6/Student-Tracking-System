<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'معلم / مستخدم';

    protected static ?string $pluralModelLabel = 'المعلمين والمستخدمين';

    protected static ?string $navigationLabel = 'المعلمين والمستخدمين';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static UnitEnum|string|null $navigationGroup = 'إدارة النظام';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الحساب')
                    ->description('أدخل البيانات الأساسية وصلاحية الحساب')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('الاسم الكامل')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('مثال: أستاذة فاطمة أحمد'),

                            TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->placeholder('teacher@example.com'),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('phone')
                                ->label('رقم الهاتف')
                                ->tel()
                                ->maxLength(20)
                                ->placeholder('01012345678'),

                            Select::make('role')
                                ->label('الدور / الصلاحية')
                                ->options(UserRole::class)
                                ->default(UserRole::Teacher)
                                ->required(),
                        ]),

                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'اترك هذا الحقل فارغاً إذا كنت لا ترغب في تغيير كلمة المرور' : null)
                            ->placeholder('أدخل كلمة المرور...'),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل الحساب')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->label('الاسم')
                                ->weight('bold')
                                ->size('lg'),

                            TextEntry::make('email')
                                ->label('البريد الإلكتروني')
                                ->copyable(),

                            TextEntry::make('phone')
                                ->label('رقم الهاتف')
                                ->placeholder('لا يوجد رقم هاتف مسجل'),

                            TextEntry::make('role')
                                ->label('الدور')
                                ->badge(),

                            TextEntry::make('created_at')
                                ->label('تاريخ الإنشاء')
                                ->dateTime('Y-m-d h:i A'),

                            TextEntry::make('updated_at')
                                ->label('آخر تعديل')
                                ->dateTime('Y-m-d h:i A'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable()
                    ->icon(Heroicon::OutlinedEnvelope),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('الدور')
                    ->options(UserRole::class),
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

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
