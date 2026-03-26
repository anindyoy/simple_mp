<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class SiteSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Site';

    protected static ?string $title = 'Pengaturan Site';

    protected static ?string $slug = 'settings/site';

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.site-settings-page';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function mount(): void
    {
        $this->form->fill([
            'site_title' => Setting::getValue('site_title', 'Lapak Online Warga'),
            'site_description' => Setting::getValue('site_description', 'Marketplace online untuk warga. Jual beli produk dan jasa lokal dengan mudah.'),
            'site_keywords' => Setting::getValue('site_keywords', 'marketplace, jual beli online, produk lokal, warga, toko online'),
            'site_region' => Setting::getValue('site_region', 'Cimanglid'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('site_title')
                    ->label('Judul Site')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Judul yang tampil di browser tab dan sebagai tagline di halaman utama.'),

                Textarea::make('site_description')
                    ->label('Deskripsi Site')
                    ->required()
                    ->rows(4)
                    ->maxLength(500)
                    ->helperText('Deskripsi singkat site untuk SEO, maks 160 karakter ideal.'),

                Textarea::make('site_keywords')
                    ->label('Keywords Site')
                    ->required()
                    ->rows(3)
                    ->maxLength(500)
                    ->helperText('Kata kunci utama, pisahkan dengan koma.'),

                TextInput::make('site_region')
                    ->label('Nama Wilayah / Kampung')
                    ->required()
                    ->maxLength(100)
                    ->helperText('Nama daerah/kampung untuk identitas site, misal: Cimanglid, Jakarta, Bandung.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::setValue('site_title', $data['site_title'] ?? '');
        Setting::setValue('site_description', $data['site_description'] ?? '');
        Setting::setValue('site_keywords', $data['site_keywords'] ?? '');
        Setting::setValue('site_region', $data['site_region'] ?? '');

        Notification::make()
            ->title('Pengaturan Site disimpan')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Simpan')
                ->submit('save'),
        ];
    }
}
