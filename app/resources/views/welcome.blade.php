<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'NeAnime' }}</title>
    <link rel="stylesheet" href="/css/tailwind.css">
  </head>
  <body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col items-center justify-center p-6">
    <main class="w-full max-w-3xl text-center space-y-6">
      <header class="space-y-2">
        <p class="text-sm font-semibold uppercase tracking-widest text-sky-300">Laravel Lumen Starter</p>
        <h1 class="text-4xl font-bold sm:text-5xl">✨ {{ $title ?? 'NeAnime' }}</h1>
      </header>
      <p class="text-lg text-slate-300">
        It works on <span class="font-semibold text-sky-300">Apache</span>, <span class="font-semibold text-sky-300">PHP</span>,
        <span class="font-semibold text-sky-300">Lumen</span>, and <span class="font-semibold text-sky-300">Blade</span>.
      </p>
    </main>
  </body>
</html>
