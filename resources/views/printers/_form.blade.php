<fieldset class="form-section">
    <legend>Printer</legend>
    <div class="form-grid">
        <div class="field">
            <label for="name">Naam</label>
            <input id="name" name="name" value="{{ old('name', $printer->name) }}" maxlength="128" required autofocus @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')<span id="name-error" class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="type">Printertype</label>
            <select id="type" name="type" required @error('type') aria-invalid="true" aria-describedby="type-error" @enderror>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected((string) old('type', $printer->type ?? 0) === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<span id="type-error" class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="adres">Adres</label>
            <input id="adres" name="adres" value="{{ old('adres', $printer->adres) }}" maxlength="45" required @error('adres') aria-invalid="true" aria-describedby="adres-error" @enderror>
            @error('adres')<span id="adres-error" class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="port">Poort</label>
            <input id="port" name="port" type="number" min="0" max="65535" step="1" value="{{ old('port', $printer->port ?: '') }}" @error('port') aria-invalid="true" aria-describedby="port-error" @enderror>
            @error('port')<span id="port-error" class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</fieldset>
<div class="form-actions">
    <button class="button primary" type="submit">{{ $printer->exists ? 'Wijzigingen opslaan' : 'Printer aanmaken' }}</button>
    <a class="button" href="{{ route('printers.index') }}">Annuleren</a>
</div>