<?php

declare(strict_types=1);

namespace App\API\Tickets;

use Company\Library\GraphQL\Exceptions\NotFoundException;
use Company\Library\GraphQL\GetSingleRecordTrait;
use Company\Library\GraphQL\OrderByTrait;
use Company\Library\GraphQL\Resolve\BufferedContext;
use Company\Library\GraphQL\Resolve\OwnLoaderTrait;
use Company\Library\GraphQL\Resolve\ProcessResultsInterface;
use Company\Library\GraphQL\Resolve\SubCollectionLoaderTrait;
use Company\Library\GraphQL\Resolver;
use Company\Library\Models\TicketWorkflow;
use Company\Library\Models\TicketWorkflowRevision;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TicketWorkflowsResolver implements Resolver, ProcessResultsInterface
{
    use OwnLoaderTrait;
    use OrderByTrait;
    use SubCollectionLoaderTrait;
    use GetSingleRecordTrait;

    public function __construct(
        protected TicketWorkflowRevisionsResolver $revisionsResolver
    ) {
    }

    public function ticketWorkflowQuery(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        return $this->getSingleRecord(
            $rootValue,
            $args,
            $context,
            $resolveInfo,
            'ticketWorkflowsQuery'
        );
    }

    public function ticketWorkflowsQuery(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): Collection {
        $qb = $this->applyOrderToBuilder($this->baseQb(), collect($args)->recursive());
        if ($ids = \data_get($args, 'filter.ids', \data_get($args, 'ids'))) {
            $qb->whereIn($this->withAlias('id'), $ids);
        }
        if (isset($args['filter']['isDeleted'])) {
            if ($args['filter']['isDeleted']) {
                $qb->whereNotNull($this->withAlias('deleted_at'));
            } else {
                $qb->whereNull($this->withAlias('deleted_at'));
            }
        }

        return $this->processResults(
            BufferedContext::createForBuilder($qb, $resolveInfo, ['ticketWorkflows', '']),
        );
    }

    public function createTicketWorkflowMutation(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        // create workflow
        $workflow = TicketWorkflow::create([
            'title' => \data_get($args, 'workflow.title'),
            'description' => \data_get($args, 'workflow.description', null),
        ]);

        return $this->ticketWorkflowQuery(
            $rootValue,
            ['id' => $workflow->getKey()],
            $context,
            $resolveInfo,
        );
    }

    public function updateTicketWorkflowMutation(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        $workflow = $this->findWorkflowById($args, key: 'id');

        $input = collect($args['workflow']);

        $workflow->title = $input->get('title');
        if ($input->has('description')) {
            $workflow->description = $input->get('description');
        }

        $workflow->save();

        return $this->ticketWorkflowQuery(
            $rootValue,
            ['id' => $workflow->getKey()],
            $context,
            $resolveInfo
        );
    }

    public function deleteTicketWorkflowMutation(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        $workflow = $this->findWorkflowById($args, key: 'id');

        if (!$workflow->trashed()) {
            $workflow->delete();
        }

        return $this->ticketWorkflowQuery(
            $rootValue,
            ['id' => $workflow->getKey()],
            $context,
            $resolveInfo
        );
    }

    public function restoreTicketWorkflowMutation(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        $workflow = $this->findWorkflowById($args, key: 'id');

        if ($workflow->trashed()) {
            $workflow->restore();
        }

        return $this->ticketWorkflowQuery(
            $rootValue,
            ['id' => $workflow->getKey()],
            $context,
            $resolveInfo
        );
    }

    public function createWorkflowDraftRevisionMutation(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        $workflow = $this->findWorkflowById($args, key: 'workflowId');

        // Handled by mutation validator
        // but another one check here just in case ...
        if (filled($workflow->draftRevision)) {
            throw new BadRequestException('Workflow already has draft revision');
        }

        DB::transaction(function () use ($args, $workflow) {
            $draftRevision = $workflow->draftRevision()->create([
                'person_id' => auth()->id(),
                'workflow_id' => $workflow->getKey(),
                'nodes' => \data_get($args, 'flowChart.nodes'),
                'edges' => \data_get($args, 'flowChart.edges'),
            ]);

            $workflow->draftRevision()->associate($draftRevision);

            $workflow->save();
        });

        return $this->ticketWorkflowQuery(
            $rootValue,
            ['id' => $workflow->getKey()],
            $context,
            $resolveInfo,
        );
    }

    public function updateWorkflowDraftRevisionMutation(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        $workflow = $this->findWorkflowById($args, key: 'workflowId');

        // Handled by mutation validator
        // but another one check here just in case ...
        if (!$workflow->draftRevision instanceof TicketWorkflowRevision) {
            throw new BadRequestException('Workflow does not have draft revision');
        }

        DB::transaction(function () use ($args, $workflow) {
            $draftRevision = $workflow->draftRevision;

            $draftRevision->nodes = \data_get($args, 'flowChart.nodes');
            $draftRevision->edges = \data_get($args, 'flowChart.edges');
            $workflow->updated_at = Carbon::now();

            $draftRevision->save();
            $workflow->save();
        });

        return $this->ticketWorkflowQuery(
            $rootValue,
            ['id' => $workflow->getKey()],
            $context,
            $resolveInfo,
        );
    }

    public function publishWorkflowDraftRevisionMutation(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): object {
        $workflow = $this->findWorkflowById($args, key: 'workflowId');

        // Handled by mutation validator
        // but another one check here just in case ...
        if (!$workflow->draftRevision instanceof TicketWorkflowRevision) {
            throw new BadRequestException('Workflow does not have draft revision');
        }

        $draftRevision = $workflow->draftRevision;

        $workflow->draftRevision()->dissociate();
        $workflow->activeRevision()->associate($draftRevision);
        $workflow->updated_at = Carbon::now();

        $workflow->save();

        return $this->ticketWorkflowQuery(
            $rootValue,
            ['id' => $workflow->getKey()],
            $context,
            $resolveInfo,
        );
    }

    public function processResults(BufferedContext $context): Collection
    {
        $this->loadManyToOne($context, TicketWorkflowRevisionsResolver::class, 'draftRevision', 'draft_revision_id');
        $this->loadManyToOne($context, TicketWorkflowRevisionsResolver::class, 'activeRevision', 'active_revision_id');

        $context->getCollection()->each(function ($workflow) {
            $workflow->allRevisions = function (
                $rootValue,
                array $args,
                GraphQLContext $context,
                ResolveInfo $resolveInfo
            ) use ($workflow) {
                // load paginated revisions list
                return $this->revisionsResolver->ticketWorkflowRevisionsQuery(
                    $rootValue,
                    [
                        'workflowId' => $workflow->id,
                        'page' => \data_get($args, 'page', 1),
                        'perPage' => \data_get($args, 'perPage'),
                        'sortOrder' => \data_get($args, 'sortOrder', 'DESC'),
                    ],
                    $context,
                    $resolveInfo,
                );
            };
        });

        return $context->getCollection();
    }

    public function baseQb(): Builder
    {
        return DB::table('ticket_wf as w')
            ->select('w.*')
        ;
    }

    public function baseQbPrimaryAlias(): string
    {
        return 'w';
    }

    protected function defaultSortOrder(): string
    {
        return 'asc';
    }

    /**
     * @throws \Throwable
     */
    private function findWorkflowById(array $args, string $key = 'workflowId'): TicketWorkflow
    {
        $workflow = TicketWorkflow::withTrashed()->find(\data_get($args, $key));
        throw_unless($workflow instanceof TicketWorkflow, NotFoundException::class);

        return $workflow;
    }
}
