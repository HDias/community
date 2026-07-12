<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait GeneratesUniqueSlugs
{
    /**
     * Generate a unique slug from the given name.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Boot the trait — auto-generate slug on creating.
     */
    protected static function bootGeneratesUniqueSlugs(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->name);
            }
        });
    }
}
