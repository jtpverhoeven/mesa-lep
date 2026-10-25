<table width="100%" class="monsterSummaryTable">
    <thead>
        <tr><th style="width: 0.75cm; text-align: left;">Nr. </th><th style="text-align: left;">Monsteromschrijving</th><th style="width: 3cm; text-align: right;">MAZ monsternr. </th></tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding-bottom: 3px; padding-top: 5px; vertical-align: top;">{{ $row['follow'] }}</td>
            <td style="padding-bottom: 3px; padding-top: 5px; vertical-align: top;">{{ $row['description'] }}</td>
            <td style="padding-bottom: 3px; padding-top: 5px; text-align: right; vertical-align: top;">MAZ-L{{ $row['sample']->barcode }}</td>
        </tr>
        @for($empty = 1; $empty < 5; $empty++)
            <tr>
                <td style="padding-bottom: 3px; padding-top: 5px; vertical-align: top;"></td>
                <td style="padding-bottom: 3px; padding-top: 5px; vertical-align: top;"></td>
                <td style="padding-bottom: 3px; padding-top: 5px; text-align: right; vertical-align: top;"></td>
            </tr>
        @endfor
    </tbody>
</table>
<table class="resultTable" width="100%">
    <thead>
        <tr>
            <th style="width: 0.73cm; text-align: left; vertical-align: text-top;">MTH </th>
            <th style="width: 5.79cm; text-align: left; vertical-align: text-top;"><p> Analyse </p></th>
            <th style="width: 0.73cm; text-align: center; vertical-align: text-top;">&nbsp;</th>
            <th style="width: 1.7cm; text-align: left; vertical-align: text-top;">Eenheid </th>
            <th style="width: 2.5cm; text-align: right;">Resultaat</th>
            <th style="width: 2.6cm; text-align: right;">Referentiewaarde</th>
            <th style="width: 0.1cm; text-align: center; vertical-align: text-top;">&nbsp;</th>
            <th style="width: 3.9cm; text-align: left;">Bron</th><th style="text-align: right;"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($sampling as $method)
            @if((int) $method['procedure']->id === (int) $row['sample']->sampling_method)
                <tr brickId="sampleMethod">
                    <td class="sampleTakeBorder">{{ $method['number'] === null ? '' : 'M'.$method['number'] }}</td>
                    <td class="sampleTakeBorder">Monsterneming</td><td class="sampleTakeBorder">{{ $method['fields']['accred'] ?? '' }}</td><td class="sampleTakeBorder">&nbsp;</td>
                    <td class="sampleTakeBorder" style="text-align: right;">{{ $method['fields']['door'] ?? '' }}</td>
                    <td class="sampleTakeBorder"></td><td class="sampleTakeBorder"></td><td class="sampleTakeBorder"></td><td class="sampleTakeBorder"></td>
                </tr>
            @endif
        @endforeach
        @foreach($methods as $assayId => $method)
            @if(isset($row['results'][$assayId]))
                @php($result = $row['results'][$assayId])
                <tr>
                    <td class="resultBorderBottomFull">M{{ $method['number'] }}</td>
                    <td class="resultBorderBottomFull">{{ \App\Support\ReportHtml::richText($method['fields']['raportnaam'] ?? '') }}</td>
                    <td class="resultBorderBottomFull">{{ $method['fields']['accred'] ?? '' }}</td>
                    <td class="resultBorderBottomFull">{{ \App\Support\ReportHtml::richText($method['fields']['resultin'] ?? '') }}</td>
                    <td class="resultBorderBottomFull" style="text-align: right;">&nbsp; {{ $result['output'] }}</td>
                    <td class="resultBorderBottomFull" style="text-align: right;">{{ $result['reference'] }}</td>
                    <td class="resultBorderBottomFull"></td>
                    <td class="resultBorderBottomFull" style="text-align: left;">{{ $result['source'] }}</td>
                    <td class="resultBorderBottomFull" style="text-align: center;"><span style="color: {{ $result['indicator'] }}; font-weight: bold;">●</span></td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
