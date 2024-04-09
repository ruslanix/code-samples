<?php

declare(strict_types=1);

namespace Company\Library\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Company\Library\Models\TicketWorkflowRevision.
 *
 * @property int                                    $id
 * @property \Company\Library\Models\TicketWorkflow $workflow
 * @property \Company\Library\Models\Person         $person
 * @property string|null                            $start_node_id
 * @property Collection|null                        $nodes
 * @property Collection|null                        $edges
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision whereId($value)
 *
 * @property int                        $workflow_id
 * @property int|null                   $person_id
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision whereEdges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision whereNodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision wherePersonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision whereStartNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflowRevision whereWorkflowId($value)
 *
 * @mixin \Eloquent
 */
final class TicketWorkflowRevision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ticket_wf_revisions';

    /**
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    protected $casts = [
        'nodes' => AsCollection::class,
        'edges' => AsCollection::class,
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(TicketWorkflow::class, 'workflow_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function flowChart(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'nodes' => $this->nodes->toJson(),
                'edges' => $this->edges->toJson(),
            ],
        );
    }
}
