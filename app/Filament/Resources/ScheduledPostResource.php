<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledPostResource\Pages;
use App\Models\BrowserProfile;
use App\Models\ScheduledPost;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduledPostResource extends Resource
{
    protected static ?string $model = ScheduledPost::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Hẹn giờ đăng';
    protected static ?string $modelLabel = 'Bài hẹn giờ';
    protected static string | \UnitEnum | null $navigationGroup = '🤖 Tự động hóa';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('browser_profile_id')
                ->label('Profile trình duyệt')
                ->options(BrowserProfile::pluck('name', 'id'))
                ->required()
                ->searchable(),

            Forms\Components\Textarea::make('content')
                ->label('Nội dung bài viết')
                ->required()
                ->rows(4)
                ->columnSpanFull(),

            Forms\Components\TagsInput::make('group_ids')
                ->label('Group IDs')
                ->placeholder('Nhập Facebook Group ID')
                ->required(),

            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('Thời gian hẹn')
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y H:i')
                ->minDate(now()),

            Forms\Components\Select::make('status')
                ->label('Trạng thái')
                ->options([
                    'pending' => '⏳ Chờ',
                    'processing' => '🔄 Đang xử lý',
                    'completed' => '✅ Hoàn thành',
                    'failed' => '❌ Thất bại',
                    'cancelled' => '🚫 Đã huỷ',
                ])
                ->default('pending')
                ->disabled(),

            Forms\Components\Section::make('Cài đặt đăng bài')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('settings.minDelay')
                            ->label('Delay tối thiểu (giây)')
                            ->numeric()
                            ->default(30)
                            ->minValue(10),

                        Forms\Components\TextInput::make('settings.maxDelay')
                            ->label('Delay tối đa (giây)')
                            ->numeric()
                            ->default(120)
                            ->minValue(15),

                        Forms\Components\Toggle::make('settings.spinEnabled')
                            ->label('Spin nội dung')
                            ->default(true)
                            ->helperText('Dùng {A|B|C} để random'),
                    ]),
                ])
                ->collapsible(),

            Forms\Components\Section::make('🌱 Seeding (tự tương tác bài sau khi đăng)')
                ->schema([
                    Forms\Components\Toggle::make('settings.seedLike')
                        ->label('Tự like bài sau khi đăng')
                        ->default(true)
                        ->helperText('Tự động like bài vừa đăng'),

                    Forms\Components\Textarea::make('settings.seedComments')
                        ->label('Seed comments')
                        ->helperText('Mỗi dòng là 1 comment sẽ tự động đăng vào bài. Hỗ trợ spin {A|B|C}')
                        ->rows(4)
                        ->columnSpanFull()
                        ->dehydrateStateUsing(fn ($state) => $state ? array_filter(array_map('trim', explode("\n", $state))) : [])
                        ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state),

                    Forms\Components\TextInput::make('settings.seedDelay')
                        ->label('Delay giữa các comment seed (giây)')
                        ->numeric()
                        ->default(10)
                        ->minValue(5)
                        ->helperText('Thời gian chờ giữa các comment seed'),
                ])
                ->collapsible()
                ->collapsed(),

            Forms\Components\KeyValue::make('results')
                ->label('Kết quả')
                ->columnSpanFull()
                ->disabled()
                ->visible(fn($record) => $record && $record->results),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('browserProfile.name')
                    ->label('Profile')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->label('Nội dung')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('group_ids')
                    ->label('Nhóm')
                    ->badge()
                    ->formatStateUsing(fn($state) => is_array($state) ? count($state) . ' nhóm' : '0'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Trạng thái')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'pending' => 'Chờ',
                        'processing' => 'Đang xử lý',
                        'completed' => 'Hoàn thành',
                        'failed' => 'Thất bại',
                        'cancelled' => 'Đã huỷ',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Hẹn lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'pending' => 'Chờ',
                        'processing' => 'Đang xử lý',
                        'completed' => 'Hoàn thành',
                        'failed' => 'Thất bại',
                        'cancelled' => 'Đã huỷ',
                    ]),

                Tables\Filters\SelectFilter::make('browser_profile_id')
                    ->label('Profile')
                    ->options(BrowserProfile::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledPosts::route('/'),
            'create' => Pages\CreateScheduledPost::route('/create'),
            'edit' => Pages\EditScheduledPost::route('/{record}/edit'),
        ];
    }
}
