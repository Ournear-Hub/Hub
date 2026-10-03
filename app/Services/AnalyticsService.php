<?php

namespace App\Services;

use Illuminate\Http\Request;

class AnalyticsService
{
    /**
     * @return array{enabled: bool, id: string|null}
     */
    public function publicPayload(Request $request): array
    {
        if ($this->isPrivateRequest($request)) {
            return [
                'enabled' => false,
                'id' => null,
            ];
        }

        $id = $this->sanitizedId();
        $enabled = (bool) config('analytics.enabled') && $id !== null;

        return [
            'enabled' => $enabled,
            'id' => $enabled ? $id : null,
        ];
    }

    public function isPrivateRequest(Request $request): bool
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return true;
        }

        if ($request->is('login', 'register', 'forgot-password', 'account', 'account/*')) {
            return true;
        }

        if ($request->is('reset-password') || $request->is('reset-password/*')) {
            return true;
        }

        return $request->routeIs(
            'jobs.apply',
            'jobs.apply.store',
            'login',
            'login.store',
            'register',
            'register.store',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
            'logout',
            'account.*',
        );
    }

    public function sanitizedId(): ?string
    {
        $id = strtoupper(trim((string) config('analytics.id')));

        if ($id === '' || preg_match('/^[A-Z]{1,3}-[A-Z0-9]+$/', $id) !== 1) {
            return null;
        }

        return $id;
    }
}
