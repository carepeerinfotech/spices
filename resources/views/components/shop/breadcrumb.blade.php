@props(['title' => null, 'slider' => false])

@if($title)
<section
    @class(['relative overflow-hidden', 'h-64 sm:h-96' => $slider, 'bg-cover bg-center bg-no-repeat' => !$slider])
    @if(!$slider) style="background-image:url('{{ asset('assets/images/products.jpg') }}')" @endif
>
    @if($slider)
        <div class="slides absolute inset-0">
            <article class="slide active">
                <img src="{{ asset('assets/images/banner1.jpg') }}" alt="Bowls of colourful Indian spices" class="w-full h-full object-cover">
            </article>
            <article class="slide">
                <img src="{{ asset('assets/images/banner2.jpg') }}" alt="Vrat Atta" class="w-full h-full object-cover">
            </article>
            <article class="slide">
                <img src="{{ asset('assets/images/banner3.jpg') }}" alt="Whole spices in wooden bowls" class="w-full h-full object-cover">
            </article>
        </div>
    @endif

    @unless($slider)
        <div class="absolute inset-0 bg-gradient-to-b from-black/75 via-black/65 to-black/80"></div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 text-center flex items-center justify-center py-16 sm:py-24 min-h-[9rem] sm:min-h-[12rem]">
            <h1 class="font-display text-3xl sm:text-5xl text-white tracking-tight">{{ $title }}</h1>
        </div>
    @endunless

    @if($slider)
        <div class="slider-dots" aria-label="Hero slider">
            <button class="active" aria-label="Slide 1"></button
            ><button aria-label="Slide 2"></button
            ><button aria-label="Slide 3"></button>
        </div>
    @endif
</section>
@endif
