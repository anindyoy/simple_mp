<?php

use App\Models\Category;
use App\Models\CategoryRequest;
use App\Filament\Resources\CategoryRequests\CategoryRequestResource;
use App\Filament\Resources\CategoryRequests\Pages\ManageCategoryRequests;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = makeUser(isAdmin: true);
    $this->user  = makeUser();
});

// ── Access Control ─────────────────────────────────────────────────────────────

describe('access control', function () {
    it('allows admins to view the resource', function () {
        $this->actingAs($this->admin);

        expect(CategoryRequestResource::canViewAny())->toBeTrue();
        expect(CategoryRequestResource::shouldRegisterNavigation())->toBeTrue();
    });

    it('denies non-admins from viewing the resource', function () {
        $this->actingAs($this->user);

        expect(CategoryRequestResource::canViewAny())->toBeFalse();
    });
});

// ── Navigation Badge ───────────────────────────────────────────────────────────

describe('navigation badge', function () {
    it('shows pending count as badge', function () {
        $this->actingAs($this->admin);

        CategoryRequest::factory()->count(3)->create(['user_id' => $this->user->id]);

        expect(CategoryRequestResource::getNavigationBadge())->toBe('3');
        expect(CategoryRequestResource::getNavigationBadgeColor())->toBe('warning');
    });

    it('returns null badge when no pending requests', function () {
        $this->actingAs($this->admin);

        CategoryRequest::factory()->approved()->create(['user_id' => $this->user->id]);

        expect(CategoryRequestResource::getNavigationBadge())->toBeNull();
    });

    it('excludes approved and rejected from badge count', function () {
        $this->actingAs($this->admin);

        CategoryRequest::factory()->count(2)->create(['user_id' => $this->user->id]);
        CategoryRequest::factory()->approved()->create(['user_id' => $this->user->id]);
        CategoryRequest::factory()->rejected()->create(['user_id' => $this->user->id]);

        expect(CategoryRequestResource::getNavigationBadge())->toBe('2');
    });
});

// ── Table Display ──────────────────────────────────────────────────────────────

describe('table display', function () {
    it('shows all requests in the table', function () {
        $this->actingAs($this->admin);

        $pending  = CategoryRequest::factory()->create(['user_id' => $this->user->id]);
        $approved = CategoryRequest::factory()->approved()->create(['user_id' => $this->user->id]);
        $rejected = CategoryRequest::factory()->rejected()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->assertCanSeeTableRecords([$pending, $approved, $rejected])
            ->assertCountTableRecords(3);
    });
});

// ── Approve Action ─────────────────────────────────────────────────────────────

describe('approve action', function () {
    it('is visible for pending requests', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->assertTableActionVisible('approve', $request);
    });

    it('is hidden for approved requests', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->approved()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->assertTableActionHidden('approve', $request);
    });

    it('is hidden for rejected requests', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->rejected()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->assertTableActionHidden('approve', $request);
    });

    it('creates a new category and marks request as approved', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create([
            'user_id'       => $this->user->id,
            'category_name' => 'Elektronik',
        ]);

        livewire(ManageCategoryRequests::class)
            ->callTableAction('approve', $request, data: [
                'category_name'      => 'Elektronik',
                'supports_condition' => true,
            ])
            ->assertHasNoTableActionErrors();

        $category = Category::where('category_name', 'Elektronik')->first();

        expect($category)->not->toBeNull();
        expect($category->supportsCondition())->toBeTrue();

        expect($request->fresh()->status)->toBe('approved');
        expect($request->fresh()->approved_category_id)->toBe($category->id);
    });

    it('allows admin to rename category on approval', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create([
            'user_id'       => $this->user->id,
            'category_name' => 'Tanaman Hias',
        ]);

        livewire(ManageCategoryRequests::class)
            ->callTableAction('approve', $request, data: [
                'category_name'      => 'Tanaman & Bunga',
                'supports_condition' => false,
            ])
            ->assertHasNoTableActionErrors();

        expect(Category::where('category_name', 'Tanaman & Bunga')->exists())->toBeTrue();
        expect(Category::where('category_name', 'Tanaman Hias')->exists())->toBeFalse();
    });

    it('requires category name on approval', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->callTableAction('approve', $request, data: [
                'category_name' => '',
            ])
            ->assertHasTableActionErrors(['category_name']);
    });
});

// ── Reject Action ──────────────────────────────────────────────────────────────

describe('reject action', function () {
    it('is visible for pending requests', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->assertTableActionVisible('reject', $request);
    });

    it('is hidden for approved requests', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->approved()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->assertTableActionHidden('reject', $request);
    });

    it('is hidden for rejected requests', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->rejected()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->assertTableActionHidden('reject', $request);
    });

    it('marks request as rejected with admin note', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->callTableAction('reject', $request, data: [
                'admin_note' => 'Kategori sudah tersedia',
            ])
            ->assertHasNoTableActionErrors();

        expect($request->fresh()->status)->toBe('rejected');
        expect($request->fresh()->admin_note)->toBe('Kategori sudah tersedia');
    });

    it('marks request as rejected without admin note', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create(['user_id' => $this->user->id]);

        livewire(ManageCategoryRequests::class)
            ->callTableAction('reject', $request, data: ['admin_note' => null])
            ->assertHasNoTableActionErrors();

        expect($request->fresh()->status)->toBe('rejected');
        expect($request->fresh()->admin_note)->toBeNull();
    });

    it('sends database notification to requester on rejection', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create([
            'user_id'       => $this->user->id,
            'category_name' => 'Otomotif',
        ]);

        livewire(ManageCategoryRequests::class)
            ->callTableAction('reject', $request, data: [
                'admin_note' => 'Sudah ada kategori serupa',
            ])
            ->assertHasNoTableActionErrors();

        expect($this->user->notifications()->where('type', 'Filament\Notifications\DatabaseNotification')->count())->toBeGreaterThanOrEqual(1);
    });

    it('does not create a category when rejecting', function () {
        $this->actingAs($this->admin);

        $request = CategoryRequest::factory()->create([
            'user_id'       => $this->user->id,
            'category_name' => 'Kategori Ditolak XYZ',
        ]);

        livewire(ManageCategoryRequests::class)
            ->callTableAction('reject', $request, data: ['admin_note' => null])
            ->assertHasNoTableActionErrors();

        expect(Category::where('category_name', 'Kategori Ditolak XYZ')->exists())->toBeFalse();
    });
});
