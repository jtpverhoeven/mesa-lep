<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Borgingsformulier {{ $form['display_date'] }}</title>
    <style>
        body { color: #14232d; font: 12px Arial, sans-serif; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 16px; }
        h2 { border-bottom: 1px solid #9eabb2; font-size: 14px; margin: 22px 0 0; padding: 8px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #c4cdd2; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f4f6f7; width: 28%; }
        .warning { background: #fff1ed; color: #903e32; }
        .explanation { color: #53636d; font-style: italic; }
    </style>
</head>
<body>
    <h1>Borgingsformulier: {{ $form['display_date'] }}</h1>
    @foreach ([0 => 'Algemeen', 1 => 'Uit stoof / aflezen', 2 => 'Ophopings- / verdunningsvloeistoffen'] as $section => $heading)
        @if (!empty($form['sections'][$section]))
            <h2>{{ $heading }}</h2>
            <table>
                <tbody>
                @foreach ($form['sections'][$section] as $field)
                    <tr class="{{ $field['out_of_specification'] || $field['out_of_date_here'] ? 'warning' : '' }}">
                        <th>{{ $field['label'] }}</th>
                        <td>
                            {{ $field['value'] }}
                            @if ($field['out_of_date_text'])<br><strong>Gebruikt na THT bij monster(s): {{ $field['out_of_date_text'] }}</strong>@endif
                            @if ($field['explanation'])<br><span class="explanation">Uitleg: {{ $field['explanation'] }}</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
    @foreach ($form['dynamic_sections'] ?? [] as $section)
        <h2>{{ $section['label'] }}</h2>
        @if (!empty($section['rows']))
            <table>
                <tbody>
                @foreach ($section['rows'] as $row)
                    <tr>
                        <th>{{ $row['parent']['label'] ?? $row['media_name'] }}</th>
                        <td class="{{ !empty($row['parent']) && ($row['parent']['out_of_specification'] || $row['parent']['out_of_date_here']) ? 'warning' : '' }}">
                            {{ $row['parent']['value'] ?? '' }}
                            @if (!empty($row['parent']['out_of_date_text']))<br><strong>Gebruikt na THT bij monster(s): {{ $row['parent']['out_of_date_text'] }}</strong>@endif
                            @if (!empty($row['parent']['explanation']))<br><span class="explanation">Uitleg: {{ $row['parent']['explanation'] }}</span>@endif
                        </td>
                        <th>
                            @foreach ($row['supplements'] as $supplement)
                                <div>{{ $supplement['name'] }}</div>
                            @endforeach
                        </th>
                        <td>
                            @foreach ($row['supplements'] as $supplement)
                                <div class="{{ $supplement['field']['out_of_specification'] || $supplement['field']['out_of_date_here'] ? 'warning' : '' }}">
                                    {{ $supplement['field']['value'] }}
                                    @if ($supplement['field']['out_of_date_text'])<br><strong>Gebruikt na THT bij monster(s): {{ $supplement['field']['out_of_date_text'] }}</strong>@endif
                                    @if ($supplement['field']['explanation'])<br><span class="explanation">Uitleg: {{ $supplement['field']['explanation'] }}</span>@endif
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>
