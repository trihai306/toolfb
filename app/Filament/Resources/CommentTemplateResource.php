<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentTemplateResource\Pages;
use App\Models\CommentTemplate;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CommentTemplateResource extends Resource
{
    protected static ?string $model = CommentTemplate::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'Mẫu comment';
    protected static ?string $modelLabel = 'Mẫu Comment';
    protected static string | \UnitEnum | null $navigationGroup = '💬 Comment dạo';
    protected static ?int $navigationSort = 2;

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
                ->description('Tạo mẫu comment để sử dụng lại trong nhiều chiến dịch')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Tên mẫu')
                        ->placeholder('VD: Review tích cực - Mỹ phẩm')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-tag'),
                    Forms\Components\Textarea::make('content')
                        ->label('Nội dung')
                        ->placeholder("VD: {spin|Sản phẩm tuyệt vời|Rất hài lòng|Mình rất thích} 👍\nChất lượng {spin|quá tốt|đỉnh cao|xuất sắc}!")
                        ->helperText('💡 Dùng {spin|text1|text2|text3} để xoay vòng nội dung. Mỗi lần comment sẽ chọn ngẫu nhiên 1 phiên bản.')
                        ->rows(6)
                        ->required(),
                    Schemas\Components\Grid::make(2)->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Hình ảnh')
                            ->multiple()
                            ->image()
                            ->directory('comment-templates')
                            ->helperText('Upload nhiều ảnh để xoay vòng'),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Nhập tag + Enter')
                            ->helperText('Tags giúp phân loại và tìm kiếm mẫu nhanh hơn'),
                    ]),
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
                Tables\Columns\TextColumn::make('content')
                    ->label('Nội dung')
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->content)
                    ->color('gray'),
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
                    ->sortable()
                    ->description(fn ($record) => $record->updated_at?->format('d/m/Y')),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Actions\EditAction::make()
                    ->icon('heroicon-m-pencil-square'),
                Actions\Action::make('duplicate')
                    ->label('Nhân bản')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (CommentTemplate $record) {
                        $record->replicate()->fill([
                            'name' => $record->name . ' (Copy)',
                            'usage_count' => 0,
                        ])->save();

                        \Filament\Notifications\Notification::make()
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
            ->emptyStateHeading('Chưa có mẫu comment nào')
            ->emptyStateDescription('Tạo mẫu comment để sử dụng nhanh trong các chiến dịch')
            ->emptyStateIcon('heroicon-o-document-duplicate');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommentTemplates::route('/'),
            'create' => Pages\CreateCommentTemplate::route('/create'),
            'edit' => Pages\EditCommentTemplate::route('/{record}/edit'),
        ];
    }
}
