<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicProfileShareUrlTest extends TestCase
{
    public function test_private_profile_does_not_expose_share_url(): void
    {
        config(['sharing.public_base_url' => 'https://ournear.app']);

        $candidate = $this->makeCandidate([
            'username' => 'private-share-user',
        ]);
        $candidate->candidateProfile?->update(['public_share' => ['enabled' => false]]);

        $this->get(route('profiles.show', ['user' => 'private-share-user']))
            ->assertNotFound();
    }

    public function test_public_profile_includes_canonical_share_url(): void
    {
        config(['sharing.public_base_url' => 'https://ournear.app']);

        $candidate = $this->makeCandidate([
            'username' => 'public-share-user',
        ]);
        $candidate->candidateProfile?->update(['public_share' => ['enabled' => true]]);

        $this->get(route('profiles.show', ['user' => 'public-share-user']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('profile.share_url', 'https://ournear.app/profiles/public-share-user'));
    }
}
