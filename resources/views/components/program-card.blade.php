@props(['program'])
<article class="card" style="cursor:pointer"
         onclick="if(!event.target.closest('a,button')){window.location.href='{{ route('program.show', $program) }}'}">
    @if($program->poster)
        <a href="{{ route('program.show', $program) }}" class="poster" style="display:block"><img src="{{ asset($program->poster) }}" alt="Poster {{ $program->title }}" loading="lazy"></a>
    @endif
    <div class="card-body">
        <div class="card-top">
            <span class="pill red">{{ $program->price_label }}</span>
            <span class="pill dark">{{ $program->jp }} JP</span>
        </div>
        <h3><a href="{{ route('program.show', $program) }}">{{ $program->short_title }}</a></h3>
        <div class="info">
            @foreach($program->meta ?? [] as $row)
                @continue(($row['icon'] ?? '') === 'people')
                <div>
                    <span class="ic" aria-hidden="true">
                        @if(($row['icon'] ?? '') === 'calendar')
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path></svg>
                        @elseif(($row['icon'] ?? '') === 'clock')
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                        @else
                            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        @endif
                    </span>
                    <span>{{ $row['text'] }}</span>
                </div>
            @endforeach
        </div>
        @if($program->isFull())
            <button class="btn" disabled>Kuota Penuh</button>
        @else
            <a href="{{ route('registration.create', $program) }}" class="btn">Daftar Sekarang</a>
        @endif
    </div>
</article>
