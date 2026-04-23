<?php

namespace App\Services;

use App\Models\Ayah;
use Illuminate\Support\Collection;

class QuranPlanService
{
    /**
     * In-memory Ayah cache: keyed by "{surah_id}_{verse_number}".
     * Populated lazily via bootCache() — one bulk query per request.
     */
    private Collection $cache;

    private bool $cacheLoaded = false;

    // ─────────────────────────────────────────────────────────────────────────
    //  Cache helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Load ALL ayahs into memory once per request lifecycle.
     * ~6,236 rows, ~3 MB — trivial for PHP, eliminates hundreds of individual
     * DB round-trips during plan generation.
     */
    public function bootCache(): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        $this->cache = Ayah::orderBy('surah_id')->orderBy('verse_number')->get()->keyBy(
            fn (Ayah $a) => "{$a->surah_id}_{$a->verse_number}"
        );

        $this->cacheLoaded = true;
    }

    private function find(int $surahId, int $verseNumber): ?Ayah
    {
        return $this->cache["{$surahId}_{$verseNumber}"] ?? null;
    }

    private function lastVerseOf(int $surahId): ?Ayah
    {
        return $this->cache
            ->filter(fn (Ayah $a) => $a->surah_id === $surahId)
            ->sortByDesc('verse_number')
            ->first();
    }

    private function lastVerseOfPage(int $pageNumber, string $direction, Ayah $startAyah): ?Ayah
    {
        $onPage = $this->cache->filter(fn (Ayah $a) => $a->page_number === $pageNumber);

        if ($direction === 'forward') {
            return $onPage->sortByDesc('surah_id')->sortByDesc('verse_number')->first();
        }

        $lowestSurahId = $onPage->min('surah_id');
        $firstOnPage = $onPage->sortBy('verse_number')->first();

        if ($firstOnPage && $firstOnPage->line_number_start == 1 && $startAyah->surah_id != $lowestSurahId) {
            $lowestSurahId++;
        }

        $lastOfStartSurah = $this->lastVerseOf($startAyah->surah_id);
        if ($lastOfStartSurah && $lastOfStartSurah->page_number > $pageNumber && $startAyah->surah_id != $lowestSurahId) {
            $lowestSurahId++;
        }

        return $onPage
            ->filter(fn (Ayah $a) => $a->surah_id === $lowestSurahId)
            ->sortByDesc('verse_number')
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the end ayah for a given start ayah, unit type, and direction.
     */
    public function getEndAyah(Ayah $startAyah, string $type, string $direction = 'forward', ?Ayah $capAyah = null): Ayah
    {
        $this->bootCache();

        $endAyah = match ($type) {
            'half' => $this->traverseLines($startAyah, 8, $direction),
            'third' => $this->traverseLines($startAyah, 5, $direction),
            'page' => $this->traverseLines($startAyah, 15, $direction),
            'surah' => $this->lastVerseOf($startAyah->surah_id) ?? $startAyah,
            '3_surahs' => $this->lastVerseOf(
                $direction === 'forward'
                    ? min(114, $startAyah->surah_id + 2)
                    : max(1, $startAyah->surah_id - 2)
            ) ?? $startAyah,
            'juz' => $this->traverseLines($startAyah, 300, $direction),
            'half_juz' => $this->traverseLines($startAyah, 150, $direction),
            '5_pages' => $this->traverseLines($startAyah, 75, $direction),
            default => $startAyah,
        };

        if ($capAyah && $this->isExceeding($endAyah, $capAyah, $direction)) {
            if ($this->isExceeding($startAyah, $capAyah, $direction)) {
                return $startAyah;
            }

            return $this->getAyahBefore($capAyah, $direction);
        }

        return $endAyah;
    }

    public function isExceeding(Ayah $current, Ayah $cap, string $direction, bool $inclusive = true): bool
    {
        if ($direction === 'reverse') {
            if ($current->surah_id < $cap->surah_id) {
                return true;
            }
            if ($current->surah_id > $cap->surah_id) {
                return false;
            }

            return $inclusive
                ? $current->verse_number >= $cap->verse_number
                : $current->verse_number > $cap->verse_number;
        }

        if ($current->surah_id > $cap->surah_id) {
            return true;
        }
        if ($current->surah_id < $cap->surah_id) {
            return false;
        }

        return $inclusive
            ? $current->verse_number >= $cap->verse_number
            : $current->verse_number > $cap->verse_number;
    }

    public function getAyahBefore(Ayah $target, string $direction): Ayah
    {
        $this->bootCache();

        if ($target->verse_number > 1) {
            return $this->find($target->surah_id, $target->verse_number - 1) ?? $target;
        }

        $prevSurahId = $direction === 'reverse' ? $target->surah_id + 1 : $target->surah_id - 1;

        if ($prevSurahId >= 1 && $prevSurahId <= 114) {
            return $this->lastVerseOf($prevSurahId) ?? $target;
        }

        return $target;
    }

    /**
     * Get the next start ayah based on direction.
     */
    public function getNextStartAyah(Ayah $currentStart, Ayah $currentEnd, string $type, string $direction = 'forward'): ?Ayah
    {
        $this->bootCache();

        $nextVerse = $this->find($currentEnd->surah_id, $currentEnd->verse_number + 1);
        if ($nextVerse) {
            return $nextVerse;
        }

        $nextSurahId = $direction === 'forward' ? $currentEnd->surah_id + 1 : $currentEnd->surah_id - 1;

        if ($nextSurahId >= 1 && $nextSurahId <= 114) {
            return $this->find($nextSurahId, 1);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Internal traversal (all lookups use the in-memory cache)
    // ─────────────────────────────────────────────────────────────────────────

    private function getAyahSize(Ayah $ayah): int
    {
        $absoluteCurrent = (($ayah->page_number - 1) * 15) + $ayah->line_number_end;

        if ($ayah->verse_number == 1) {
            if ($ayah->surah_id == 1) {
                return $absoluteCurrent;
            }
            $prevAyah = $this->lastVerseOf($ayah->surah_id - 1);
        } else {
            $prevAyah = $this->find($ayah->surah_id, $ayah->verse_number - 1);
        }

        if ($prevAyah) {
            $absolutePrev = (($prevAyah->page_number - 1) * 15) + $prevAyah->line_number_end;

            return max($ayah->verse_number == 1 ? 1 : 0, $absoluteCurrent - $absolutePrev);
        }

        return 1;
    }

    private function getTemporalNextAyah(Ayah $current, string $direction): ?Ayah
    {
        $next = $this->find($current->surah_id, $current->verse_number + 1);
        if ($next) {
            return $next;
        }

        $nextSurahId = ($direction === 'forward') ? $current->surah_id + 1 : $current->surah_id - 1;

        if ($nextSurahId < 1 || $nextSurahId > 114) {
            return null;
        }

        return $this->find($nextSurahId, 1);
    }

    private function traverseLines(Ayah $startAyah, int $linesToConsume, string $direction): Ayah
    {
        $isPageMultiple = ($linesToConsume % 15 === 0);
        $pagesTarget = $linesToConsume / 15;

        $currentPageEnd = $this->lastVerseOfPage($startAyah->page_number, $direction, $startAyah);
        $linesOnCurrentPage = 0;
        $curr = $startAyah;

        while ($curr) {
            $linesOnCurrentPage += $this->getAyahSize($curr);

            if ($currentPageEnd && $curr->id == $currentPageEnd->id) {
                break;
            }

            $curr = $this->getTemporalNextAyah($curr, $direction);
        }

        // RULE 1: If 10 or more lines, it counts as a FULL Page!
        if ($isPageMultiple && $linesOnCurrentPage >= 10 && $pagesTarget == 1) {
            return $currentPageEnd ?? $startAyah;
        }

        // RULE 2 & 3: fractional walk with mitigation
        $totalLines = 0;
        $curr = $startAyah;
        $lastValidAyah = $curr;

        while ($curr && $totalLines < $linesToConsume) {
            $size = $this->getAyahSize($curr);

            // RULE 3: prefer lesser amount when exceeding
            if ($totalLines + $size > $linesToConsume) {
                return ($totalLines > 0) ? $lastValidAyah : $curr;
            }

            $totalLines += $size;
            $lastValidAyah = $curr;
            $curr = $this->getTemporalNextAyah($curr, $direction);
        }

        return $lastValidAyah;
    }
}
