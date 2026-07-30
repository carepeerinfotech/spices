@extends('shop.layouts.app')

@section('title', ($page->meta_title ?: $page->title).' — '.config('app.name'))
@section('meta_description', $page->meta_description)

@section('content')
<article class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
    <h1 class="font-display text-4xl tracking-tight mb-8">{{ $page->title }}</h1>
    <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed space-y-4">
        {!! $page->content !!}
    </div>
</article>
@endsection
