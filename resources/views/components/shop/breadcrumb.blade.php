@props(['title' => null])

@if($title)
<section class="relative overflow-hidden bg-cover bg-center bg-no-repeat" style="background-image:url('{{ asset('assets/images/products.jpg') }}')">
    <div class="absolute inset-0 bg-gradient-to-b from-black/75 via-black/65 to-black/80"></div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center min-h-[9rem] sm:min-h-[12rem] flex items-center justify-center">
        <h1 class="font-display text-3xl sm:text-5xl text-white tracking-tight">{{ $title }}</h1>
    </div>
</section>
@endif
