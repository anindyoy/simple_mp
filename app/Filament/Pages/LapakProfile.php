<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\LapakProfile as ModelLapakProfile;

class LapakProfile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
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

                    FileUpload::make('profile_image')
                        ->label('Foto Lapak')
                        ->image()
                        ->disk('public')
                        ->directory('lapak-profiles')
                        ->imagePreviewHeight('150')
                        ->maxSize(2048),
                ]),

                // Grid::make(2)->schema([
                //     TextInput::make('latitude')
                //         ->numeric(),

                //     TextInput::make('longitude')
                //         ->numeric(),
                // ]),
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
