<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Group Tour Manager') }}</title>
    @viteReactRefresh
    @vite('src/Main.jsx', 'dist')
</head>
<body>
    <div id="root"></div>
</body>
</html>
