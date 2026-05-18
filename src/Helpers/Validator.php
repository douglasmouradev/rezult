<?php

declare(strict_types=1);

namespace App\Helpers;

final class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
                $this->errors[$field] = "O campo {$field} é obrigatório.";
            }
        }
        return $this;
    }

    public function email(string $field): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'E-mail inválido.';
        }
        return $this;
    }

    public function min(string $field, int $min): self
    {
        if (isset($this->data[$field]) && mb_strlen((string) $this->data[$field]) < $min) {
            $this->errors[$field] = "Mínimo de {$min} caracteres.";
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field] = 'Valor inválido.';
        }
        return $this;
    }

    public function numeric(string $field): self
    {
        if (isset($this->data[$field]) && !is_numeric(str_replace([',', '.'], '', (string) $this->data[$field]))) {
            $this->errors[$field] = 'Deve ser um número válido.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        return $this->errors[array_key_first($this->errors)] ?? null;
    }
}
