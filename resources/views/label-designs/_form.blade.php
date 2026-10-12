<fieldset class="form-section">
    <legend>Labelontwerp</legend>
    <div class="form-grid">
        <div class="field">
            <label for="name">Naam</label>
            <input id="name" name="name" value="{{ old('name', $labelDesign->name) }}" maxlength="128" required autofocus @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')<span id="name-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="default_printer">Standaardprinter</label>
            <select id="default_printer" name="default_printer" @error('default_printer') aria-invalid="true" aria-describedby="default-printer-error" @enderror>
                <option value="">Geen standaardprinter</option>
                @foreach($printers as $printer)
                    <option value="{{ $printer->id }}" @selected((string) old('default_printer', $labelDesign->default_printer) === (string) $printer->id)>{{ $printer->name }}</option>
                @endforeach
            </select>
            @error('default_printer')<span id="default-printer-error">{{ $message }}</span>@enderror
        </div>
        <div class="check-grid wide">
            <input type="hidden" name="default_sample" value="0">
            <label for="default_sample"><input id="default_sample" type="checkbox" name="default_sample" value="1" @checked(old('default_sample', $labelDesign->default_sample)) @error('default_sample') aria-invalid="true" aria-describedby="default-sample-error" @enderror>Standaard voor monsters</label>
            <input type="hidden" name="default_analysis" value="0">
            <label for="default_analysis"><input id="default_analysis" type="checkbox" name="default_analysis" value="1" @checked(old('default_analysis', $labelDesign->default_analysis)) @error('default_analysis') aria-invalid="true" aria-describedby="default-analysis-error" @enderror>Standaard voor analyses</label>
            @error('default_sample')<span id="default-sample-error">{{ $message }}</span>@enderror
            @error('default_analysis')<span id="default-analysis-error">{{ $message }}</span>@enderror
        </div>
        <div class="field wide">
            <label for="zpl">ZPL-code</label>
            <textarea id="zpl" name="zpl" rows="16" maxlength="65535" spellcheck="false" autocapitalize="off" autocomplete="off" wrap="off" required :value="{{ Illuminate\Support\Js::from(old('zpl', $labelDesign->zpl ?? '')) }}" @error('zpl') aria-invalid="true" aria-describedby="zpl-error" @enderror></textarea>
            @error('zpl')<span id="zpl-error">{{ $message }}</span>@enderror
        </div>
    </div>
</fieldset>
<div class="form-actions">
    <button class="button primary" type="submit">{{ $labelDesign->exists ? 'Wijzigingen opslaan' : 'Labelontwerp aanmaken' }}</button>
    <a class="button" href="{{ route('label-designs.index') }}">Annuleren</a>
</div>