Buat agar user hanya bisa push produk 6 jam sekali
push produk akan memperbarui tanggal pushed_at product

<?php

namespace App\Filament\Resources\Products\Tables;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['primaryImage', 'lapak', 'category']))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('primaryImage.image_url')
                    ->label('Foto')
                    ->disk('public')
                    ->height(48)
                    ->width(48)
                    ->square()
                    ->defaultImageUrl(url('/images/no-image.png')),

                TextColumn::make('title')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('lapak.name')
                    ->label('Lapak')
                    ->searchable()
                    ->hidden(!auth()->user()->is_admin)
                    ->sortable(),

                TextColumn::make('category.category_name')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                BadgeColumn::make('condition')
                    ->label('Kondisi')
                    ->colors([
                        'success' => 'baru',
                        'warning' => 'seken',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('pushed_at')
                    ->label('Disundul')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(
                fn(Builder $query) => $query->when(
                    !auth()->user()->is_admin,
                    fn($q) => $q->where('lapak_id', auth()->user()->lapak->id)
                )
            )
            ->filtersFormColumns(3)
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'category_name'),

                SelectFilter::make('lapak_id')
                    ->label('Lapak')
                    ->hidden(!auth()->user()->is_admin)
                    ->relationship('lapak', 'name'),

                SelectFilter::make('condition')
                    ->label('Kondisi')
                    ->options([
                        'baru' => 'Baru',
                        'seken' => 'Seken',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                Filter::make('price_range')
                    ->label('Rentang Harga')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min_price')
                            ->numeric()
                            ->label('Harga Min'),
                        \Filament\Forms\Components\TextInput::make('max_price')
                            ->numeric()
                            ->label('Harga Max'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn($q) => $q->where('price', '>=', $data['min_price'])
                            )
                            ->when(
                                $data['max_price'],
                                fn($q) => $q->where('price', '<=', $data['max_price'])
                            );
                    }),

                Filter::make('pushed_at')
                    ->label('Tanggal Sundul')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q) => $q->whereDate('pushed_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($q) => $q->whereDate('pushed_at', '<=', $data['until'])
                            );
                    }),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Products\Pages;
class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}


================================================

bantu atasi error ini

PS C:\Repo\simple_mp> pa db:seed ProductSeeder


   INFO  Seeding database.


   InvalidArgumentException

  Condition wajib diisi untuk kategori ini.

  at app\Models\Product.php:44
     40▕             if (
     41▕                 $product->category?->supportsCondition()
     42▕                 && is_null($product->condition)
     43▕             ) {
  ➜  44▕                 throw new \InvalidArgumentException(
     45▕                     'Condition wajib diisi untuk kategori ini.'
     46▕                 );
     47▕             }
     48▕         });

  1   vendor\laravel\framework\src\Illuminate\Events\Dispatcher.php:488
      App\Models\Product::{closure:App\Models\Product::boot():39}(Object(App\Models\Product))

  2   vendor\laravel\framework\src\Illuminate\Events\Dispatcher.php:315
      Illuminate\Events\Dispatcher::{closure:Illuminate\Events\Dispatcher::makeListener():483}("eloquent.saving: App\Models\Product")

<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Jalankan seeder produk
     */
    public function run(): void
    {
        $categories = Category::all();
        $existingLapaks = LapakProfile::all();

        Product::factory(50)->make()->each(function ($product) use ($categories, $existingLapaks) {
            // Tentukan kategori random
            $product->category_id = $categories->random()->id;

            if ($existingLapaks->isNotEmpty() && rand(0, 2) != 0) {
                $product->lapak_id = $existingLapaks->random()->id;
            } else {
                $product->lapak_id = LapakProfile::factory()->create()->id;
            }

            // Simpan produk ke DB
            $product->save();

            // Buat 1-3 gambar untuk produk ini
            ProductImage::factory(rand(1, 3))->create([
                'product_id' => $product->id,
            ]);
        });
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ambil atau buat kategori
        $category = Category::inRandomOrder()->first()
            ?? Category::factory()->create();

        return [
            'category_id' => $category->id,

            'title' => $title = $this->faker->words(3, true),
            'slug' => Str::slug($title) . '-' . rand(100, 999),

            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->numberBetween(10_000, 2_000_000),

            // 👉 logic kondisi produk
            'condition' => $category->supportsCondition()
                ? $this->faker->randomElement(['baru', 'seken'])
                : null,

            'is_active' => true,
            'pushed_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
        ];
    }
}

<?php

namespace App\Models;

use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Mengubah string pushed_at menjadi objek Carbon (Waktu) secara otomatis
    protected $casts = [
        'pushed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        /**
         * Generate slug saat create
         */
        static::creating(function ($product) {
            $product->slug = Str::slug($product->title) . '-' . rand(1000, 9999);
        });

        /**
         * Validasi condition berdasarkan kategori
         */
        static::saving(function ($product) {
            if (
                $product->category?->supportsCondition()
                && is_null($product->condition)
            ) {
                throw new \InvalidArgumentException(
                    'Condition wajib diisi untuk kategori ini.'
                );
            }
        });

        /**
         * Pastikan hanya 1 gambar primary per produk
         * Dieksekusi SETELAH product tersimpan
         */
        static::saved(function ($product) {
            // Ambil 1 gambar primary (yang paling akhir diupdate)
            $primaryImage = $product->images()
                ->where('is_primary', true)
                ->orderByDesc('updated_at')
                ->first();

            if ($primaryImage) {
                // Set gambar lain menjadi non-primary
                $product->images()
                    ->where('id', '!=', $primaryImage->id)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function lapak(): BelongsTo
    {
        return $this->belongsTo(LapakProfile::class, 'lapak_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }


    // Helper untuk cek apakah sudah boleh push (6 jam)
    public function canBePushed(): bool
    {
        return $this->pushed_at->diffInHours(now()) >= 6;
    }

    public function hasCondition(): bool
    {
        return !is_null($this->condition)
            && $this->category?->supportsCondition();
    }

    public function conditionLabel(): ?string
    {
        return match ($this->condition) {
            'baru' => 'Baru',
            'seken' => 'Bekas',
            default => null,
        };
    }
}

<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['category_name'];
    public $timestamps = false;

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function supportsCondition(): bool
    {
        // contoh kategori barang fisik
        return in_array($this->id, [
            2, // Fashion
            3, // Elektronik
            4 // Otomotif
        ]);
    }
}
