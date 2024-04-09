<?php

declare(strict_types=1);

namespace App\API\Tickets;

use Company\Library\GraphQL\GetSingleRecordTrait;
use Company\Library\GraphQL\OrderByTrait;
use Company\Library\GraphQL\PaginateResponseTrait;
use Company\Library\GraphQL\Resolve\BufferedContext;
use Company\Library\GraphQL\Resolve\OwnLoaderTrait;
use Company\Library\GraphQL\Resolve\ProcessResultsInterface;
use Company\Library\GraphQL\Resolve\SubCollectionLoaderTrait;
use Company\Library\GraphQL\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TicketWorkflowRevisionsResolver implements Resolver, ProcessResultsInterface
{
    use OwnLoaderTrait;
    use SubCollectionLoaderTrait;
    use GetSingleRecordTrait;
    use PaginateResponseTrait;
    use OrderByTrait;

    public function ticketWorkflowRevisionsQuery(
        $rootValue,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): array {
        $qb = $this->baseQb()->where('r.workflow_id', $args['workflowId'] ?? 0);
        $qb = $this->applyOrderToBuilder($qb, collect($args)->recursive());

        return $this->paginateResponse($qb, $args, $resolveInfo, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function processResults(BufferedContext $context): Collection
    {
        $context->getCollection()->each(function ($revision) {
            $revision->flowChart = [
                'nodes' => $revision->nodes,
                'edges' => $revision->edges,
            ];
        });

        return $context->getCollection();
    }

    public function baseQb(): Builder
    {
        return DB::table('ticket_wf_revisions as r')
            ->select('r.*');
    }

    /**
     * {@inheritDoc}
     */
    public function baseQbPrimaryAlias(): string
    {
        return 'r';
    }
}
