@if($includeHeaders)
    @include('reports.pdf.maz2020.header-footer')
@endif
<!--mpdf
    <sethtmlpagefooter name="standardFooter" value="on" show-this-page="1" />
mpdf-->
@foreach($samples as $index => $row)
    @if($index > 0)<pagebreak />@endif
    @include('reports.pdf.maz2020.report-info', ['follow' => $index > 0])
    @if($accredited)
        <!--mpdf
            <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
        mpdf-->
    @endif
    @include('reports.pdf.maz2020.sample-results')
@endforeach
<pagebreak />
@include('reports.pdf.maz2020.report-info', ['follow' => true])
<!--mpdf
    <sethtmlpagefooter name="standardFooter" value="on" show-this-page="1" />
mpdf-->
@if($accredited)
    <!--mpdf
        <sethtmlpagefooter name="rvaFooter" value="on" show-this-page="1" />
    mpdf-->
@endif
@include('reports.pdf.maz2020.methods')
<table class="methodTable" width="100%">
    <thead><tr><th style="text-align: left;"> Opmerkingen, opinies en interpretaties: </th></tr></thead>
    <tbody>
        @foreach($notes as $note)
            <tr><td style="padding-top: 5px;"><strong>{{ $note['barcode'] }}</strong>: {{ $note['text'] }} <br /><br /></td></tr>
        @endforeach
    </tbody>
</table>
<table class="methodTable" width="100%">
    <thead><tr><th style="text-align: left;"> Aanvullende informatie: </th></tr></thead>
    <tbody><tr><td style="padding-top: 5px;">{{ $footer }}</td></tr></tbody>
</table>
