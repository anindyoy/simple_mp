<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $category_name
 * @property string|null $reason
 * @property string $status
 * @property string|null $admin_note
 * @property int|null $approved_category_id
 */
class CategoryRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'approved_category_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
