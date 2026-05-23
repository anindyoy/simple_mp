<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\LapakProfile as ModelLapakProfile;

class LapakProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Lapak Saya';
    protected string $view = 'filament.pages.lapak-profile';

    public ?ModelLapakProfile $lapak = null;
    public ?array $data = [];

    public function getTitle(): string
    {
        return (is_null($this->lapak) || !$this->lapak->exists)
            ? 'Buat Lapak Profile'
            : 'Edit Lapak Profile';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // ❌ Admin TIDAK melihat halaman ini
        if (! $user || $user->is_admin) {
            return false;
        }

        // ✅ User biasa boleh
        return true;
    }

    public function mount(): void
    {
        $user = Auth::user();

        // Cek apakah user sudah memiliki lapak
        $this->lapak = ModelLapakProfile::where('user_id', $user->id)->first();

        // Jika belum ada, buat instance baru untuk create form
        if (!$this->lapak) {
            $this->lapak = new ModelLapakProfile(['user_id' => $user->id]);
        } else {
            // Jika ada, check permission untuk update
            abort_unless(
                $user->can('update', $this->lapak),
                403
            );

            $this->form->fill($this->lapak->attributesToArray());
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestReactivation')
                ->label('Ajukan Aktivasi Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(
                    fn() =>
                    $this->lapak
                        && $this->lapak->exists
                        && ! $this->lapak->is_active
                )
                ->disabled(
                    fn() =>
                    (bool) $this->lapak->pendingReactivationRequest()
                )
                ->schema([
                    TextInput::make('reason')
                        ->label('Alasan Pengajuan')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data, \App\Services\ProductModerationService $service) {

                    try {

                        $service->requestReactivation(
                            $this->lapak,
                            auth()->user(),
                            $data['reason']
                        );

                        Notification::make()
                            ->title('Pengajuan aktivasi ulang dikirim')
                            ->success()
                            ->send();
                    } catch (\DomainException $e) {

                        Notification::make()
                            ->title($e->getMessage())
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Status Moderasi')
                    ->visible(
                        fn() =>
                        $this->lapak
                            && $this->lapak->exists
                            && ! $this->lapak->is_active
                            && $this->lapak->latestDeactivation()
                    )
                    ->schema([
                        Placeholder::make('status')
                            ->label('Status')
                            ->content('Lapak Anda dinonaktifkan oleh admin.'),

                        Placeholder::make('reason')
                            ->label('Alasan')
                            ->content(
                                fn() =>
                                optional($this->lapak->latestDeactivation())->reason
                            ),

                        Placeholder::make('reactivation_status')
                            ->label('Status Aktivasi Ulang')
                            ->visible(
                                fn() =>
                                $this->lapak->pendingReactivationRequest()
                            )
                            ->content('Pengajuan aktivasi ulang sedang menunggu moderasi.'),
                    ]),

                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->label('Nama Lapak')
                        ->required()
                        ->maxLength(100)
                        ->helperText(function () {
                            if ($this->lapak && $this->lapak->exists) {
                                return '';
                            }

                            $suggestions = $this->getLapakNameSuggestions();
                            return 'Contoh: ' . implode(', ', $suggestions);
                        }),

                    Textarea::make('address_raw')
                        ->label('Alamat')
                        ->required(),

                    TextInput::make('whatsapp_number')
                        ->label('Nomor WhatsApp')
                        ->unique(
                            table: ModelLapakProfile::class,
                            column: 'whatsapp_number',
                            ignoreRecord: true
                        )
                        ->required(),

                    TextInput::make('telegram_username')
                        ->label('Username Telegram')
                        ->unique(
                            table: ModelLapakProfile::class,
                            column: 'telegram_username',
                            ignoreRecord: true
                        )
                        ->prefix('@'),

                    Toggle::make('can_be_delivered')
                        ->label('Bisa Antar')
                        ->helperText('Aktifkan jika lapak Anda menyediakan layanan antar ke pembeli')
                        ->default(false),

                    Repeater::make('external_links')
                        ->label('Link External Toko')
                        ->schema([
                            Select::make('label')
                                ->label('Jenis Link')
                                ->options($this->getExternalLinkLabelOptions())
                                ->required()
                                ->searchable(),
                            TextInput::make('link')
                                ->label('Link')
                                ->required()
                                ->url()
                                ->maxLength(2048),
                        ])
                        ->columns(2)
                        ->default([])
                        ->reorderable(false)
                        ->addActionLabel('Tambah Link'),

                    FileUpload::make('profile_image')
                        ->label('Foto Lapak')
                        ->image()
                        ->disk('public')
                        ->directory('lapak-profiles')
                        ->imagePreviewHeight('150')
                        ->maxSize(2048),
                ]),
            ])
            ->model($this->lapak)
            ->statePath('data');
    }

    public function save(): void
    {
        $user = auth()->user();

        // Jika lapak sudah ada, cek permission untuk update
        if ($this->lapak->exists) {
            abort_unless(
                $user->can('update', $this->lapak),
                403
            );

            $this->lapak->update($this->form->getState());
            $message = 'Lapak berhasil diperbarui';
        } else {
            // Jika lapak belum ada, create baru
            $this->lapak->fill($this->form->getState());
            $this->lapak->save();
            $message = 'Lapak berhasil dibuat';
        }

        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    protected function getFirstName(): string
    {
        return explode(' ', auth()->user()->name)[0];
    }

    protected function getLapakNameSuggestions(): array
    {
        $name = $this->getFirstName();

        return [
            "Toko {$name}",
            "Lapak {$name}",
            "Warung {$name}",
            "{$name} Store",
            "{$name} Mart",
        ];
    }

    protected function getExternalLinkLabelOptions(): array
    {
        $default = ['Website', 'Shopee', 'Tokopedia', 'Tiktok', 'Instagram', 'Facebook'];

        $stored = Setting::getValue('lapak_external_link_labels');
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        $labels = is_array($decoded)
            ? collect($decoded)
                ->map(fn($label): string => trim((string) $label))
                ->filter()
                ->unique()
                ->values()
                ->all()
            : $default;

        if ($labels === []) {
            $labels = $default;
        }

        return collect($labels)
            ->sort(fn(string $left, string $right): int => strcasecmp($left, $right))
            ->mapWithKeys(fn(string $label): array => [$label => $label])
            ->all();
    }
}
