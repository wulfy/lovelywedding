<?php

declare(strict_types=1);

namespace LovelyWedding\Validation;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

abstract class Validator
{
    /** @var array<string, string> */
    protected array $errors = [];

    protected function required(string $field, ?string $value, string $message): bool
    {
        if (null === $value || '' === trim($value)) {
            $this->errors[$field] = $message;

            return false;
        }

        return true;
    }

    protected function maxLength(string $field, ?string $value, int $max, string $message): bool
    {
        if (null !== $value && mb_strlen($value, 'UTF-8') > $max) {
            $this->errors[$field] = $message;

            return false;
        }

        return true;
    }

    protected function email(string $field, ?string $value, string $message): bool
    {
        if (null === $value || '' === $value) {
            return true;
        }
        $validator = new EmailValidator();
        if (!$validator->isValid($value, new RFCValidation())) {
            $this->errors[$field] = $message;

            return false;
        }

        return true;
    }

    protected function url(string $field, ?string $value, string $message): bool
    {
        if (null === $value || '' === $value) {
            return true;
        }
        if (false === filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message;

            return false;
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            $this->errors[$field] = $message;

            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function isValid(): bool
    {
        return [] === $this->errors;
    }
}
