<?php

namespace App\Services\Knowledge;

class DocumentChunker
{
    /**
     * @return array<int, string>
     */
    public function chunk(string $content, int $size = 600, int $overlap = 100): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($content)) ?? '';

        if ($normalized === '') {
            return [];
        }

        $chunks = [];
        $length = strlen($normalized);
        $offset = 0;

        while ($offset < $length) {
            $chunks[] = trim(substr($normalized, $offset, $size));
            $offset += ($size - $overlap);
        }

        return array_values(array_filter($chunks, fn (string $chunk): bool => $chunk !== ''));
    }
}
