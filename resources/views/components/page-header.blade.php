@props(['title', 'description' => null, 'breadcrumbs' => []])

{{-- breadcrumbs: ['Rótulo' => 'url'] ou ['Rótulo'] para o item atual --}}
<div class="gb-page-header">
    <div>
        @if ($breadcrumbs)
            <nav aria-label="Trilha de navegação">
                <ol class="gb-breadcrumb">
                    @foreach ($breadcrumbs as $label => $url)
                        @if (is_int($label))
                            <li aria-current="page">{{ $url }}</li>
                        @else
                            <li><a href="{{ $url }}">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="gb-page-title">{{ $title }}</h1>
        @if ($description)
            <p class="gb-page-description">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="gb-page-actions">{{ $actions }}</div>
    @endisset
</div>
