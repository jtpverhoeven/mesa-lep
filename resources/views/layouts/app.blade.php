<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Mesa'))</title>

        <script>
            const storedTheme = window.localStorage.getItem('mesa-lims-theme');
            const theme = ['dark', 'light'].includes(storedTheme)
                ? storedTheme
                : window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

            document.documentElement.dataset.theme = theme;
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="lims-shell">
        <div id="app">
        {{-- <a class="skip-link" href="#content">Naar inhoud</a> --}}
        <header class="topbar">
            <a href="{{ route('dashboard') }}" class="brand"><span>mesa<span class="muted mr-12">LIMS</span></span></a>
            @auth
                <nav class="topnav" aria-label="Hoofdnavigatie">
                    <a href="{{ route('dashboard') }}" @class(['selected' => request()->routeIs('dashboard') || request()->is('laboratory*')])>LIMS</a>
                    @if(auth()->user()->can('viewAny', \App\Models\Assay::class) || auth()->user()->can('research-profiles.manage'))
                        <a href="{{ route('beheer.dashboard') }}" @class(['selected' => request()->is('admin*')])>Beheer</a>
                    @endif
                </nav>
                <user-menu v-bind="{{ Illuminate\Support\Js::from(['name' => auth()->user()->name, 'email' => auth()->user()->email, 'logoutUrl' => route('logout'), 'csrf' => csrf_token()]) }}">
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="button" type="submit">Uitloggen</button></form>
                </user-menu>
            @else
                <span class="topbar-label">Laboratorium informatiesysteem</span>
            @endauth
        </header>
        <div class="workspace">
            @auth
                <aside class="sidebar">
                    <div class="sidebar-heading"><span class="eyebrow">Werkruimte</span><strong>{{ request()->is('admin*') ? 'Beheer' : 'LIMS' }}</strong></div>
                    <nav aria-label="Werkruimtenavigatie">
                        @if(request()->is('admin*'))
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>Client Portal</span>
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.828 8.828a2 2 0 0 0 2.828 0l6.172-6.172a2 2 0 0 0 0-2.828z"></path><circle cx="7.5" cy="7.5" r=".5"></circle></svg>Labels</span>
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><circle cx="6" cy="5" r="3"></circle><circle cx="18" cy="12" r="3"></circle><circle cx="6" cy="19" r="3"></circle><path d="m8.7 6.5 6.6 4"></path><path d="m8.7 17.5 6.6-4"></path></svg>Aanalyse stroom</span>
                            @can('research-profiles.manage')<a href="{{ route('research-profiles.index') }}" @class(['selected' => request()->is('admin/research-profiles*')])>Onderzoeksprofielen</a>@endcan
                            <a href="{{ route('assays.index') }}" @class(['selected' => request()->is('admin/assays*')])>Analyses</a>
                            <a href="{{ route('assay-fields.index') }}" @class(['selected' => request()->is('admin/assay-fields*')])>Analysevelden</a>
                            <a href="{{ route('assay-types.index') }}" @class(['selected' => request()->is('admin/assay-types*')])>Analytische basistypen</a>
                            @can('sampling-procedures.manage')<a href="{{ route('sample-procedures.index') }}" @class(['selected' => request()->is('admin/sample-procedures*')])>Bemonster procedures</a>@endcan
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><line x1="21" x2="14" y1="4" y2="4"></line><line x1="10" x2="3" y1="4" y2="4"></line><line x1="21" x2="12" y1="12" y2="12"></line><line x1="8" x2="3" y1="12" y2="12"></line><line x1="21" x2="16" y1="20" y2="20"></line><line x1="12" x2="3" y1="20" y2="20"></line><line x1="14" x2="14" y1="2" y2="6"></line><line x1="8" x2="8" y1="10" y2="14"></line><line x1="16" x2="16" y1="18" y2="22"></line></svg>Basis instellingen</span>
                            <a href="{{ route('matrices.index') }}" @class(['selected' => request()->is('admin/matrices*')])>Analyse matrices</a>
                            @can('settings.advanced')<a href="{{ route('reference-sources.index') }}" @class(['selected' => request()->is('admin/reference-sources*')])>Referentie bronnen</a>@endcan
                            <a href="{{ route('media.index') }}" @class(['selected' => request()->is('admin/media*')])>Media &amp; bevestigingen</a>
                            @can('sampling-procedure-fields.manage')<a href="{{ route('sample-procedure-fields.index') }}" @class(['selected' => request()->is('admin/sample-procedure-fields*')])>Monstername velden</a>@endcan
                            @can('sample-fields.manage')<a href="{{ route('sample-fields.index') }}" @class(['selected' => request()->is('admin/sample-fields*')])>Monster velden</a>@endcan
                            @can('project-fields.manage')<a href="{{ route('project-fields.index') }}" @class(['selected' => request()->is('admin/project-fields*')])>Project velden</a>@endcan
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M18 21a8 8 0 0 0-16 0"></path><circle cx="10" cy="7" r="4"></circle><path d="M22 21a8 8 0 0 0-6-7.75"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Gebruikers &amp; groepen</span>
                            @can('users.manage')<a href="{{ route('users.index') }}" @class(['selected' => request()->is('admin/users*')])>Gebruikers</a>@endcan
                            @can('groups.manage')<a href="{{ route('user-groups.index') }}" @class(['selected' => request()->is('admin/user-groups*')])>Gebruikersgroepen</a>@endcan
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M9 3h6"></path><path d="M10 9V3"></path><path d="M14 9V3"></path><path d="M7 21h10"></path><path d="M7 21a2 2 0 0 1-1.66-3.12L10 9h4l4.66 8.88A2 2 0 0 1 17 21Z"></path><path d="M6.5 15h11"></path></svg>Testing</span>
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.09a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>Systeembeheer</span>
                            @can('settings.advanced')<a href="{{ route('cvars.index') }}" @class(['selected' => request()->is('admin/cvars*')])>Geavanceerde instellingen</a>@endcan
                        @else
                            <span class="nav-group">Overzicht</span>
                            <a href="{{ route('dashboard') }}" class="selected">Dashboard</a>
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M12 13v8"></path><path d="m16 17-4 4-4-4"></path><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"></path></svg>Klant import</span>
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M9 3h6"></path><path d="M10 9V3"></path><path d="M14 9V3"></path><path d="M7 21h10"></path><path d="M7 21a2 2 0 0 1-1.66-3.12L10 9h4l4.66 8.88A2 2 0 0 1 17 21Z"></path><path d="M6.5 15h11"></path></svg>Monsters</span>
                            @can('samples.create')<a href="{{ route('samples.create') }}" @class(['selected' => request()->routeIs('samples.create')])>Aanmelden</a>@endcan
                            @can('samples.view')<a href="{{ route('samples.lookup') }}" @class(['selected' => request()->routeIs('samples.lookup*')])>Resultaten invoeren</a>@endcan
                            @can('samples.list')<a href="{{ route('samples.register') }}" @class(['selected' => request()->routeIs('samples.register*')])>Monsterlijst</a>@endcan
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><rect width="20" height="14" x="2" y="7" rx="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><path d="M2 12h20"></path></svg>Projecten</span>
                            @can('projects.view')<a href="{{ route('projects.search') }}" @class(['selected' => request()->routeIs('projects.search*')])>Projecten zoeken</a>@endcan
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3z"></path></svg>Borging</span>
                                @can('assurance-form.view')<a href="{{ route('assurance-forms.index') }}" @class(['selected' => request()->is('laboratory/assurance-forms*')])>Borgingsformulier</a>@endcan
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M18 21a8 8 0 0 0-16 0"></path><circle cx="10" cy="7" r="4"></circle><path d="M22 21a8 8 0 0 0-6-7.75"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Klanten</span>
                            @can('clients.view')<a href="{{ route('clients.index') }}" @class(['selected' => request()->is('laboratory/clients*')])>Klantenoverzicht</a>@endcan
                            @can('clients.view')<a href="{{ route('client-categories.index') }}" @class(['selected' => request()->is('laboratory/client-categories*')])>Klantcategorieen</a>@endcan
                            <span class="nav-group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px" aria-hidden="true" focusable="false"><path d="M3 5v14"></path><path d="M8 5v14"></path><path d="M12 5v14"></path><path d="M17 5v14"></path><path d="M21 5v14"></path></svg>Afdrukken</span>
                        @endif
                    </nav>
                    <div class="sidebar-footer">MESA <span>{{ app()->environment() }}</span></div>
                </aside>
            @endauth
            <main id="content" class="main-content">
                @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())
                    <div class="notice error" role="alert"><strong>Controleer de invoer.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </main>
        </div>
        </div>
    </body>
</html>