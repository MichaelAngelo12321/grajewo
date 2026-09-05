<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ExcerptGenerator;
use PHPUnit\Framework\TestCase;

class ExcerptGeneratorTest extends TestCase
{
    private ExcerptGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ExcerptGenerator();
    }

    public function testStripsHtmlTags(): void
    {
        self::assertSame(
            'Pierwsze zdanie. Drugie zdanie.',
            $this->generator->fromHtml('<p>Pierwsze zdanie.</p><p>Drugie zdanie.</p>'),
        );
    }

    public function testDecodesNamedHtmlEntities(): void
    {
        self::assertSame(
            'Najlepsi sportowcy zostali wyróżnieni.',
            $this->generator->fromHtml('<p>Najlepsi sportowcy zostali wyr&oacute;&zdot;nieni.</p>'),
        );
    }

    public function testDecodesNumericEntitiesAndNbsp(): void
    {
        self::assertSame(
            'Ełk – miasto',
            $this->generator->fromHtml('E&#322;k&nbsp;&ndash;&nbsp;miasto'),
        );
    }

    public function testKeepsOnlyFirstThreeSentences(): void
    {
        self::assertSame(
            'Raz. Dwa. Trzy',
            $this->generator->fromHtml('Raz. Dwa. Trzy. Cztery. Pięć.'),
        );
    }

    public function testTruncatesTo300Characters(): void
    {
        $excerpt = $this->generator->fromHtml(str_repeat('ą', 400));

        self::assertSame(300, mb_strlen($excerpt));
        self::assertStringEndsWith('...', $excerpt);
    }

    public function testEntityDecodedBeforeSentenceSplitAndTruncation(): void
    {
        // 297 chars of "ó" encoded as entities must count as characters, not as "&oacute;" strings
        $excerpt = $this->generator->fromHtml(str_repeat('&oacute;', 297));

        self::assertSame(str_repeat('ó', 297), $excerpt);
    }
}
