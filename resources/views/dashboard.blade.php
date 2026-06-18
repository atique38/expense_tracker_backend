<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} - Expense Tracker</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <style>
            :root {
                color-scheme: dark;
            }

            body {
                margin: 0;
                background:
                    radial-gradient(circle at top, rgba(34, 211, 238, 0.18), transparent 30%),
                    radial-gradient(circle at 85% 20%, rgba(16, 185, 129, 0.14), transparent 26%),
                    linear-gradient(180deg, #020617 0%, #0f172a 100%);
                color: #e2e8f0;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            }
        </style>

        @php
            $currentPath = request()->path() ?: '/';
        @endphp

        <script>
            window.expenseTrackerConfig = {
                apiBaseUrl: @json(url('/api')),
                currentPath: @json($currentPath),
            };
        </script>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen antialiased text-slate-100">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -left-32 top-0 h-72 w-72 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="absolute right-0 top-24 h-96 w-96 rounded-full bg-emerald-400/10 blur-3xl"></div>
        </div>

        <main id="app" class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col justify-center px-6 py-12 lg:px-10">
            <div class="mb-6 inline-flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300 backdrop-blur">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Expense tracker shell
            </div>

            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="space-y-6">
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Track income, expenses, accounts, and budgets from one dashboard.
                    </h1>

                    <p class="max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">
                        The web routes now resolve to a dedicated dashboard shell, and the API exposes the user-scoped expense tracker endpoints behind a clean base URL.
                        Mount the Vue UI here and point requests at the routes below.
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <a href="#api-endpoints" class="rounded-full bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                            View endpoints
                        </a>
                        <a href="#web-routes" class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            Web routes
                        </a>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Current path</p>
                            <p class="mt-3 text-lg font-medium text-white">{{ $currentPath }}</p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">API base</p>
                            <p class="mt-3 text-lg font-medium text-white">{{ url('/api') }}</p>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Modules</p>
                            <p class="mt-3 text-lg font-medium text-white">4 CRUD resources</p>
                        </div>
                    </div>
                </section>

                <aside class="space-y-4">
                    <div id="web-routes" class="rounded-3xl border border-white/10 bg-slate-950/70 p-6 shadow-2xl shadow-cyan-950/10 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Web routes</p>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 font-mono text-slate-300">GET /</li>
                            <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 font-mono text-slate-300">GET /dashboard</li>
                            <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 font-mono text-slate-300">GET /accounts</li>
                            <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 font-mono text-slate-300">GET /categories</li>
                            <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 font-mono text-slate-300">GET /transactions</li>
                            <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 font-mono text-slate-300">GET /budgets</li>
                        </ul>
                    </div>

                    <div id="api-endpoints" class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">API endpoints</p>
                        <ul class="mt-4 space-y-3 text-sm text-slate-300">
                            <li class="rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3">
                                <span class="font-medium text-white">Auth</span>
                                <div class="mt-1 font-mono text-slate-400">POST /api/login</div>
                            </li>
                            <li class="rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3">
                                <span class="font-medium text-white">Dashboard</span>
                                <div class="mt-1 font-mono text-slate-400">GET /api/users/{user}/dashboard</div>
                            </li>
                            <li class="rounded-2xl border border-white/10 bg-slate-950/70 px-4 py-3">
                                <span class="font-medium text-white">Categories, accounts, transactions, budgets</span>
                                <div class="mt-1 font-mono text-slate-400">Standard CRUD routes under /api/users/{user}/...</div>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </main>
    </body>
</html>
