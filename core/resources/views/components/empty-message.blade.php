@props([
    'table' => false,
    'div' => false,
    'h4' => false,
    'textAlign' => 'center',
    'colspan' => '100%',
    'message' => $emptyMessage,
    'class'=> ''
])

@if($table)
    <tr>
        <td class="text-{{ $textAlign }} not-found {{ $class }}" colspan="{{ $colspan }}">{{ __($message) }}</td>
    </tr>
@elseif($div) 
    <div class="text-{{ $textAlign }}">
        {{ __($message) }}
    </div>
@elseif($h4) 
    <h4 class="text--muted text-{{ $textAlign }}">{{ __($emptyMessage) }}</h4>
@else 
    {{ __($message) }}
@endif
