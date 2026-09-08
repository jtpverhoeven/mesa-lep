<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mesa LIMS | Operations Console</title>

        @vite('resources/css/app.css')

        <style>
            :root {
                --canvas: #e9edf0;
                --surface: #ffffff;
                --surface-alt: #f4f6f7;
                --surface-deep: #dde3e6;
                --line: #c4cdd2;
                --line-strong: #9eabb2;
                --ink: #14232d;
                --ink-soft: #53636d;
                --accent: #126782;
                --accent-strong: #0a5169;
                --accent-faint: #d9edf2;
                --success: #28734e;
                --warning: #a36b13;
                --danger: #a84238;
                --control-highlight: #ffffff;
                --control-shadow: #8c989e;
                color-scheme: light;
            }

            [data-theme="dark"] {
                --canvas: #172127;
                --surface: #202c33;
                --surface-alt: #1b272d;
                --surface-deep: #304047;
                --line: #3c4d55;
                --line-strong: #596c75;
                --ink: #e5ecee;
                --ink-soft: #a9b8bd;
                --accent: #66bfd0;
                --accent-strong: #9adbe4;
                --accent-faint: #214750;
                --success: #80c89e;
                --warning: #e0b465;
                --danger: #ef8d80;
                --control-highlight: #46555d;
                --control-shadow: #12191d;
                color-scheme: dark;
            }

            .lims-modern {
                font-family: Tahoma, "MS Sans Serif", "Segoe UI", sans-serif;
                background: var(--canvas);
                color: var(--ink);
            }

            .lims-modern button,
            .lims-modern a,
            .lims-modern input {
                -webkit-tap-highlight-color: transparent;
            }

            .lims-modern ::selection {
                background: var(--accent-faint);
            }

            .lims-modern .nt-control {
                box-shadow: inset 1px 1px 0 var(--control-highlight), inset -1px -1px 0 var(--control-shadow);
            }
        </style>
    </head>
    <body class="lims-modern min-h-screen text-[12px] antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="z-10 flex h-10 shrink-0 items-stretch border-b border-[var(--line-strong)] bg-[var(--surface)]">
                <a href="{{ route('welcome') }}" class="flex shrink-0 items-center gap-2 border-r border-[var(--line)] px-3 font-semibold tracking-tight text-[var(--ink)]">
                    <span class="grid h-5 w-5 place-items-center bg-[var(--accent)] text-[10px] font-bold text-white">M</span>
                    <span>MESA <span class="font-normal text-[var(--ink-soft)]">/ LIMS</span></span>
                </a>

                <nav aria-label="Application sections" class="flex min-w-0 flex-1 overflow-x-auto">
                    <a href="#" class="flex shrink-0 items-center border-b-2 border-[var(--accent)] bg-[var(--accent-faint)] px-4 font-semibold text-[var(--accent-strong)]">Operations</a>
                    <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-4 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Accessioning</a>
                    <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-4 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Results</a>
                    <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-4 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Inventory</a>
                    <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-4 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Quality</a>
                    <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-4 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Administration</a>
                </nav>

                <div class="flex shrink-0 items-center gap-3 border-l border-[var(--line)] px-3">
                    <span class="hidden items-center gap-1.5 text-[11px] text-[var(--success)] md:flex"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>Connected</span>
                    <span class="hidden border-l border-[var(--line)] pl-3 text-[11px] text-[var(--ink-soft)] lg:block">A. Analyst</span>
                    <button id="modern-theme-toggle" type="button" class="nt-control grid h-6 w-6 place-items-center border border-[var(--line-strong)] bg-[var(--surface-alt)] text-[var(--ink-soft)] hover:border-[var(--accent)] hover:text-[var(--accent)]" aria-label="Toggle dark mode" title="Toggle dark mode">
                        <span aria-hidden="true" class="text-[13px] leading-none">D</span>
                    </button>
                </div>
            </header>

            <div class="relative flex min-h-0 flex-1 flex-col lg:flex-row">
                <aside class="z-[1] flex max-h-64 w-full shrink-0 flex-col overflow-y-auto border-b border-[var(--line-strong)] bg-[var(--surface)] lg:max-h-none lg:w-60 lg:border-b-0 lg:border-r">
                    <div class="border-b border-[var(--line)] px-3 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--ink-soft)]">Workspace</div>
                                <div class="mt-1 text-[15px] font-semibold tracking-tight">Operations</div>
                            </div>
                            <span class="border border-[var(--success)] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-[var(--success)]">Test</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-[var(--line)] pt-2 text-[10px] text-[var(--ink-soft)]">
                            <span>Site: Central Laboratory</span>
                            <span class="tabular-nums">v2.6.14</span>
                        </div>
                    </div>

                    <nav aria-label="Operations navigation" class="flex-1 py-2">
                        <div class="px-3 pb-1 pt-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--ink-soft)]">Monitoring</div>
                        <a href="#" class="flex items-center justify-between border-l-2 border-[var(--accent)] bg-[var(--accent-faint)] px-3 py-2 font-semibold text-[var(--accent-strong)]"><span>Operations console</span><span class="text-[10px]">01</span></a>
                        <a href="#" class="flex items-center justify-between border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]"><span>Exceptions</span><span class="font-semibold text-[var(--danger)]">02</span></a>
                        <a href="#" class="flex items-center justify-between border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]"><span>Turnaround time</span><span class="text-[10px]">07</span></a>

                        <div class="px-3 pb-1 pt-4 text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--ink-soft)]">Worklists</div>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">My assigned work</a>
                        <a href="#" class="flex items-center justify-between border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]"><span>Pending verification</span><span class="font-semibold text-[var(--warning)]">04</span></a>
                        <a href="#" class="flex items-center justify-between border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]"><span>Pending authorization</span><span class="font-semibold text-[var(--warning)]">04</span></a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Completed today</a>

                        <div class="px-3 pb-1 pt-4 text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--ink-soft)]">Actions</div>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Register new sample</a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Enter manual result</a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-soft)] hover:bg-[var(--surface-alt)] hover:text-[var(--ink)]">Print labels</a>
                    </nav>

                    <div class="border-t border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2.5 text-[10px] text-[var(--ink-soft)]">
                        <div class="mb-1 flex justify-between"><span>Last instrument sync</span><span class="tabular-nums text-[var(--ink)]">09:42:18</span></div>
                        <div class="flex justify-between"><span>Session expires</span><span class="tabular-nums text-[var(--ink)]">07:18:44</span></div>
                    </div>
                </aside>

                <main class="z-[1] min-w-0 flex-1 overflow-y-auto">
                    <div class="mx-auto max-w-[1680px] p-3 lg:p-4">
                        <div class="mb-3 flex flex-wrap items-end justify-between gap-3 border-b border-[var(--line-strong)] pb-3">
                            <div>
                                <div class="mb-1 text-[10px] uppercase tracking-[0.14em] text-[var(--ink-soft)]">Operations / Monitoring</div>
                                <h1 class="text-[21px] font-semibold tracking-tight text-[var(--ink)]">Operations console</h1>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="sr-only" for="global-search">Search records</label>
                                <div class="flex h-7 items-center border border-[var(--line-strong)] bg-[var(--surface)] px-2 text-[11px] text-[var(--ink-soft)] focus-within:border-[var(--accent)]">
                                    <span class="mr-2 text-[10px] font-bold">F3</span>
                                    <input id="global-search" type="search" placeholder="Search accession or patient..." class="w-40 bg-transparent outline-none placeholder:text-[var(--ink-soft)] sm:w-56">
                                </div>
                                <button type="button" class="nt-control h-7 border border-[var(--accent)] bg-[var(--accent)] px-3 text-[11px] font-semibold text-white hover:bg-[var(--accent-strong)]">New sample</button>
                            </div>
                        </div>

                        <section aria-label="Queue summary" class="mb-3 grid grid-cols-2 gap-px border border-[var(--line)] bg-[var(--line)] sm:grid-cols-4">
                            <div class="bg-[var(--surface)] px-3 py-2.5"><div class="flex items-center justify-between text-[10px] text-[var(--ink-soft)]"><span>Open accessions</span><span class="text-[var(--success)]">+8%</span></div><div class="mt-1 text-[22px] font-semibold tabular-nums">18</div><div class="text-[10px] text-[var(--ink-soft)]">6 due before noon</div></div>
                            <div class="bg-[var(--surface)] px-3 py-2.5"><div class="flex items-center justify-between text-[10px] text-[var(--ink-soft)]"><span>Awaiting review</span><span class="text-[var(--warning)]">Attention</span></div><div class="mt-1 text-[22px] font-semibold tabular-nums">04</div><div class="text-[10px] text-[var(--ink-soft)]">Oldest: 41 minutes</div></div>
                            <div class="bg-[var(--surface)] px-3 py-2.5"><div class="flex items-center justify-between text-[10px] text-[var(--ink-soft)]"><span>Received today</span><span class="text-[var(--ink-soft)]">Target 150</span></div><div class="mt-1 text-[22px] font-semibold tabular-nums">126</div><div class="text-[10px] text-[var(--ink-soft)]">84% of daily volume</div></div>
                            <div class="bg-[var(--surface)] px-3 py-2.5"><div class="flex items-center justify-between text-[10px] text-[var(--ink-soft)]"><span>Instrument alerts</span><span class="text-[var(--danger)]">Action</span></div><div class="mt-1 text-[22px] font-semibold tabular-nums">02</div><div class="text-[10px] text-[var(--ink-soft)]">1 reagent warning</div></div>
                        </section>

                        <section class="border border-[var(--line)] bg-[var(--surface)]">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2">
                                <div class="flex items-center gap-2"><h2 class="font-semibold">Active worklist</h2><span class="border border-[var(--line-strong)] bg-[var(--surface)] px-1.5 py-0.5 text-[10px] text-[var(--ink-soft)]">12 records</span></div>
                                <div class="flex items-center gap-2 text-[11px] text-[var(--ink-soft)]"><span>Updated 09:42:18</span><button type="button" class="nt-control border border-[var(--line-strong)] bg-[var(--surface)] px-2 py-1 font-semibold text-[var(--ink)] hover:border-[var(--accent)] hover:text-[var(--accent)]">Refresh</button></div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[850px] border-collapse text-left text-[11px]">
                                    <thead class="bg-[var(--surface-deep)] text-[10px] uppercase tracking-wider text-[var(--ink-soft)]"><tr><th class="border-b border-[var(--line)] px-3 py-2 font-semibold">Accession</th><th class="border-b border-[var(--line)] px-3 py-2 font-semibold">Patient / specimen</th><th class="border-b border-[var(--line)] px-3 py-2 font-semibold">Order</th><th class="border-b border-[var(--line)] px-3 py-2 font-semibold">Priority</th><th class="border-b border-[var(--line)] px-3 py-2 font-semibold">Due</th><th class="border-b border-[var(--line)] px-3 py-2 font-semibold">Status</th><th class="border-b border-[var(--line)] px-3 py-2 font-semibold">Owner</th></tr></thead>
                                    <tbody class="divide-y divide-[var(--line)]">
                                        <tr class="bg-[var(--accent-faint)]/60 hover:bg-[var(--accent-faint)]"><td class="whitespace-nowrap px-3 py-2.5 font-semibold text-[var(--accent-strong)]">ACC-260907-0142</td><td class="px-3 py-2.5"><span class="block font-semibold">P. Hart</span><span class="text-[10px] text-[var(--ink-soft)]">Serum / S-0142</span></td><td class="px-3 py-2.5">CMP panel</td><td class="px-3 py-2.5 font-semibold text-[var(--danger)]">STAT</td><td class="whitespace-nowrap px-3 py-2.5 font-semibold text-[var(--danger)]">10:14</td><td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--accent-strong)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>In progress</span></td><td class="px-3 py-2.5 text-[var(--ink-soft)]">A. Analyst</td></tr>
                                        <tr class="hover:bg-[var(--surface-alt)]"><td class="whitespace-nowrap px-3 py-2.5 font-semibold text-[var(--accent-strong)]">ACC-260907-0138</td><td class="px-3 py-2.5"><span class="block font-semibold">D. Okafor</span><span class="text-[10px] text-[var(--ink-soft)]">Whole blood / B-0138</span></td><td class="px-3 py-2.5">CBC + diff</td><td class="px-3 py-2.5 text-[var(--ink-soft)]">Routine</td><td class="whitespace-nowrap px-3 py-2.5 text-[var(--ink-soft)]">12:00</td><td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--warning)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>Pending review</span></td><td class="px-3 py-2.5 text-[var(--ink-soft)]">J. Mendez</td></tr>
                                        <tr class="hover:bg-[var(--surface-alt)]"><td class="whitespace-nowrap px-3 py-2.5 font-semibold text-[var(--accent-strong)]">ACC-260907-0135</td><td class="px-3 py-2.5"><span class="block font-semibold">L. Singh</span><span class="text-[10px] text-[var(--ink-soft)]">Serum / S-0135</span></td><td class="px-3 py-2.5">Creatinine</td><td class="px-3 py-2.5 text-[var(--ink-soft)]">Routine</td><td class="whitespace-nowrap px-3 py-2.5 text-[var(--ink-soft)]">12:00</td><td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--success)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>Complete</span></td><td class="px-3 py-2.5 text-[var(--ink-soft)]">A. Analyst</td></tr>
                                        <tr class="hover:bg-[var(--surface-alt)]"><td class="whitespace-nowrap px-3 py-2.5 font-semibold text-[var(--accent-strong)]">ACC-260907-0129</td><td class="px-3 py-2.5"><span class="block font-semibold">R. Alvarez</span><span class="text-[10px] text-[var(--ink-soft)]">Urine / U-0129</span></td><td class="px-3 py-2.5">Urinalysis</td><td class="px-3 py-2.5 text-[var(--ink-soft)]">Routine</td><td class="whitespace-nowrap px-3 py-2.5 text-[var(--ink-soft)]">11:30</td><td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--accent-strong)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>In progress</span></td><td class="px-3 py-2.5 text-[var(--ink-soft)]">R. Chen</td></tr>
                                        <tr class="hover:bg-[var(--surface-alt)]"><td class="whitespace-nowrap px-3 py-2.5 font-semibold text-[var(--accent-strong)]">ACC-260907-0124</td><td class="px-3 py-2.5"><span class="block font-semibold">M. Foster</span><span class="text-[10px] text-[var(--ink-soft)]">Serum / S-0124</span></td><td class="px-3 py-2.5">Lipid profile</td><td class="px-3 py-2.5 text-[var(--ink-soft)]">Routine</td><td class="whitespace-nowrap px-3 py-2.5 text-[var(--ink-soft)]">13:00</td><td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--success)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>Complete</span></td><td class="px-3 py-2.5 text-[var(--ink-soft)]">A. Analyst</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2 text-[10px] text-[var(--ink-soft)]"><span>Showing 1-5 of 12 records</span><div class="flex items-center gap-1"><button type="button" class="h-6 w-6 border border-[var(--line-strong)] hover:border-[var(--accent)] hover:text-[var(--accent)]" aria-label="Previous page">&lt;</button><span class="px-2 font-semibold text-[var(--ink)]">1 / 3</span><button type="button" class="h-6 w-6 border border-[var(--line-strong)] hover:border-[var(--accent)] hover:text-[var(--accent)]" aria-label="Next page">&gt;</button></div></div>
                        </section>

                        <div class="mt-3 grid gap-3 xl:grid-cols-[1.35fr_1fr]">
                            <section class="border border-[var(--line)] bg-[var(--surface)]"><div class="flex items-center justify-between border-b border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2"><h2 class="font-semibold">Instrument readiness</h2><a href="#" class="text-[10px] font-semibold text-[var(--accent-strong)] hover:underline">Open monitor</a></div><div class="grid divide-y divide-[var(--line)] sm:grid-cols-3 sm:divide-x sm:divide-y-0"><div class="px-3 py-3"><div class="flex items-center justify-between font-semibold"><span>Cobas c311</span><span class="h-1.5 w-1.5 rounded-full bg-[var(--success)]"></span></div><div class="mt-1 text-[10px] text-[var(--ink-soft)]">Chemistry / Ready</div><div class="mt-2 h-1 bg-[var(--surface-deep)]"><div class="h-1 w-[78%] bg-[var(--success)]"></div></div></div><div class="px-3 py-3"><div class="flex items-center justify-between font-semibold"><span>XN-1000</span><span class="h-1.5 w-1.5 rounded-full bg-[var(--warning)]"></span></div><div class="mt-1 text-[10px] text-[var(--warning)]">Hematology / Reagent low</div><div class="mt-2 h-1 bg-[var(--surface-deep)]"><div class="h-1 w-[34%] bg-[var(--warning)]"></div></div></div><div class="px-3 py-3"><div class="flex items-center justify-between font-semibold"><span>ACL TOP 550</span><span class="h-1.5 w-1.5 rounded-full bg-[var(--success)]"></span></div><div class="mt-1 text-[10px] text-[var(--ink-soft)]">Coagulation / Ready</div><div class="mt-2 h-1 bg-[var(--surface-deep)]"><div class="h-1 w-[91%] bg-[var(--success)]"></div></div></div></div></section>
                            <section class="border border-[var(--line)] bg-[var(--surface)]"><div class="flex items-center justify-between border-b border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2"><h2 class="font-semibold">System activity</h2><a href="#" class="text-[10px] font-semibold text-[var(--accent-strong)] hover:underline">Audit log</a></div><div class="divide-y divide-[var(--line)] text-[10px]"><div class="flex items-center justify-between gap-3 px-3 py-2"><span><strong class="font-semibold">J. Mendez</strong> authorized ACC-260907-0118</span><span class="shrink-0 tabular-nums text-[var(--ink-soft)]">09:38</span></div><div class="flex items-center justify-between gap-3 px-3 py-2"><span><strong class="font-semibold">Interface</strong> imported 24 results</span><span class="shrink-0 tabular-nums text-[var(--ink-soft)]">09:31</span></div><div class="flex items-center justify-between gap-3 px-3 py-2"><span><strong class="font-semibold">R. Chen</strong> registered ACC-260907-0142</span><span class="shrink-0 tabular-nums text-[var(--ink-soft)]">08:14</span></div></div></section>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            const modernThemeToggle = document.getElementById('modern-theme-toggle');
            const storedModernTheme = window.localStorage.getItem('mesa-lims-theme-modern');
            const prefersModernDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const initialModernTheme = storedModernTheme || (prefersModernDark ? 'dark' : 'light');

            document.documentElement.dataset.theme = initialModernTheme;

            modernThemeToggle.addEventListener('click', () => {
                const nextModernTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';

                document.documentElement.dataset.theme = nextModernTheme;
                window.localStorage.setItem('mesa-lims-theme-modern', nextModernTheme);
            });
        </script>
    </body>
</html>
