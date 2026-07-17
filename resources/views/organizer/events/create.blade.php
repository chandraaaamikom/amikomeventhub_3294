@extends('layouts.organizer')

@section('title', 'Tambah Event')

@section('content')
<header class="mb-10">
    <a href="{{ route('organizer.events.index') }}" class="text-indigo-600 font-bold inline-flex items-center gap-2 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Kembali ke Event Saya
    </a>
    <h1 class="text-3xl font-black">Tambah Event Baru</h1>
    <p class="text-slate-500 font-medium">Event ini akan terbit atas nama {{ $organization->name }}.</p>
</header>

<div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm p-8 max-w-4xl">
    <form action="{{ route('organizer.events.store') }}" method="POST" enctype="multipart/form-data">
        @include('organizer.events._form')
    </form>
</div>
@endsection
