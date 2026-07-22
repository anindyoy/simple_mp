<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

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
        return (bool) auth()->user()?->is_admin;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);

        $this->form->fill([
            'site_title' => Setting::getValue('site_title', 'Lapak Warga'),
            'site_description' => Setting::getValue('site_description', 'Marketplace online untuk warga. Jual beli produk dan jasa lokal dengan mudah.'),
            'site_keywords' => Setting::getValue('site_keywords', 'marketplace, jual beli online, produk lokal, warga, toko online'),
            'site_region' => Setting::getValue('site_region', 'Cimanglid'),
            'product_schedule_delay_hours' => Setting::getIntValue('product_schedule_delay_hours', 4),
            'product_view_guard_hours' => Setting::getIntValue('product_view_guard_hours', 6),
            'show_visitor_count' => filter_var(Setting::getValue('show_visitor_count', '1'), FILTER_VALIDATE_BOOLEAN),
            'visitor_count_min_views' => Setting::getIntValue('visitor_count_min_views', 0),
            'daily_minimum_push_tokens' => Setting::getIntValue('daily_minimum_push_tokens', 2),
            'weekly_minimum_push_tokens' => Setting::getIntValue('weekly_minimum_push_tokens', 3),
            'initial_push_tokens' => Setting::getIntValue('initial_push_tokens', 10),
            'token_price' => Setting::getIntValue('token_price', 2000),
            'min_tokens_for_normal_price' => Setting::getIntValue('min_tokens_for_normal_price', 5),
            'token_purchase_whatsapp' => Setting::getValue('token_purchase_whatsapp', ''),
            'token_bank_accounts' => $this->getBankAccounts(),
            'rules_content' => Setting::getValue('user_rules_content', ''),
            'external_link_labels' => implode("\n", $this->getExternalLinkLabels()),
            'whatsapp_agents' => $this->getWhatsappAgents(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make()
                    ->columns(2)
                    ->schema([
                        // Kolom Kiri
                        Section::make('Informasi Site')
                            ->description('Kelola identitas dasar situs seperti judul dan nama wilayah.')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
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

                        // Kolom Kanan
                        Section::make('SEO Site')
                            ->description('Optimasi mesin pencari dengan mengatur deskripsi dan kata kunci situs.')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
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

                        // Kolom Kiri
                        Section::make('Konfigurasi Antrian Produk')
                            ->description('Atur jeda waktu antar produk dan batas hitung klik untuk mencegah manipulasi.')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
                            ->schema([
                                TextInput::make('product_schedule_delay_hours')
                                    ->label('Jeda Antar Produk (jam)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(4)
                                    ->suffix('jam')
                                    ->helperText('Produk kedua dari lapak yang sama baru muncul di halaman utama setelah jeda ini sejak produk sebelumnya ditampilkan.'),

                                TextInput::make('product_view_guard_hours')
                                    ->label('Jeda Hitung Klik Produk (jam)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(6)
                                    ->suffix('jam')
                                    ->helperText('Klik produk dari IP yang sama hanya dihitung sekali dalam rentang jam ini, agar spam reload tidak menaikkan jumlah klik.'),
                            ]),

                        // Kolom Kanan
                        Section::make('Tampilan Halaman Utama')
                            ->description('Atur elemen yang ditampilkan di halaman utama.')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
                            ->schema([
                                Toggle::make('show_visitor_count')
                                    ->label('Tampilkan Jumlah Pengunjung')
                                    ->default(true)
                                    ->helperText('Tampilkan teks jumlah pengunjung 24 jam terakhir di halaman utama, di atas grid produk.'),

                                TextInput::make('visitor_count_min_views')
                                    ->label('Minimum Pengunjung untuk Ditampilkan')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix('pengunjung')
                                    ->helperText('Jumlah pengunjung hanya akan ditampilkan jika mencapai angka minimum ini (0 = selalu tampilkan).'),
                            ]),

                        // Kolom Kiri
                        Section::make('Konfigurasi Token')
                            ->description('Tentukan nilai minimum token harian, mingguan, dan token awal untuk pengguna baru.')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
                            ->columns(2)
                            ->schema([
                                TextInput::make('daily_minimum_push_tokens')
                                    ->label('Minimum Token Harian')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(2)
                                    ->helperText('Nilai minimum token user setiap refill harian.'),

                                TextInput::make('weekly_minimum_push_tokens')
                                    ->label('Minimum Token Mingguan')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(3)
                                    ->helperText('Nilai minimum token user setiap refill mingguan.'),

                                TextInput::make('initial_push_tokens')
                                    ->label('Token Awal User Baru')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(10)
                                    ->helperText('Nilai token awal saat user mendaftar.'),
                            ]),

                        // Kolom Kanan
                        Section::make('Konfigurasi Pembelian Token')
                            ->description('Atur harga token, nomor WhatsApp konfirmasi, dan rekening bank tujuan pembayaran.')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
                            ->columns(2)
                            ->schema([
                                TextInput::make('token_price')
                                    ->label('Harga Per Token')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(2000)
                                    ->suffix('IDR')
                                    ->helperText('Harga standar per token dalam Rupiah.'),

                                TextInput::make('min_tokens_for_normal_price')
                                    ->label('Minimum Token untuk Harga Normal')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(5)
                                    ->helperText('Minimum pembelian token untuk mendapat harga normal (tanpa diskon).'),

                                TextInput::make('token_purchase_whatsapp')
                                    ->label('Nomor WhatsApp Konfirmasi')
                                    ->required()
                                    ->tel()
                                    ->placeholder('+62812345678')
                                    ->helperText('Nomor WhatsApp untuk konfirmasi pembayaran pembelian token.')
                                    ->columnSpanFull(),

                                Repeater::make('token_bank_accounts')
                                    ->label('Rekening Bank Tujuan')
                                    ->helperText('Daftar rekening bank untuk menerima pembayaran pembelian token.')
                                    ->itemLabel(fn(array $state): ?string => filled($state['bank_name'] ?? null) ? (string) $state['bank_name'] : null)
                                    ->schema([
                                        TextInput::make('bank_name')
                                            ->label('Nama Bank')
                                            ->required()
                                            ->placeholder('BCA, Mandiri, BNI, dsb')
                                            ->maxLength(100),

                                        TextInput::make('account_number')
                                            ->label('Nomor Rekening')
                                            ->required()
                                            ->maxLength(50),

                                        TextInput::make('account_holder')
                                            ->label('Nama Pemilik Rekening')
                                            ->required()
                                            ->maxLength(100),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed()
                                    ->columnSpanFull()
                                    ->minItems(1)
                                    ->orderable()
                                    ->reorderable(),
                            ]),

                        // Kolom Kiri
                        Section::make('Konten Peraturan Pengguna')
                            ->description('Kelola halaman peraturan pengguna yang ditampilkan saat pendaftaran.')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
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

                        // Kolom Kanan
                        Section::make('Nomor WhatsApp Admin')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
                            ->description('Atur daftar nomor WhatsApp yang akan ditampilkan di menu "Hubungi Admin" pada sidebar.')
                            ->schema([
                                Repeater::make('whatsapp_agents')
                                    ->label('')
                                    ->itemLabel(fn(array $state): ?string => filled($state['name'] ?? null) ? (string) $state['name'] : 'Agen Baru')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nama / Label')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('WhatsApp Admin'),
                                        Select::make('gender')
                                            ->label('Gender')
                                            ->options([
                                                'pria' => 'Pria',
                                                'wanita' => 'Wanita',
                                            ])
                                            ->native(false)
                                            ->placeholder('Pilih gender'),
                                        TextInput::make('phone')
                                            ->label('Nomor WhatsApp')
                                            ->required()
                                            ->tel()
                                            ->maxLength(20)
                                            ->placeholder('62812345678')
                                            ->helperText('Nomor tanpa tanda +, contoh: 62812345678'),
                                        Toggle::make('active')
                                            ->label('Aktif')
                                            ->default(true),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->minItems(1)
                                    ->addActionLabel('Tambah Nomor WhatsApp')
                                    ->orderable()
                                    ->reorderable(),
                            ]),

                        // Kolom Kanan
                        Section::make('Label Link External Lapak')
                            ->collapsible()
                            ->collapsed()
                            ->columnSpan(1)
                            ->description('Atur daftar label yang bisa dipilih user pada link external lapak. Satu label per baris.')
                            ->schema([
                                Textarea::make('external_link_labels')
                                    ->label('Daftar Label')
                                    ->required()
                                    ->rows(6)
                                    ->helperText('Default: Website, Shopee, Tokopedia, Tiktok, Instagram, Facebook'),
                            ]),
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
        Setting::setValue('product_schedule_delay_hours', (string) max(1, (int) ($data['product_schedule_delay_hours'] ?? 4)));
        Setting::setValue('product_view_guard_hours', (string) max(1, (int) ($data['product_view_guard_hours'] ?? 6)));
        Setting::setValue('show_visitor_count', filter_var($data['show_visitor_count'] ?? true, FILTER_VALIDATE_BOOLEAN) ? '1' : '0');
        Setting::setValue('visitor_count_min_views', (string) max(0, (int) ($data['visitor_count_min_views'] ?? 0)));
        Setting::setValue('daily_minimum_push_tokens', (string) max(0, (int) ($data['daily_minimum_push_tokens'] ?? 2)));
        Setting::setValue('weekly_minimum_push_tokens', (string) max(0, (int) ($data['weekly_minimum_push_tokens'] ?? 3)));
        Setting::setValue('initial_push_tokens', (string) max(0, (int) ($data['initial_push_tokens'] ?? 10)));
        Setting::setValue('token_price', (string) max(0, (int) ($data['token_price'] ?? 2000)));
        Setting::setValue('min_tokens_for_normal_price', (string) max(1, (int) ($data['min_tokens_for_normal_price'] ?? 5)));
        Setting::setValue('token_purchase_whatsapp', $data['token_purchase_whatsapp'] ?? '');
        Setting::setValue('user_rules_content', $data['rules_content'] ?? '');

        // Save bank accounts
        $bankAccounts = collect($data['token_bank_accounts'] ?? [])
            ->filter()
            ->unique('account_number')
            ->values()
            ->all();

        Setting::setValue(
            'token_bank_accounts',
            json_encode($bankAccounts, JSON_UNESCAPED_UNICODE)
        );

        // Sync WhatsApp agents
        $this->syncWhatsappAgents($data['whatsapp_agents'] ?? []);

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

    protected function getWhatsappAgents(): array
    {
        return WhatsappAgent::query()
            ->orderBy('id')
            ->get()
            ->map(fn(WhatsappAgent $agent): array => [
                'name' => $agent->name,
                'gender' => $agent->gender,
                'phone' => $agent->phone,
                'active' => $agent->active,
            ])
            ->values()
            ->all();
    }

    protected function syncWhatsappAgents(array $agents): void
    {
        $submittedPhones = collect($agents)->pluck('phone')->filter()->values()->all();

        // Delete agents that are no longer in the list
        WhatsappAgent::query()
            ->whereNotIn('phone', $submittedPhones)
            ->delete();

        // Upsert submitted agents
        foreach ($agents as $agent) {
            if (blank($agent['phone'] ?? null)) {
                continue;
            }

            $record = WhatsappAgent::query()->updateOrCreate(
                ['phone' => $agent['phone']],
                [
                    'name' => $agent['name'] ?? 'WhatsApp Admin',
                    'active' => filter_var($agent['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'text' => 'Halo, saya ingin bertanya tentang ' . config('app.name') . '.',
                ]
            );

            $record->forceFill([
                'gender' => $agent['gender'] ?? null,
            ])->save();
        }

        // Clear the cache used by the modal
        Cache::forget('whatsapp_active_agents');
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

    protected function getBankAccounts(): array
    {
        $stored = Setting::getValue('token_bank_accounts');
        if (blank($stored)) {
            return [];
        }

        $decoded = json_decode($stored, true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn($account) => is_array($account) && isset($account['bank_name'], $account['account_number'], $account['account_holder']))
            ->values()
            ->all();
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