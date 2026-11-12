<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Ui
{
    public static function navIcon(string $name): HtmlString
    {
        $icons = [
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect>',
            'bag' => '<path d="M6 8h12l-1 12H7L6 8z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2"></path>',
            'hexagon' => '<path d="M12 2 21 7v10l-9 5-9-5V7l9-5z"></path>',
            'tag' => '<path d="M20.59 13.41 12 21.99a2 2 0 0 1-2.83 0l-8.17-8.17a2 2 0 0 1 0-2.83L9.59 2.41A2 2 0 0 1 11 2H19a2 2 0 0 1 2 2v8a2 2 0 0 1-.41 1.41z"></path><circle cx="15" cy="7" r="1.2"></circle>',
            'truck' => '<rect x="1" y="6" width="14" height="10" rx="1"></rect><path d="M15 10h4l3 3v3h-7z"></path><circle cx="6" cy="18" r="2"></circle><circle cx="17" cy="18" r="2"></circle>',
            'file' => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"></path><path d="M14 3v5h5"></path>',
            'search' => '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>',
            'cart' => '<circle cx="9" cy="20" r="1.4"></circle><circle cx="17" cy="20" r="1.4"></circle><path d="M2 3h2l2.4 12.2a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.6L20 7H5.2"></path>',
            'menu' => '<path d="M3 6h18"></path><path d="M3 12h18"></path><path d="M3 18h18"></path>',
        ];
        $path = $icons[$name] ?? '';

        return new HtmlString(
            '<svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'
            . $path . '</svg>'
        );
    }

    public static function categoryBadgeClass(?string $name, ?string $color = null): string
    {
        if ($color !== null && $color !== '') {
            return 'badge-' . $color;
        }
        if ($name === null || $name === '') {
            return 'badge-default';
        }

        $palette = ['badge-blue', 'badge-orange', 'badge-purple', 'badge-yellow', 'badge-teal', 'badge-pink'];

        return $palette[crc32(strtolower($name)) % count($palette)];
    }

    public static function colorSwatchPicker(string $selected = ''): HtmlString
    {
        $colors = [
            'blue' => ['#dbeafe', '#1d4ed8'],
            'orange' => ['#ffe4d5', '#c2410c'],
            'purple' => ['#ede9fe', '#7c3aed'],
            'yellow' => ['#fef3c7', '#b45309'],
            'teal' => ['#ccfbf1', '#0f766e'],
            'pink' => ['#fce7f3', '#be185d'],
        ];

        $html = '<div class="color-swatch-group">';
        foreach ($colors as $key => [$bg, $fg]) {
            $checked = $selected === $key ? 'checked' : '';
            $html .= '<label class="color-swatch" style="--swatch-bg:' . $bg . ';--swatch-fg:' . $fg . ';" title="' . ucfirst($key) . '">'
                . '<input type="radio" name="color" value="' . $key . '" ' . $checked . '></label>';
        }
        $autoChecked = $selected === '' ? 'checked' : '';
        $html .= '<label class="color-swatch color-swatch-auto" title="Auto"><input type="radio" name="color" value="" ' . $autoChecked . '><span>Auto</span></label>';
        $html .= '</div>';

        return new HtmlString($html);
    }
}
