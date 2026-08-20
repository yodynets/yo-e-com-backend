<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Shared\Domain\Support;

use Normalizer;

/**
 * Ukrainian Transliteration and Slugification Utility.
 *
 * Implements official transliteration rules according to the Resolution of the
 * Cabinet of Ministers of Ukraine No. 55 of January 27, 2010, "On streamlining
 * the transliteration of the Ukrainian alphabet in Latin")and provides a lightweight,
 * dependency-free slug generator for URL generation.
 *
 * This is a stateless utility class: it is never instantiated, all methods are static.
 */
final class SlugifyUk
{
    /**
     * Character map for direct (position-independent) transliteration
     * according to Resolution of the Cabinet of Ministers of Ukraine No. 55 (27.01.2010).
     *
     * @var array<string, string>
     */
    private const array DIRECT_CHAR_MAP = [
        'А' => 'A',
        'а' => 'a',
        'В' => 'V',
        'в' => 'v',
        'Г' => 'H',
        'г' => 'h',
        'Ґ' => 'G',
        'ґ' => 'g',
        'Д' => 'D',
        'д' => 'd',
        'Е' => 'E',
        'е' => 'e',
        'Ж' => 'Zh',
        'ж' => 'zh',
        'З' => 'Z',
        'з' => 'z',
        'И' => 'Y',
        'и' => 'y',
        'І' => 'I',
        'і' => 'i',
        'К' => 'K',
        'к' => 'k',
        'Л' => 'L',
        'л' => 'l',
        'М' => 'M',
        'м' => 'm',
        'Н' => 'N',
        'н' => 'n',
        'О' => 'O',
        'о' => 'o',
        'П' => 'P',
        'п' => 'p',
        'Р' => 'R',
        'р' => 'r',
        'С' => 'S',
        'с' => 's',
        'Т' => 'T',
        'т' => 't',
        'У' => 'U',
        'у' => 'u',
        'Ф' => 'F',
        'ф' => 'f',
        'Х' => 'Kh',
        'х' => 'kh',
        'Ц' => 'Ts',
        'ц' => 'ts',
        'Ч' => 'Ch',
        'ч' => 'ch',
        'Ш' => 'Sh',
        'ш' => 'sh',
        'Щ' => 'Shch',
        'щ' => 'shch',
        "'" => '',
        'Ь' => '',
        'ь' => '',
    ];

    /**
     * Word-position dependent transliteration rules.
     * Each entry is [sourceChar, replacementAtWordStart, replacementElsewhere].
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const array POSITIONAL_RULES = [
        ['Є', 'Ye', 'ie'],
        ['є', 'ye', 'ie'],
        ['Ї', 'Yi', 'i'],
        ['ї', 'yi', 'i'],
        ['Й', 'Y', 'i'],
        ['й', 'y', 'i'],
        ['Ю', 'Yu', 'iu'],
        ['ю', 'yu', 'iu'],
        ['Я', 'Ya', 'ia'],
        ['я', 'ya', 'ia'],
    ];

    /**
     * Regex character class defining a word boundary: any character that is
     * NOT a Ukrainian/Latin letter or an apostrophe (including Unicode variants).
     */
    private const string WORD_BOUNDARY = "[^A-Za-zА-Яа-яІіЇїЄєҐґ'’ʼ]";

    /**
     * Utility class — instantiation is not allowed.
     */
    private function __construct() {}

    /**
     * Creates, accordingly, a slug as the final chord
     */
    public static function slugify(string $input, string|array|null $options = null): string
    {
        $opts = is_string($options)
            ? ['replacement' => $options]
            : ($options ?? []);

        $replacement = $opts['replacement'] ?? $opts['separator'] ?? '-';
        $lower = (bool)($opts['lower'] ?? true);
        $trim = (bool)($opts['trim'] ?? true);
        $remove = $opts['remove'] ?? null;

        // Transliteration of the Ukrainian Cyrillic alphabet according
        // to the Cabinet of Ministers of Ukraine No. 55
        $slug = self::transliterate($input);

        // Removing of Latin diacritics (ç -> c, é -> e, ä -> a, etc.)
        if (function_exists('transliterator_transliterate')) {
            $slug = transliterator_transliterate('Any-Latin; Latin-ASCII', $slug) ?: $slug;
        } elseif (class_exists(Normalizer::class)) {
            $slug = Normalizer::normalize($slug, Normalizer::FORM_D);
            $slug = preg_replace('/\p{M}+/u', '', $slug);
        } elseif (function_exists('iconv')) {
            $slug = (string)iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        }

        if ($remove !== null) {
            $slug = preg_replace($remove, '', $slug);
        }

        $quotedReplacement = preg_quote($replacement, '/');

        // 3. Заміна решти не-ASCII та спецсимволів на пробіли
        $slug = preg_replace('/[^A-Za-z0-9'.$quotedReplacement.'\s]+/u', ' ', $slug);

        // 4. Схлопування пробілів і розділювачів
        $slug = preg_replace('/[\s'.$quotedReplacement.']+/u', $replacement, $slug);

        if ($trim) {
            $slug = trim($slug, $replacement);
        }

        if ($lower) {
            $slug = mb_strtolower($slug, 'UTF-8');
        }

        return $slug;
    }

    /**
     * Transliterates a Ukrainian string into Latin script following
     * the official rules of Resolution No. 55 (27.01.2010) of the
     * Cabinet of Ministers of Ukraine.
     */
    public static function transliterate(string $input): string
    {
        $result = self::applyPositionalRules($input);
        $chars = mb_str_split($result, 1, 'UTF-8');

        $final = '';

        foreach ($chars as $charIndex => $char) {
            if (array_key_exists($char, self::DIRECT_CHAR_MAP)) {
                $word = self::getWordAt($result, $charIndex);
                $final .= self::preserveReplacementCase($char, self::DIRECT_CHAR_MAP[$char], $word);
            } else {
                $final .= $char;
            }
        }

        return str_replace(["'", '’', 'ʼ'], '', $final);
    }

    /**
     * Applies word-position dependent transliteration rules and the specific
     * multi-character digraph rule ("ЗГ" -> "ZGH") to the input string.
     */
    private static function applyPositionalRules(string $input): string
    {
        $result = str_replace(['ЗГ', 'Зг', 'зг', 'зГ'], ['ZGH', 'Zgh', 'zgh', 'zGH'], $input);

        foreach (self::POSITIONAL_RULES as [$char, $wordStart, $other]) {
            $subjectForStart = $result;
            $startPattern = '/(^|'.self::WORD_BOUNDARY.')('.preg_quote($char, '/').')/u';

            $result = self::replaceWithOffset(
                $startPattern,
                $subjectForStart,
                static function (array $match) use ($subjectForStart, $wordStart): string {
                    [$prefix] = $match[1];
                    [$matchedChar, $matchedCharByteOffset] = $match[2];

                    $charOffset = self::byteToCharOffset($subjectForStart, $matchedCharByteOffset);
                    $word = self::getWordAt($subjectForStart, $charOffset);

                    return $prefix.self::preserveReplacementCase($matchedChar, $wordStart, $word);
                }
            );

            $subjectForOther = $result;
            $otherPattern = '/'.preg_quote($char, '/').'/u';

            $result = self::replaceWithOffset(
                $otherPattern,
                $subjectForOther,
                static function (array $match) use ($subjectForOther, $other): string {
                    [$matchedChar, $matchedCharByteOffset] = $match[0];

                    $charOffset = self::byteToCharOffset($subjectForOther, $matchedCharByteOffset);
                    $word = self::getWordAt($subjectForOther, $charOffset);

                    return self::preserveReplacementCase($matchedChar, $other, $word);
                }
            );
        }

        return $result;
    }

    /**
     * Multibyte-safe equivalent of `String.prototype.replace` with a callback that
     * receives match data with PREG_OFFSET_CAPTURE structures.
     */
    private static function replaceWithOffset(string $pattern, string $subject, callable $callback): string
    {
        if (! preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return $subject;
        }

        $out = '';
        $lastEnd = 0;

        foreach ($matches as $match) {
            /** @var list<array{0: string, 1: int}> $match */
            [$fullMatch, $byteOffset] = $match[0];

            $out .= substr($subject, $lastEnd, $byteOffset - $lastEnd);
            $out .= $callback($match, $byteOffset);
            $lastEnd = $byteOffset + strlen($fullMatch);
        }

        $out .= substr($subject, $lastEnd);

        return $out;
    }

    /**
     * Converts a PCRE byte offset into a multibyte character offset.
     */
    private static function byteToCharOffset(string $haystack, int $byteOffset): int
    {
        return mb_strlen(substr($haystack, 0, $byteOffset), 'UTF-8');
    }

    /**
     * Returns the contiguous run of letters and apostrophes starting at the given offset.
     */
    private static function getWordAt(string $haystack, int $charOffset): string
    {
        $tail = mb_substr($haystack, $charOffset, null, 'UTF-8');

        // We take into account letters and apostrophes inside the word
        /** @noinspection PhpRangesInClassCanBeMergedInspection */
        if (preg_match('/^[A-Za-zА-Яа-яІіЇїЄєҐґ\'’ʼ]+/u', $tail, $match) === 1) {
            return $match[0];
        }

        return '';
    }

    /**
     * Preserves the casing style of the source token for a given replacement.
     */
    private static function preserveReplacementCase(string $sourceChar, string $replacement, string $fullWord): string
    {
        if ($replacement === '') {
            return '';
        }

        $isLowerLike = $sourceChar !== mb_strtoupper($sourceChar, 'UTF-8')
            || $sourceChar === mb_strtolower($sourceChar, 'UTF-8');

        if ($isLowerLike) {
            return mb_strtolower($replacement, 'UTF-8');
        }

        $isWordAllUpper = $fullWord !== ''
            && $fullWord === mb_strtoupper($fullWord, 'UTF-8')
            && $fullWord !== mb_strtolower($fullWord, 'UTF-8');

        if ($isWordAllUpper) {
            return mb_strtoupper($replacement, 'UTF-8');
        }

        $firstChar = mb_substr($replacement, 0, 1, 'UTF-8');
        $rest = mb_substr($replacement, 1, null, 'UTF-8');

        return mb_strtoupper($firstChar, 'UTF-8').mb_strtolower($rest, 'UTF-8');
    }
}
