<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo_documento ?? 'Petição Inicial Trabalhista' }}</title>
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

        .right {
            text-align: right;
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

        .signature-block {
            margin-top: 48px;
        }

        .signature-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36px;
        }

        .signature-grid td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 48px;
            padding-top: 8px;
        }

        .small {
            font-size: 12px;
        }

        .uppercase {
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ENDEREÇAMENTO --}}
        <p class="center uppercase">
            <strong>AO DOUTO JUÍZO DA {{ $vara_competente ?? '___ VARA DO TRABALHO DE __________' }}</strong>
        </p>

        <div class="spacer-lg"></div>

        {{-- QUALIFICAÇÃO RECLAMANTE --}}
        <p class="indent">
            <strong>{{ $cliente_nome ?? 'NOME DA PARTE AUTORA' }}</strong>, {{ $cliente_nacionalidade ?? 'nacionalidade não informada' }}, {{ $cliente_estado_civil ?? 'estado civil não informado' }}, {{ $cliente_profissao ?? 'profissão não informada' }}@if (!empty($cliente_rg)), RG {{ $cliente_rg }}@if (!empty($cliente_orgao_expedidor)), {{ $cliente_orgao_expedidor }}@endif @endif @if (!empty($cliente_cpf)), CPF {{ $cliente_cpf }}@endif, residente e domiciliada na {{ $cliente_endereco ?? 'endereço não informado' }}@if (!empty($cliente_numero)), nº {{ $cliente_numero }}@endif @if (!empty($cliente_complemento)), {{ $cliente_complemento }}@endif, bairro {{ $cliente_bairro ?? 'não informado' }}, CEP {{ $cliente_cep ?? 'não informado' }}, {{ $cliente_cidade ?? 'cidade não informada' }}/{{ $cliente_uf ?? 'UF' }}@if (!empty($cliente_ctps)), CTPS: {{ $cliente_ctps }}@endif @if (!empty($cliente_ctps_serie)), série {{ $cliente_ctps_serie }}@endif @if (!empty($cliente_pis)), PIS/PASEP {{ $cliente_pis }}@endif, por seus advogados, instrumento de mandato incluso, com endereço profissional no timbrado da presente, local onde recebem intimações e notificações, vem, respeitosamente, à presença de Vossa Excelência, nos termos do art. 840 da CLT, propor a presente:
        </p>

        {{-- TÍTULO DA AÇÃO --}}
        <p class="title-main">{{ $titulo_acao ?? 'RECLAMAÇÃO TRABALHISTA' }}</p>

        {{-- QUALIFICAÇÃO RECLAMADA --}}
        <p class="indent">
            em face de <strong>{{ $empregador_nome ?? 'NOME DA RECLAMADA' }}</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº {{ $empregador_cnpj ?? 'CNPJ não informado' }}, com sede em {{ $empregador_endereco ?? 'endereço não informado' }}@if (!empty($empregador_numero)), nº {{ $empregador_numero }}@endif @if (!empty($empregador_complemento)), {{ $empregador_complemento }}@endif, bairro {{ $empregador_bairro ?? 'não informado' }}, {{ $empregador_cidade ?? 'cidade não informada' }}/{{ $empregador_uf ?? 'UF' }}, CEP {{ $empregador_cep ?? 'não informado' }}@if (!empty($empregador_email)), e-mail: {{ $empregador_email }}@endif, pelos fatos e fundamentos a seguir expostos:
        </p>

        {{-- TOMADORAS / GRUPO ECONÔMICO --}}
        @if (!empty($incluir_tomadoras_ou_grupo) && !empty($tomadoras_grupo_html))
            <p class="indent">{!! $tomadoras_grupo_html !!}</p>
        @endif

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

        {{-- REQUERIMENTOS FINAIS --}}
        <p class="indent">
            Diante do exposto, requer de Vossa Excelência que proceda a citação da reclamada para contestar a
            presente reclamatória trabalhista, sob pena de revelia e confissão quanto à matéria de fato, que,
            ao final, deverá ser julgada procedente, condenando a demandada na forma do pedido, acrescido de juros
            e correção monetária.
        </p>

        <p class="indent">
            A tramitação do processo no Juízo 100% digital, bem como sejam intimadas as partes requeridas para
            manifestarem sobre a petição no prazo de 5 dias, sob pena do silêncio entender-se como concordância
            tácita.
        </p>

        <p class="indent">
            Os benefícios da Assistência Judiciária Gratuita, nos termos da Lei nº 1.060/50 e suas alterações,
            por ser a reclamante pessoa pobre no sentido legal.
        </p>

        @if (!empty($requerimentos_finais_html))
            {!! $requerimentos_finais_html !!}
        @endif

        <p class="indent">
            Protesta provar o alegado por todos os meios de prova em direito admitidos, sem exclusão de nenhuma,
            especialmente pelo depoimento pessoal da reclamada, sob pena de confissão (En. 74 do C. TST), oitiva
            de testemunhas, perícia técnica para comprovar a insalubridade e periculosidade, juntada de documentos
            e demais provas que se fizerem necessárias.
        </p>

        {{-- VALOR DA CAUSA --}}
        <p class="indent">
            Dá à causa o valor de {{ !empty($valor_causa) ? $valor_causa : 'R$ 0,00' }}
            @if (!empty($valor_causa_extenso))
                ({{ $valor_causa_extenso }})
            @endif
            , para os fins legais.
        </p>

        {{-- FECHO --}}
        <div class="signature-block">
            <p>Nesses termos,</p>
            <p>Pede deferimento.</p>

            <div class="spacer-md"></div>

            <p>{{ $cidade_assinatura ?? 'Cidade' }}, {{ $data_assinatura ?? now()->format('d/m/Y') }}</p>

            {{-- ASSINATURAS HTML --}}
            @if (!empty($assinaturas_html))
                {!! $assinaturas_html !!}
            @elseif(!empty($advogados) && is_array($advogados))
                <table class="signature-grid">
                    @foreach (array_chunk($advogados, 2) as $linha)
                        <tr>
                            @foreach ($linha as $advogado)
                                <td>
                                    <div class="signature-line">
                                        {{ $advogado['nome'] ?? '' }}<br>
                                        {{ $advogado['oab'] ?? '' }}
                                    </div>
                                </td>
                            @endforeach

                            @if (count($linha) === 1)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <table class="signature-grid">
                    <tr>
                        <td>
                            <div class="signature-line">
                                {{ $advogado_1_nome ?? 'Grimaldo Bruno Fernandes Botelho' }}<br>
                                {{ $advogado_1_oab ?? 'OAB/MG 120.920' }}
                            </div>
                        </td>
                        <td>
                            <div class="signature-line">
                                {{ $advogado_2_nome ?? 'Helen Cristina Ribeiro Soares Real' }}<br>
                                {{ $advogado_2_oab ?? 'OAB/MG 139.212' }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        @if (!empty($advogado_4_nome))
                            <td>
                                <div class="signature-line">
                                    {{ $advogado_3_nome ?? 'Igor Felippe N. F. de Oliveira' }}<br>
                                    {{ $advogado_3_oab ?? 'OAB/MG 191.60' }}
                                </div>
                            </td>
                            <td>
                                <div class="signature-line">
                                    {{ $advogado_4_nome }}<br>
                                    {{ $advogado_4_oab ?? '' }}
                                </div>
                            </td>
                        @else
                            <td colspan="2">
                                <div class="signature-line">
                                    {{ $advogado_3_nome ?? 'Igor Felippe N. F. de Oliveira' }}<br>
                                    {{ $advogado_3_oab ?? 'OAB/MG 191.60' }}
                                </div>
                            </td>
                        @endif
                    </tr>
                </table>
            @endif
        </div>

    </div>
</body>

</html>
