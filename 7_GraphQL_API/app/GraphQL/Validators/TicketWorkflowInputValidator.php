<?php

declare(strict_types=1);

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class TicketWorkflowInputValidator extends Validator
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'max:255'],
        ];
    }
}
