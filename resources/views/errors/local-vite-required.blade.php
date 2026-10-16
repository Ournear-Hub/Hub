<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Frontend dev server required</title>
        <style>
            body { font-family: system-ui, sans-serif; margin: 2rem; line-height: 1.5; color: #214449; max-width: 42rem; }
            code { background: #f4f4f4; padding: 0.15rem 0.35rem; border-radius: 4px; }
            pre { background: #214449; color: #fff; padding: 1rem; border-radius: 8px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>Homepage assets are stale</h1>
        <p>
            This Laravel app is serving an outdated or incomplete <code>public/build</code> bundle while
            <code>resources/js/Pages/Home.tsx</code> has been updated. The Vite dev server is not reachable, so the browser cannot load the current React source.
        </p>
        @if (! empty($missingChunks))
            <p>Missing build chunks referenced by the app bundle:</p>
            <ul>
                @foreach ($missingChunks as $chunk)
                    <li><code>{{ $chunk }}</code></li>
                @endforeach
            </ul>
        @endif
        @if (empty($manifestListsPublicHome))
            <p>
                <code>public/build/manifest.json</code> does not include an entry for
                <code>resources/js/Pages/Home.tsx</code> (only the built app script still lazy-loads the old homepage chunk).
            </p>
        @endif
        <p>Project root (confirm artisan and Vite run from the same folder):</p>
        <pre>{{ $projectPath }}</pre>
        <p>From that folder, start the full local stack (recommended):</p>
        <pre>composer dev</pre>
        <p>Or use the web server helper (starts Vite and syncs <code>public/hot</code>):</p>
        <pre>powershell -File .\scripts\start-local-web-server.ps1</pre>
        <p>Production-style assets require a full Vite build after homepage changes:</p>
        <pre>npm run build</pre>
        <p><small>(Build not run in this environment — deferred by prod.)</small></p>
        <p>Hard-refresh <code>http://127.0.0.1:8000/</code>. Network tab should show scripts from <code>127.0.0.1:5173</code>, not only <code>/build/assets/</code>.</p>
    </body>
</html>
