<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledPostResource\Pages;
use App\Models\BrowserProfile;
use App\Models\FacebookGroup;
use App\Models\PostTemplate;
use App\Models\ScheduledPost;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ScheduledPostResource extends Resource
{
    protected static ?string $model = ScheduledPost::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Đăng bài nhóm';
    protected static ?string $modelLabel = 'Bài đăng nhóm';
    protected static ?string $pluralModelLabel = 'Bài đăng nhóm';
    protected static string | UnitEnum | null $navigationGroup = '🤖 Tự động hóa';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $pending = ScheduledPost::where('status', 'pending')->count();
        return $pending > 0 ? "{$pending}" : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            // === Left column: Content ===
            Group::make([
                Section::make('📝 Nội dung bài viết')
                    ->description('Soạn nội dung để đăng lên các nhóm Facebook')
                    ->schema([
                        Forms\Components\Select::make('template_id')
                            ->label('📋 Chọn mẫu bài đăng')
                            ->options(
                                PostTemplate::active()->orderBy('usage_count', 'desc')
                                    ->get()
                                    ->mapWithKeys(fn ($t) => [
                                        $t->id => $t->name . ($t->category !== 'general' ? " [{$t->category}]" : ''),
                                    ])
                            )
                            ->searchable()
                            ->placeholder('-- Chọn mẫu để điền nhanh --')
                            ->prefixIcon('heroicon-o-rectangle-stack')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $template = PostTemplate::find($state);
                                    if ($template) {
                                        $set('content', $template->content);
                                        if ($template->images) {
                                            $set('images', $template->images);
                                        }
                                        if ($template->seed_comments) {
                                            $seedTexts = collect($template->seed_comments)
                                                ->pluck('text')
                                                ->filter()
                                                ->values()
                                                ->toArray();
                                            $set('settings.seed_comments', $seedTexts);
                                        }
                                        $template->incrementUsage();
                                    }
                                }
                            })
                            ->helperText('Chọn mẫu để tự động điền nội dung, hình ảnh và seed comments')
                            ->columnSpanFull()
                            ->dehydrated(false),

                        Forms\Components\Select::make('browser_profile_id')
                            ->label('Profile trình duyệt')
                            ->options(
                                BrowserProfile::all()->mapWithKeys(fn ($p) => [
                                    $p->id => ($p->facebook_name ?: $p->name) . ($p->facebook_uid ? " (UID: {$p->facebook_uid})" : ''),
                                ])
                            )
                            ->required()
                            ->searchable()
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->reactive()
                            ->helperText('Chọn trình duyệt sẽ thực hiện đăng bài'),

                        Forms\Components\Textarea::make('content')
                            ->label('Nội dung')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull()
                            ->placeholder("Nhập nội dung bài viết...\n\nHỗ trợ spin: {Xin chào|Hello|Hi} các bạn!")
                            ->helperText('Dùng {A|B|C} để random nội dung cho mỗi nhóm — tránh bị spam'),

                        Forms\Components\FileUpload::make('images')
                            ->label('Hình ảnh đính kèm')
                            ->multiple()
                            ->image()
                            ->maxFiles(10)
                            ->maxSize(5120) // 5MB
                            ->directory('scheduled-posts')
                            ->columnSpanFull()
                            ->helperText('Tối đa 10 ảnh, mỗi ảnh ≤ 5MB'),
                    ]),

                Section::make('⚙️ Cài đặt đăng bài')
                    ->schema([
                        Grid::make(3)->schema([
                            Forms\Components\TextInput::make('settings.minDelay')
                                ->label('Delay tối thiểu (giây)')
                                ->numeric()
                                ->default(30)
                                ->minValue(10)
                                ->helperText('Nghỉ giữa 2 nhóm'),

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
                    ->collapsible()
                    ->collapsed(),

                Section::make('🌱 Seeding (tự tương tác bài sau khi đăng)')
                    ->schema([
                        Forms\Components\Toggle::make('settings.seedLike')
                            ->label('Tự like bài sau khi đăng')
                            ->default(true),

                        Forms\Components\Textarea::make('settings.seedComments')
                            ->label('Seed comments')
                            ->helperText('Mỗi dòng = 1 comment tự đăng vào bài. Hỗ trợ spin {A|B|C}')
                            ->rows(3)
                            ->columnSpanFull()
                            ->dehydrateStateUsing(fn ($state) => $state ? array_filter(array_map('trim', explode("\n", $state))) : [])
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state),

                        Forms\Components\TextInput::make('settings.seedDelay')
                            ->label('Delay giữa các comment (giây)')
                            ->numeric()
                            ->default(10)
                            ->minValue(5),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])->columnSpan(2),

            // === Right column: Groups + Schedule ===
            Group::make([
                Section::make('📅 Lên lịch')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Thời gian đăng')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->minDate(now())
                            ->default(now()->addMinutes(5))
                            ->helperText('Chọn thời gian đăng bài'),

                        Forms\Components\Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'pending' => '⏳ Chờ đăng',
                                'processing' => '🔄 Đang đăng',
                                'completed' => '✅ Hoàn thành',
                                'failed' => '❌ Thất bại',
                                'cancelled' => '🚫 Đã huỷ',
                            ])
                            ->default('pending')
                            ->disabled(),
                    ]),

                Section::make('👥 Chọn nhóm đăng')
                    ->description('Chọn nhóm Facebook để đăng bài')
                    ->schema([
                        Forms\Components\CheckboxList::make('group_ids')
                            ->label('')
                            ->options(function (callable $get) {
                                $profileId = $get('browser_profile_id');
                                if (!$profileId) {
                                    return [];
                                }
                                return FacebookGroup::where('browser_profile_id', $profileId)
                                    ->orderBy('name')
                                    ->pluck('name', 'group_id');
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(1)
                            ->helperText(function (callable $get) {
                                $profileId = $get('browser_profile_id');
                                if (!$profileId) {
                                    return '⚠️ Vui lòng chọn Profile trước';
                                }
                                $count = FacebookGroup::where('browser_profile_id', $profileId)->count();
                                return $count > 0
                                    ? "📌 {$count} nhóm khả dụng — tick chọn nhóm muốn đăng"
                                    : '⚠️ Profile này chưa sync nhóm. Mở extension → vào Facebook → sync nhóm';
                            })
                            ->required(),
                    ]),

                Section::make('📊 Kết quả')
                    ->schema([
                        Forms\Components\KeyValue::make('results')
                            ->label('')
                            ->columnSpanFull()
                            ->disabled(),
                    ])
                    ->visible(fn($record) => $record && $record->results)
                    ->collapsible(),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('browserProfile.name')
                    ->label('Profile')
                    ->description(fn ($record) => $record->browserProfile?->facebook_name)
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-globe-alt'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Nội dung')
                    ->limit(60)
                    ->searchable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->content),

                Tables\Columns\TextColumn::make('group_ids')
                    ->label('Nhóm')
                    ->badge()
                    ->formatStateUsing(fn($state) => is_array($state) ? count($state) . ' nhóm' : '0')
                    ->color('info'),

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
                        'pending' => '⏳ Chờ',
                        'processing' => '🔄 Đang xử lý',
                        'completed' => '✅ Hoàn thành',
                        'failed' => '❌ Thất bại',
                        'cancelled' => '🚫 Đã huỷ',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Hẹn lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->scheduled_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
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
                Actions\EditAction::make(),
                Actions\Action::make('cancel')
                    ->label('Huỷ')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'processing']))
                    ->action(fn ($record) => $record->update(['status' => 'cancelled', 'completed_at' => now()])),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
