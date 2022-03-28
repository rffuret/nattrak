<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\RclMessage
 *
 * @property int $id
 * @property int $vatsim_account_id
 * @property string $callsign
 * @property string $destination
 * @property string $flight_level
 * @property string $mach
 * @property int|null $track_id
 * @property string|null $random_routeing
 * @property string $entry_fix
 * @property string $entry_time
 * @property string|null $tmi
 * @property string $request_time
 * @property string|null $free_text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $clx_message_id
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereCallsign($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereClxMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereEntryFix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereEntryTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereFlightLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereFreeText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereMach($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereRandomRouteing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereRequestTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereTmi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereTrackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereVatsimAccountId($value)
 * @mixin \Eloquent
 * @property string|null $max_flight_level
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ClxMessage[] $clxMessages
 * @property-read int|null $clx_messages_count
 * @property-read \App\Models\Track|null $track
 * @property-read \App\Models\VatsimAccount $vatsimAccount
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage whereMaxFlightLevel($value)
 * @property-read mixed $data_link_message
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage cleared()
 * @method static \Illuminate\Database\Eloquent\Builder|RclMessage pending()
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Query\Builder|RclMessage onlyTrashed()
 * @method static Builder|RclMessage whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|RclMessage withTrashed()
 * @method static \Illuminate\Database\Query\Builder|RclMessage withoutTrashed()
 * @method static \Database\Factories\RclMessageFactory factory(...$parameters)
 * @method static Builder|RclMessage requestedRandomRouteing()
 * @method static Builder|RclMessage requestedTrack(\App\Models\Track $track)
 */
class RclMessage extends Model
{
    use LogsActivity, SoftDeletes, HasFactory;

    /**
     * Activity log options
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('rcl');
    }

    /**
     * Mass assignable attributes.
     *
     * @var string[]
     */
    protected $fillable = [
        'vatsim_account_id', 'callsign', 'destination', 'flight_level', 'max_flight_level', 'mach', 'track_id', 'random_routeing', 'entry_fix', 'entry_time', 'tmi', 'request_time', 'free_text'
    ];

    /**
     * Attributes casted as date/times
     *
     * @var string[]
     */
    protected $dates = [
        'request_time'
    ];

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('clx_message_id', null);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeCleared(Builder $query): Builder
    {
        return $query->where('clx_message_id', '!=', null);
    }

    /**
     * Scope to messages for a specific track.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRequestedTrack(Builder $query, Track $track): Builder
    {
        return $query->where('track_id', $track->id)->where('random_routeing', null);
    }

    /**
     * Scope to messages for a random routeing.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRequestedRandomRouteing(Builder $query): Builder
    {
        return $query->where('random_routeing', '!=', null)->where('track_id', null);
    }

    /**
     * Returns the VATSIM account this RCL message was transmitted by
     *
     * @return BelongsTo
     */
    public function vatsimAccount(): BelongsTo
    {
        return $this->belongsTo(VatsimAccount::class);
    }

    /**
     * Returns the CLX messages in reply to this message.
     *
     * @return HasMany
     */
    public function clxMessages(): HasMany
    {
        return $this->hasMany(ClxMessage::class);
    }

    /**
     * Returns the track this was a request for.
     *
     * @return BelongsTo
     */
    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    /**
     * Returns the formatted datalink message string.
     *
     * @return string
     */
    public function getDataLinkMessageAttribute(): string
    {
        if ($this->track) {
            return "{$this->callsign} REQ CLRNCE {$this->destination} VIA {$this->entry_fix}/{$this->entry_time} TRACK {$this->track->identifier} F{$this->flight_level} M{$this->mach} MAX F{$this->max_flight_level} TMI {$this->tmi}";
        } else {
            return "{$this->callsign} REQ CLRNCE {$this->destination} VIA {$this->entry_fix}/{$this->entry_time} {$this->random_routeing} F{$this->flight_level} M{$this->mach} MAX F{$this->max_flight_level} TMI {$this->tmi}";
        }
    }
}