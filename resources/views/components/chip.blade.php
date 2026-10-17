@props(['tone' => 'gray'])
<span {{ $attributes->class(['chip', 'chip-'.$tone]) }}>{{ $slot }}</span>
