<?php

namespace Webkul\Automation\Helpers;

class EmailTemplatePlaceholderAutocomplete
{
    /**
     * Build a catalog of entities and attributes from placeholder groups.
     *
     * @param  array<int, array{text: string, menu: array<int, array{text: string, value: string}>}>  $placeholders
     * @return array<int, array{type: string, label: string, attributes: array<int, array{code: string, label: string, value: string}>}>
     */
    public function catalog(array $placeholders): array
    {
        $catalog = [];

        foreach ($placeholders as $group) {
            $type = null;
            $attributes = [];

            foreach ($group['menu'] ?? [] as $item) {
                if (! preg_match('/\{\%\s*([^.]+)\.([^%]+?)\s*\%\}/', (string) ($item['value'] ?? ''), $matches)) {
                    continue;
                }

                $type = $matches[1];

                $attributes[] = [
                    'code' => trim($matches[2]),
                    'label' => (string) ($item['text'] ?? $matches[2]),
                    'value' => (string) $item['value'],
                ];
            }

            if ($type === null) {
                continue;
            }

            $catalog[] = [
                'type' => $type,
                'label' => (string) ($group['text'] ?? $type),
                'attributes' => $attributes,
            ];
        }

        return $catalog;
    }

    /**
     * Suggest completions for the text typed after `{%`.
     *
     * Examples:
     * - `l` → leads entity (`{%leads.`)
     * - `leads.t` → `{%leads.title%}` (and other t* attributes)
     *
     * @param  array<int, array{type: string, label: string, attributes: array<int, array{code: string, label: string, value: string}>}>  $catalog
     * @return array<int, array{label: string, insert: string, kind: string}>
     */
    public function suggest(string $pattern, array $catalog): array
    {
        $pattern = strtolower(ltrim($pattern));

        if (! str_contains($pattern, '.')) {
            return $this->suggestEntities($pattern, $catalog);
        }

        return $this->suggestAttributes($pattern, $catalog);
    }

    /**
     * @param  array<int, array{type: string, label: string, attributes: array<int, array{code: string, label: string, value: string}>}>  $catalog
     * @return array<int, array{label: string, insert: string, kind: string}>
     */
    protected function suggestEntities(string $pattern, array $catalog): array
    {
        $results = [];

        foreach ($catalog as $entity) {
            $type = $entity['type'];
            $label = $entity['label'];

            if (
                $pattern !== ''
                && ! str_starts_with($type, $pattern)
                && ! str_starts_with(strtolower($label), $pattern)
            ) {
                continue;
            }

            $results[] = [
                'label' => $label.' ('.$type.')',
                'insert' => '{%'.$type.'.',
                'kind' => 'entity',
            ];
        }

        return $results;
    }

    /**
     * @param  array<int, array{type: string, label: string, attributes: array<int, array{code: string, label: string, value: string}>}>  $catalog
     * @return array<int, array{label: string, insert: string, kind: string}>
     */
    protected function suggestAttributes(string $pattern, array $catalog): array
    {
        [$entityPart, $attrPart] = array_pad(explode('.', $pattern, 2), 2, '');

        $results = [];

        foreach ($catalog as $entity) {
            if ($entity['type'] !== $entityPart) {
                continue;
            }

            foreach ($entity['attributes'] as $attribute) {
                $code = strtolower($attribute['code']);
                $name = strtolower($attribute['label']);

                if (
                    $attrPart !== ''
                    && ! str_starts_with($code, $attrPart)
                    && ! str_starts_with($name, $attrPart)
                ) {
                    continue;
                }

                $results[] = [
                    'label' => $attribute['label'].' — '.$entity['type'].'.'.$attribute['code'],
                    'insert' => $attribute['value'],
                    'kind' => 'attribute',
                ];
            }
        }

        return $results;
    }
}
