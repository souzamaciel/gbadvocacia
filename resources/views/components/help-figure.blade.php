@props(['src', 'caption', 'phone' => false])

@php([$width, $height] = getimagesize(public_path('images/ajuda/'.$src.'.jpg')) ?: [null, null])

<figure class="gb-help-figure {{ $phone ? 'is-phone' : '' }}">
    <a href="{{ asset('images/ajuda/'.$src.'.jpg') }}" target="_blank" rel="noopener" aria-label="Abrir imagem ampliada: {{ $caption }}">
        <img src="{{ asset('images/ajuda/'.$src.'.jpg') }}" alt="{{ $caption }}" width="{{ $width }}" height="{{ $height }}" loading="lazy">
    </a>
    <figcaption>{{ $caption }}</figcaption>
</figure>
