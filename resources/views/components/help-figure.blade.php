@props(['src', 'caption', 'phone' => false])

<figure class="gb-help-figure {{ $phone ? 'is-phone' : '' }}">
    <a href="{{ asset('images/ajuda/'.$src.'.jpg') }}" target="_blank" rel="noopener" aria-label="Abrir imagem ampliada: {{ $caption }}">
        <img src="{{ asset('images/ajuda/'.$src.'.jpg') }}" alt="{{ $caption }}" loading="lazy">
    </a>
    <figcaption>{{ $caption }}</figcaption>
</figure>
