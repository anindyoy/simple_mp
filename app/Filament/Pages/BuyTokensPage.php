<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Resources\TokenPurchases\TokenPurchaseResource;

class BuyTokensPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Tambah Token';

    protected static ?string $navigationLabel = 'Tambah Token';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-plus-circle';

    protected static UnitEnum|string|null $navigationGroup = 'Akun';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.buy-tokens-page';

    public ?array $data = [];

    public int $tokenPrice = 2000;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        $this->tokenPrice = Setting::getIntValue('token_price', 2000);

        $this->form->fill([
            'quantity' => 1,
            'bank_account' => null,
            'proof_of_payment' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Form Tambah Token')
                    ->columns(2)
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Jumlah Token')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->default(1)
                            ->live()
                            ->helperText('Harga per token: Rp ' . number_format($this->tokenPrice, 0, ',', '.')),

                        Select::make('bank_account')
                            ->label('Rekening Tujuan')
                            ->options($this->getBankAccountOptions())
                            ->searchable()
                            ->live()
                            ->required(),

                        FileUpload::make('proof_of_payment')
                            ->label('Bukti Transfer')
                            ->image()
                            ->disk('public')
                            ->directory('token-purchases/proof')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif'])
                            ->maxSize(5120)
                            ->required()
                            ->columnSpanFull(),

                        ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $bankAccounts = $this->getBankAccounts();

        $selectedBank = collect($bankAccounts)
            ->first(fn(array $account) => ($account['account_number'] ?? null) === ($data['bank_account'] ?? null));

        if (!$selectedBank) {
            Notification::make()
                ->title('Rekening bank tidak valid')
                ->danger()
                ->send();

            return;
        }

        $quantity = (int) ($data['quantity'] ?? 1);
        $purchase = auth()->user()->tokenPurchases()->create([
            'quantity' => $quantity,
            'total_price' => $quantity * $this->tokenPrice,
            'status' => 'pending',
            'bank_account' => $data['bank_account'],
            'proof_of_payment' => $data['proof_of_payment'] ?? null,
            'payment_method' => 'bank_transfer',
        ]);

        Notification::make()
            ->title('Permintaan pembelian token berhasil dibuat')
            ->success()
            ->send();

        $this->redirect(TokenPurchaseResource::getUrl('view', ['record' => $purchase]));
    }

    protected function getBankAccountOptions(): array
    {
        return collect($this->getBankAccounts())
            ->mapWithKeys(function (array $account): array {
                $number = $account['account_number'] ?? '';
                $label = trim(($account['bank_name'] ?? '-') . ' - ' . $number . ' a.n. ' . ($account['account_holder'] ?? '-'));

                return [$number => $label];
            })
            ->all();
    }

    protected function getBankAccounts(): array
    {
        $stored = Setting::getValue('token_bank_accounts');
        if (blank($stored)) {
            return [];
        }

        $decoded = json_decode($stored, true);
        if (!is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn($account) => is_array($account) && isset($account['bank_name'], $account['account_number'], $account['account_holder']))
            ->values()
            ->all();
    }

    public function getTotalAmount(): int
    {
        return max(1, (int) data_get($this->data, 'quantity', 1)) * $this->tokenPrice;
    }

    public function getSelectedBankAccountNumber(): ?string
    {
        return data_get($this->data, 'bank_account');
    }

    public function getSelectedBankName(): ?string
    {
        return data_get($this->getSelectedBankAccount(), 'bank_name');
    }

    public function getSelectedBankAccountHolder(): ?string
    {
        return data_get($this->getSelectedBankAccount(), 'account_holder');
    }

    protected function getSelectedBankAccount(): ?array
    {
        $selectedNumber = $this->getSelectedBankAccountNumber();

        if (blank($selectedNumber)) {
            return null;
        }

        return collect($this->getBankAccounts())
            ->first(fn(array $account) => ($account['account_number'] ?? null) === $selectedNumber);
    }
}
