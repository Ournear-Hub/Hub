<?php

namespace App\Support;

use App\Models\User;

trait AppendsEnquiryNotes
{
    /**
     * @param  list<array<string, mixed>>|null  $existing
     * @return list<array{body: string, author_id: int|null, author_name: string, created_at: string}>
     */
    protected function appendNote(?array $existing, string $body, User $author): array
    {
        $notes = array_values($existing ?? []);
        $notes[] = [
            'body' => $body,
            'author_id' => $author->id,
            'author_name' => $author->name,
            'created_at' => now()->toIso8601String(),
        ];

        return $notes;
    }
}
