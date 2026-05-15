<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\Product;
use App\Models\LapakProfile;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\CreateUser;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);

    $this->user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);
});

function actingAsAdmin(): void
{
    test()->actingAs(test()->admin);
}

function dateRangeFilter(int $days = 29): string
{
    return now()->subDays($days)->format('d/m/Y')
        . ' - ' .
        now()->format('d/m/Y');
}

describe('authorization', function () {
    test('admin dapat mengakses halaman users', function () {
        actingAsAdmin();

        $this->get(ListUsers::getUrl())
            ->assertOk();
    });

    test('non admin diarahkan dari halaman users', function () {
        $this->actingAs($this->user);

        $this->get(ListUsers::getUrl())
            ->assertRedirect('/admin/lapak-profile');
    });

    test('admin dapat mengakses halaman create user', function () {
        actingAsAdmin();

        $this->get(CreateUser::getUrl())
            ->assertOk();
    });

    test('admin dapat mengakses halaman edit user', function () {
        actingAsAdmin();

        $this->get(EditUser::getUrl([
            'record' => $this->user,
        ]))
            ->assertOk();
    });
});

describe('table', function () {
    beforeEach(fn() => actingAsAdmin());

    test('table menampilkan data user', function () {
        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([
                $this->admin,
                $this->user,
            ]);
    });

    test('products_count termuat dengan benar', function () {
        $user = User::factory()->create();

        $lapak = LapakProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        Product::factory()
            ->count(3)
            ->create([
                'lapak_id' => $lapak->id,
            ]);

        $record = Livewire::test(ListUsers::class)
            ->instance()
            ->getTableRecords()
            ->firstWhere('id', $user->id);

        expect($record->products_count)
            ->toBe(3);
    });
});

describe('table actions', function () {
    beforeEach(fn() => actingAsAdmin());

    test('admin dapat menambahkan push token', function () {
        $user = User::factory()->create([
            'push_tokens' => 0,
        ]);

        Livewire::test(ListUsers::class)
            ->callTableAction('addPushToken', $user, data: [
                'amount' => 5,
            ]);

        expect($user->fresh()->push_tokens)
            ->toBe(5);
    });

    test('admin dapat mengatur jumlah push token', function () {
        $user = User::factory()->create([
            'push_tokens' => 10,
        ]);

        Livewire::test(ListUsers::class)
            ->callTableAction('setPushToken', $user, data: [
                'amount' => 25,
            ]);

        expect($user->fresh()->push_tokens)
            ->toBe(25);
    });
});

describe('filters', function () {
    beforeEach(fn() => actingAsAdmin());

    test('filter email verified bekerja', function () {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Livewire::test(ListUsers::class)
            ->filterTable('email_verified_at', true)
            ->assertCanSeeTableRecords([$verifiedUser])
            ->assertCanNotSeeTableRecords([$unverifiedUser]);
    });

    it('filter tanggal $column bekerja', function (string $column) {
        $oldUser = User::factory()->create([
            $column => now()->subMonths(3),
        ]);

        $newUser = User::factory()->create([
            $column => now()->subDays(5),
        ]);

        Livewire::test(ListUsers::class)
            ->filterTable($column, [
                $column => dateRangeFilter(),
            ])
            ->assertCanSeeTableRecords([$newUser])
            ->assertCanNotSeeTableRecords([$oldUser]);
    })->with([
        'tanggal daftar' => ['created_at'],
        'tanggal update' => ['updated_at'],
    ]);
});
