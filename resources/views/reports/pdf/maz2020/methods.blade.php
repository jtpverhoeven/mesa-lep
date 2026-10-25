<table class="methodTable" width="100%">
    <thead>
        <tr><th style="border: none; text-align: left;" colspan="6"> Methodeverwijzingen van uitgevoerde verrichtingen </th></tr>
        <tr>
            <th style="width: 0.75cm; text-align: left;"> MTH </th>
            <th style="width: 6.6cm; text-align: left;"> Analyse </th>
            <th style="width: 0.8cm;">&nbsp;</th><th style="text-align: left;"> Techniek </th>
            <th style="text-align: left;"> Conformiteit </th><th style="text-align: right;"> Referentiemethode </th>
        </tr>
    </thead>
    <tbody>
        @foreach($sampling as $method)
            @if($method['number'] !== null)
                <tr>
                    <td class="border"> M{{ $method['number'] }} </td><td class="border"> Monsterneming</td>
                    <td class="border">{{ $method['fields']['accred'] ?? '' }}</td><td class="border"></td>
                    <td class="border">{{ $method['fields']['conform'] ?? '' }}</td>
                    <td class="border" style="text-align: right;">{{ $method['fields']['referentiemethode'] ?? '' }}</td>
                </tr>
            @endif
        @endforeach
        @foreach($methods as $method)
            @php($rowStyle = count($methods) >= $method['number'] ? 'sampleTakeBorder' : 'samplingNoBorder')
            <tr>
                <td class="{{ $rowStyle }}" style="padding-top: 3px;"> M{{ $method['number'] }} </td>
                <td class="{{ $rowStyle }}">{{ \App\Support\ReportHtml::richText($method['fields']['raportnaam'] ?? '') }}</td>
                <td class="{{ $rowStyle }}">{{ $method['fields']['accred'] ?? '' }}</td>
                <td class="{{ $rowStyle }}">{{ $method['fields']['techniek'] ?? '' }}</td>
                <td class="{{ $rowStyle }}">{{ $method['fields']['conform'] ?? '' }}</td>
                <td class="{{ $rowStyle }}" style="text-align: right;">{{ $method['fields']['referentiemethode'] ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
