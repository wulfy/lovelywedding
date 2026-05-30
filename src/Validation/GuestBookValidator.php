<?php

declare(strict_types=1);

namespace LovelyWedding\Validation;

final class GuestBookValidator extends Validator
{
    /**
     * @param array<string, string|null> $input
     */
    public function validate(array $input): bool
    {
        // Honeypot: legacy form has a visible-but-bot-bait field `prenom` that must remain empty.
        $honeypot = $input['prenom'] ?? '';
        if (null !== $honeypot && '' !== trim($honeypot)) {
            $this->errors['prenom'] = 'BOT DETECTE!';

            return false;
        }

        $this->required('nom', $input['nom'] ?? null, 'Vous devez entrer un nom');
        $this->maxLength('nom', $input['nom'] ?? null, 100, 'Le nom est trop long');

        if ($this->required('email', $input['email'] ?? null, 'Vous devez entrer un email')) {
            $this->email('email', $input['email'] ?? null, "L'adresse mail n'est pas valide");
        }

        $this->required('ville', $input['ville'] ?? null, 'Vous devez entrer une ville');
        $this->maxLength('ville', $input['ville'] ?? null, 100, 'La ville est trop longue');

        $this->required('message', $input['message'] ?? null, 'Laissez au moins un message :)');
        $this->maxLength('message', $input['message'] ?? null, 5000, 'Message trop long');

        $image = $input['image'] ?? null;
        if (null !== $image && '' !== trim($image)) {
            $this->url('image', $image, "L'URL de l'image n'est pas valide");
        }

        return $this->isValid();
    }
}
