<?php

declare(strict_types=1);

namespace LovelyWedding\Tests\Unit\Validation;

use LovelyWedding\Validation\GuestBookValidator;
use PHPUnit\Framework\TestCase;

final class GuestBookValidatorTest extends TestCase
{
    public function testNominalCaseAccepted(): void
    {
        $v = new GuestBookValidator();
        self::assertTrue($v->validate([
            'nom'     => 'Audrey',
            'prenom'  => '',
            'email'   => 'audrey@example.com',
            'ville'   => 'Lyon',
            'message' => 'Bravo aux mariés !',
            'image'   => '',
        ]));
        self::assertSame([], $v->errors());
    }

    public function testHoneypotTriggersRejection(): void
    {
        $v = new GuestBookValidator();
        self::assertFalse($v->validate([
            'nom'     => 'Audrey',
            'prenom'  => 'spam',
            'email'   => 'audrey@example.com',
            'ville'   => 'Lyon',
            'message' => 'Hello',
            'image'   => '',
        ]));
        self::assertArrayHasKey('prenom', $v->errors());
    }

    public function testMissingMandatoryFieldsAreReported(): void
    {
        $v = new GuestBookValidator();
        self::assertFalse($v->validate([
            'nom'     => '',
            'prenom'  => '',
            'email'   => '',
            'ville'   => '',
            'message' => '',
            'image'   => '',
        ]));
        $errs = $v->errors();
        self::assertArrayHasKey('nom', $errs);
        self::assertArrayHasKey('email', $errs);
        self::assertArrayHasKey('ville', $errs);
        self::assertArrayHasKey('message', $errs);
    }

    public function testBrokenLegacyRegexEmailIsNowRejectedProperly(): void
    {
        // Legacy used `^...$^` delimiters which made the regex semi-broken; the new validator
        // uses egulias/email-validator and must reject malformed emails.
        $v = new GuestBookValidator();
        self::assertFalse($v->validate([
            'nom'     => 'X',
            'prenom'  => '',
            'email'   => 'not-an-email',
            'ville'   => 'Lyon',
            'message' => 'hi',
            'image'   => '',
        ]));
        self::assertArrayHasKey('email', $v->errors());
    }

    public function testImageUrlMustBeHttpOrHttps(): void
    {
        $v = new GuestBookValidator();
        self::assertFalse($v->validate([
            'nom'     => 'Audrey',
            'prenom'  => '',
            'email'   => 'a@b.fr',
            'ville'   => 'Lyon',
            'message' => 'hi',
            'image'   => 'javascript:alert(1)',
        ]));
        self::assertArrayHasKey('image', $v->errors());
    }

    public function testMaxLengths(): void
    {
        $v = new GuestBookValidator();
        self::assertFalse($v->validate([
            'nom'     => str_repeat('a', 101),
            'prenom'  => '',
            'email'   => 'a@b.fr',
            'ville'   => 'Lyon',
            'message' => 'hi',
            'image'   => '',
        ]));
        self::assertArrayHasKey('nom', $v->errors());
    }
}
