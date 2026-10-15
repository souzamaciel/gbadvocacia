@extends('layouts.app')

@section('title', 'Novo atendimento')

@section('fonts')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
@endsection

@section('navbar-class', 'bg-dark bg-opacity-10')

@section('body-font', "'Inter', sans-serif")

@section('page-header')
    {{-- Page Header --}}
    <div class="container-fluid px-4 px-sm-5 py-4 d-flex align-items-center gap-2 no-print">
        {{-- Ícone back step --}}
        <div class="flex-shrink-0" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
            <a href="{{ route('home') }}">
                <i class="fa-regular fa-circle-left text-white fs-5"></i>
            </a>
        </div>

        {{-- Título + Subtítulo --}}
        <div class="flex-grow-1 d-flex flex-column gap-2">
            <h1 style="color: var(--gb-gold);font-size:24px;font-family:'Roboto',sans-serif;font-weight:500;line-height:26.15px;margin:0;">
                Gerador de Petição Inicial
            </h1>
            <p style="color:white;font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:13px;margin:0;">
                Selecione os pedidos que irão compor a petição inicial e gere o documento automaticamente.
            </p>
        </div>

        {{-- Imprimir --}}
        <button type="button" id="btn-print-form" class="btn btn-sm no-print d-none d-sm-inline-flex align-items-center gap-2 flex-shrink-0"
                style="color:var(--gb-gold);border:1px solid var(--gb-gold);font-size:13px;font-family:'Roboto',sans-serif;">
            <i class="fa-solid fa-print"></i> Imprimir formulário
        </button>

        {{-- Breadcrumbs --}}
        <nav class="d-none d-sm-flex align-items-center gap-1 flex-shrink-0 ps-4"
             style="font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:20px;color:white;">
            <a href="{{ route('home') }}" class="text-decoration-none" style="color:white;">Home</a>
            <span class="px-1" style="color:white;">/</span>
            <span style="color:white;">Petição inicial</span>
        </nav>
    </div>
@endsection

@section('styles')
<style>
    .section-card { background:#fff; border:1px solid #e2e8f0; border-radius:.5rem; margin-bottom:1rem; }
    .section-header { padding:.75rem 1.25rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:.75rem; }
    .section-badge { width:28px;height:28px;min-width:28px; border-radius:4px; background-color:var(--gb-navy); color:white; font-size:11px;font-weight:700; display:flex;align-items:center;justify-content:center; }
    .section-title { font-size:12px;font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#1e293b; margin:0; }
    .section-body { padding:1.25rem; display:flex; flex-direction:column; gap:1rem; }
    .form-label { font-size:12px;font-weight:500; color:#64748b; margin-bottom:4px; }
    .form-control, .form-select { font-size:13px;color:#314158; }
    .form-control:focus { border-color:var(--gb-navy); box-shadow:0 0 0 .2rem rgba(35,42,84,.15); }
    .form-control::placeholder { color:rgba(10,10,10,.35); }
    .subsection-title { font-size:11px;font-weight:600; text-transform:uppercase; color:#64748b; letter-spacing:.05em; margin-bottom:.5rem; }
    .accent-note { background:#f8fafc;border-radius:6px; padding:10px 12px;font-size:12px; color:#64748b;font-style:italic; }
    .add-btn { font-size:13px;font-weight:500; color:var(--gb-navy);background:none; border:none;padding:0;cursor:pointer; display:inline-flex;align-items:center;gap:6px; }
    .add-btn:hover { opacity:.75; }
    .check-group { display:flex;flex-wrap:wrap;gap:12px; }
    .check-item { display:flex;align-items:center;gap:6px;cursor:pointer;margin:0;font-size:13px; }
    .check-item input { width:15px;height:15px;accent-color:var(--gb-navy);cursor:pointer;margin:0; }

    .print-signature-area { display:none; }

    @media print {
        body { background:white !important; min-height:auto !important; }
        .navbar, .no-print { display:none !important; }
        .section-card { break-inside:avoid; border:1px solid #ccc !important; }
        .section-badge { background-color:#232A54 !important; color:white !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .form-control, .form-select, textarea.form-control { border:none !important; box-shadow:none !important; padding-left:0 !important; background:transparent !important; }
        .form-control::placeholder, .form-select option[value=""] { color:transparent !important; }
        .check-item input[type="checkbox"]:not(:checked),
        .check-item input[type="radio"]:not(:checked) { opacity:.3; }
        .print-signature-area { display:block !important; margin-top:48px; page-break-inside:avoid; }
    }
</style>
@endsection

@section('content')
    <form id="appointment-form" method="POST" action="{{ route('appointment.store') }}">
    @csrf
    <div class="container-fluid px-4 px-sm-5 pb-5 d-flex flex-column flex-lg-row gap-4 align-items-start">
            <div class="flex-grow-1 w-100">

                {{-- ─── Section 1: Informações do Cliente ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">1</span>
                        <h6 class="section-title">Informações do Cliente</h6>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="cliente_nome" class="form-control form-control-sm" placeholder="Nome completo">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Profissão</label>
                                <input type="text" name="cliente_profissao" class="form-control form-control-sm" placeholder="Ex: Auxiliar Administrativo">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Nacionalidade</label>
                                <input type="text" name="cliente_nacionalidade" class="form-control form-control-sm" placeholder="Ex: Brasileira">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Estado Civil</label>
                                <input type="text" name="cliente_estado_civil" class="form-control form-control-sm" placeholder="Ex: Solteiro(a)">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data de Nascimento</label>
                                <input type="date" name="cliente_nascimento" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">RG</label>
                                <input type="text" name="cliente_rg" class="form-control form-control-sm" placeholder="Número do RG">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Órgão Expedidor</label>
                                <input type="text" name="cliente_orgao_expedidor" class="form-control form-control-sm" placeholder="Ex: SSP/MG">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">CPF</label>
                                <input type="text" name="cliente_cpf" class="form-control form-control-sm" placeholder="000.000.000-00" data-mask="cpf">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">PIS</label>
                                <input type="text" name="cliente_pis" class="form-control form-control-sm" placeholder="000.00000.00-0" data-mask="pis">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">CTPS</label>
                                <input type="text" name="cliente_ctps" class="form-control form-control-sm" placeholder="Número da CTPS">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Série</label>
                                <input type="text" name="cliente_ctps_serie" class="form-control form-control-sm" placeholder="Série da CTPS">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nome da Mãe</label>
                                <input type="text" name="cliente_nome_mae" class="form-control form-control-sm" placeholder="Nome completo da mãe">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">CEP</label>
                                <input type="text" name="cliente_cep" class="form-control form-control-sm" placeholder="00000-000" data-mask="cep" data-cep-lookup="cliente">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Endereço</label>
                                <input type="text" name="cliente_endereco" class="form-control form-control-sm" placeholder="Rua, Avenida...">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Número</label>
                                <input type="text" name="cliente_numero" class="form-control form-control-sm" placeholder="Nº">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Complemento</label>
                                <input type="text" name="cliente_complemento" class="form-control form-control-sm" placeholder="Apto, Sala...">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="cliente_bairro" class="form-control form-control-sm" placeholder="Bairro">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="cliente_cidade" class="form-control form-control-sm" placeholder="Cidade">
                            </div>
                            <div class="col-12 col-md-1">
                                <label class="form-label">UF</label>
                                <x-uf-select name="cliente_uf" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 2: Contatos ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">2</span>
                        <h6 class="section-title">Contatos</h6>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Telefone Residencial</label>
                                <input type="text" name="contato_tel_residencial" class="form-control form-control-sm" placeholder="(31) 0000-0000" data-mask="phone">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Celular</label>
                                <input type="text" name="contato_celular" class="form-control form-control-sm" placeholder="(31) 90000-0000" data-mask="phone">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Telefone de Terceiro</label>
                                <input type="text" name="contato_tel_terceiro" class="form-control form-control-sm" placeholder="(31) 90000-0000" data-mask="phone">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nome do Terceiro</label>
                                <input type="text" name="contato_nome_terceiro" class="form-control form-control-sm" placeholder="Nome completo">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Grau de Parentesco</label>
                                <input type="text" name="contato_grau_parentesco" class="form-control form-control-sm" placeholder="Ex: Cônjuge, Filho(a)">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 3: Informações do Empregador ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">3</span>
                        <h6 class="section-title">Informações do Empregador</h6>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label">Empregador</label>
                                <input type="text" name="empregador_nome" class="form-control form-control-sm" placeholder="Razão social ou nome">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">CNPJ</label>
                                <input type="text" name="empregador_cnpj" class="form-control form-control-sm" placeholder="00.000.000/0000-00" data-mask="cnpj">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">CEP</label>
                                <input type="text" name="empregador_cep" class="form-control form-control-sm" placeholder="00000-000" data-mask="cep" data-cep-lookup="empregador">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Endereço</label>
                                <input type="text" name="empregador_endereco" class="form-control form-control-sm" placeholder="Rua, Avenida...">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="empregador_cidade" class="form-control form-control-sm" placeholder="Cidade">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-2">
                                <label class="form-label">UF</label>
                                <x-uf-select name="empregador_uf" />
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="empregador_bairro" class="form-control form-control-sm" placeholder="Bairro">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="empregador_email" class="form-control form-control-sm" placeholder="email@empresa.com.br">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="empregador_telefone" class="form-control form-control-sm" placeholder="(00) 00000-0000" data-mask="phone">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Superior Hierárquico</label>
                                <input type="text" name="empregador_superior" class="form-control form-control-sm" placeholder="Nome do superior hierárquico">
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Grupo Econômico</p>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Empresas do Grupo Econômico</label>
                                    <input type="text" name="grupo_economico" class="form-control form-control-sm" placeholder="Nome das empresas do grupo">
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Tomadoras de Serviço</p>
                            <div id="tomadoras-container"></div>
                            <button type="button" class="add-btn mt-2" onclick="addTomadora()">
                                <i class="fa-solid fa-plus fa-xs"></i> Adicionar tomadora
                            </button>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <label class="form-label">Autoriza incluir no polo passivo?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="empregador_autoriza_polo" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="empregador_autoriza_polo" value="nao"> Não</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 4: Urgência na Ação ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">4</span>
                        <h6 class="section-title">Urgência na Ação</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Motivos de urgência</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="urgencia_motivos[]" value="prescricao"> Prescrição</label>
                                <label class="check-item"><input type="checkbox" name="urgencia_motivos[]" value="doenca"> Doença</label>
                                <label class="check-item"><input type="checkbox" name="urgencia_motivos[]" value="desemprego"> Desemprego</label>
                                <label class="check-item"><input type="checkbox" name="urgencia_motivos[]" value="financeiro"> Dificuldade financeira</label>
                                <label class="check-item"><input type="checkbox" name="urgencia_motivos[]" value="outro"> Outro</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Outros motivos</label>
                                <input type="text" name="urgencia_outros" class="form-control form-control-sm" placeholder="Descreva outros motivos">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Prescrição Bienal</label>
                                <input type="date" name="urgencia_prescricao_bienal" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Possui seguro-desemprego ou benefício?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="urgencia_seguro_beneficio" value="seguro"> Seguro-desemprego</label>
                                <label class="check-item"><input type="radio" name="urgencia_seguro_beneficio" value="beneficio"> Benefício</label>
                                <label class="check-item"><input type="radio" name="urgencia_seguro_beneficio" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Qual benefício?</label>
                                <input type="text" name="urgencia_qual_beneficio" class="form-control form-control-sm" placeholder="Descreva o benefício">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 5: Contrato de Trabalho ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">5</span>
                        <h6 class="section-title">Contrato de Trabalho</h6>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Data de Admissão</label>
                                <input type="date" name="contrato_admissao" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Data de Dispensa</label>
                                <input type="date" name="contrato_dispensa" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Registro na CTPS?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="contrato_registro_ctps" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="contrato_registro_ctps" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="contrato_registro_ctps" value="parcial"> Parcial</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Modalidade do Contrato</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="contrato_modalidade" value="prazo_indeterminado"> Prazo indeterminado</label>
                                <label class="check-item"><input type="radio" name="contrato_modalidade" value="experiencia"> Experiência</label>
                                <label class="check-item"><input type="radio" name="contrato_modalidade" value="prazo_determinado"> Prazo determinado</label>
                                <label class="check-item"><input type="radio" name="contrato_modalidade" value="temporario"> Temporário</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Dias de Experiência</label>
                                <input type="number" name="contrato_dias_experiencia" class="form-control form-control-sm" placeholder="Ex: 45">
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Funções Exercidas</p>
                            <div id="funcoes-container"></div>
                            <button type="button" class="add-btn mt-2" onclick="addFuncao()">
                                <i class="fa-solid fa-plus fa-xs"></i> Adicionar função
                            </button>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Vigilante</p>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Armado ou desarmado?</label>
                                    <div class="check-group mt-1">
                                        <label class="check-item"><input type="radio" name="vigilante_armado" value="armado"> Armado</label>
                                        <label class="check-item"><input type="radio" name="vigilante_armado" value="desarmado"> Desarmado</label>
                                        <label class="check-item"><input type="radio" name="vigilante_armado" value="nao_aplicavel"> N/A</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Portava colete?</label>
                                    <div class="check-group mt-1">
                                        <label class="check-item"><input type="radio" name="vigilante_colete" value="sim"> Sim</label>
                                        <label class="check-item"><input type="radio" name="vigilante_colete" value="nao"> Não</label>
                                        <label class="check-item"><input type="radio" name="vigilante_colete" value="nao_aplicavel"> N/A</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 6: Local e Jornada de Trabalho ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">6</span>
                        <h6 class="section-title">Local e Jornada de Trabalho</h6>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="jornada_cidade" class="form-control form-control-sm" placeholder="Cidade de trabalho">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label">UF</label>
                                <input type="text" name="jornada_uf" class="form-control form-control-sm" placeholder="UF" maxlength="2">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Empresa / Local</label>
                                <input type="text" name="jornada_empresa_local" class="form-control form-control-sm" placeholder="Local de trabalho">
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Tomadoras de Serviço</p>
                            <div id="jornada-tomadoras-container"></div>
                            <button type="button" class="add-btn mt-2" onclick="addJornadaTomadora()">
                                <i class="fa-solid fa-plus fa-xs"></i> Adicionar tomadora
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Horário Contratual</label>
                                <input type="text" name="jornada_horario_contratual" class="form-control form-control-sm" placeholder="Ex: 08:00 às 17:00">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Horário Realizado</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="jornada_horario_realizado[]" value="igual_contratual"> Igual ao contratual</label>
                                <label class="check-item"><input type="checkbox" name="jornada_horario_realizado[]" value="horas_extras"> Com horas extras</label>
                                <label class="check-item"><input type="checkbox" name="jornada_horario_realizado[]" value="variavel"> Variável</label>
                                <label class="check-item"><input type="checkbox" name="jornada_horario_realizado[]" value="outro"> Outro</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Outros horários</label>
                                <input type="text" name="jornada_horario_outros" class="form-control form-control-sm" placeholder="Descreva">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Saía mais cedo ou ficava mais tarde?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="jornada_mais_cedo_tarde" value="mais_cedo"> Mais cedo</label>
                                <label class="check-item"><input type="radio" name="jornada_mais_cedo_tarde" value="mais_tarde"> Mais tarde</label>
                                <label class="check-item"><input type="radio" name="jornada_mais_cedo_tarde" value="ambos"> Ambos</label>
                                <label class="check-item"><input type="radio" name="jornada_mais_cedo_tarde" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Minutos além da jornada</label>
                                <input type="number" name="jornada_minutos_extras" class="form-control form-control-sm" placeholder="Ex: 30">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Vezes por semana</label>
                                <input type="number" name="jornada_vezes_semana" class="form-control form-control-sm" placeholder="Ex: 5">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 7: Jornada – Domingos, Feriados e Intervalo ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">7</span>
                        <h6 class="section-title">Jornada – Domingos, Feriados e Intervalo</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Trabalhava aos domingos?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="jornada_domingos" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="jornada_domingos" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="jornada_domingos" value="as_vezes"> Às vezes</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Trabalhava em feriados?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="jornada_feriados" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="jornada_feriados" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="jornada_feriados" value="as_vezes"> Às vezes</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Frequência (domingos/feriados)</label>
                                <input type="text" name="jornada_frequencia" class="form-control form-control-sm" placeholder="Ex: Todo domingo">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Dia(s) de folga</label>
                                <input type="text" name="jornada_folga_dias" class="form-control form-control-sm" placeholder="Ex: Segunda-feira">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Folga no domingo do mês?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="jornada_domingo_mes" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="jornada_domingo_mes" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Trabalhava 6 dias consecutivos?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="jornada_6_dias" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="jornada_6_dias" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Quantos dias consecutivos?</label>
                                <input type="number" name="jornada_dias_consecutivos" class="form-control form-control-sm" placeholder="Ex: 6">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Intervalo de refeição</label>
                                <input type="text" name="jornada_intervalo_refeicao" class="form-control form-control-sm" placeholder="Ex: 1 hora">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Intervalo inferior ao legal?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="jornada_intervalo_inferior" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="jornada_intervalo_inferior" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Quantas vezes por semana?</label>
                                <input type="number" name="jornada_intervalo_vezes_semana" class="form-control form-control-sm" placeholder="Ex: 5">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 8: Cartão de Ponto ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">8</span>
                        <h6 class="section-title">Cartão de Ponto</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Tinha controle de ponto?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="ponto_tem" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="ponto_tem" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Tipo de ponto</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="ponto_tipo[]" value="papel"> Papel</label>
                                <label class="check-item"><input type="checkbox" name="ponto_tipo[]" value="biometrico"> Biométrico</label>
                                <label class="check-item"><input type="checkbox" name="ponto_tipo[]" value="eletronico"> Eletrônico</label>
                                <label class="check-item"><input type="checkbox" name="ponto_tipo[]" value="aplicativo"> Aplicativo</label>
                                <label class="check-item"><input type="checkbox" name="ponto_tipo[]" value="outro"> Outro</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Celular pessoal ou da empresa?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="ponto_celular" value="pessoal"> Pessoal</label>
                                <label class="check-item"><input type="radio" name="ponto_celular" value="empresa"> Da empresa</label>
                                <label class="check-item"><input type="radio" name="ponto_celular" value="nao_aplicavel"> N/A</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Empresa com mais de 20 funcionários?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="ponto_mais_20_func" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="ponto_mais_20_func" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="ponto_mais_20_func" value="nao_sabe"> Não sabe</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Ponto registrava a jornada verdadeira?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="ponto_verdadeiro" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="ponto_verdadeiro" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Havia alteração dos cartões de ponto?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="ponto_alteracao" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="ponto_alteracao" value="nao"> Não</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 9: Salário e Remuneração ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">9</span>
                        <h6 class="section-title">Salário e Remuneração</h6>
                    </div>
                    <div class="section-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Último Salário</label>
                                <input type="text" name="salario_ultimo" class="form-control form-control-sm" placeholder="R$ 0,00" data-mask="money">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Recebia pagamento "por fora"?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="salario_por_fora" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="salario_por_fora" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Valor pago "por fora"</label>
                                <input type="text" name="salario_valor_por_fora" class="form-control form-control-sm" placeholder="R$ 0,00" data-mask="money">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Forma de pagamento</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="salario_forma_pagamento[]" value="dinheiro"> Dinheiro</label>
                                <label class="check-item"><input type="checkbox" name="salario_forma_pagamento[]" value="deposito"> Depósito bancário</label>
                                <label class="check-item"><input type="checkbox" name="salario_forma_pagamento[]" value="pix"> PIX</label>
                                <label class="check-item"><input type="checkbox" name="salario_forma_pagamento[]" value="cheque"> Cheque</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Provas do pagamento "por fora"</label>
                            <textarea name="salario_prova_por_fora" class="form-control form-control-sm" rows="3" placeholder="Descreva as provas disponíveis"></textarea>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Comissões</p>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Recebia comissões?</label>
                                    <div class="check-group mt-1">
                                        <label class="check-item"><input type="radio" name="comissoes_recebia" value="sim"> Sim</label>
                                        <label class="check-item"><input type="radio" name="comissoes_recebia" value="nao"> Não</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Valor / Percentual</label>
                                    <input type="text" name="comissoes_valor" class="form-control form-control-sm" placeholder="Ex: 5% ou R$ 500,00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 10: Transferência ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">10</span>
                        <h6 class="section-title">Transferência</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Foi transferido?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="transferencia_foi" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="transferencia_foi" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data da Transferência</label>
                                <input type="date" name="transferencia_data" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Motivo da transferência</label>
                            <textarea name="transferencia_motivo" class="form-control form-control-sm" rows="2" placeholder="Descreva o motivo"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Implicou mudança de endereço?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="transferencia_mudanca_endereco" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="transferencia_mudanca_endereco" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Provisória ou definitiva?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="transferencia_tipo" value="provisoria"> Provisória</label>
                                <label class="check-item"><input type="radio" name="transferencia_tipo" value="definitiva"> Definitiva</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Empresa arcou com as despesas?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="transferencia_despesas" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="transferencia_despesas" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="transferencia_despesas" value="parcial"> Parcialmente</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Detalhes adicionais</label>
                            <textarea name="transferencia_detalhes" class="form-control form-control-sm" rows="2" placeholder="Outros detalhes sobre a transferência"></textarea>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 11: Trabalho Fora de Ipatinga ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">11</span>
                        <h6 class="section-title">Trabalho Fora de Ipatinga</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Trabalhou fora de Ipatinga?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="fora_ipatinga" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="fora_ipatinga" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Localidade</label>
                                <input type="text" name="fora_ipatinga_localidade" class="form-control form-control-sm" placeholder="Cidade/Estado">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Contrato celebrado por telefone?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="fora_ipatinga_contrato_telefone" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="fora_ipatinga_contrato_telefone" value="nao"> Não</label>
                            </div>
                        </div>
                        <p class="accent-note">Quando o contrato é celebrado em uma cidade e o trabalho é realizado em outra, pode haver competência da Vara do Trabalho da cidade contratante.</p>
                    </div>
                </div>

                {{-- ─── Section 12: Insalubridade e Periculosidade ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">12</span>
                        <h6 class="section-title">Insalubridade e Periculosidade</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Estava exposto a agentes nocivos?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="insalubridade_exposto" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="insalubridade_exposto" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Agentes nocivos</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="insalubridade_agentes[]" value="ruido"> Ruído</label>
                                <label class="check-item"><input type="checkbox" name="insalubridade_agentes[]" value="calor"> Calor</label>
                                <label class="check-item"><input type="checkbox" name="insalubridade_agentes[]" value="quimico"> Químico</label>
                                <label class="check-item"><input type="checkbox" name="insalubridade_agentes[]" value="biologico"> Biológico</label>
                                <label class="check-item"><input type="checkbox" name="insalubridade_agentes[]" value="eletricidade"> Eletricidade</label>
                                <label class="check-item"><input type="checkbox" name="insalubridade_agentes[]" value="explosivos"> Explosivos/Inflamáveis</label>
                                <label class="check-item"><input type="checkbox" name="insalubridade_agentes[]" value="outro"> Outro</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Outros agentes</label>
                                <input type="text" name="insalubridade_outros_agentes" class="form-control form-control-sm" placeholder="Descreva outros agentes">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Quantidade de banheiros</label>
                                <input type="number" name="insalubridade_qtd_banheiro" class="form-control form-control-sm" placeholder="Ex: 2">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Recebia adicional de insalubridade/periculosidade?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="insalubridade_adicional" value="insalubridade"> Insalubridade</label>
                                <label class="check-item"><input type="radio" name="insalubridade_adicional" value="periculosidade"> Periculosidade</label>
                                <label class="check-item"><input type="radio" name="insalubridade_adicional" value="nao"> Não recebia</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 13: EPIs ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">13</span>
                        <h6 class="section-title">EPIs</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">EPI era necessário?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="epi_necessario" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="epi_necessario" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">EPIs recebidos</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="epi_recebeu[]" value="luvas"> Luvas</label>
                                <label class="check-item"><input type="checkbox" name="epi_recebeu[]" value="capacete"> Capacete</label>
                                <label class="check-item"><input type="checkbox" name="epi_recebeu[]" value="bota"> Bota</label>
                                <label class="check-item"><input type="checkbox" name="epi_recebeu[]" value="protetor_auricular"> Protetor auricular</label>
                                <label class="check-item"><input type="checkbox" name="epi_recebeu[]" value="oculos"> Óculos</label>
                                <label class="check-item"><input type="checkbox" name="epi_recebeu[]" value="mascara"> Máscara</label>
                                <label class="check-item"><input type="checkbox" name="epi_recebeu[]" value="outro"> Outro</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Outros EPIs</label>
                                <input type="text" name="epi_outros" class="form-control form-control-sm" placeholder="Descreva outros EPIs">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Recebeu treinamento para uso?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="epi_treinamento" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="epi_treinamento" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">EPIs eram substituídos quando necessário?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="epi_substituidos" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="epi_substituidos" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Usava os EPIs corretamente?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="epi_usava" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="epi_usava" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Empresa fiscalizava o uso?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="epi_fiscalizava" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="epi_fiscalizava" value="nao"> Não</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 14: Acidente de Trabalho ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">14</span>
                        <h6 class="section-title">Acidente de Trabalho</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Sofreu acidente de trabalho?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="acidente_sofreu" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="acidente_sofreu" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Detalhes do acidente</label>
                            <textarea name="acidente_detalhes" class="form-control form-control-sm" rows="3" placeholder="Descreva como ocorreu o acidente"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data do acidente</label>
                                <input type="date" name="acidente_data" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">CAT foi emitida?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="acidente_cat" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="acidente_cat" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Quem emitiu a CAT?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="acidente_cat_emissor" value="empresa"> Empresa</label>
                                <label class="check-item"><input type="radio" name="acidente_cat_emissor" value="sindicato"> Sindicato</label>
                                <label class="check-item"><input type="radio" name="acidente_cat_emissor" value="medico"> Médico</label>
                                <label class="check-item"><input type="radio" name="acidente_cat_emissor" value="autoridade"> Autoridade pública</label>
                                <label class="check-item"><input type="radio" name="acidente_cat_emissor" value="nao_emitida"> Não foi emitida</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 15: Problemas de Saúde Relacionados ao Trabalho ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">15</span>
                        <h6 class="section-title">Problemas de Saúde Relacionados ao Trabalho</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Tem problema de saúde relacionado ao trabalho?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="saude_problema" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="saude_problema" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Quais problemas de saúde?</label>
                            <textarea name="saude_quais" class="form-control form-control-sm" rows="2" placeholder="Liste os problemas de saúde"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Como se desenvolveu?</label>
                            <textarea name="saude_desenvolvimento" class="form-control form-control-sm" rows="2" placeholder="Descreva como o problema se desenvolveu"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Período</label>
                                <input type="text" name="saude_periodo" class="form-control form-control-sm" placeholder="Ex: 2020 a 2023">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Possui relatório médico?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="saude_relatorio" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="saude_relatorio" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Situações pessoais que podem ter contribuído?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="saude_situacoes_pessoais" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="saude_situacoes_pessoais" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Quais situações pessoais?</label>
                            <textarea name="saude_situacoes_pessoais_quais" class="form-control form-control-sm" rows="2" placeholder="Descreva as situações pessoais"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Empresa estava ciente do problema?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="saude_empresa_ciente" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="saude_empresa_ciente" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Permanece com o problema?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="saude_permanece" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="saude_permanece" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Tipo de tratamento</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="saude_tratamento[]" value="medico"> Médico</label>
                                <label class="check-item"><input type="checkbox" name="saude_tratamento[]" value="fisioterapia"> Fisioterapia</label>
                                <label class="check-item"><input type="checkbox" name="saude_tratamento[]" value="psicologo"> Psicólogo</label>
                                <label class="check-item"><input type="checkbox" name="saude_tratamento[]" value="cirurgia"> Cirurgia</label>
                                <label class="check-item"><input type="checkbox" name="saude_tratamento[]" value="nenhum"> Nenhum</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Faz uso de medicamentos?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="saude_medicamentos" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="saude_medicamentos" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Quais medicamentos?</label>
                                <input type="text" name="saude_medicamentos_quais" class="form-control form-control-sm" placeholder="Liste os medicamentos">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 16: Afastamento do Trabalho ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">16</span>
                        <h6 class="section-title">Afastamento do Trabalho</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Foi afastado do trabalho?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="afastamento_foi" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="afastamento_foi" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Motivo do afastamento</label>
                                <input type="text" name="afastamento_motivo" class="form-control form-control-sm" placeholder="Ex: Acidente, Doença">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data de início</label>
                                <input type="date" name="afastamento_data_inicio" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data de fim</label>
                                <input type="date" name="afastamento_data_fim" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Senha Gov.br</label>
                                <input type="text" name="afastamento_senha_gov" class="form-control form-control-sm" placeholder="Senha de acesso Gov.br">
                            </div>
                        </div>
                        <p class="accent-note">A senha Gov.br é necessária para acessar o extrato de benefício previdenciário do cliente junto ao INSS.</p>
                    </div>
                </div>

                {{-- ─── Section 17: FGTS ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">17</span>
                        <h6 class="section-title">FGTS</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Empresa recolhia o FGTS corretamente?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="fgts_recolhia" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="fgts_recolhia" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="fgts_recolhia" value="parcial"> Parcialmente</label>
                                <label class="check-item"><input type="radio" name="fgts_recolhia" value="nao_sabe"> Não sabe</label>
                            </div>
                        </div>
                        <p class="accent-note">Para verificar o saldo do FGTS, o trabalhador pode acessar o aplicativo FGTS disponível para smartphones ou o site da Caixa Econômica Federal.</p>
                    </div>
                </div>

                {{-- ─── Section 18: Equiparação Salarial ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">18</span>
                        <h6 class="section-title">Equiparação Salarial</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Tem colega com salário superior realizando as mesmas funções?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="equiparacao_colega" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="equiparacao_colega" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nome do colega paradigma</label>
                                <input type="text" name="equiparacao_nome_colega" class="form-control form-control-sm" placeholder="Nome completo">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Função do colega</label>
                                <input type="text" name="equiparacao_funcao_colega" class="form-control form-control-sm" placeholder="Cargo/função">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Atividades exercidas pelo colega</label>
                            <textarea name="equiparacao_atividades" class="form-control form-control-sm" rows="2" placeholder="Descreva as atividades do colega"></textarea>
                        </div>
                        <div>
                            <label class="form-label">As atividades eram idênticas?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="equiparacao_identicas" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="equiparacao_identicas" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Diferença superior a 2 anos na função?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="equiparacao_2_anos" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="equiparacao_2_anos" value="nao"> Não</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 19: Desvio e Acúmulo de Função ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">19</span>
                        <h6 class="section-title">Desvio e Acúmulo de Função</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Houve desvio de função?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="desvio_funcao" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="desvio_funcao" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Atividades realizadas além da função contratada</label>
                            <textarea name="desvio_atividades" class="form-control form-control-sm" rows="2" placeholder="Descreva as atividades exercidas em desvio de função"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Período de (início)</label>
                                <input type="date" name="desvio_periodo_de" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Período até (fim)</label>
                                <input type="date" name="desvio_periodo_ate" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Acúmulo de Função</p>
                            <div>
                                <label class="form-label">Houve acúmulo de função?</label>
                                <div class="check-group mt-1">
                                    <label class="check-item"><input type="radio" name="acumulo_funcao" value="sim"> Sim</label>
                                    <label class="check-item"><input type="radio" name="acumulo_funcao" value="nao"> Não</label>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-12">
                                    <label class="form-label">Atividades acumuladas</label>
                                    <textarea name="acumulo_atividades" class="form-control form-control-sm" rows="2" placeholder="Descreva as atividades acumuladas"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 20: Extinção do Contrato de Trabalho ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">20</span>
                        <h6 class="section-title">Extinção do Contrato de Trabalho</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Causa da extinção</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="extincao_causa" value="sem_justa_causa"> Dispensa sem justa causa</label>
                                <label class="check-item"><input type="radio" name="extincao_causa" value="justa_causa"> Dispensa por justa causa</label>
                                <label class="check-item"><input type="radio" name="extincao_causa" value="pedido_demissao"> Pedido de demissão</label>
                                <label class="check-item"><input type="radio" name="extincao_causa" value="rescisao_indireta"> Rescisão indireta</label>
                                <label class="check-item"><input type="radio" name="extincao_causa" value="comum_acordo"> Comum acordo</label>
                                <label class="check-item"><input type="radio" name="extincao_causa" value="termino_contrato"> Término de contrato</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Forma de comunicação do término</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="extincao_termino[]" value="pessoalmente"> Pessoalmente</label>
                                <label class="check-item"><input type="checkbox" name="extincao_termino[]" value="carta"> Por carta</label>
                                <label class="check-item"><input type="checkbox" name="extincao_termino[]" value="whatsapp"> WhatsApp</label>
                                <label class="check-item"><input type="checkbox" name="extincao_termino[]" value="email"> E-mail</label>
                                <label class="check-item"><input type="checkbox" name="extincao_termino[]" value="telefone"> Telefone</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Dias de experiência restantes</label>
                                <input type="number" name="extincao_dias_experiencia" class="form-control form-control-sm" placeholder="Ex: 15">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Forma acordada de rescisão</label>
                            <textarea name="extincao_forma_acordada" class="form-control form-control-sm" rows="2" placeholder="Descreva os termos acordados"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Recebeu todas as verbas rescisórias certas?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="extincao_verbas_certas" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="extincao_verbas_certas" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="extincao_verbas_certas" value="parcial"> Parcialmente</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data de pagamento das verbas</label>
                                <input type="date" name="extincao_data_pagamento" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Valor total pago</label>
                                <input type="text" name="extincao_valor_pago" class="form-control form-control-sm" placeholder="R$ 0,00" data-mask="money">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Forma de pagamento das verbas</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="extincao_forma_pagamento[]" value="dinheiro"> Dinheiro</label>
                                <label class="check-item"><input type="checkbox" name="extincao_forma_pagamento[]" value="deposito"> Depósito bancário</label>
                                <label class="check-item"><input type="checkbox" name="extincao_forma_pagamento[]" value="pix"> PIX</label>
                                <label class="check-item"><input type="checkbox" name="extincao_forma_pagamento[]" value="cheque"> Cheque</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data de entrega das guias</label>
                                <input type="date" name="extincao_data_entrega_guias" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Há erros nas guias?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="extincao_erro_guias" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="extincao_erro_guias" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Quais erros nas guias?</label>
                                <input type="text" name="extincao_quais_erros" class="form-control form-control-sm" placeholder="Descreva os erros">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Guias recebidas</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="extincao_guias[]" value="fgts"> FGTS</label>
                                <label class="check-item"><input type="checkbox" name="extincao_guias[]" value="seguro_desemprego"> Seguro-desemprego</label>
                                <label class="check-item"><input type="checkbox" name="extincao_guias[]" value="ctps"> CTPS anotada</label>
                                <label class="check-item"><input type="checkbox" name="extincao_guias[]" value="termo_rescisao"> Termo de rescisão</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Solicitou seguro-desemprego?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="extincao_solicitou_seguro" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="extincao_solicitou_seguro" value="nao"> Não</label>
                                <label class="check-item"><input type="radio" name="extincao_solicitou_seguro" value="pendente"> Pendente</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 21: Testemunhas e Ações Anteriores ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">21</span>
                        <h6 class="section-title">Testemunhas e Ações Anteriores</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Nomes das testemunhas</label>
                            <textarea name="testemunhas_nomes" class="form-control form-control-sm" rows="3" placeholder="Nome e contato das testemunhas"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Possui ação trabalhista anterior?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="acao_anterior" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="acao_anterior" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Data da ação anterior</label>
                                <input type="date" name="acao_anterior_data" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label">Número do processo</label>
                                <input type="text" name="acao_anterior_processo" class="form-control form-control-sm" placeholder="0000000-00.0000.0.00.0000">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Motivos da ação anterior</label>
                            <textarea name="acao_anterior_motivos" class="form-control form-control-sm" rows="2" placeholder="Descreva os motivos da ação anterior"></textarea>
                        </div>

                        <div class="pt-3 border-top mt-2">
                            <p class="subsection-title">Testemunha da Defesa</p>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Nome da testemunha da defesa</label>
                                    <input type="text" name="testemunha_defesa_nome" class="form-control form-control-sm" placeholder="Nome completo">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Contato</label>
                                    <input type="text" name="testemunha_defesa_contato" class="form-control form-control-sm" placeholder="Telefone ou e-mail" data-mask="phone">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 22: Benefícios da Convenção Coletiva ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">22</span>
                        <h6 class="section-title">Benefícios da Convenção Coletiva</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Benefícios recebidos</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="beneficios[]" value="lanche"> Lanche</label>
                                <label class="check-item"><input type="checkbox" name="beneficios[]" value="vale_combustivel"> Vale combustível</label>
                                <label class="check-item"><input type="checkbox" name="beneficios[]" value="abono"> Abono</label>
                                <label class="check-item"><input type="checkbox" name="beneficios[]" value="vale_alimentacao"> Vale alimentação</label>
                                <label class="check-item"><input type="checkbox" name="beneficios[]" value="plano_saude"> Plano de saúde</label>
                                <label class="check-item"><input type="checkbox" name="beneficios[]" value="transporte"> Vale transporte</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Composição do lanche</label>
                                <input type="text" name="beneficio_lanche_composicao" class="form-control form-control-sm" placeholder="Ex: Pão, café, fruta">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Valor do vale combustível</label>
                                <input type="text" name="beneficio_vale_combustivel_valor" class="form-control form-control-sm" placeholder="R$ 0,00" data-mask="money">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Valor do abono</label>
                                <input type="text" name="beneficio_abono_valor" class="form-control form-control-sm" placeholder="R$ 0,00" data-mask="money">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Meio de recebimento do vale alimentação</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="checkbox" name="beneficio_vale_alimentacao_meio[]" value="cartao"> Cartão</label>
                                <label class="check-item"><input type="checkbox" name="beneficio_vale_alimentacao_meio[]" value="dinheiro"> Dinheiro</label>
                                <label class="check-item"><input type="checkbox" name="beneficio_vale_alimentacao_meio[]" value="cheque"> Cheque</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Valor do vale alimentação</label>
                                <input type="text" name="beneficio_vale_alimentacao_valor" class="form-control form-control-sm" placeholder="R$ 0,00" data-mask="money">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Participou da homologação?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="beneficio_homologacao" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="beneficio_homologacao" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Outros benefícios</label>
                            <textarea name="beneficio_outros" class="form-control form-control-sm" rows="2" placeholder="Descreva outros benefícios previstos na CCT"></textarea>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 23: Salário Família ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">23</span>
                        <h6 class="section-title">Salário Família</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Possui filhos menores de 14 anos?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="salario_familia_filhos" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="salario_familia_filhos" value="nao"> Não</label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Nomes dos filhos</label>
                            <textarea name="salario_familia_nomes" class="form-control form-control-sm" rows="2" placeholder="Nome e data de nascimento de cada filho"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Idade(s)</label>
                                <input type="text" name="salario_familia_idades" class="form-control form-control-sm" placeholder="Ex: 5, 8, 12">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Recebeu salário família?</label>
                            <div class="check-group mt-1">
                                <label class="check-item"><input type="radio" name="salario_familia_recebeu" value="sim"> Sim</label>
                                <label class="check-item"><input type="radio" name="salario_familia_recebeu" value="nao"> Não</label>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Valor recebido</label>
                                <input type="text" name="salario_familia_valor" class="form-control form-control-sm" placeholder="R$ 0,00" data-mask="money">
                            </div>
                        </div>
                        <p class="accent-note">O salário família é devido ao empregado de baixa renda para auxiliar no sustento dos filhos menores de 14 anos ou inválidos. O valor é fixado pelo governo e atualizado periodicamente.</p>
                    </div>
                </div>

                {{-- ─── Section 24: Outras Informações ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">24</span>
                        <h6 class="section-title">Outras Informações</h6>
                    </div>
                    <div class="section-body">
                        <div>
                            <label class="form-label">Informações adicionais relevantes</label>
                            <textarea name="outras_informacoes" class="form-control form-control-sm" rows="5" placeholder="Registre aqui quaisquer outras informações relevantes para o caso que não foram abordadas nos campos anteriores"></textarea>
                        </div>
                    </div>
                </div>

                {{-- ─── Section 25: Relação de Documentos ─── --}}
                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge">25</span>
                        <h6 class="section-title">Relação de Documentos</h6>
                    </div>
                    <div class="section-body">
                        <p class="accent-note">Liste abaixo todos os documentos recebidos do cliente durante o atendimento. Esses documentos serão utilizados para instruir a petição inicial e devem ser devidamente organizados e conferidos.</p>
                        <div>
                            <label class="form-label">Documentos recebidos</label>
                            <textarea name="documentos_recebidos" class="form-control form-control-sm" rows="5" placeholder="Ex: RG, CPF, CTPS, Contrato de trabalho, Holerites, Cartões de ponto..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- Área de assinatura (visível somente na impressão) --}}
                <div class="print-signature-area">
                    <div class="section-card">
                        <div class="section-header">
                            <span class="section-badge"><i class="fa-solid fa-signature" style="font-size:11px;"></i></span>
                            <h6 class="section-title">Assinatura do Cliente</h6>
                        </div>
                        <div class="section-body" style="padding:2rem 1.25rem;">
                            <p style="font-size:12px;color:#64748b;margin-bottom:2rem;">
                                Declaro que as informações acima são verdadeiras e que autorizo o escritório GB Advocacia a utilizá-las para fins de elaboração da petição inicial.
                            </p>
                            <div class="row">
                                <div class="col-6">
                                    <div style="border-top:1px solid #000;padding-top:8px;text-align:center;font-size:12px;">
                                        Assinatura do(a) Cliente
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div style="border-top:1px solid #000;padding-top:8px;text-align:center;font-size:12px;">
                                        Data
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="d-flex flex-column gap-3 flex-shrink-0 w-100" style="max-width: 453px;">
                <x-preliminares-panel :preliminares="$preliminares" />
                <x-orders-panel :pedidos="$pedidos" buttonText="Finalizar atendimento" />
            </div>
    </div>
    </form>
@endsection

@section('scripts')
<script>
    // ─── Input Masks ───
    function maskCpf(v) {
        return v.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2').slice(0, 14);
    }
    function maskCnpj(v) {
        return v.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2').slice(0, 18);
    }
    function maskCep(v) {
        return v.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2').slice(0, 9);
    }
    function maskPhone(v) {
        v = v.replace(/\D/g, '');
        if (v.length <= 10) {
            return v.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2').slice(0, 14);
        }
        return v.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2').slice(0, 15);
    }
    function maskPis(v) {
        return v.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{5})(\d)/, '$1.$2').replace(/(\d{2})(\d{1})$/, '$1-$2').slice(0, 14);
    }
    function maskMoney(v) {
        v = v.replace(/\D/g, '');
        if (v === '') { return ''; }
        var num = parseInt(v, 10);
        var formatted = (num / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return 'R$ ' + formatted;
    }

    function applyMask(input, maskFn) {
        input.addEventListener('input', function () {
            var pos = this.selectionStart;
            var oldLen = this.value.length;
            this.value = maskFn(this.value);
            var newLen = this.value.length;
            var newPos = pos + (newLen - oldLen);
            this.setSelectionRange(newPos, newPos);
        });
    }

    function applyAllMasks(container) {
        container = container || document;
        container.querySelectorAll('[data-mask]').forEach(function (el) {
            if (el._maskApplied) { return; }
            el._maskApplied = true;
            var fn = {
                'cpf': maskCpf,
                'cnpj': maskCnpj,
                'cep': maskCep,
                'phone': maskPhone,
                'pis': maskPis,
                'money': maskMoney,
            }[el.getAttribute('data-mask')];
            if (fn) { applyMask(el, fn); }
        });
    }

    function formatExistingValues(container) {
        container = container || document;
        container.querySelectorAll('[data-mask]').forEach(function (el) {
            if (el.value) {
                var fn = {
                    'cpf': maskCpf,
                    'cnpj': maskCnpj,
                    'cep': maskCep,
                    'phone': maskPhone,
                    'pis': maskPis,
                    'money': maskMoney,
                }[el.getAttribute('data-mask')];
                if (fn) { el.value = fn(el.value); }
            }
        });
    }

    // ─── Lookup de CEP (ViaCEP) ───
    function lookupCep(input) {
        var prefix = input.dataset.cepLookup;
        if (!prefix) { return; }

        var cep = (input.value || '').replace(/\D/g, '');
        if (cep.length !== 8) { return; }

        var fields = {
            endereco: document.querySelector('[name="' + prefix + '_endereco"]'),
            bairro: document.querySelector('[name="' + prefix + '_bairro"]'),
            cidade: document.querySelector('[name="' + prefix + '_cidade"]'),
            uf: document.querySelector('[name="' + prefix + '_uf"]'),
        };

        input.disabled = true;
        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.erro) { return; }
                if (fields.endereco) { fields.endereco.value = data.logradouro || ''; }
                if (fields.bairro) { fields.bairro.value = data.bairro || ''; }
                if (fields.cidade) { fields.cidade.value = data.localidade || ''; }
                if (fields.uf) { fields.uf.value = data.uf || ''; }
            })
            .catch(function () {})
            .finally(function () { input.disabled = false; });
    }

    function applyCepLookup(container) {
        container = container || document;
        container.querySelectorAll('[data-cep-lookup]').forEach(function (el) {
            if (el._cepLookupApplied) { return; }
            el._cepLookupApplied = true;
            el.addEventListener('blur', function () { lookupCep(el); });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        applyAllMasks();
        applyCepLookup();
    });

    function addFuncao() {
        const container = document.getElementById('funcoes-container');
        const index = container.querySelectorAll('.funcao-row').length;
        const row = document.createElement('div');
        row.className = 'funcao-row row g-3 mb-3';
        row.innerHTML = `
            <div class="col-12 col-md-4">
                <label class="form-label">Função</label>
                <input type="text" name="funcoes[${index}][nome]" class="form-control form-control-sm" placeholder="Ex: Auxiliar de Produção">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">De</label>
                <input type="date" name="funcoes[${index}][de]" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Até</label>
                <input type="date" name="funcoes[${index}][ate]" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.funcao-row').remove()">
                    <i class="fa-solid fa-trash fa-xs"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    function addTomadora() {
        const container = document.getElementById('tomadoras-container');
        const index = container.querySelectorAll('.tomadora-row').length;
        const row = document.createElement('div');
        row.className = 'tomadora-row row g-3 mb-3';
        row.innerHTML = `
            <div class="col-12 col-md-5">
                <label class="form-label">Nome da Tomadora</label>
                <input type="text" name="tomadoras[${index}][nome]" class="form-control form-control-sm" placeholder="Razão social">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">CNPJ</label>
                <input type="text" name="tomadoras[${index}][cnpj]" class="form-control form-control-sm" placeholder="00.000.000/0000-00" data-mask="cnpj">
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.tomadora-row').remove()">
                    <i class="fa-solid fa-trash fa-xs"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
        applyAllMasks(row);
    }

    @if($prefill)
    document.addEventListener('DOMContentLoaded', function () {
        const prefill = @json($prefill);

        // Scalar fields (text, date, number, textarea, email)
        Object.entries(prefill.scalars || {}).forEach(([name, value]) => {
            if (!value) { return; }
            const el = document.querySelector(`[name="${name}"]`);
            if (el) { el.value = value; }
        });

        // Radio buttons
        Object.entries(prefill.radios || {}).forEach(([name, value]) => {
            const el = document.querySelector(`[name="${name}"][value="${value}"]`);
            if (el) { el.checked = true; }
        });

        // Checkbox lists
        Object.entries(prefill.lists || {}).forEach(([name, values]) => {
            values.forEach(v => {
                const el = document.querySelector(`[name="${name}[]"][value="${v}"]`);
                if (el) { el.checked = true; }
            });
        });

        // Dynamic rows: funções
        (prefill.funcoes || []).forEach(row => {
            addFuncao();
            const rows = document.querySelectorAll('.funcao-row');
            const idx  = rows.length - 1;
            const last = rows[idx];
            last.querySelector(`[name="funcoes[${idx}][nome]"]`).value = row.nome || '';
            last.querySelector(`[name="funcoes[${idx}][de]"]`).value  = row.de   || '';
            last.querySelector(`[name="funcoes[${idx}][ate]"]`).value = row.ate  || '';
        });

        // Dynamic rows: tomadoras do empregador
        (prefill.tomadoras || []).forEach(row => {
            addTomadora();
            const rows = document.querySelectorAll('#tomadoras-container .tomadora-row');
            const idx  = rows.length - 1;
            const last = rows[idx];
            last.querySelector(`[name="tomadoras[${idx}][nome]"]`).value = row.nome || '';
            last.querySelector(`[name="tomadoras[${idx}][cnpj]"]`).value = row.cnpj || '';
        });

        // Dynamic rows: tomadoras da jornada
        (prefill.jornada_tomadoras || []).forEach(row => {
            addJornadaTomadora();
            const rows = document.querySelectorAll('#jornada-tomadoras-container .tomadora-row');
            const idx  = rows.length - 1;
            const last = rows[idx];
            last.querySelector(`[name="jornada_tomadoras[${idx}][nome]"]`).value  = row.nome  || '';
            last.querySelector(`[name="jornada_tomadoras[${idx}][local]"]`).value = row.local || '';
        });

        // Pedidos panel
        (prefill.pedidos || []).forEach(p => addPedido(p));

        // Preliminares panel
        const prefillPreliminarIds = (prefill.preliminares || []).map(p => String(p.id));
        document.querySelectorAll('.preliminar-checkbox').forEach(cb => {
            cb.checked = prefillPreliminarIds.includes(cb.value);
        });
        if (typeof onPreliminarChange === 'function') { onPreliminarChange(); }

        // Apply masks to prefilled values
        formatExistingValues();
    });
    @endif

    function addJornadaTomadora() {
        const container = document.getElementById('jornada-tomadoras-container');
        const index = container.querySelectorAll('.tomadora-row').length;
        const row = document.createElement('div');
        row.className = 'tomadora-row row g-3 mb-3';
        row.innerHTML = `
            <div class="col-12 col-md-5">
                <label class="form-label">Nome da Tomadora</label>
                <input type="text" name="jornada_tomadoras[${index}][nome]" class="form-control form-control-sm" placeholder="Razão social">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Local</label>
                <input type="text" name="jornada_tomadoras[${index}][local]" class="form-control form-control-sm" placeholder="Cidade/Endereço">
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.tomadora-row').remove()">
                    <i class="fa-solid fa-trash fa-xs"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var printBtn = document.getElementById('btn-print-form');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                var form = document.getElementById('appointment-form');
                var formData = new FormData(form);

                var originalText = printBtn.innerHTML;
                printBtn.disabled = true;
                printBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Gerando...';

                fetch('{{ route('appointment.print') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': formData.get('_token'),
                    },
                    body: formData,
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (data) {
                            throw new Error(data.message || 'Erro ao gerar formulário.');
                        });
                    }

                    var disposition = response.headers.get('Content-Disposition') || '';
                    var filenameMatch = disposition.match(/filename="?([^";\s]+)"?/);
                    var filename = filenameMatch ? filenameMatch[1] : 'formulario-atendimento.docx';

                    return response.blob().then(function (blob) {
                        var url = URL.createObjectURL(blob);
                        var link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                    });
                })
                .catch(function (err) {
                    alert(err.message || 'Erro de conexão. Tente novamente.');
                })
                .finally(function () {
                    printBtn.disabled = false;
                    printBtn.innerHTML = originalText;
                });
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('appointment-form');
        if (!form) { return; }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var btn = document.getElementById('btn-submit-appointment');
            var originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Carregando...';

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                },
                body: formData,
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (data) {
                        throw new Error(data.message || 'Erro ao gerar petição.');
                    });
                }

                var disposition = response.headers.get('Content-Disposition') || '';
                var filenameMatch = disposition.match(/filename="?([^";\s]+)"?/);
                var filename = filenameMatch ? filenameMatch[1] : 'peticao-inicial.docx';

                return response.blob().then(function (blob) {
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);

                    btn.disabled = false;
                    btn.innerHTML = originalText;

                    Swal.fire({
                        title: 'Petição gerada com sucesso!',
                        text: 'Deseja gerar outra petição?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--gb-navy)',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sim, gerar outra',
                        cancelButtonText: 'Não',
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route('appointment.create') }}';
                        }
                    });
                });
            })
            .catch(function (err) {
                alert(err.message || 'Erro de conexão. Tente novamente.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    });
</script>
@endsection
