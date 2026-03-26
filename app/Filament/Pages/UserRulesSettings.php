<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;

class UserRulesSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Peraturan Pengguna';
    protected static ?string $title = 'Peraturan Pengguna';
    protected static ?string $slug = 'settings/peraturan-pengguna';
    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';
    protected string $view = 'filament.pages.user-rules-settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function mount(): void
    {
        $this->form->fill([
            'rules_content' => Setting::getValue('user_rules_content', ''),
            'external_link_labels' => implode("\n", $this->getExternalLinkLabels()),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
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
        $state = $this->form->getState();

        Setting::setValue('user_rules_content', $state['rules_content'] ?? '');

        $labels = collect(preg_split('/\r\n|\r|\n/', (string) ($state['external_link_labels'] ?? '')))
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
            ->title('Pengaturan berhasil disimpan')
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
}
