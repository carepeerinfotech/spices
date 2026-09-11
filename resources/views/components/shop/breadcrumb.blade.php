@props(['title' => null])

@if($title)
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat sm:hidden" style="background-image:url('{{ asset('assets/images/breadd-mobb2.jpg') }}')"></div>
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat hidden sm:block" style="background-image:url('{{ asset('assets/images/breadd-deskk2.jpg') }}')"></div>

    <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-black/25 to-black/40"></div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 text-center flex items-center justify-center h-[300px] sm:h-[255px]">
        <h1 class="font-display text-3xl sm:text-5xl text-white tracking-tight">{{ $title }}</h1>
    </div>
</section>
@endif
