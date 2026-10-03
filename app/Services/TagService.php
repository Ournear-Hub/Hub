<?php

namespace App\Services;

use App\Enums\SecurityActivityType;
use App\Enums\UserActivityType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TagService
{
    public function __construct(
        private SecurityAuditService $audit,
        private UserActivityService $activities,
    ) {}

    public function create(User $actor, string $name): Tag
    {
        $name = Tag::normalizeName($name);
        $this->assertUniqueName($name);

        $tag = Tag::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'is_active' => true,
        ]);

        $this->audit->record(
            SecurityActivityType::TagCreated,
            'Tag "'.$tag->name.'" was created.',
            $actor,
            $tag,
        );

        return $tag;
    }

    public function rename(User $actor, Tag $tag, string $name): Tag
    {
        $name = Tag::normalizeName($name);
        $this->assertUniqueName($name, $tag->id);

        $previous = $tag->name;
        $tag->fill([
            'name' => $name,
            'slug' => $this->uniqueSlug($name, $tag->id),
        ])->save();

        $this->audit->record(
            SecurityActivityType::TagUpdated,
            'Tag "'.$previous.'" was renamed to "'.$tag->name.'".',
            $actor,
            $tag,
            ['from' => $previous, 'to' => $tag->name],
        );

        return $tag->fresh() ?? $tag;
    }

    public function setActive(User $actor, Tag $tag, bool $active): Tag
    {
        if ((bool) $tag->is_active === $active) {
            return $tag;
        }

        $tag->forceFill(['is_active' => $active])->save();

        $this->audit->record(
            SecurityActivityType::TagStatusChanged,
            'Tag "'.$tag->name.'" was '.($active ? 'activated' : 'deactivated').'.',
            $actor,
            $tag,
            ['is_active' => $active],
        );

        return $tag->fresh() ?? $tag;
    }

    /**
     * @param  list<int>  $tagIds
     */
    public function syncUserTags(User $actor, User $target, array $tagIds): User
    {
        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));
        $tags = Tag::query()->whereIn('id', $tagIds)->get();

        if ($tags->count() !== count($tagIds)) {
            throw ValidationException::withMessages([
                'tag_ids' => 'One or more selected tags are invalid.',
            ]);
        }

        $previous = $target->tags()->pluck('tags.id')->sort()->values()->all();
        $inactive = $tags->first(
            fn (Tag $tag) => ! $tag->is_active && ! in_array($tag->id, $previous, true)
        );
        if ($inactive instanceof Tag) {
            throw ValidationException::withMessages([
                'tag_ids' => 'Inactive tags cannot be assigned.',
            ]);
        }
        $next = $tags->pluck('id')->sort()->values()->all();

        $target->tags()->sync($tags->pluck('id')->all());

        if ($previous === $next) {
            return $target->fresh(['tags']) ?? $target;
        }

        $assigned = array_values(array_diff($next, $previous));
        $removed = array_values(array_diff($previous, $next));

        if ($assigned !== []) {
            $names = $tags->whereIn('id', $assigned)->pluck('name')->all();
            $this->activities->record(
                $target,
                UserActivityType::UpdatedProfile,
                'Tags assigned: '.implode(', ', $names).'.',
                $actor,
                ['assigned_tag_ids' => $assigned],
            );
            $this->audit->record(
                SecurityActivityType::TagAssigned,
                'Tags assigned to '.$target->email.'.',
                $actor,
                $target,
                ['tag_ids' => $assigned],
            );
        }

        if ($removed !== []) {
            $removedNames = Tag::query()->whereIn('id', $removed)->pluck('name')->all();
            $this->activities->record(
                $target,
                UserActivityType::UpdatedProfile,
                'Tags removed: '.implode(', ', $removedNames).'.',
                $actor,
                ['removed_tag_ids' => $removed],
            );
            $this->audit->record(
                SecurityActivityType::TagRemoved,
                'Tags removed from '.$target->email.'.',
                $actor,
                $target,
                ['tag_ids' => $removed],
            );
        }

        return $target->fresh(['tags']) ?? $target;
    }

    private function assertUniqueName(string $name, ?int $ignoreId = null): void
    {
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'A tag name is required.',
            ]);
        }

        $query = Tag::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A tag with this name already exists.',
            ]);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Tag::slugFromName($name);
        $slug = $base;
        $i = 2;

        while (Tag::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
