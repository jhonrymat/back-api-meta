<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMedia
{
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot(['tag', 'order'])
            ->withTimestamps()
            ->orderBy('order');
    }

    public function attachMedia($mediaId, string $tag = null, int $order = 0)
    {
        return $this->media()->attach($mediaId, [
            'tag' => $tag,
            'order' => $order,
        ]);
    }

    public function detachMedia($mediaId)
    {
        return $this->media()->detach($mediaId);
    }

    public function syncMedia(array $mediaIds, string $tag = null)
    {
        if ($tag) {
            $existing = $this->media()->wherePivot('tag', $tag)->pluck('media.id')->toArray();
            $this->media()->wherePivot('tag', $tag)->detach();
        }

        foreach ($mediaIds as $index => $mediaId) {
            $this->attachMedia($mediaId, $tag, $index);
        }
    }

    public function getMediaByTag(string $tag)
    {
        return $this->media()->wherePivot('tag', $tag)->get();
    }

    public function getFirstMediaByTag(string $tag)
    {
        return $this->media()->wherePivot('tag', $tag)->first();
    }
}
