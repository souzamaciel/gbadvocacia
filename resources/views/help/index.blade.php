@extends('layouts.app')

@section('title', 'Base de conhecimento')

@section('page-header')
    <span id="top"></span>
    <x-page-header title="Base de conhecimento"
                   description="Tutoriais de cada recurso do sistema. Use a busca ou o índice para encontrar o que precisa."
                   :breadcrumbs="['Ajuda', 'Base de conhecimento']" />
@endsection

@section('content')
    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-3">
            <nav class="card gb-help-nav" aria-label="Índice da base de conhecimento">
                <div class="p-3 pb-2">
                    <div class="gb-input-icon">
                        <i data-lucide="search"></i>
                        <label for="help-search" class="visually-hidden">Buscar na base de conhecimento</label>
                        <input type="search" id="help-search" class="form-control" placeholder="Buscar tutorial…" autocomplete="off">
                    </div>
                </div>
                @foreach ($categories as $category)
                    <div class="gb-help-nav-group">
                        <p class="gb-list-label"><i data-lucide="{{ $category['icon'] }}"></i> {{ $category['title'] }}</p>
                        <ul>
                            @foreach ($category['articles'] as $article)
                                <li><a href="#{{ $article['slug'] }}" data-nav="{{ $article['slug'] }}">{{ $article['title'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </div>

        <div class="col-12 col-lg-9">
            <p class="gb-library-empty card" id="help-empty" hidden>Nenhum tutorial encontrado. Tente outra palavra.</p>

            @foreach ($categories as $category)
                <section class="gb-help-category" data-category aria-labelledby="category-{{ $loop->index }}">
                    <h2 class="gb-help-category-title" id="category-{{ $loop->index }}"><i data-lucide="{{ $category['icon'] }}"></i> {{ $category['title'] }}</h2>
                    @foreach ($category['articles'] as $article)
                        <article class="card gb-help-article" id="{{ $article['slug'] }}" data-article aria-labelledby="{{ $article['slug'] }}-title">
                            <header class="card-header">
                                <h3 class="card-title" id="{{ $article['slug'] }}-title">{{ $article['title'] }}</h3>
                                <p class="card-description">{{ $article['summary'] }}</p>
                            </header>
                            <div class="card-body gb-help-body">
                                @include('help.articles.'.$article['slug'])
                            </div>
                            <footer class="gb-help-top"><a href="#top"><i data-lucide="arrow-up"></i> Voltar ao topo</a></footer>
                        </article>
                    @endforeach
                </section>
            @endforeach
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const articles = Array.from(document.querySelectorAll('[data-article]'));
            const normalize = text => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            articles.forEach(article => { article.dataset.text = normalize(article.textContent); });

            document.getElementById('help-search').addEventListener('input', event => {
                const words = normalize(event.target.value).split(/\s+/).filter(Boolean);
                let visible = 0;
                articles.forEach(article => {
                    const show = words.every(word => article.dataset.text.includes(word));
                    article.hidden = !show;
                    document.querySelector(`[data-nav="${article.id}"]`).parentElement.hidden = !show;
                    visible += show ? 1 : 0;
                });
                document.querySelectorAll('[data-category]').forEach(category => {
                    category.hidden = !category.querySelector('[data-article]:not([hidden])');
                });
                document.querySelectorAll('.gb-help-nav-group').forEach(group => {
                    group.hidden = !group.querySelector('li:not([hidden])');
                });
                document.getElementById('help-empty').hidden = visible > 0;
            });

            const links = new Map(Array.from(document.querySelectorAll('[data-nav]')).map(link => [link.dataset.nav, link]));
            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        links.forEach(link => link.classList.remove('is-active'));
                        links.get(entry.target.id)?.classList.add('is-active');
                    }
                });
            }, { rootMargin: '-20% 0px -70% 0px' });
            articles.forEach(article => observer.observe(article));
        })();
    </script>
@endsection
