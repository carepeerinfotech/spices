@props(['text' => 'No Added Preservatives'])

<section class="marquee-strip" aria-label="{{ $text }}">
  <div class="marquee-track">
    <div class="marquee-content">
      @for ($i = 0; $i < 8; $i++)
        <span>{{ $text }}</span>
        <span class="marquee-dot" aria-hidden="true">&#10022;</span>
      @endfor
    </div>
    <div class="marquee-content" aria-hidden="true">
      @for ($i = 0; $i < 8; $i++)
        <span>{{ $text }}</span>
        <span class="marquee-dot" aria-hidden="true">&#10022;</span>
      @endfor
    </div>
  </div>
</section>
