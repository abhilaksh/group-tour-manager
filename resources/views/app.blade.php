<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Group Tour Manager') }}</title>
    @php
        $manifestPath = public_path('dist/.vite/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $entrypoint = $manifest['src/Main.jsx'] ?? null;
        } else {
            $entrypoint = null;
        }
    @endphp
    @if($entrypoint)
        @foreach($entrypoint['css'] ?? [] as $css)
            <link rel="stylesheet" href="/dist/{{ $css }}">
        @endforeach
    @endif
</head>
<body>
    <div id="root"></div>
    @if($entrypoint)
        <script type="module" src="/dist/{{ $entrypoint['file'] }}"></script>
    @else
        <div style="padding: 40px; text-align: center; font-family: sans-serif;">
            <h1>Frontend Not Built</h1>
            <p>The frontend assets have not been built yet. Please run the installer or build the frontend manually.</p>
            <a href="/install.php" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">Run Installer</a>
        </div>
    @endif
</body>
</html>
