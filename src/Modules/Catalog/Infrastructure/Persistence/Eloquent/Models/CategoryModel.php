<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models;

use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EloquentModel layer (deptrac.yaml): read-only outside the write path.
 *
 * Writes go through commands -- see CreateCategory::handleRecordCreation() (Presentation/Filament).
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $ref reference to the original uuid in 1C
 * @property int|null $code reference to the original code in 1C
 * @property bool $is_active
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property array<array-key, mixed>|null $description
 * @property array<array-key, mixed>|null $meta
 * @property array<array-key, mixed>|null $image
 * @property string|null $comment
 * @property bool $is_top
 * @property int $menu_columns_count
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, CategoryModel> $children
 * @property-read int|null $children_count
 * @property-read CategoryModel|null $parent
 * @method static Builder<static>|CategoryModel newModelQuery()
 * @method static Builder<static>|CategoryModel newQuery()
 * @method static Builder<static>|CategoryModel query()
 * @method static Builder<static>|CategoryModel whereCode($value)
 * @method static Builder<static>|CategoryModel whereComment($value)
 * @method static Builder<static>|CategoryModel whereCreatedAt($value)
 * @method static Builder<static>|CategoryModel whereDescription($value)
 * @method static Builder<static>|CategoryModel whereId($value)
 * @method static Builder<static>|CategoryModel whereImage($value)
 * @method static Builder<static>|CategoryModel whereIsActive($value)
 * @method static Builder<static>|CategoryModel whereIsTop($value)
 * @method static Builder<static>|CategoryModel whereMenuColumnsCount($value)
 * @method static Builder<static>|CategoryModel whereMeta($value)
 * @method static Builder<static>|CategoryModel whereName($value)
 * @method static Builder<static>|CategoryModel whereParentId($value)
 * @method static Builder<static>|CategoryModel whereRef($value)
 * @method static Builder<static>|CategoryModel whereSlug($value)
 * @method static Builder<static>|CategoryModel whereSortOrder($value)
 * @method static Builder<static>|CategoryModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
final class CategoryModel extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'parent_id',
        'ref',
        'code',
        'is_top',
        'menu_columns_count',
        'sort_order',
        'is_active',
        'name',
        'description',
        'meta',
        'slug',
        'image',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    protected function casts(): array
    {
        return [
            'is_top'      => 'boolean',
            'is_active'   => 'boolean',
            'name'        => 'array',
            'description' => 'array',
            'slug'        => 'array',
            'meta'        => 'array',
            'image'       => 'array',
            'created_at'  => 'immutable_datetime',
            'updated_at'  => 'immutable_datetime',
        ];
    }
}
