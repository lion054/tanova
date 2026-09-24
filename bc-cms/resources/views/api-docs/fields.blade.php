<div class="tbl"><table><tr><th>{{ __('Field') }}</th><th>{{ __('Type') }}</th><th>{{ __('Description') }}</th></tr>
@foreach($rows as $r)
<tr><td style="padding-left:{{ 8 + $r['depth'] * 14 }}px"><code>{{ $r['name'] }}</code>@if($r['required']) <span class="req">*</span>@endif</td>
<td class="ty">{{ $r['type'] }}</td>
<td>{!! $r['desc'] !!}@if($r['enum']) @foreach($r['enum'] as $v)<code>{{ $v }}</code> @endforeach @endif</td></tr>
@endforeach
</table></div>
