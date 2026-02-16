<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use App\Models\LapakProfile as ModelLapakProfile;
use Filament\Forms\Components\Card;

class LapakProfile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Lapak Saya';
    protected static ?string $title = 'Edit Lapak Profile';
    protected string $view = 'filament.pages.lapak-profile';

    public ?ModelLapakProfile $lapak = null;

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

        $this->form->fill($this->lapak->toArray());
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nama Lapak')
                ->required()
                ->maxLength(100),

            FileUpload::make('profile_image')
                ->label('Foto Lapak')
                ->image()
                ->directory('lapak-profiles')
                ->imagePreviewHeight('150')
                ->maxSize(2048),

            Grid::make(2)->schema([
                TextInput::make('whatsapp_number')
                    ->label('Nomor WhatsApp')
                    ->required(),

                TextInput::make('telegram_username')
                    ->label('Username Telegram')
                    ->prefix('@'),
            ]),

            Textarea::make('address_raw')
                ->label('Alamat')
                ->required()
                ->rows(3),

            // Grid::make(2)->schema([
            //     TextInput::make('latitude')
            //         ->numeric(),

            //     TextInput::make('longitude')
            //         ->numeric(),
            // ]),
        ];
    }

    protected function getFormModel(): ModelLapakProfile
    {
        return $this->lapak;
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
