<?php

declare(strict_types=1);

namespace App\GraphQL\Validators\Mutation;

use Company\Library\Models\TicketWorkflow;
use Nuwave\Lighthouse\Validation\Validator;

class PublishWorkflowDraftRevisionValidator extends Validator
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            'workflowId' => [function ($attribute, $value, $fail) {
                $workflow = TicketWorkflow::find($value);
                if ($workflow instanceof TicketWorkflow && blank($workflow->draftRevision)) {
                    $fail('admin.workflows.validation.draft_not_exist');
                }
            }],
        ];
    }
}
