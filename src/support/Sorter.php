<?php

namespace Arbor\support;

final class Sorter
{
    /**
     * Sort resources by priority.
     *
     * Higher priority values come later.
     * Original order is preserved when priorities are equal.
     */
    public static function sort(array $items): array
    {
        $indexed = [];

        foreach (array_values($items) as $index => $item) {
            $indexed[] = [
                'index' => $index,
                'priority' => $item['priority'] ?? 0,
                'item' => $item,
            ];
        }

        usort($indexed, static function ($a, $b): int {
            return ($a['priority'] <=> $b['priority'])
                ?: ($a['index'] <=> $b['index']);
        });

        return array_column($indexed, 'item');
    }
}
