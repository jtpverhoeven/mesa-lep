@php
    $editedUser = $editedUser ?? null;
    $profile = $editedUser?->profile;
    $selectedRoles = array_map('intval', old('roles', $editedUser?->roles->pluck('id')->all() ?? []));
@endphp
<fieldset class="form-section"><legend>Account</legend><div class="form-grid">
    <div class="field"><label for="username">Gebruikersnaam</label><input id="username" name="username" value="{{ old('username', $profile?->username) }}" maxlength="255" required autofocus></div>
    <div class="field"><label for="email">E-mailadres</label><input type="email" id="email" name="email" value="{{ old('email', $editedUser?->email) }}" maxlength="255" required></div>
    <div class="field"><label for="password">{{ $editedUser ? 'Nieuw wachtwoord' : 'Wachtwoord' }}</label><input type="password" id="password" name="password" autocomplete="new-password" @required(! $editedUser)></div>
    <div class="field"><label for="password_confirmation">Wachtwoord herhalen</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @required(! $editedUser)></div>
    <div class="check-grid wide"><input type="hidden" name="enabled" value="0"><label><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $editedUser?->enabled ?? true))>Account actief</label></div>
</div></fieldset>
<fieldset class="form-section"><legend>Profiel</legend><div class="form-grid">
    <div class="field"><label for="title">Titel</label><input id="title" name="title" value="{{ old('title', $profile?->title) }}" maxlength="50"></div>
    <div class="field"><label for="first_name">Voornaam</label><input id="first_name" name="first_name" value="{{ old('first_name', $profile?->first_name) }}" maxlength="255" required></div>
    <div class="field"><label for="last_name">Achternaam</label><input id="last_name" name="last_name" value="{{ old('last_name', $profile?->last_name) }}" maxlength="255" required></div>
    <div class="field"><label for="gender">Geslacht</label><select id="gender" name="gender"><option value="">Niet opgegeven</option><option value="M" @selected(old('gender', $profile?->gender) === 'M')>Man</option><option value="F" @selected(old('gender', $profile?->gender) === 'F')>Vrouw</option><option value="X" @selected(old('gender', $profile?->gender) === 'X')>Anders</option></select></div>
    <div class="field"><label for="job_title">Functie</label><input id="job_title" name="job_title" value="{{ old('job_title', $profile?->job_title) }}" maxlength="150"></div>
    <div class="field"><label for="phone_number">Telefoonnummer</label><input id="phone_number" name="phone_number" value="{{ old('phone_number', $profile?->phone_number) }}" maxlength="50"></div>
    <div class="field"><label for="locale">Taal</label><select id="locale" name="locale"><option value="nl" @selected(old('locale', $profile?->locale ?? 'nl') === 'nl')>Nederlands</option><option value="en" @selected(old('locale', $profile?->locale) === 'en')>Engels</option></select></div>
</div></fieldset>
<fieldset class="form-section"><legend>Gebruikersgroepen</legend><div class="check-grid">
    @forelse($roles as $role)<label><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $selectedRoles, true))>{{ $role->name }}</label>@empty<span class="muted">Geen gebruikersgroepen beschikbaar.</span>@endforelse
</div></fieldset>