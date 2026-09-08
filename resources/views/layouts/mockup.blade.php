<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mesa LIMS | Workbench</title>

        @vite('resources/css/app.css')

        <style>
            :root {
                --canvas: #eef1f3;
                --surface: #ffffff;
                --surface-muted: #f6f8f9;
                --surface-raised: #fbfcfc;
                --line: #cbd2d7;
                --line-strong: #aeb8bf;
                --ink: #172027;
                --ink-muted: #5c6871;
                --accent: #16637b;
                --accent-soft: #d9edf2;
                --positive: #236b4c;
                --warning: #9a6415;
                --negative: #a33c32;
                color-scheme: light;
            }

            [data-theme="dark"] {
                --canvas: #1b2226;
                --surface: #242c31;
                --surface-muted: #20282d;
                --surface-raised: #2a3338;
                --line: #3c484f;
                --line-strong: #536169;
                --ink: #e3e9eb;
                --ink-muted: #a5b1b7;
                --accent: #6bc0d0;
                --accent-soft: #214851;
                --positive: #78c59f;
                --warning: #e2b364;
                --negative: #ee887c;
                color-scheme: dark;
            }

            .lims-mockup {
                font-family: Tahoma, "Segoe UI", sans-serif;
                background: var(--canvas);
                color: var(--ink);
            }

            .lims-mockup button,
            .lims-mockup a {
                -webkit-tap-highlight-color: transparent;
            }

            .lims-mockup ::selection {
                background: var(--accent-soft);
            }
        </style>
    </head>
    <body class="lims-mockup min-h-screen text-[13px] antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="shrink-0 border-b border-[var(--line-strong)] bg-[var(--surface)]">
                <div class="flex h-12 items-center gap-4 px-3">
                    <a href="{{ route('welcome') }}" class="flex shrink-0 items-center gap-2 border-r border-[var(--line)] pr-4 text-[15px] font-bold tracking-tight text-[var(--ink)]">
                        <span class="grid h-6 w-6 place-items-center bg-[var(--accent)] text-[11px] font-bold text-white">M</span>
                        Mesa LIMS
                    </a>

                    <nav aria-label="Application sections" class="flex min-w-0 flex-1 items-stretch self-stretch overflow-x-auto">
                        <a href="#" class="flex shrink-0 items-center border-b-2 border-[var(--accent)] px-3 font-semibold text-[var(--accent)]">Workbench</a>
                        <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-3 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Samples</a>
                        <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-3 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Results</a>
                        <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-3 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Inventory</a>
                        <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-3 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Reports</a>
                        <a href="#" class="flex shrink-0 items-center border-b-2 border-transparent px-3 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Administration</a>
                    </nav>

                    <div class="flex shrink-0 items-center gap-3 border-l border-[var(--line)] pl-3">
                        <span class="hidden text-right leading-tight sm:block">
                            <span class="block font-semibold text-[var(--ink)]">A. Analyst</span>
                            <span class="block text-[11px] text-[var(--ink-muted)]">Central Laboratory</span>
                        </span>
                        <button id="theme-toggle" type="button" class="grid h-7 w-7 place-items-center border border-[var(--line-strong)] bg-[var(--surface-muted)] text-[var(--ink-muted)] hover:border-[var(--accent)] hover:text-[var(--accent)]" aria-label="Toggle dark mode" title="Toggle dark mode">
                            <span aria-hidden="true" class="text-[15px] leading-none">◐</span>
                        </button>
                    </div>
                </div>
            </header>

            <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
                <aside class="flex max-h-72 w-full shrink-0 flex-col overflow-y-auto border-b border-[var(--line-strong)] bg-[var(--surface)] lg:max-h-none lg:w-56 lg:border-b-0 lg:border-r">
                    <div class="border-b border-[var(--line)] px-3 py-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[var(--ink-muted)]">Current section</span>
                            <span class="h-2 w-2 bg-[var(--positive)]" title="System online"></span>
                        </div>
                        <div class="font-bold text-[var(--ink)]">Workbench</div>
                        <div class="mt-1 text-[11px] text-[var(--ink-muted)]">Routine laboratory operations</div>
                    </div>

                    <nav aria-label="Workbench navigation" class="flex-1 py-2">
                        <div class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-wider text-[var(--ink-muted)]">Operations</div>
                        <a href="#" class="flex items-center justify-between border-l-2 border-[var(--accent)] bg-[var(--accent-soft)] px-3 py-2 font-semibold text-[var(--accent)]">
                            <span>Overview</span><span class="text-[11px]">01</span>
                        </a>
                        <a href="#" class="flex items-center justify-between border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">
                            <span>My worklist</span><span class="text-[11px]">12</span>
                        </a>
                        <a href="#" class="flex items-center justify-between border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">
                            <span>Pending review</span><span class="text-[11px] text-[var(--warning)]">04</span>
                        </a>
                        <a href="#" class="flex items-center justify-between border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">
                            <span>Exceptions</span><span class="text-[11px] text-[var(--negative)]">02</span>
                        </a>

                        <div class="px-3 pb-1 pt-4 text-[10px] font-bold uppercase tracking-wider text-[var(--ink-muted)]">Quick access</div>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Register sample</a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Scan accession</a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Enter result</a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Print labels</a>

                        <div class="px-3 pb-1 pt-4 text-[10px] font-bold uppercase tracking-wider text-[var(--ink-muted)]">Saved views</div>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Chemistry queue</a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Today's accessions</a>
                        <a href="#" class="block border-l-2 border-transparent px-3 py-2 text-[var(--ink-muted)] hover:bg-[var(--surface-muted)] hover:text-[var(--ink)]">Awaiting authorization</a>
                    </nav>

                    <div class="border-t border-[var(--line)] p-3 text-[11px] text-[var(--ink-muted)]">
                        <div class="flex justify-between"><span>Environment</span><span class="font-semibold text-[var(--positive)]">TEST</span></div>
                        <div class="mt-1 flex justify-between"><span>Last sync</span><span>09:42:18</span></div>
                    </div>
                </aside>

                <main class="min-w-0 flex-1 overflow-y-auto">
                    <div class="mx-auto max-w-[1600px] p-4 lg:p-5">
                        <div class="mb-4 flex flex-wrap items-end justify-between gap-3 border-b border-[var(--line)] pb-3">
                            <div>
                                <div class="mb-1 text-[11px] text-[var(--ink-muted)]">Workbench / Overview</div>
                                <h1 class="text-xl font-bold tracking-tight text-[var(--ink)]">Laboratory workbench</h1>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-[var(--ink-muted)]">
                                <span class="h-2 w-2 bg-[var(--positive)]"></span>
                                <span>All services operational</span>
                                <span class="mx-1 text-[var(--line-strong)]">|</span>
                                <span>Monday, 07 September 2026</span>
                            </div>
                        </div>

                        <section aria-label="Queue summary" class="mb-4 grid grid-cols-2 gap-px border border-[var(--line)] bg-[var(--line)] sm:grid-cols-4">
                            <div class="bg-[var(--surface)] px-3 py-3">
                                <div class="text-[11px] text-[var(--ink-muted)]">Open work items</div>
                                <div class="mt-1 text-2xl font-bold tabular-nums text-[var(--ink)]">18</div>
                                <div class="mt-1 text-[11px] text-[var(--positive)]">6 due today</div>
                            </div>
                            <div class="bg-[var(--surface)] px-3 py-3">
                                <div class="text-[11px] text-[var(--ink-muted)]">Pending authorization</div>
                                <div class="mt-1 text-2xl font-bold tabular-nums text-[var(--ink)]">04</div>
                                <div class="mt-1 text-[11px] text-[var(--warning)]">2 high priority</div>
                            </div>
                            <div class="bg-[var(--surface)] px-3 py-3">
                                <div class="text-[11px] text-[var(--ink-muted)]">Samples received</div>
                                <div class="mt-1 text-2xl font-bold tabular-nums text-[var(--ink)]">126</div>
                                <div class="mt-1 text-[11px] text-[var(--ink-muted)]">Since 00:00 today</div>
                            </div>
                            <div class="bg-[var(--surface)] px-3 py-3">
                                <div class="text-[11px] text-[var(--ink-muted)]">Instrument alerts</div>
                                <div class="mt-1 text-2xl font-bold tabular-nums text-[var(--ink)]">02</div>
                                <div class="mt-1 text-[11px] text-[var(--negative)]">Requires attention</div>
                            </div>
                        </section>

                        <section class="border border-[var(--line)] bg-[var(--surface)]">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2">
                                <div class="flex items-center gap-3">
                                    <h2 class="font-bold text-[var(--ink)]">My worklist</h2>
                                    <span class="border border-[var(--line-strong)] bg-[var(--surface)] px-2 py-0.5 text-[11px] text-[var(--ink-muted)]">12 records</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="sr-only" for="worklist-filter">Filter worklist</label>
                                    <input id="worklist-filter" type="search" placeholder="Filter records..." class="h-7 w-44 border border-[var(--line-strong)] bg-[var(--surface)] px-2 text-[12px] text-[var(--ink)] outline-none placeholder:text-[var(--ink-muted)] focus:border-[var(--accent)]">
                                    <button type="button" class="h-7 border border-[var(--line-strong)] bg-[var(--surface)] px-2 text-[12px] font-semibold text-[var(--ink)] hover:border-[var(--accent)] hover:text-[var(--accent)]">Refresh</button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[780px] border-collapse text-left text-[12px]">
                                    <thead class="bg-[var(--surface-raised)] text-[10px] uppercase tracking-wider text-[var(--ink-muted)]">
                                        <tr>
                                            <th class="border-b border-[var(--line)] px-3 py-2 font-bold">Accession</th>
                                            <th class="border-b border-[var(--line)] px-3 py-2 font-bold">Test / Panel</th>
                                            <th class="border-b border-[var(--line)] px-3 py-2 font-bold">Priority</th>
                                            <th class="border-b border-[var(--line)] px-3 py-2 font-bold">Received</th>
                                            <th class="border-b border-[var(--line)] px-3 py-2 font-bold">Due</th>
                                            <th class="border-b border-[var(--line)] px-3 py-2 font-bold">Status</th>
                                            <th class="border-b border-[var(--line)] px-3 py-2 font-bold">Assigned to</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--line)]">
                                        <tr class="bg-[var(--accent-soft)]/50 hover:bg-[var(--accent-soft)]">
                                            <td class="whitespace-nowrap px-3 py-2.5 font-bold text-[var(--accent)]">ACC-260907-0142</td>
                                            <td class="px-3 py-2.5"><span class="block font-semibold text-[var(--ink)]">CMP panel</span><span class="text-[11px] text-[var(--ink-muted)]">Serum / Chemistry</span></td>
                                            <td class="px-3 py-2.5"><span class="font-semibold text-[var(--negative)]">STAT</span></td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">07 Sep 08:14</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums font-semibold text-[var(--negative)]">10:14</td>
                                            <td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--accent)]"><span class="h-1.5 w-1.5 bg-[var(--accent)]"></span>In progress</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">A. Analyst</td>
                                        </tr>
                                        <tr class="hover:bg-[var(--surface-muted)]">
                                            <td class="whitespace-nowrap px-3 py-2.5 font-bold text-[var(--accent)]">ACC-260907-0138</td>
                                            <td class="px-3 py-2.5"><span class="block font-semibold text-[var(--ink)]">CBC with differential</span><span class="text-[11px] text-[var(--ink-muted)]">Whole blood / Hematology</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">Routine</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">07 Sep 07:56</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">12:00</td>
                                            <td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--warning)]"><span class="h-1.5 w-1.5 bg-[var(--warning)]"></span>Pending review</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">J. Mendez</td>
                                        </tr>
                                        <tr class="hover:bg-[var(--surface-muted)]">
                                            <td class="whitespace-nowrap px-3 py-2.5 font-bold text-[var(--accent)]">ACC-260907-0135</td>
                                            <td class="px-3 py-2.5"><span class="block font-semibold text-[var(--ink)]">Creatinine</span><span class="text-[11px] text-[var(--ink-muted)]">Serum / Chemistry</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">Routine</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">07 Sep 07:40</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">12:00</td>
                                            <td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--positive)]"><span class="h-1.5 w-1.5 bg-[var(--positive)]"></span>Complete</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">A. Analyst</td>
                                        </tr>
                                        <tr class="hover:bg-[var(--surface-muted)]">
                                            <td class="whitespace-nowrap px-3 py-2.5 font-bold text-[var(--accent)]">ACC-260907-0129</td>
                                            <td class="px-3 py-2.5"><span class="block font-semibold text-[var(--ink)]">Urinalysis</span><span class="text-[11px] text-[var(--ink-muted)]">Urine / General</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">Routine</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">07 Sep 07:22</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">11:30</td>
                                            <td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--accent)]"><span class="h-1.5 w-1.5 bg-[var(--accent)]"></span>In progress</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">R. Chen</td>
                                        </tr>
                                        <tr class="hover:bg-[var(--surface-muted)]">
                                            <td class="whitespace-nowrap px-3 py-2.5 font-bold text-[var(--accent)]">ACC-260907-0124</td>
                                            <td class="px-3 py-2.5"><span class="block font-semibold text-[var(--ink)]">Lipid profile</span><span class="text-[11px] text-[var(--ink-muted)]">Serum / Chemistry</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">Routine</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">07 Sep 06:58</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 tabular-nums text-[var(--ink-muted)]">13:00</td>
                                            <td class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 font-semibold text-[var(--positive)]"><span class="h-1.5 w-1.5 bg-[var(--positive)]"></span>Complete</span></td>
                                            <td class="px-3 py-2.5 text-[var(--ink-muted)]">A. Analyst</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2 text-[11px] text-[var(--ink-muted)]">
                                <span>Showing 1-5 of 12 records</span>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="h-6 border border-[var(--line)] px-2 hover:border-[var(--accent)] hover:text-[var(--accent)]" aria-label="Previous page">&lt;</button>
                                    <span class="px-2 font-semibold text-[var(--ink)]">1 / 3</span>
                                    <button type="button" class="h-6 border border-[var(--line)] px-2 hover:border-[var(--accent)] hover:text-[var(--accent)]" aria-label="Next page">&gt;</button>
                                </div>
                            </div>
                        </section>

                        <div class="mt-4 grid gap-4 xl:grid-cols-2">
                            <section class="border border-[var(--line)] bg-[var(--surface)]">
                                <div class="flex items-center justify-between border-b border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2">
                                    <h2 class="font-bold text-[var(--ink)]">Instrument status</h2>
                                    <a href="#" class="text-[11px] font-semibold text-[var(--accent)] hover:underline">View all</a>
                                </div>
                                <div class="grid grid-cols-1 divide-y divide-[var(--line)] sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                                    <div class="px-3 py-3"><div class="flex items-center justify-between"><span class="font-semibold">Cobas c311</span><span class="h-2 w-2 bg-[var(--positive)]"></span></div><div class="mt-1 text-[11px] text-[var(--ink-muted)]">Chemistry · Ready</div></div>
                                    <div class="px-3 py-3"><div class="flex items-center justify-between"><span class="font-semibold">XN-1000</span><span class="h-2 w-2 bg-[var(--warning)]"></span></div><div class="mt-1 text-[11px] text-[var(--warning)]">Hematology · Reagent low</div></div>
                                    <div class="px-3 py-3"><div class="flex items-center justify-between"><span class="font-semibold">ACL TOP 550</span><span class="h-2 w-2 bg-[var(--positive)]"></span></div><div class="mt-1 text-[11px] text-[var(--ink-muted)]">Coagulation · Ready</div></div>
                                </div>
                            </section>

                            <section class="border border-[var(--line)] bg-[var(--surface)]">
                                <div class="flex items-center justify-between border-b border-[var(--line)] bg-[var(--surface-muted)] px-3 py-2">
                                    <h2 class="font-bold text-[var(--ink)]">Recent activity</h2>
                                    <a href="#" class="text-[11px] font-semibold text-[var(--accent)] hover:underline">Audit log</a>
                                </div>
                                <div class="divide-y divide-[var(--line)] text-[11px]">
                                    <div class="flex items-center justify-between gap-3 px-3 py-2"><span><strong class="font-semibold text-[var(--ink)]">J. Mendez</strong> authorized ACC-260907-0118</span><span class="shrink-0 tabular-nums text-[var(--ink-muted)]">09:38</span></div>
                                    <div class="flex items-center justify-between gap-3 px-3 py-2"><span><strong class="font-semibold text-[var(--ink)]">System</strong> imported 24 instrument results</span><span class="shrink-0 tabular-nums text-[var(--ink-muted)]">09:31</span></div>
                                    <div class="flex items-center justify-between gap-3 px-3 py-2"><span><strong class="font-semibold text-[var(--ink)]">R. Chen</strong> registered ACC-260907-0142</span><span class="shrink-0 tabular-nums text-[var(--ink-muted)]">08:14</span></div>
                                </div>
                            </section>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script>
            const themeToggle = document.getElementById('theme-toggle');
            const storedTheme = window.localStorage.getItem('mesa-lims-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const initialTheme = storedTheme || (prefersDark ? 'dark' : 'light');

            document.documentElement.dataset.theme = initialTheme;

            themeToggle.addEventListener('click', () => {
                const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';

                document.documentElement.dataset.theme = nextTheme;
                window.localStorage.setItem('mesa-lims-theme', nextTheme);
            });
        </script>
    </body>
</html>