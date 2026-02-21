<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\Models\LapakProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
	protected $model = Report::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		[$reportableType, $reportableId] = $this->resolveReportableTarget();

		$userId = User::query()->inRandomOrder()->value('id')
			?? User::factory()->create()->id;

		return [
			'reportable_type' => $reportableType,
			'reportable_id' => $reportableId,
			'user_id' => $userId,
			'reporter_name' => null,
			'reporter_email' => null,
			'reason' => $this->faker->randomElement([
				'produk_terlarang',
				'konten_tidak_pantas',
                'penipuan',
                'spam'
			]),
			'description' => null,
			'status' => 'pending',
		];
	}

	public function forProduct(Product|int|null $product = null): static
	{
		return $this->state(fn() => [
			'reportable_type' => Product::class,
			'reportable_id' => $this->resolveProductId($product),
		]);
	}

	public function forLapak(LapakProfile|int|null $lapak = null): static
	{
		return $this->state(fn() => [
			'reportable_type' => LapakProfile::class,
			'reportable_id' => $this->resolveLapakId($lapak),
		]);
	}

	/**
	 * @return array{0: class-string, 1: int}
	 */
	protected function resolveReportableTarget(): array
	{
		if ($this->faker->boolean()) {
			$productId = $this->resolveProductId();

			return [Product::class, $productId];
		}

		$lapakId = $this->resolveLapakId();

		return [LapakProfile::class, $lapakId];
	}

	protected function resolveProductId(Product|int|null $product = null): int
	{
		if ($product instanceof Product) {
			return $product->id;
		}

		if (is_int($product)) {
			return $product;
		}

		return Product::query()->inRandomOrder()->value('id')
			?? Product::factory()->create()->id;
	}

	protected function resolveLapakId(LapakProfile|int|null $lapak = null): int
	{
		if ($lapak instanceof LapakProfile) {
			return $lapak->id;
		}

		if (is_int($lapak)) {
			return $lapak;
		}

		return LapakProfile::query()->inRandomOrder()->value('id')
			?? LapakProfile::factory()->create()->id;
	}
}
