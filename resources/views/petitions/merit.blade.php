<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo_documento ?? 'Mérito - Petição Trabalhista' }}</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 14px;
            line-height: 1.6;
            color: #000;
            margin: 40px;
            text-align: justify;
        }

        .page {
            max-width: 900px;
            margin: 0 auto;
        }

        p {
            margin: 0 0 12px 0;
        }

        .center {
            text-align: center;
        }

        .title-main {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin: 28px 0 18px;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin: 24px 0 12px;
        }

        .topic-title {
            font-weight: bold;
            text-transform: uppercase;
            margin: 22px 0 10px;
        }

        .subtopic-title {
            font-weight: bold;
            margin: 18px 0 8px;
        }

        .indent {
            text-indent: 40px;
        }

        .no-indent {
            text-indent: 0;
        }

        .spacer-md {
            height: 18px;
        }

        .spacer-lg {
            height: 32px;
        }

        .list-alpha {
            margin: 10px 0 14px 24px;
            padding: 0;
        }

        .list-alpha li {
            margin-bottom: 8px;
        }

        .list-bullet {
            margin: 10px 0 14px 24px;
            padding: 0;
        }

        .list-bullet li {
            margin-bottom: 6px;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- PRELIMINARES --}}
        @if (!empty($preliminares_html))
            <p class="section-title">PRELIMINARES</p>
            {!! $preliminares_html !!}
        @endif

        {{-- MÉRITO --}}
        <p class="section-title">MÉRITO</p>

        {{-- 01 - CONTRATO DE TRABALHO --}}
        <p class="topic-title">01 – DO CONTRATO DE TRABALHO.</p>
        {!! $contrato_trabalho_html ?? '<p class="indent">Inserir descrição do contrato de trabalho.</p>' !!}

        {{-- TÓPICOS DINÂMICOS EM HTML --}}
        @if (!empty($topicos_html))
            {!! $topicos_html !!}
        @endif

        {{-- TÓPICOS DINÂMICOS POR ARRAY --}}
        @if (!empty($topicos) && is_array($topicos))
            @foreach ($topicos as $topico)
                <p class="topic-title">
                    {{ $topico['ordem'] ?? '' }} – {{ $topico['titulo'] ?? 'TÓPICO' }}
                </p>

                @if (!empty($topico['conteudo_html']))
                    {!! $topico['conteudo_html'] !!}
                @elseif(!empty($topico['conteudo']))
                    <p class="indent">{{ $topico['conteudo'] }}</p>
                @endif

                @if (!empty($topico['subtopicos']) && is_array($topico['subtopicos']))
                    @foreach ($topico['subtopicos'] as $subtopico)
                        <p class="subtopic-title">
                            {{ $subtopico['ordem'] ?? '' }} {{ $subtopico['titulo'] ?? 'Subtópico' }}
                        </p>

                        @if (!empty($subtopico['conteudo_html']))
                            {!! $subtopico['conteudo_html'] !!}
                        @elseif(!empty($subtopico['conteudo']))
                            <p class="indent">{{ $subtopico['conteudo'] }}</p>
                        @endif
                    @endforeach
                @endif
            @endforeach
        @endif

        {{-- HONORÁRIOS --}}
        @if (!empty($incluir_honorarios))
            <p class="topic-title">{{ $honorarios_ordem ?? '06' }} – DOS HONORÁRIOS ADVOCATÍCIOS.</p>
            {!! $honorarios_html ??
                '<p class="indent">Requer a condenação da reclamada ao pagamento de honorários advocatícios sucumbenciais, na forma da lei.</p>' !!}
        @endif

        {{-- PEDIDOS --}}
        <p class="topic-title">{{ $pedidos_ordem ?? '07' }} – DOS PEDIDOS.</p>

        <p class="indent">
            Diante de todo o exposto, requer a parte reclamante a total procedência da presente reclamação trabalhista,
            com a condenação da reclamada ao pagamento e cumprimento das parcelas e obrigações abaixo descritas,
            tudo a ser apurado em regular liquidação de sentença, acrescido de juros, correção monetária e demais
            consectários legais:
        </p>

        @if (!empty($pedidos_itens_html))
            <ol class="list-alpha" type="a">
                {!! $pedidos_itens_html !!}
            </ol>
        @elseif(!empty($pedidos) && is_array($pedidos))
            <ol class="list-alpha" type="a">
                @foreach ($pedidos as $pedido)
                    <li>
                        @if (is_array($pedido))
                            {{ $pedido['descricao'] ?? '' }}
                        @else
                            {{ $pedido }}
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <ol class="list-alpha" type="a">
                <li>Inserir pedidos da petição.</li>
            </ol>
        @endif

    </div>
</body>

</html>
