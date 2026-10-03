<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page not found · OurNear</title>
        <style>
            body { margin: 0; font-family: system-ui, sans-serif; background: #f6f4f1; color: #1b1b1b; }
            main { max-width: 40rem; margin: 0 auto; padding: 4rem 1.5rem; }
            h1 { color: #214449; font-size: 2rem; }
            p { color: #5c5c5c; line-height: 1.5; }
            a { color: #F15A24; }
        </style>
    </head>
    <body>
        <main>
            <h1>Page not found</h1>
            <p>This page is not available. The role may have closed, or the link may be incorrect.</p>
            <p>
                <a href="{{ url('/') }}">Home</a>
                ·
                <a href="{{ url('/jobs') }}">Browse open roles</a>
            </p>
        </main>
    </body>
</html>
