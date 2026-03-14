<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostTemplateResource\Pages;
use App\Models\PostTemplate;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PostTemplateResource extends Resource
{
    protected static ?string $model = PostTemplate::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Mẫu bài đăng';
    protected static ?string $modelLabel = 'Mẫu bài đăng';
    protected static ?string $pluralModelLabel = 'Mẫu bài đăng';
    protected static string | UnitEnum | null $navigationGroup = '🤖 Tự động hóa';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Schemas\Components\Section::make('📝 Thông tin mẫu')
                ->description('Tạo mẫu nội dung để tái sử dụng khi đăng bài vào các nhóm')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Schemas\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Tên mẫu')
                            ->placeholder('VD: Giới thiệu sản phẩm ABC')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-tag'),

                        Forms\Components\Select::make('category')
                            ->label('Danh mục')
                            ->options([
                                'general' => '📋 Chung',
                                'promotion' => '🏷️ Khuyến mãi',
                                'review' => '⭐ Review/Đánh giá',
                                'news' => '📰 Tin tức',
                                'recruitment' => '💼 Tuyển dụng',
                                'event' => '🎉 Sự kiện',
                                'question' => '❓ Hỏi đáp',
                                'sharing' => '💡 Chia sẻ kiến thức',
                            ])
                            ->default('general')
                            ->prefixIcon('heroicon-o-folder'),
                    ]),

                    Forms\Components\Textarea::make('content')
                        ->label('Nội dung bài đăng')
                        ->placeholder("VD: {spin|Xin chào|Hello|Hi} các bạn! 👋\n\nMình muốn {spin|giới thiệu|chia sẻ|review} về...\n\n✅ Ưu điểm 1\n✅ Ưu điểm 2\n\n{spin|Cảm ơn đã đọc|Mọi người cho ý kiến nhé|Để lại comment nha}! 🔥")
                        ->helperText('💡 Dùng {spin|text1|text2|text3} để xoay vòng nội dung — mỗi nhóm sẽ nhận nội dung khác nhau, tránh bị Facebook phát hiện spam')
                        ->rows(8)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('images')
                        ->label('Hình ảnh kèm theo')
                        ->multiple()
                        ->image()
                        ->maxFiles(10)
                        ->maxSize(5120)
                        ->directory('post-templates')
                        ->columnSpanFull()
                        ->helperText('Tối đa 10 ảnh, mỗi ảnh ≤ 5MB. Ảnh sẽ được đính kèm bài đăng'),

                    Schemas\Components\Grid::make(2)->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Nhập tag + Enter')
                            ->helperText('Phân loại để tìm mẫu nhanh hơn'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Đang hoạt động')
                            ->default(true)
                            ->helperText('Tắt để ẩn mẫu khỏi danh sách chọn'),
                    ]),
                ]),

            Schemas\Components\Section::make('🌱 Seed Comments (Tùy chọn)')
                ->description('Tự động like + comment vào bài mình vừa đăng')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->collapsed()
                ->schema([
                    Forms\Components\Repeater::make('seed_comments')
                        ->label('Comment tự seed')
                        ->schema([
                            Forms\Components\Textarea::make('text')
                                ->label('Nội dung comment')
                                ->placeholder('VD: {spin|Quá hay|Tuyệt vời|Rất bổ ích} 🔥')
                                ->rows(2)
                                ->required(),
                        ])
                        ->addActionLabel('+ Thêm comment seed')
                        ->defaultItems(0)
                        ->columnSpanFull()
                        ->helperText('Extension sẽ tự comment vào bài mình vừa đăng để tạo tương tác ban đầu. Hỗ trợ spin.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên mẫu')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-m-document-text')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->name),

                Tables\Columns\TextColumn::make('category')
                    ->label('Danh mục')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'general' => '📋 Chung',
                        'promotion' => '🏷️ Khuyến mãi',
                        'review' => '⭐ Review',
                        'news' => '📰 Tin tức',
                        'recruitment' => '💼 Tuyển dụng',
                        'event' => '🎉 Sự kiện',
                        'question' => '❓ Hỏi đáp',
                        'sharing' => '💡 Chia sẻ',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'promotion' => 'danger',
                        'review' => 'warning',
                        'recruitment' => 'info',
                        'event' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('content')
                    ->label('Nội dung')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->content)
                    ->color('gray'),

                Tables\Columns\ImageColumn::make('images')
                    ->label('Ảnh')
                    ->circular()
                    ->stacked()
                    ->limit(3),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Đã dùng')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 10 => 'success',
                        $state >= 3 => 'info',
                        default => 'gray',
                    })
                    ->suffix(' lần')
                    ->icon('heroicon-m-arrow-trending-up'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Danh mục')
                    ->options([
                        'general' => '📋 Chung',
                        'promotion' => '🏷️ Khuyến mãi',
                        'review' => '⭐ Review',
                        'news' => '📰 Tin tức',
                        'recruitment' => '💼 Tuyển dụng',
                        'event' => '🎉 Sự kiện',
                        'question' => '❓ Hỏi đáp',
                        'sharing' => '💡 Chia sẻ',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Trạng thái')
                    ->trueLabel('Đang hoạt động')
                    ->falseLabel('Đã tắt'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->icon('heroicon-m-pencil-square'),
                Actions\Action::make('useTemplate')
                    ->label('Dùng ngay')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->url(fn (PostTemplate $record) => route('filament.admin.resources.scheduled-posts.create', [
                        'template_id' => $record->id,
                    ])),
                Actions\Action::make('duplicate')
                    ->label('Nhân bản')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (PostTemplate $record) {
                        $record->replicate()->fill([
                            'name' => $record->name . ' (Copy)',
                            'usage_count' => 0,
                        ])->save();

                        Notification::make()
                            ->title('Đã nhân bản mẫu')
                            ->success()
                            ->send();
                    }),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có mẫu bài đăng nào')
            ->emptyStateDescription('Tạo mẫu nội dung để tái sử dụng khi đăng bài vào nhiều nhóm')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostTemplates::route('/'),
            'create' => Pages\CreatePostTemplate::route('/create'),
            'edit' => Pages\EditPostTemplate::route('/{record}/edit'),
        ];
    }
}
