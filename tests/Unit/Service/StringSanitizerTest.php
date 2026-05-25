<?php

declare(strict_types=1);

namespace LovelyWedding\Tests\Unit\Service;

use LovelyWedding\Service\StringSanitizer;
use PHPUnit\Framework\TestCase;

final class StringSanitizerTest extends TestCase
{
    public function testEscapeNeutralizesXssScriptTag(): void
    {
        $out = StringSanitizer::escape('<script>alert(1)</script>');
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $out);
    }

    public function testEscapeHandlesQuotesAndAmpersands(): void
    {
        $out = StringSanitizer::escape('"\' & <a>');
        self::assertStringContainsString('&quot;', $out);
        // ENT_HTML5 emits &apos; for single quotes (vs &#039; for HTML4)
        self::assertStringContainsString('&apos;', $out);
        self::assertStringContainsString('&amp;', $out);
    }

    public function testRemoveAccentsConvertsCommonFrenchLetters(): void
    {
        $out = StringSanitizer::removeAccents('Éléonore Ça va à côté');
        self::assertStringNotContainsString('é', $out);
        self::assertStringNotContainsString('à', $out);
        self::assertStringNotContainsString('ç', $out);
        self::assertSame('Eleonore Ca va a cote', trim($out));
    }

    public function testTruncateRespectsMultibyte(): void
    {
        $out = StringSanitizer::truncate('éléphant', 4);
        self::assertSame('élép...', $out);
    }
}
