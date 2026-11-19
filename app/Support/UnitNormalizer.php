<?php

namespace App\Support;

class UnitNormalizer
{
    public static function canonical(?string $unit): string
    {
        $u = strtolower(trim((string) $unit));
        if ($u === '') {
            return '';
        }

        // Remove common prefixes like "per ", "1 ", "a "
        $u = preg_replace('/^(per\s+|1\s+|a\s+)/i', '', $u) ?? $u;
        $u = trim(preg_replace('/\s+/', ' ', $u) ?? $u);

        return match ($u) {
            'modu', 'mudu', 'mudus', 'modus' => 'mudu',
            'kg', 'kgs', 'kilo', 'kilos', 'kilogram', 'kilograms' => 'kg',
            'cup', 'cups', 'derica', 'tiya' => $u === 'cups' ? 'cup' : $u,
            'paint', 'paints', 'paint rubber', 'paint bucket', 'custard bucket' => 'paint',
            'bag', 'bags' => 'bag',
            '50kg', '50kg bag', '50 kg bag', 'bag (50kg)' => '50kg bag',
            '25kg', '25kg bag', '25 kg bag', 'bag (25kg)' => '25kg bag',
            '10kg', '10kg bag', '10 kg bag', 'bag (10kg)' => '10kg bag',
            'bundle', 'bundles', 'bunch', 'bunches' => 'bundle',
            'tuber', 'tubers' => 'tuber',
            'crate', 'crates' => 'crate',
            'bottle', 'bottles' => 'bottle',
            'litre', 'litres', 'liter', 'liters', 'l' => 'litre',
            'piece', 'pieces', 'pcs' => 'piece',
            'basket', 'baskets' => 'basket',
            default => $u,
        };
    }

    public static function areCompatible(?string $unitA, ?string $unitB): bool
    {
        $a = self::canonical($unitA);
        $b = self::canonical($unitB);

        if ($a === '' || $b === '') {
            return true;
        }

        return $a === $b;
    }

    public static function displayUnit(string $unit): string
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $unit) ?? $unit);

        return $trimmed !== '' ? $trimmed : 'unit';
    }

    public static function baseProductName(string $name): string
    {
        // Strip trailing unit tag in parentheses if present, e.g. "Beans (cup)" -> "Beans"
        return trim(preg_replace('/\s*\([^)]+\)\s*$/u', '', $name) ?? $name);
    }
}
