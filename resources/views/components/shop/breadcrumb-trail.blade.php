@props(['items' => []])

<nav aria-label="Breadcrumb" class="text-xs sm:text-xl text-stone-500 mb-5">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li class="flex items-center gap-1.5">
            <a href="{{ route('shop.home') }}" class="hover:text-brand transition-colors">Home</a>
            @if(count($items))
                <span class="text-stone-300">/</span>
            @endif
        </li>
        @foreach($items as $item)
            <li class="flex items-center gap-1.5">
                @if(!empty($item['url']) && !$loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-brand transition-colors">{{ $item['label'] }}</a>
                    <span class="text-stone-300">/</span>
                @else
                    <span class="text-stone-700 font-medium" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
