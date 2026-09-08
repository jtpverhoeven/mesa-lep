@php
    $currentType = (int) old('type', $media?->type ?? $type ?? 1);
    $currentConfirmationMedia = (int) old('confirmation_media', $media?->confirmation_media ?? 0);
    $storedSupplements = json_decode((string) ($media?->supplements ?? ''), true);
    $supplementsText = $media && $currentType !== 3 && is_array($storedSupplements)
        ? collect($storedSupplements)->map(fn ($supplement) => is_array($supplement) ? ($supplement['name'] ?? '') : $supplement)->filter()->implode("\n")
        : (string) ($media?->supplements ?? '');
    $supplementsText = old('supplements_txt', $supplementsText);
    $hasSupplements = filter_var(old('has_supplements', trim($supplementsText) !== ''), FILTER_VALIDATE_BOOLEAN);
    $confirmationControls = json_decode((string) ($media?->confirmation_controls ?? ''), true);
    $confirmationControls = is_array($confirmationControls) ? $confirmationControls : [];
@endphp
<fieldset class="form-section"><legend>Algemeen</legend><div class="form-grid">
    <div class="field wide"><label for="name">Naam</label><input id="name" name="name" value="{{ old('name', $media?->name) }}" required autofocus></div>
    <div class="field"><label for="short_name">Korte naam</label><input id="short_name" name="short_name" maxlength="32" value="{{ old('short_name', $media?->short_name) }}"></div>
    <div class="field"><label for="type">Soort</label><select id="type" name="type" required><option value="1" @selected($currentType === 1)>Telling</option><option value="2" @selected($currentType === 2)>Aanwezigheid</option><option value="3" @selected($currentType === 3)>Materiaal</option></select></div>
    <div class="field" id="confirmation_media_field"><label for="confirmation_media">Bevestigingsmedia?</label><select id="confirmation_media" name="confirmation_media" required><option value="0" @selected($currentConfirmationMedia === 0)>Nee</option><option value="1" @selected($currentConfirmationMedia === 1)>Ja</option></select></div>
    <div class="field"><label for="hasDate">Heeft datum / positie?</label><select id="hasDate" name="hasDate" required><option value="0" @selected(old('hasDate', $media?->hasDate ?? 1) == 0)>Nee</option><option value="1" @selected(old('hasDate', $media?->hasDate ?? 1) == 1)>Ja</option></select></div>
</div></fieldset>

<fieldset class="form-section"><legend>Inhoud</legend><div class="form-grid">
    <div class="field wide">
        <div class="check-grid" id="supplements_toggle_field">
            <input type="hidden" name="has_supplements" value="0">
            <label><input id="has_supplements" name="has_supplements" type="checkbox" value="1" @checked($hasSupplements)>Dit medium heeft supplementen</label>
        </div>
        <div id="supplements_field">
            <label id="supplements_label" for="supplements_txt">Supplementen in dit medium (1 per regel)</label>
            <textarea id="supplements_txt" name="supplements_txt" rows="5" @disabled(!$hasSupplements && $currentType !== 3)>{{ $supplementsText }}</textarea>
        </div>
    </div>
    <div class="field wide" id="acceptable_range_field"><label for="acceptable_range">Acceptabele waarde</label><input id="acceptable_range" name="acceptable_range" value="{{ old('acceptable_range', $media?->acceptable_range) }}" placeholder=">0&lt;=2 of &lt;2 of &lt;=2 of &gt;=1&lt;=20" maxlength="255"></div>
</div></fieldset>

<fieldset class="form-section"><legend>Voorspelling</legend><div class="form-grid">
    <div class="check-grid wide"><input type="hidden" name="used_for_prediction" value="0"><label><input type="checkbox" name="used_for_prediction" value="1" @checked(old('used_for_prediction', $media?->used_for_prediction ?? 1))>Meenemen in voorspelling media voorraad</label></div>
    <div class="field"><label for="prediction_default_quant">Standaard gebruikte hoeveelheid</label><input id="prediction_default_quant" name="prediction_default_quant" type="number" min="0" value="{{ old('prediction_default_quant', $media?->prediction_default_quant ?? 18) }}"></div>
</div></fieldset>

<fieldset class="form-section" id="confirmation_controls_field"><legend>Bevestigingscontroles</legend><div class="check-grid">
    <label><input type="checkbox" name="conf_enabled_pos" value="1" @checked(old('conf_enabled_pos', $confirmationControls['pos'] ?? false))>Positieve controle</label>
    <label><input type="checkbox" name="conf_enabled_neg" value="1" @checked(old('conf_enabled_neg', $confirmationControls['neg'] ?? false))>Negatieve controle</label>
    <label><input type="checkbox" name="conf_enabled_blank" value="1" @checked(old('conf_enabled_blank', $confirmationControls['blank'] ?? false))>Blanco controle</label>
</div></fieldset>

<script>
    (() => {
        const typeField = document.getElementById('type');
        const confirmationField = document.getElementById('confirmation_media');
        const confirmationFieldWrapper = document.getElementById('confirmation_media_field');
        const confirmationControls = document.getElementById('confirmation_controls_field');
        const acceptableRange = document.getElementById('acceptable_range_field');
        const supplementsToggle = document.getElementById('has_supplements');
        const supplementsToggleField = document.getElementById('supplements_toggle_field');
        const supplementsField = document.getElementById('supplements_field');
        const supplementsInput = document.getElementById('supplements_txt');
        const supplementsLabel = document.getElementById('supplements_label');

        function syncMediaType() {
            const isMaterial = typeField.value === '3';
            const showControls = !isMaterial && confirmationField.value === '1';
            const showSupplements = isMaterial || supplementsToggle.checked;

            if (isMaterial) confirmationField.value = '0';
            confirmationFieldWrapper.hidden = isMaterial;
            confirmationControls.hidden = !showControls;
            acceptableRange.hidden = !isMaterial;
            supplementsToggleField.hidden = isMaterial;
            supplementsField.hidden = !showSupplements;
            supplementsInput.disabled = !showSupplements;
            supplementsLabel.textContent = isMaterial ? 'Eenheid' : 'Supplementen in dit medium (1 per regel)';
        }

        typeField.addEventListener('change', syncMediaType);
        confirmationField.addEventListener('change', syncMediaType);
        supplementsToggle.addEventListener('change', syncMediaType);
        syncMediaType();
    })();
</script>