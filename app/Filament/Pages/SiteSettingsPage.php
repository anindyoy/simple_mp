<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;

class SiteSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';

    protected static ?string $slug = 'settings/site';

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.site-settings-page';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $this->form->fill([
            'site_title' => Setting::getValue('site_title', 'Lapak Online Warga'),
            'site_description' => Setting::getValue('site_description', 'Marketplace online untuk warga. Jual beli produk dan jasa lokal dengan mudah.'),
            'site_keywords' => Setting::getValue('site_keywords', 'marketplace, jual beli online, produk lokal, warga, toko online'),
            'site_region' => Setting::getValue('site_region', 'Cimanglid'),
            'weekly_minimum_push_tokens' => Setting::getIntValue('weekly_minimum_push_tokens', 3),
            'initial_push_tokens' => Setting::getIntValue('initial_push_tokens', 10),
            'rules_content' => Setting::getValue('user_rules_content', ''),
            'external_link_labels' => implode("\n", $this->getExternalLinkLabels()),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Site')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('site_title')
                            ->label('Judul Site')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Judul yang tampil di browser tab dan sebagai tagline di halaman utama.'),

                        TextInput::make('site_region')
                            ->label('Nama Wilayah / Kampung')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Nama daerah/kampung untuk identitas site, misal: Cimanglid, Jakarta, Bandung.'),
                    ]),

                Section::make('SEO Site')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
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
                    ]),

                Section::make('Konfigurasi Token Angkat Produk')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('weekly_minimum_push_tokens')
                            ->label('Minimum Token Angkat Produk Mingguan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(3)
                            ->helperText('Nilai minimum token angkat produk user setiap refill mingguan.'),

                        TextInput::make('initial_push_tokens')
                            ->label('Token Angkat Produk Awal User Baru')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(10)
                            ->helperText('Nilai token angkat produk awal saat user mendaftar.'),
                    ]),

                Section::make('Konten Peraturan Pengguna')
                    ->collapsible()
                    ->collapsed()
                    ->description('Konten ini akan ditampilkan pada halaman khusus peraturan pengguna di website publik.')
                    ->schema([
                        RichEditor::make('rules_content')
                            ->label('Isi Peraturan')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'strike',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'undo',
                                'redo',
                                'link',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Label Link External Lapak')
                    ->collapsible()
                    ->collapsed()
                    ->description('Atur daftar label yang bisa dipilih user pada link external lapak. Satu label per baris.')
                    ->schema([
                        Textarea::make('external_link_labels')
                            ->label('Daftar Label')
                            ->required()
                            ->rows(6)
                            ->helperText('Default: Website, Shopee, Tokopedia, Tiktok, Instagram, Facebook'),
                    ]),
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
        Setting::setValue('weekly_minimum_push_tokens', (string) max(0, (int) ($data['weekly_minimum_push_tokens'] ?? 3)));
        Setting::setValue('initial_push_tokens', (string) max(0, (int) ($data['initial_push_tokens'] ?? 10)));
        Setting::setValue('user_rules_content', $data['rules_content'] ?? '');

        $labels = collect(preg_split('/\r\n|\r|\n/', (string) ($data['external_link_labels'] ?? '')))
            ->map(fn(string $label): string => trim($label))
            ->filter()
            ->unique()
            ->values()
            ->all();

        Setting::setValue(
            'lapak_external_link_labels',
            json_encode($labels, JSON_UNESCAPED_UNICODE)
        );

        Notification::make()
            ->title('Pengaturan aplikasi disimpan')
            ->success()
            ->send();
    }

    protected function getExternalLinkLabels(): array
    {
        $default = ['Website', 'Shopee', 'Tokopedia', 'Tiktok', 'Instagram', 'Facebook'];

        $stored = Setting::getValue('lapak_external_link_labels');
        if (blank($stored)) {
            return $default;
        }

        $decoded = json_decode($stored, true);
        if (! is_array($decoded)) {
            return $default;
        }

        $labels = collect($decoded)
            ->map(fn($label): string => trim((string) $label))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $labels !== [] ? $labels : $default;
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
