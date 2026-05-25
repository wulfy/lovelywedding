<?php

declare(strict_types=1);

namespace LovelyWedding\Tests\Unit\Validation;

use LovelyWedding\Validation\NewsletterValidator;
use PHPUnit\Framework\TestCase;

final class NewsletterValidatorTest extends TestCase
{
    public function testValidEmail(): void
    {
        $v = new NewsletterValidator();
        self::assertTrue($v->validate(['email' => 'foo@bar.com']));
    }

    public function testMissingEmail(): void
    {
        $v = new NewsletterValidator();
        self::assertFalse($v->validate(['email' => null]));
        self::assertArrayHasKey('email', $v->errors());
    }

    public function testInvalidEmail(): void
    {
        $v = new NewsletterValidator();
        self::assertFalse($v->validate(['email' => 'not-an-email']));
        self::assertArrayHasKey('email', $v->errors());
    }
}
