<?php

namespace App\Support;

class CategoryIcon
{
    /**
     * Resolve a MaterialCommunityIcons-compatible glyph for a category.
     * Keeps stored icons when present; otherwise infers from name/slug.
     */
    public static function resolve(?string $icon, ?string $name = null, ?string $slug = null): string
    {
        $raw = trim((string) $icon);
        if ($raw !== '') {
            return $raw;
        }

        $hay = strtolower(trim(($slug ?? '').' '.($name ?? '')));

        return match (true) {
            str_contains($hay, 'vegetable'), str_contains($hay, 'veggie'), str_contains($hay, 'tomato'), str_contains($hay, 'pepper'), str_contains($hay, 'onion') => 'carrot',
            str_contains($hay, 'grain'), str_contains($hay, 'cereal'), str_contains($hay, 'rice'), str_contains($hay, 'maize'), str_contains($hay, 'beans'), str_contains($hay, 'garri') => 'barley',
            str_contains($hay, 'tuber'), str_contains($hay, 'yam'), str_contains($hay, 'potato'), str_contains($hay, 'plantain') => 'food',
            str_contains($hay, 'protein'), str_contains($hay, 'meat'), str_contains($hay, 'chicken'), str_contains($hay, 'beef') => 'food-drumstick',
            str_contains($hay, 'fish'), str_contains($hay, 'sea') => 'fish',
            str_contains($hay, 'fruit') => 'fruit-watermelon',
            str_contains($hay, 'beverage'), str_contains($hay, 'drink'), str_contains($hay, 'juice') => 'cup',
            str_contains($hay, 'cook'), str_contains($hay, 'oil'), str_contains($hay, 'spice'), str_contains($hay, 'essential'), str_contains($hay, 'provision') => 'pot-steam',
            str_contains($hay, 'dairy'), str_contains($hay, 'milk'), str_contains($hay, 'egg') => 'cheese',
            str_contains($hay, 'build'), str_contains($hay, 'cement'), str_contains($hay, 'paint'), str_contains($hay, 'block') => 'wall',
            str_contains($hay, 'snack'), str_contains($hay, 'bread') => 'bread-slice',
            default => 'basket-outline',
        };
    }
}
