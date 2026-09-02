<?php

namespace App\Services;

class ArabicSearchService
{
    /**
     * Convert an Arabic search string into a MySQL REGEXP pattern
     * that is insensitive to common orthographic variations.
     *
     * @param string $search
     * @return string
     */
    public function generateRegexPattern(string $search): string
    {
        if (empty($search)) {
            return '';
        }

        // Normalize: remove diacritics and kashida from the search input itself
        $normalized = preg_replace('/[\x{064B}-\x{0652}\x{0640}]/u', '', $search);
        // Normalize spaces by removing them entirely
        $normalized = preg_replace('/\s+/u', '', trim($normalized));

        // Group definitions for orthographic variations
        $alef = '[اأإآٱ]';
        $tehMarbuta = '[ةه]';
        $yaa = '[يىی]'; // Includes Arabic and Persian Yeh
        $hamza = '[ءؤئ]';
        $noise = '[\x{064B}-\x{0652}\x{0640}]*'; // Optional diacritics/kashida

        $chars = mb_str_split($normalized);
        $pattern = '';

        foreach ($chars as $char) {
            // Map character to its group or escape it
            if (preg_match('/[اأإآٱ]/u', $char)) {
                $pattern .= $alef;
            } elseif (preg_match('/[ةه]/u', $char)) {
                $pattern .= $tehMarbuta;
            } elseif (preg_match('/[يىی]/u', $char)) {
                $pattern .= $yaa;
            } elseif (preg_match('/[ءؤئ]/u', $char)) {
                $pattern .= $hamza;
            } else {
                $pattern .= preg_quote($char, '/');
            }

            // Add noise (diacritics/kashida) and optional space after every character
            $pattern .= $noise . '\s*';
        }

        return $pattern;
    }
}
