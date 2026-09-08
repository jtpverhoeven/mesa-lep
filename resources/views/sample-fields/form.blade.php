<fieldset class="form-section"><legend>Veldgegevens</legend>
    <div class="field"><label for="name">Veldnaam</label><input id="name" name="name" value="{{ old('name', $field?->name) }}" maxlength="32" required autofocus></div>
    <div class="field"><label for="alias">Weergavenaam</label><input id="alias" name="alias" value="{{ old('alias', $field?->alias) }}" maxlength="64" required></div>
    <div class="field"><label for="type">Type</label><select id="type" name="type" required><option value="text" @selected(old('type', $field?->type) === 'text')>Tekst</option><option value="date" @selected(old('type', $field?->type) === 'date')>Datum</option><option value="textarea" @selected(old('type', $field?->type) === 'textarea')>Tekstvak</option></select></div>
    <div class="field"><label for="std_value">Standaardwaarde</label><textarea id="std_value" name="std_value" rows="3" required>{{ old('std_value', $field?->std_value) }}</textarea></div>
    <div class="field"><label for="position">Positie</label><input type="number" id="position" name="position" value="{{ old('position', $field?->position ?? 0) }}" required></div>
</fieldset>