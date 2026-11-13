@foreach($pages as $page)
    @unless($loop->first)<pagebreak />@endunless
    {{-- One table per page keeps both columns on shared rows so they cannot drift apart. --}}
    <table class="CSSTableGenerator" style="width: 100%">
        @if($groups !== [])
            <tr>
                @foreach($page as $section)
                    @unless($loop->first)<td style="width: 4mm; border: 0px; background-color: #ffffff;"></td>@endunless
                    @foreach($groups as $group)
                        @if(($group['title'] ?? '') === '')
                            <td colspan="{{ $group['colspan'] }}" style="border: 0px; background-color: #ffffff;">&nbsp;</td>
                        @else
                            <td colspan="{{ $group['colspan'] }}">{{ $group['title'] }}</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
        @endif
        <tr>
            @foreach($page as $section)
                @unless($loop->first)<td style="width: 4mm; border: 0px; background-color: #ffffff;"></td>@endunless
                <td>Monsternummer <br /> Labnummer</td>
                @foreach($columns as $column)
                    <td style="width: 8mm; text-rotate: 90;">{{ $column['name'] }}</td>
                @endforeach
            @endforeach
        </tr>
        @foreach(array_keys($page[0]) as $index)
            <tr class="line">
                @foreach($page as $lines)
                    @php($line = $lines[$index])
                    @unless($loop->first)<td style="border: 0px; background-color: #ffffff;"></td>@endunless
                    @if($line['kind'] === 'sample')
                        <td>
                            <strong>{{ $line['row']['sample_number'] }}</strong><br />
                            <barcode code="{{ $line['row']['barcode'] }}" type="C128B" size="0.8" height="0.4" text="0" /><br />
                            {{ $line['row']['description'] }}<br />{{ $line['row']['client'] }}
                        </td>
                        @foreach($line['row']['ticks'] as $ticked)
                            <td>{{ $ticked ? 'X' : '' }}</td>
                        @endforeach
                    @elseif($line['kind'] === 'custom')
                        <td>{{ $line['text'] }}</td>
                        @foreach($columns as $column)<td></td>@endforeach
                    @else
                        <td>&nbsp;</td>
                        @foreach($columns as $column)<td>&nbsp;</td>@endforeach
                    @endif
                @endforeach
            </tr>
        @endforeach
    </table>
@endforeach
@if($extraPages !== '')
    @if($pages !== [])<pagebreak />@endif
    {!! $extraPages !!}
@endif
