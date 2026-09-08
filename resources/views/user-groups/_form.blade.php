@php
    $group = $group ?? null;
    $selectedUsers = array_map('intval', old('users', $group?->users->pluck('id')->all() ?? []));
    $selectedPermissions = old('permissions', $group?->permissions->pluck('name')->all() ?? []);
    $isSuperGroup = (bool) old('superGroup', $group?->superGroup ?? false);
@endphp
<fieldset class="form-section"><legend>Groep</legend><div class="form-grid">
    <div class="field"><label for="name">Groepsnaam</label><input id="name" name="name" value="{{ old('name', $group?->name) }}" maxlength="255" required autofocus></div>
    <div class="field"><label for="groupLeader">Groepsleider</label><select id="groupLeader" name="groupLeader"><option value="">Geen groepsleider</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(old('groupLeader', $group?->groupLeader) == $user->id)>{{ $user->name }} ({{ $user->profile?->username ?? $user->email }})</option>@endforeach</select></div>
    <div class="check-grid wide"><input type="hidden" name="superGroup" value="0"><label><input type="checkbox" name="superGroup" value="1" @checked($isSuperGroup)>Supergroep</label></div>
</div></fieldset>
<fieldset class="form-section"><legend>Gebruikers</legend><div class="check-grid">
    @forelse($users as $user)<label><input type="checkbox" name="users[]" value="{{ $user->id }}" @checked(in_array($user->id, $selectedUsers, true))>{{ $user->name }} <span class="muted">{{ $user->profile?->username }}</span></label>@empty<span class="muted">Geen gebruikers beschikbaar.</span>@endforelse
</div></fieldset>
<fieldset class="form-section"><legend>Rechten</legend>
    @foreach($permissionGroups as $category => $permissions)
        <div class="field" style="margin-bottom:20px"><strong>{{ $category }}</strong><div class="check-grid">@foreach($permissions as $name => $label)<label><input type="checkbox" name="permissions[]" value="{{ $name }}" @checked(in_array($name, $selectedPermissions, true)) @disabled($isSuperGroup)>{{ $label }}</label>@endforeach</div></div>
    @endforeach
</fieldset>