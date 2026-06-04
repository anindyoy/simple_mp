<?php

namespace App\Filament\Resources\CategoryRequests\Tables;

use App\Models\Category;
use App\Models\CategoryRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pengaju')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category_name')
                    ->label('Nama Kategori Diajukan')
                    ->searchable(),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(60)
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending'  => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    }),

                TextColumn::make('admin_note')
                    ->label('Catatan Admin')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->modalHeading('Setujui Ajuan Kategori')
                    ->visible(fn(CategoryRequest $record) => $record->isPending())
                    ->fillForm(fn(CategoryRequest $record): array => [
                        'category_name' => $record->category_name,
                        'supports_condition' => false,
                    ])
                    ->form([
                        TextInput::make('category_name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(50),
                        Toggle::make('supports_condition')
                            ->label('Mendukung Kondisi Produk')
                            ->helperText('Aktifkan jika produk dalam kategori ini memiliki kondisi (baru/bekas)'),
                    ])
                    ->action(function (array $data, CategoryRequest $record): void {
                        $category = Category::create([
                            'category_name' => $data['category_name'],
                            'supports_condition' => $data['supports_condition'] ?? false,
                        ]);

                        $record->update([
                            'status' => 'approved',
                            'approved_category_id' => $category->id,
                        ]);

                        Notification::make()
                            ->title('Kategori berhasil dibuat')
                            ->success()
                            ->send();

                        if ($requester = User::find($record->user_id)) {
                            Notification::make()
                                ->title('Ajuan kategori disetujui')
                                ->body("Kategori \"{$data['category_name']}\" telah ditambahkan dan siap digunakan.")
                                ->success()
                                ->sendToDatabase($requester);
                        }
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->modalHeading('Tolak Ajuan Kategori')
                    ->visible(fn(CategoryRequest $record) => $record->isPending())
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Catatan Penolakan')
                            ->placeholder('Tuliskan alasan penolakan (opsional)')
                            ->rows(3),
                    ])
                    ->action(function (array $data, CategoryRequest $record): void {
                        $adminNote = $data['admin_note'] ?? null;

                        $record->update([
                            'status'     => 'rejected',
                            'admin_note' => $adminNote,
                        ]);

                        Notification::make()
                            ->title('Ajuan telah ditolak')
                            ->success()
                            ->send();

                        if ($requester = User::find($record->user_id)) {
                            $body = $adminNote
                                ? "Ajuan kategori \"{$record->category_name}\" ditolak. Catatan admin: {$adminNote}"
                                : "Ajuan kategori \"{$record->category_name}\" ditolak.";

                            Notification::make()
                                ->title('Ajuan kategori ditolak')
                                ->body($body)
                                ->danger()
                                ->sendToDatabase($requester);
                        }
                    }),
            ]);
    }
}
