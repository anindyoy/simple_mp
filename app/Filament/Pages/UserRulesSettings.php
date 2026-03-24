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
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Konten Peraturan')
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        Setting::setValue('user_rules_content', $this->form->getState()['rules_content'] ?? '');

        Notification::make()
            ->title('Peraturan pengguna berhasil disimpan')
            ->success()
            ->send();
    }
}
