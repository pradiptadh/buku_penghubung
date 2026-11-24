@props(['items', 'current', 'base', 'param' => 'filter'])
<div class="chips">
    @foreach ($items as $key => $label)
        <a class="chip {{ (string) $key === (string) $current ? 'on' : '' }}"
           href="{{ $base.'?'.$param.'='.$key }}">{{ $label }}</a>
    @endforeach
</div>
