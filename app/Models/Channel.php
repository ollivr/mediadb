<?php

namespace App\Models;

use App\Support\Scout\ChannelIndexConfigurator;
use App\Support\Scout\Rules\MultiMatchRule;
use App\Traits\Activityable;
use App\Traits\Hashidable;
use App\Traits\Randomable;
use App\Traits\Taggable;
use App\Traits\Viewable as ViewableHelpers;
use Cviebrock\EloquentSluggable\Sluggable;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;
use Multicaret\Acquaintances\Traits\CanSubscribe;
use ScoutElastic\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Tags\HasTags;

class Channel extends Model implements HasMedia, Viewable
{
    use Activityable;
    use CanSubscribe;
    use Hashidable;
    use HasStatuses;
    use HasTags;
    use InteractsWithMedia;
    use InteractsWithViews;
    use Notifiable;
    use Randomable;
    use Searchable;
    use Sluggable;
    use Taggable;
    use ViewableHelpers;

    /**
     * @var array
     */
    protected $casts = [
        'custom_properties' => 'json',
    ];

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var bool
     */
    protected $removeViewsOnDelete = true;

    /**
     * @var string
     */
    protected $indexConfigurator = ChannelIndexConfigurator::class;

    /**
     * @var array
     */
    protected $searchRules = [
        MultiMatchRule::class,
    ];

    /**
     * @var array
     */
    protected $mapping = [
        'properties' => [
            'name' => [
                'type' => 'text',
                'analyzer' => 'autocomplete',
                'search_analyzer' => 'autocomplete_search',
            ],
            'description' => [
                'type' => 'text',
                'analyzer' => 'autocomplete',
                'search_analyzer' => 'autocomplete_search',
            ],
        ],
    ];

    /**
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    /**
     * @return array
     */
    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'name',
            'description',
            'model_type',
            'model_id',
        ]);
    }

    /**
     * @return void
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return string
     */
    public static function getTagClassName(): string
    {
        return Tag::class;
    }

    /**
     * @return MorphToMany
     */
    public function tags()
    {
        return $this
            ->morphToMany(self::getTagClassName(), 'taggable', 'taggables', null, 'tag_id')
            ->orderBy('order_column');
    }

    /**
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('videos')
             ->useDisk('media');
    }

    /**
     * We need to define media conversion to use them.
     * Jobs do the actual conversion.
     *
     * @param Media $media
     */
    public function registerMediaConversions($media = null): void
    {
        $conversions = ['thumbnail', 'preview', 'sprite'];

        foreach ($conversions as $conversion) {
            $this->addMediaConversion($conversion)
             ->withoutManipulations()
             ->performOnCollections('no-conversions')
             ->nonQueued();
        }
    }

    /**
     * @return string
     */
    public function getThumbnailUrlAttribute(): string
    {
        $model = $this->media()->orderByDesc('created_at')->first();

        return $model ? $model->thumbnail_url : '';
    }

    /**
     * @return int
     */
    public function getItemsAttribute(): int
    {
        return $this->media->count();
    }
}
