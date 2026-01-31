Buat agar user hanya bisa push produk 6 jam sekali
push produk akan memperbarui tanggal pushed_at product

<?php

namespace App\Filament\Resources\Products\Tables;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['primaryImage', 'lapak', 'category']))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('primaryImage.image_url')
                    ->label('Foto')
                    ->disk('public')
                    ->height(48)
                    ->width(48)
                    ->square()
                    ->defaultImageUrl(url('/images/no-image.png')),

                TextColumn::make('title')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('lapak.name')
                    ->label('Lapak')
                    ->searchable()
                    ->hidden(!auth()->user()->is_admin)
                    ->sortable(),

                TextColumn::make('category.category_name')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                BadgeColumn::make('condition')
                    ->label('Kondisi')
                    ->colors([
                        'success' => 'baru',
                        'warning' => 'seken',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('pushed_at')
                    ->label('Disundul')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(
                fn(Builder $query) => $query->when(
                    !auth()->user()->is_admin,
                    fn($q) => $q->where('lapak_id', auth()->user()->lapak->id)
                )
            )
            ->filtersFormColumns(3)
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'category_name'),

                SelectFilter::make('lapak_id')
                    ->label('Lapak')
                    ->hidden(!auth()->user()->is_admin)
                    ->relationship('lapak', 'name'),

                SelectFilter::make('condition')
                    ->label('Kondisi')
                    ->options([
                        'baru' => 'Baru',
                        'seken' => 'Seken',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                Filter::make('price_range')
                    ->label('Rentang Harga')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min_price')
                            ->numeric()
                            ->label('Harga Min'),
                        \Filament\Forms\Components\TextInput::make('max_price')
                            ->numeric()
                            ->label('Harga Max'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn($q) => $q->where('price', '>=', $data['min_price'])
                            )
                            ->when(
                                $data['max_price'],
                                fn($q) => $q->where('price', '<=', $data['max_price'])
                            );
                    }),

                Filter::make('pushed_at')
                    ->label('Tanggal Sundul')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q) => $q->whereDate('pushed_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($q) => $q->whereDate('pushed_at', '<=', $data['until'])
                            );
                    }),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Products\Pages;
class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}


================================================

<?
scripts.blade
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('countdownPush', (targetTime) => ({
            label: '',
            timer: null,

            start() {
                this.tick()
                this.timer = setInterval(() => this.tick(), 1000)
            },

            tick() {
                const now = Date.now()
                const diff = targetTime - now

                if (diff <= 0) {
                    this.label = 'Push dimulai'
                    clearInterval(this.timer)
                    return
                }

                const h = Math.floor(diff / 1000 / 60 / 60)
                const m = Math.floor((diff / 1000 / 60) % 60)
                const s = Math.floor((diff / 1000) % 60)

                this.label = `Push dalam ${h}j ${m}m ${s}d`
            },
        }))
    })
</script>

boot pada AppServiceProvider
public function boot(): void
{
    FilamentView::registerRenderHook(
        PanelsRenderHook::HEAD_END,
        fn() => view('filament.topbar.scripts')->render()
    );

    FilamentView::registerRenderHook(
        PanelsRenderHook::GLOBAL_SEARCH_AFTER,
        function (): string {
            $user = Auth::user();

            $html = view('filament.topbar.home-button')->render();

            if ($user && ! $user->is_admin) {
                $pushAt = Carbon::today()
                    ->setTime(21, 0)
                    ->timestamp * 1000;

                $html .= view('filament.topbar.push-countdown', [
                    'pushAt' => $pushAt,
                ])->render();
            }

            return $html;
        }
    );
}

push-countdown.blade:
<div
    x-data="countdownPush({{ $pushAt }})"
    x-init="start()"
    class="fi-topbar-item flex items-center gap-2 px-3 py-1 rounded-lg
           bg-warning-50 text-warning-700 text-xs font-semibold"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 6v6l4 2" />
    </svg>

    <span x-text="label"></span>
</div>
