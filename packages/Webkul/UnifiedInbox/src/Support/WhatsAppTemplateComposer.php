<?php

namespace Webkul\UnifiedInbox\Support;

class WhatsAppTemplateComposer
{
    /**
     * Text placeholders in header and body, in display order.
     * A non-text header is returned as a media slot so the caller can refuse it.
     *
     * @param  array<int, mixed>  $components
     * @return array<int, array{component: string, index: int, media?: string}>
     */
    public static function slots(array $components): array
    {
        $slots = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $type = strtolower((string) ($component['type'] ?? ''));

            if (! in_array($type, ['header', 'body'], true)) {
                continue;
            }

            $format = strtoupper((string) ($component['format'] ?? 'TEXT'));

            if ($type === 'header' && $format !== 'TEXT') {
                $slots[] = [
                    'component' => 'header',
                    'index' => 0,
                    'media' => strtolower($format),
                ];

                continue;
            }

            if (preg_match_all('/\{\{(\d+)\}\}/', (string) ($component['text'] ?? ''), $matches)) {
                foreach ($matches[1] as $index) {
                    $slots[] = [
                        'component' => $type,
                        'index' => (int) $index,
                    ];
                }
            }
        }

        return $slots;
    }

    public static function requiresMedia(array $components): bool
    {
        foreach (self::slots($components) as $slot) {
            if (! empty($slot['media'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{component: string, index: int}>  $slots
     * @param  array<int, string>  $values
     * @return array<int, array{type: string, parameters: array<int, array{type: string, text: string}>}>
     */
    public static function components(array $slots, array $values): array
    {
        $grouped = [];

        foreach ($slots as $position => $slot) {
            if (! empty($slot['media'])) {
                continue;
            }

            $grouped[$slot['component']][] = [
                'type' => 'text',
                'text' => trim((string) ($values[$position] ?? '')),
            ];
        }

        $payload = [];

        foreach (['header', 'body'] as $type) {
            if (! empty($grouped[$type])) {
                $payload[] = [
                    'type' => $type,
                    'parameters' => $grouped[$type],
                ];
            }
        }

        return $payload;
    }

    public static function phone(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    public static function preview(string $name, array $values): string
    {
        $filled = array_values(array_filter(array_map('trim', $values), fn ($value) => $value !== ''));
        $suffix = $filled === [] ? '' : ': '.implode(', ', $filled);

        return 'Template '.$name.$suffix;
    }
}
