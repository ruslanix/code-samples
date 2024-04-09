<?php

declare(strict_types=1);

namespace Company\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Company\Library\Models\TicketWorkflow.
 *
 * @property int                                                                                       $id
 * @property string                                                                                    $title
 * @property string                                                                                    $description
 * @property int|null                                                                                  $active_revision_id
 * @property int|null                                                                                  $draft_revision_id
 * @property Carbon                                                                                    $updated_at
 * @property \Company\Library\Models\TicketWorkflowRevision|null                                       $activeRevision
 * @property \Company\Library\Models\TicketWorkflowRevision|null                                       $draftRevision
 * @property \Illuminate\Database\Eloquent\Collection|\Company\Library\Models\TicketWorkflowRevision[] $revisions
 * @property bool                                                                                      $is_disabled
 * @property int|null                                                                                  $num_revisions
 * @property \Closure|null                                                                             $allRevisions
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereTitle($value)
 *
 * @property Carbon      $created_at
 * @property Carbon|null $deleted_at
 * @property int|null    $revisions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereActiveRevisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereDraftRevisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketWorkflow withoutTrashed()
 *
 * @mixin \Eloquent
 */
final class TicketWorkflow extends Model
{
    use SoftDeletes;

    protected $table = 'ticket_wf';

    protected $guarded = ['id'];

    public function activeRevision(): BelongsTo
    {
        return $this->belongsTo(TicketWorkflowRevision::class, 'active_revision_id');
    }

    public function draftRevision(): BelongsTo
    {
        return $this->belongsTo(TicketWorkflowRevision::class, 'draft_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TicketWorkflowRevision::class, 'workflow_id');
    }
}
