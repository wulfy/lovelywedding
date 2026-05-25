<?php

declare(strict_types=1);

namespace LovelyWedding\Validation;

final class NewsletterValidator extends Validator
{
    /**
     * @param array<string, string|null> $input
     */
    public function validate(array $input): bool
    {
        if ($this->required('email', $input['email'] ?? null, 'Vous devez entrer un email valide')) {
            $this->email('email', $input['email'] ?? null, "L'adresse mail n'est pas valide");
        }

        return $this->isValid();
    }
}
