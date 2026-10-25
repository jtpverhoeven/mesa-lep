@if($follow)
    <table class="topinfo">
        <tr><td style="width: 5cm;"><p style="font-weight: bold;"> Datum bemonstering</p></td><td style="width: 6cm;">{{ $info['project_monster'] ?? '' }}</td><td style="text-align: right; padding-right: 10px;"><p style="font-weight: bold;"> </p></td><td></td></tr>
        <tr><td><p style="font-weight: bold;"> Datum ontvangst</p></td><td>{{ $info['project_ontvangst'] ?? '' }}</td><td></td><td></td></tr>
        <tr><td><p style="font-weight: bold;"> Datum aanvang analyse(s)</p></td><td>{{ $info['project_inzet'] }}</td><td></td><td></td></tr>
        <tr><td><p style="font-weight: bold;"> Referentie </p></td><td>MAZ-L {{ $info['project_reference'] }}</td><td></td><td></td></tr>
    </table>
@else
    <table class="topinfo">
        <tr>
            <td style="width: 4.28cm;"><p style="font-size: 12px; font-weight: bold;"> Analysecertificaat </p></td>
            <td style="width: 5.55cm;">{{ $info['project_generated'] }}</td><td style="width: 3.07cm;"></td><td></td>
        </tr>
        <tr><td colspan="4">&nbsp;</td></tr>
        <tr><td><p style="font-weight: bold;"> Datum bemonstering</p></td><td>{{ $info['project_monster'] ?? '' }}</td><td style="text-align: right; padding-right: 10px;"><p style="font-weight: bold;"> Opdrachtgever </p></td><td>{{ $client->name }}</td></tr>
        <tr><td><p style="font-weight: bold;"> Datum ontvangst</p></td><td>{{ $info['project_ontvangst'] ?? '' }}</td><td></td><td>{{ $client->title }} {{ $client->fname }} {{ $client->mname }} {{ $client->lname }}</td></tr>
        <tr><td><p style="font-weight: bold;"> Datum aanvang analyse(s)</p></td><td>{{ $info['project_inzet'] }}</td><td></td><td>{{ $client->street_name }} {{ $client->street_number }}</td></tr>
        <tr><td><p style="font-weight: bold;"> Referentie Micro-Analyse Zeeland </p></td><td>MAZ-L {{ $info['project_reference'] }}</td><td></td><td>{{ $client->postal_code }} {{ $client->place }}</td></tr>
        <tr><td style="vertical-align: top;"><p style="font-weight: bold; {{ $info['shown_client_reference'] === '' ? 'display: none' : '' }}"> Referentie Opdrachtgever </p></td><td>{{ $info['shown_client_reference'] }}</td><td></td><td style="vertical-align: top;">{{ $client->country }}</td></tr>
    </table>
@endif
