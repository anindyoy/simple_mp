<?php

use App\Filament\Pages\BuyTokensPage;
use App\Filament\Resources\TokenPurchases\TokenPurchaseResource;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Cache::flush();
    $this->user = makeUser();
});

// ── canAccess ──────────────────────────────────────────────────────────────────

describe('canAccess', function () {
    it('returns true for an authenticated user', function () {
        $this->actingAs($this->user);

        expect(BuyTokensPage::canAccess())->toBeTrue();
    });

    it('returns false for an unauthenticated visitor', function () {
        expect(BuyTokensPage::canAccess())->toBeFalse();
    });
});

// ── mount ──────────────────────────────────────────────────────────────────────

describe('mount', function () {
    it('aborts with 403 when not authenticated', function () {
        expect(fn () => (new BuyTokensPage())->mount())
            ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('defaults tokenPrice to 2000 when setting is not stored', function () {
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->assertSet('tokenPrice', 2000);
    });

    it('loads tokenPrice from settings', function () {
        Setting::setValue('token_price', '5000');
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->assertSet('tokenPrice', 5000);
    });

    it('fills form with default values', function () {
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->assertSet('data.quantity', 1)
            ->assertSet('data.bank_account', null)
            // FileUpload initialises to [] (empty array), not null
            ->assertSet('data.proof_of_payment', []);
    });
});

// ── form ──────────────────────────────────────────────────────────────────────

describe('form', function () {
    it('renders without error', function () {
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->assertSuccessful();
    });

    it('validates quantity, bank_account, and proof_of_payment as required', function () {
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->fillForm([
                'quantity'         => null,
                'bank_account'     => null,
                'proof_of_payment' => null,
            ])
            ->call('submit')
            ->assertHasFormErrors(['quantity', 'bank_account', 'proof_of_payment']);
    });

    it('validates quantity must be numeric', function () {
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->fillForm(['quantity' => 'bukan-angka'])
            ->call('submit')
            ->assertHasFormErrors(['quantity']);
    });

    it('validates quantity must be at least 1', function () {
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->fillForm(['quantity' => 0])
            ->call('submit')
            ->assertHasFormErrors(['quantity']);
    });

    it('validates quantity cannot exceed 10000', function () {
        $this->actingAs($this->user);

        livewire(BuyTokensPage::class)
            ->fillForm(['quantity' => 10001])
            ->call('submit')
            ->assertHasFormErrors(['quantity']);
    });
});

// ── submit ────────────────────────────────────────────────────────────────────

// ── submit helpers ────────────────────────────────────────────────────────────
//
// Filament 5's FileUpload stores state as an array internally and applies its own
// validation closure that expects array values. Passing data through the Livewire
// form lifecycle would require a real TemporaryUploadedFile upload.  Instead, we
// use the same direct-instantiation + form-mock pattern used in SiteSettingsPageTest,
// which bypasses FileUpload entirely while still testing the submit business logic.
//
// redirect() uses Livewire's DataStore keyed by object reference, so we can
// retrieve the stored URL via store($page)->get('redirect') after calling submit().
//
function makeBuyPage(array $data, int $tokenPrice = 2000): BuyTokensPage
{
    $page           = new BuyTokensPage();
    $page->tokenPrice = $tokenPrice;
    $page->data     = $data;
    $page->form     = new class($page) {
        public function __construct(private BuyTokensPage $page) {}
        public function getState(): array { return $this->page->data; }
    };
    return $page;
}

describe('submit', function () {
    it('creates a pending token purchase with correct data', function () {
        Setting::setValue('token_price', '3000');
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'BCA', 'account_number' => '123456', 'account_holder' => 'John Doe'],
        ]));

        $this->actingAs($this->user);

        $page = makeBuyPage(
            ['quantity' => 5, 'bank_account' => '123456', 'proof_of_payment' => 'proof.jpg'],
            tokenPrice: 3000,
        );
        $page->submit();

        $purchase = $this->user->tokenPurchases()->latest()->first();

        expect($purchase)->not->toBeNull();
        expect($purchase->quantity)->toBe(5);
        expect($purchase->total_price)->toBe(15000); // 5 × 3000
        expect($purchase->status)->toBe('pending');
        expect($purchase->bank_account)->toBe('123456');
        expect($purchase->payment_method)->toBe('bank_transfer');
    });

    it('redirects to the token purchase view page after successful submit', function () {
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'BCA', 'account_number' => '123456', 'account_holder' => 'John Doe'],
        ]));

        $this->actingAs($this->user);

        $page = makeBuyPage(
            ['quantity' => 1, 'bank_account' => '123456', 'proof_of_payment' => 'proof.jpg'],
        );
        $page->submit();

        $purchase = $this->user->tokenPurchases()->latest()->first();

        expect($purchase)->not->toBeNull();

        // store($page)->get('redirect') reads the URL that submit() passed to $this->redirect()
        $redirectUrl = \Livewire\store($page)->get('redirect');
        expect($redirectUrl)->toBe(
            TokenPurchaseResource::getUrl('view', ['record' => $purchase])
        );
    });

    it('does not create a token purchase when bank account is not in settings', function () {
        $this->actingAs($this->user);

        // No bank accounts configured: getBankAccounts() returns [], selectedBank is null
        $page = makeBuyPage(
            ['quantity' => 1, 'bank_account' => '123456', 'proof_of_payment' => 'proof.jpg'],
        );
        $page->submit();

        expect($this->user->tokenPurchases()->count())->toBe(0);
    });

    it('uses the tokenPrice loaded during mount for total_price calculation', function () {
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'Mandiri', 'account_number' => '999999', 'account_holder' => 'Admin'],
        ]));

        $this->actingAs($this->user);

        $page = makeBuyPage(
            ['quantity' => 3, 'bank_account' => '999999', 'proof_of_payment' => 'proof.jpg'],
            tokenPrice: 4000,
        );
        $page->submit();

        $purchase = $this->user->tokenPurchases()->latest()->first();

        expect($purchase->total_price)->toBe(12000); // 3 × 4000
    });
});

// ── getTotalAmount ────────────────────────────────────────────────────────────

describe('getTotalAmount', function () {
    it('returns quantity × tokenPrice', function () {
        $this->actingAs($this->user);

        $component = livewire(BuyTokensPage::class)
            ->set('tokenPrice', 3000)
            ->set('data.quantity', 4);

        expect($component->instance()->getTotalAmount())->toBe(12000);
    });

    it('returns at least 1 × tokenPrice when quantity is zero or missing', function () {
        $this->actingAs($this->user);

        $component = livewire(BuyTokensPage::class)
            ->set('tokenPrice', 2000)
            ->set('data.quantity', 0);

        expect($component->instance()->getTotalAmount())->toBe(2000);
    });
});

// ── getSelectedBankAccountNumber ──────────────────────────────────────────────

describe('getSelectedBankAccountNumber', function () {
    it('returns null when no bank account is selected', function () {
        $this->actingAs($this->user);

        $component = livewire(BuyTokensPage::class);

        expect($component->instance()->getSelectedBankAccountNumber())->toBeNull();
    });

    it('returns the selected account number', function () {
        $this->actingAs($this->user);

        $component = livewire(BuyTokensPage::class)
            ->set('data.bank_account', '123456');

        expect($component->instance()->getSelectedBankAccountNumber())->toBe('123456');
    });
});

// ── getSelectedBankName ───────────────────────────────────────────────────────

describe('getSelectedBankName', function () {
    it('returns null when no bank account is selected', function () {
        $this->actingAs($this->user);

        expect(livewire(BuyTokensPage::class)->instance()->getSelectedBankName())->toBeNull();
    });

    it('returns the bank name for the selected account', function () {
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'BCA', 'account_number' => '123456', 'account_holder' => 'John Doe'],
        ]));

        $this->actingAs($this->user);

        $component = livewire(BuyTokensPage::class)
            ->set('data.bank_account', '123456');

        expect($component->instance()->getSelectedBankName())->toBe('BCA');
    });
});

// ── getSelectedBankAccountHolder ──────────────────────────────────────────────

describe('getSelectedBankAccountHolder', function () {
    it('returns null when no bank account is selected', function () {
        $this->actingAs($this->user);

        expect(livewire(BuyTokensPage::class)->instance()->getSelectedBankAccountHolder())->toBeNull();
    });

    it('returns the account holder for the selected account', function () {
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'BCA', 'account_number' => '123456', 'account_holder' => 'John Doe'],
        ]));

        $this->actingAs($this->user);

        $component = livewire(BuyTokensPage::class)
            ->set('data.bank_account', '123456');

        expect($component->instance()->getSelectedBankAccountHolder())->toBe('John Doe');
    });
});

// ── getBankAccounts ───────────────────────────────────────────────────────────

describe('getBankAccounts', function () {
    it('returns empty array when no settings are stored', function () {
        $this->actingAs($this->user);

        $page   = new BuyTokensPage();
        $method = new ReflectionMethod($page, 'getBankAccounts');
        $method->setAccessible(true);

        expect($method->invoke($page))->toBe([]);
    });

    it('filters out incomplete account entries', function () {
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'BCA', 'account_number' => '123456', 'account_holder' => 'A'],
            ['bank_name' => 'Mandiri'], // missing account_number and account_holder
        ]));

        $this->actingAs($this->user);

        $page   = new BuyTokensPage();
        $method = new ReflectionMethod($page, 'getBankAccounts');
        $method->setAccessible(true);

        expect($method->invoke($page))->toHaveCount(1);
    });
});

// ── getBankAccountOptions ─────────────────────────────────────────────────────

describe('getBankAccountOptions', function () {
    it('returns empty array when no bank accounts are configured', function () {
        $this->actingAs($this->user);

        $page   = new BuyTokensPage();
        $method = new ReflectionMethod($page, 'getBankAccountOptions');
        $method->setAccessible(true);

        expect($method->invoke($page))->toBe([]);
    });

    it('uses account_number as key and formats the label correctly', function () {
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'BCA', 'account_number' => '123456', 'account_holder' => 'John Doe'],
        ]));

        $this->actingAs($this->user);

        $page   = new BuyTokensPage();
        $method = new ReflectionMethod($page, 'getBankAccountOptions');
        $method->setAccessible(true);

        $options = $method->invoke($page);

        expect($options)->toHaveKey('123456');
        expect($options['123456'])->toBe('BCA - 123456 a.n. John Doe');
    });
});
