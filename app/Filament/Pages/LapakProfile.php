<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
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
    protected static ?string $title = 'Edit Lapak Profile';
    protected string $view = 'filament.pages.lapak-profile';

    public ?ModelLapakProfile $lapak = null;
    public ?array $data = [];

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

        $this->lapak = ModelLapakProfile::where('user_id', $user->id)->firstOrFail();

        abort_unless(
            $user->can('update', $this->lapak),
            403
        );

        $this->form->fill($this->lapak->attributesToArray());
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
                    ! $this->lapak->is_active
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
                        ->maxLength(100),

                    Textarea::make('address_raw')
                        ->label('Alamat')
                        ->required(),

                    TextInput::make('whatsapp_number')
                        ->label('Nomor WhatsApp')
                        ->required(),

                    TextInput::make('telegram_username')
                        ->label('Username Telegram')
                        ->prefix('@'),

                    Repeater::make('external_links')
                        ->label('Link External Toko')
                        ->schema([
                            TextInput::make('label')
                                ->label('Label')
                                ->required()
                                ->maxLength(100),
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
        abort_unless(
            auth()->user()->can('update', $this->lapak),
            403
        );

        $this->lapak->update($this->form->getState());

        Notification::make()
            ->title('Lapak berhasil diperbarui')
            ->success()
            ->send();
    }
}
