<?php

namespace App\Support;

class CategoryIcon
{
    /**
     * Resolve a MaterialCommunityIcons-compatible glyph for a category.
     * Keeps stored icons when valid; otherwise infers from name/slug.
     */
    public static function resolve(?string $icon, ?string $name = null, ?string $slug = null): string
    {
        $raw = trim((string) $icon);
        if ($raw !== '' && !str_contains($raw, '?') && preg_match('/^[a-z0-9-]+$/i', $raw)) {
            return $raw;
        }

        $hay = strtolower(trim(($slug ?? '').' '.($name ?? '')));

        return match (true) {
            str_contains($hay, 'pepper'), str_contains($hay, 'chili'), str_contains($hay, 'rodo'), str_contains($hay, 'shombo'), str_contains($hay, 'tatashe') => 'chili-mild',
            str_contains($hay, 'vegetable'), str_contains($hay, 'veggie'), str_contains($hay, 'tomato'), str_contains($hay, 'onion'), str_contains($hay, 'spinach'), str_contains($hay, 'ugwu'), str_contains($hay, 'cabbage'), str_contains($hay, 'carrot'), str_contains($hay, 'cucumber') => 'carrot',
            str_contains($hay, 'rice'), str_contains($hay, 'ofada') => 'rice',
            str_contains($hay, 'corn'), str_contains($hay, 'maize') => 'corn',
            str_contains($hay, 'grain'), str_contains($hay, 'cereal'), str_contains($hay, 'wheat'), str_contains($hay, 'sorghum'), str_contains($hay, 'millet'), str_contains($hay, 'barley') => 'barley',
            str_contains($hay, 'bean'), str_contains($hay, 'oloyin'), str_contains($hay, 'cowpea') => 'seed',
            str_contains($hay, 'garri'), str_contains($hay, 'gari'), str_contains($hay, 'cassava'), str_contains($hay, 'flour'), str_contains($hay, 'semovita'), str_contains($hay, 'semo'), str_contains($hay, 'elubo') => 'sack',
            str_contains($hay, 'tuber'), str_contains($hay, 'yam'), str_contains($hay, 'potato'), str_contains($hay, 'cocoyam') => 'food-croissant',
            str_contains($hay, 'plantain'), str_contains($hay, 'dodo') => 'food-apple',
            str_contains($hay, 'chicken'), str_contains($hay, 'fowl'), str_contains($hay, 'poultry'), str_contains($hay, 'turkey') => 'food-drumstick',
            str_contains($hay, 'protein'), str_contains($hay, 'meat'), str_contains($hay, 'beef'), str_contains($hay, 'goat'), str_contains($hay, 'ram'), str_contains($hay, 'cow'), str_contains($hay, 'suya'), str_contains($hay, 'kpomo') => 'food-steak',
            str_contains($hay, 'fish'), str_contains($hay, 'sea'), str_contains($hay, 'titus'), str_contains($hay, 'croaker'), str_contains($hay, 'crayfish'), str_contains($hay, 'prawn') => 'fish',
            str_contains($hay, 'fruit'), str_contains($hay, 'watermelon'), str_contains($hay, 'banana'), str_contains($hay, 'mango'), str_contains($hay, 'orange'), str_contains($hay, 'pineapple') => 'fruit-watermelon',
            str_contains($hay, 'water'), str_contains($hay, 'pure water') => 'water',
            str_contains($hay, 'coffee'), str_contains($hay, 'tea'), str_contains($hay, 'milo'), str_contains($hay, 'beverage'), str_contains($hay, 'drink'), str_contains($hay, 'juice'), str_contains($hay, 'soda') => 'cup',
            str_contains($hay, 'palm oil'), str_contains($hay, 'oil'), str_contains($hay, 'cooking'), str_contains($hay, 'spice'), str_contains($hay, 'seasoning'), str_contains($hay, 'salt'), str_contains($hay, 'sugar'), str_contains($hay, 'maggi'), str_contains($hay, 'essential'), str_contains($hay, 'provision') => 'pot-steam',
            str_contains($hay, 'egg'), str_contains($hay, 'dairy'), str_contains($hay, 'milk'), str_contains($hay, 'cheese') => 'egg-easter',
            str_contains($hay, 'snack'), str_contains($hay, 'bread'), str_contains($hay, 'biscuit'), str_contains($hay, 'bakery') => 'bread-slice',
            str_contains($hay, 'frozen'), str_contains($hay, 'ice') => 'snowflake',
            str_contains($hay, 'build'), str_contains($hay, 'cement'), str_contains($hay, 'paint'), str_contains($hay, 'block'), str_contains($hay, 'hardware') => 'wall',
            str_contains($hay, 'detergent'), str_contains($hay, 'soap'), str_contains($hay, 'cleaning'), str_contains($hay, 'toiletries') => 'spray-bottle',
            str_contains($hay, 'baby'), str_contains($hay, 'diaper') => 'baby-carriage',
            str_contains($hay, 'clothing'), str_contains($hay, 'fabric'), str_contains($hay, 'wear'), str_contains($hay, 'cloth') => 'tshirt-crew',
            str_contains($hay, 'electric'), str_contains($hay, 'phone'), str_contains($hay, 'gadget') => 'cellphone',
            str_contains($hay, 'health'), str_contains($hay, 'drug'), str_contains($hay, 'medicine') => 'pill',
            default => 'basket-outline',
        };
    }
}
