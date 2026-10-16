@extends('layouts.app')
@section('title', 'Program Pelatihan')
@section('content')
    <section class="section-head">
        <h1>Program Pelatihan</h1>
        <p>Pilih jadwal pelatihan dan kembangkan keahlian kamu bersama Karir Akademi.</p>
    </section>

    <section class="cards">
        @forelse($programs as $program)
            <x-program-card :program="$program" />
        @empty
            <p style="grid-column:1/-1;text-align:center;color:#6b7280">Belum ada program pelatihan yang dibuka.</p>
        @endforelse
    </section>
@endsection
