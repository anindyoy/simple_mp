<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    protected const DEFAULT_EXTERNAL_LABELS = [
        'Website',
        'Shopee',
        'Tokopedia',
        'Tiktok',
        'Instagram',
        'Facebook',
    ];

    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'lapak_external_link_labels',
            'value' => json_encode(self::DEFAULT_EXTERNAL_LABELS, JSON_UNESCAPED_UNICODE),
        ];
    }

    public function externalLinkLabels(array $labels = self::DEFAULT_EXTERNAL_LABELS): static
    {
        return $this->state(fn(): array => [
            'key' => 'lapak_external_link_labels',
            'value' => json_encode(array_values($labels), JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function userRulesContent(?string $content = null): static
    {
        $rulesContent = $content ?? $this->readRulesContentFromDefaultFile();

        return $this->state(fn(): array => [
            'key' => 'user_rules_content',
            'value' => $rulesContent,
        ]);
    }

    protected function readRulesContentFromDefaultFile(): string
    {
        $path = base_path('peraturan-pengguna.md');

        if (! file_exists($path)) {
            return '';
        }

        $content = file_get_contents($path);

        return is_string($content) ? $content : '';
    }
}
