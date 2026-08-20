<?php

/**
 * @package fila
 * @author  Yevhen Odynets
 * @since   2026-08-19
 */

declare(strict_types = 1);

namespace Yeod\Tests\Unit\Shared\Domain\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Yeod\Shared\Domain\Support\SlugifyUk;

/**
 * Test suite for SlugifyUk.
 *
 * Covers the official transliteration examples from Resolution of the
 * Cabinet of Ministers of Ukraine No. 55 (27.01.2010), case-preservation
 * behaviour, and URL slug generation.
 */
#[CoversClass(SlugifyUk::class)]
final class SlugifyUkTest extends TestCase
{
    // ------------------------------------------------------------------
    // Official examples per Resolution No. 55 (27.01.2010)
    // ------------------------------------------------------------------

    public function testLetterA(): void
    {
        self::assertSame('Alushta', SlugifyUk::transliterate('Алушта'));
        self::assertSame('Andrii', SlugifyUk::transliterate('Андрій'));
    }

    public function testLetterB(): void
    {
        self::assertSame('Borshchahivka', SlugifyUk::transliterate('Борщагівка'));
        self::assertSame('Borysenko', SlugifyUk::transliterate('Борисенко'));
    }

    public function testLetterV(): void
    {
        self::assertSame('Vinnytsia', SlugifyUk::transliterate('Вінниця'));
        self::assertSame('Volodymyr', SlugifyUk::transliterate('Володимир'));
    }

    public function testLetterH(): void
    {
        self::assertSame('Hadiach', SlugifyUk::transliterate('Гадяч'));
        self::assertSame('Bohdan', SlugifyUk::transliterate('Богдан'));
        self::assertSame('Zghurskyi', SlugifyUk::transliterate('Згурський'));
    }

    public function testLetterG(): void
    {
        self::assertSame('Galagan', SlugifyUk::transliterate('Ґалаґан'));
        self::assertSame('Gorgany', SlugifyUk::transliterate('Ґорґани'));
    }

    public function testLetterD(): void
    {
        self::assertSame('Donetsk', SlugifyUk::transliterate('Донецьк'));
        self::assertSame('Dmytro', SlugifyUk::transliterate('Дмитро'));
    }

    public function testLetterE(): void
    {
        self::assertSame('Rivne', SlugifyUk::transliterate('Рівне'));
        self::assertSame('Oleh', SlugifyUk::transliterate('Олег'));
        self::assertSame('Esman', SlugifyUk::transliterate('Есмань'));
    }

    public function testLetterYe(): void
    {
        self::assertSame('Yenakiieve', SlugifyUk::transliterate('Єнакієве'));
        self::assertSame('Haievych', SlugifyUk::transliterate('Гаєвич'));
        self::assertSame('Koropie', SlugifyUk::transliterate("Короп'є"));
    }

    public function testLetterZh(): void
    {
        self::assertSame('Zhytomyr', SlugifyUk::transliterate('Житомир'));
        self::assertSame('Zhanna', SlugifyUk::transliterate('Жанна'));
        self::assertSame('Zhezheliv', SlugifyUk::transliterate('Жежелів'));
    }

    public function testLetterZ(): void
    {
        self::assertSame('Zakarpattia', SlugifyUk::transliterate('Закарпаття'));
        self::assertSame('Kazymyrchuk', SlugifyUk::transliterate('Казимирчук'));
    }

    public function testLetterY(): void
    {
        self::assertSame('Medvyn', SlugifyUk::transliterate('Медвин'));
        self::assertSame('Mykhailenko', SlugifyUk::transliterate('Михайленко'));
    }

    public function testLetterI(): void
    {
        self::assertSame('Ivankiv', SlugifyUk::transliterate('Іванків'));
        self::assertSame('Ivashchenko', SlugifyUk::transliterate('Іващенко'));
    }

    public function testLetterYi(): void
    {
        self::assertSame('Yizhakevych', SlugifyUk::transliterate('Їжакевич'));
        self::assertSame('Kadyivka', SlugifyUk::transliterate('Кадиївка'));
        self::assertSame('Marine', SlugifyUk::transliterate("Мар'їне"));
    }

    public function testLetterYo(): void
    {
        self::assertSame('Yosypivka', SlugifyUk::transliterate('Йосипівка'));
        self::assertSame('Stryi', SlugifyUk::transliterate('Стрий'));
        self::assertSame('Oleksii', SlugifyUk::transliterate('Олексій'));
    }

    public function testLetterK(): void
    {
        self::assertSame('Kyiv', SlugifyUk::transliterate('Київ'));
        self::assertSame('Kovalenko', SlugifyUk::transliterate('Коваленко'));
    }

    public function testLetterL(): void
    {
        self::assertSame('Lebedyn', SlugifyUk::transliterate('Лебедин'));
        self::assertSame('Leonid', SlugifyUk::transliterate('Леонід'));
    }

    public function testLetterM(): void
    {
        self::assertSame('Mykolaiv', SlugifyUk::transliterate('Миколаїв'));
        self::assertSame('Marynych', SlugifyUk::transliterate('Маринич'));
    }

    public function testLetterN(): void
    {
        self::assertSame('Nizhyn', SlugifyUk::transliterate('Ніжин'));
        self::assertSame('Nataliia', SlugifyUk::transliterate('Наталія'));
    }

    public function testLetterO(): void
    {
        self::assertSame('Odesa', SlugifyUk::transliterate('Одеса'));
        self::assertSame('Onyshchenko', SlugifyUk::transliterate('Онищенко'));
    }

    public function testLetterP(): void
    {
        self::assertSame('Poltava', SlugifyUk::transliterate('Полтава'));
        self::assertSame('Petro', SlugifyUk::transliterate('Петро'));
    }

    public function testLetterR(): void
    {
        self::assertSame('Reshetylivka', SlugifyUk::transliterate('Решетилівка'));
        self::assertSame('Rybchynskyi', SlugifyUk::transliterate('Рибчинський'));
    }

    public function testLetterS(): void
    {
        self::assertSame('Sumy', SlugifyUk::transliterate('Суми'));
        self::assertSame('Solomiia', SlugifyUk::transliterate('Соломія'));
    }

    public function testLetterT(): void
    {
        self::assertSame('Ternopil', SlugifyUk::transliterate('Тернопіль'));
        self::assertSame('Trots', SlugifyUk::transliterate('Троць'));
    }

    public function testLetterU(): void
    {
        self::assertSame('Uzhhorod', SlugifyUk::transliterate('Ужгород'));
        self::assertSame('Uliana', SlugifyUk::transliterate('Уляна'));
    }

    public function testLetterF(): void
    {
        self::assertSame('Fastiv', SlugifyUk::transliterate('Фастів'));
        self::assertSame('Filipchuk', SlugifyUk::transliterate('Філіпчук'));
    }

    public function testLetterKh(): void
    {
        self::assertSame('Kharkiv', SlugifyUk::transliterate('Харків'));
        self::assertSame('Khrystyna', SlugifyUk::transliterate('Христина'));
    }

    public function testLetterTs(): void
    {
        self::assertSame('Bila Tserkva', SlugifyUk::transliterate('Біла Церква'));
        self::assertSame('Stetsenko', SlugifyUk::transliterate('Стеценко'));
    }

    public function testLetterCh(): void
    {
        self::assertSame('Chernivtsi', SlugifyUk::transliterate('Чернівці'));
        self::assertSame('Shevchenko', SlugifyUk::transliterate('Шевченко'));
    }

    public function testLetterSh(): void
    {
        self::assertSame('Shostka', SlugifyUk::transliterate('Шостка'));
        self::assertSame('Kyshenky', SlugifyUk::transliterate('Кишеньки'));
    }

    public function testLetterShch(): void
    {
        self::assertSame('Shcherbukhy', SlugifyUk::transliterate('Щербухи'));
        self::assertSame('Hoshcha', SlugifyUk::transliterate('Гоща'));
        self::assertSame('Harashchenko', SlugifyUk::transliterate('Гаращенко'));
    }

    public function testLetterYu(): void
    {
        self::assertSame('Yurii', SlugifyUk::transliterate('Юрій'));
        self::assertSame('Koriukivka', SlugifyUk::transliterate('Корюківка'));
    }

    public function testLetterYa(): void
    {
        self::assertSame('Yahotyn', SlugifyUk::transliterate('Яготин'));
        self::assertSame('Yaroshenko', SlugifyUk::transliterate('Ярошенко'));
        self::assertSame('Kostiantyn', SlugifyUk::transliterate('Костянтин'));
        self::assertSame('Znamianka', SlugifyUk::transliterate("Знам'янка"));
        self::assertSame('Feodosiia', SlugifyUk::transliterate('Феодосія'));
    }

    public function testNoteZgVsZh(): void
    {
        self::assertSame('Zghorany', SlugifyUk::transliterate('Згорани'));
        self::assertSame('Rozghon', SlugifyUk::transliterate('Розгон'));
    }

    // ------------------------------------------------------------------
    // Case-preservation (Title Case and UPPERCASE)
    // ------------------------------------------------------------------

    public function testUppercaseWithComplexDigraphs(): void
    {
        self::assertSame('BOHDAN YURIIOVYCH', SlugifyUk::transliterate('БОГДАН ЮРІЙОВИЧ'));
    }

    public function testUppercaseZgDigraph(): void
    {
        self::assertSame('ZGHORANY', SlugifyUk::transliterate('ЗГОРАНИ'));
    }

    // ------------------------------------------------------------------
    // URL slug generation (slugify)
    // ------------------------------------------------------------------

    public function testSlugGenerationRespectsUkrainianSpecifics(): void
    {
        self::assertSame('znamianka-ta-marine', SlugifyUk::slugify("Знам'янка та Мар'їне"));
        self::assertSame('proiekt-yurii-u-zghoranakh', SlugifyUk::slugify('Проєкт: Юрій у  𒀙 𒀃 Згоранах'));
    }

    public function testSlugGenerationWithCustomSeparator(): void
    {
        self::assertSame('nova-stattia', SlugifyUk::slugify('Нова стаття', '-'));
    }

    // ------------------------------------------------------------------
    // Additional edge cases (options array, empty input, apostrophes)
    // ------------------------------------------------------------------

    public function testSlugifyWithOptionsArray(): void
    {
        self::assertSame(
            'znamianka_ta_marine',
            SlugifyUk::slugify("Знам'янка та Мар'їне", ['replacement' => '_'])
        );

        // 'lower' => false only skips the final mb_strtolower() call — it does NOT
        // force uppercase. The casing is whatever transliterate() produced, which
        // mirrors the casing of each original word ("Знам'янка" -> "Znamianka",
        // "та" -> "ta", "Мар'їне" -> "Marine").
        self::assertSame(
            'Znamianka-ta-Marine',
            SlugifyUk::slugify("Знам'янка та Мар'їне", ['lower' => false])
        );

        // To actually verify UPPERCASE preservation end-to-end, use an all-caps input.
        self::assertSame(
            'ZNAMIANKA-TA-MARINE',
            SlugifyUk::slugify("ЗНАМ'ЯНКА ТА МАР'ЇНЕ", ['lower' => false])
        );
    }

    public function testSlugifyWithRemovePattern(): void
    {
        self::assertSame(
            'proiekt-yurii-u-zghoranakh',
            SlugifyUk::slugify('Проєкт: Юрій у Згоранах!!!', ['remove' => '/[:!]+/u'])
        );
    }

    public function testEmptyInput(): void
    {
        self::assertSame('', SlugifyUk::transliterate(''));
        self::assertSame('', SlugifyUk::slugify(''));
    }

    public function testApostropheIsStrippedNotTransliterated(): void
    {
        self::assertSame('Koropie', SlugifyUk::transliterate("Короп'є"));
        self::assertSame('Marine', SlugifyUk::transliterate("Мар'їне"));
    }

    public function testSoftSignIsStripped(): void
    {
        self::assertSame('Donetsk', SlugifyUk::transliterate('Донецьк'));
        self::assertSame('Trots', SlugifyUk::transliterate('Троць'));
    }
}
