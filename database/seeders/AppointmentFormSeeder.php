<?php

namespace Database\Seeders;

use App\Models\AppointmentFormQuestion;
use App\Models\AppointmentFormStep;
use Illuminate\Database\Seeder;

/**
 * Loads the office's attendance form ("Ficha de atendimento atualizada") as the first
 * version of the configurable form. It only runs when the form is still empty.
 */
class AppointmentFormSeeder extends Seeder
{
    private int $headingCount = 0;

    private int $noticeCount = 0;

    public function run(): void
    {
        if (AppointmentFormStep::query()->exists()) {
            return;
        }

        foreach ($this->steps() as $stepPosition => $step) {
            $model = AppointmentFormStep::create([
                'title' => $step['title'],
                'description' => $step['description'],
                'icon' => $step['icon'],
                'position' => $stepPosition + 1,
            ]);

            foreach ($step['questions'] as $position => $question) {
                AppointmentFormQuestion::create([
                    'appointment_form_step_id' => $model->id,
                    'key' => $question['key'],
                    'type' => $question['type'],
                    'label' => $question['label'],
                    'help' => $question['help'] ?? null,
                    'placeholder' => $question['placeholder'] ?? null,
                    'options' => $question['options'] ?? null,
                    'show_if' => $question['show_if'] ?? null,
                    'width' => $question['width'] ?? 'full',
                    'required' => $question['required'] ?? false,
                    'send_to_ai' => $question['send_to_ai'] ?? true,
                    'is_system' => in_array($question['key'], AppointmentFormQuestion::SYSTEM_KEYS, true),
                    'position' => $position + 1,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function q(string $key, string $type, string $label, array $extra = []): array
    {
        return ['key' => $key, 'type' => $type, 'label' => $label, ...$extra];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function heading(string $label, array $extra = []): array
    {
        return $this->q('subtitulo_'.(++$this->headingCount), 'heading', $label, $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function notice(string $label, array $extra = []): array
    {
        return $this->q('orientacao_'.(++$this->noticeCount), 'notice', $label, $extra);
    }

    /**
     * @param  list<string>  $choices
     * @return array{choices: list<string>, allow_other: bool}
     */
    private function choices(array $choices, bool $allowOther = false): array
    {
        return ['choices' => $choices, 'allow_other' => $allowOther];
    }

    /**
     * @param  list<string>|string  $values
     * @return array{key: string, values: list<string>}
     */
    private function when(string $key, array|string $values = 'Sim'): array
    {
        return ['key' => $key, 'values' => (array) $values];
    }

    /**
     * @return list<array{key: string, label: string, type: string}>
     */
    private function companyColumns(): array
    {
        return [
            ['key' => 'nome', 'label' => 'Empresa', 'type' => 'short_text'],
            ['key' => 'cnpj', 'label' => 'CNPJ/CPF', 'type' => 'cpf_cnpj'],
            ['key' => 'endereco', 'label' => 'Endereço', 'type' => 'short_text'],
            ['key' => 'email', 'label' => 'E-mail', 'type' => 'short_text'],
            ['key' => 'cep', 'label' => 'CEP', 'type' => 'cep'],
            ['key' => 'bairro', 'label' => 'Bairro', 'type' => 'short_text'],
            ['key' => 'cidade', 'label' => 'Cidade', 'type' => 'short_text'],
            ['key' => 'uf', 'label' => 'UF', 'type' => 'uf'],
        ];
    }

    /**
     * @return list<array{title: string, description: string, icon: string, questions: list<array<string, mixed>>}>
     */
    private function steps(): array
    {
        $third = ['width' => 'third'];
        $half = ['width' => 'half'];

        return [
            [
                'title' => 'Cliente',
                'description' => 'Qualificação do autor e contatos.',
                'icon' => 'user',
                'questions' => [
                    $this->notice('O preenchimento da qualificação do autor é obrigatório. A ficha de atendimento, em formato Word, deverá ser anexada à pasta do cliente.'),
                    $this->q('cliente_nome', 'short_text', 'Nome', ['width' => 'two_thirds', 'required' => true, 'placeholder' => 'Nome completo']),
                    $this->q('cliente_nascimento', 'date', 'Data de nascimento', $third),
                    $this->q('cliente_nacionalidade', 'short_text', 'Nacionalidade', [...$third, 'placeholder' => 'Ex.: brasileira']),
                    $this->q('cliente_estado_civil', 'short_text', 'Estado civil', [...$third, 'placeholder' => 'Ex.: solteira']),
                    $this->q('cliente_profissao', 'short_text', 'Profissão', $third),
                    $this->q('cliente_rg', 'short_text', 'RG', $third),
                    $this->q('cliente_orgao_expedidor', 'short_text', 'Órgão expedidor', [...$third, 'placeholder' => 'Ex.: SSP/MG']),
                    $this->q('cliente_cpf', 'cpf', 'CPF', [...$third, 'required' => true]),
                    $this->q('cliente_ctps', 'short_text', 'CTPS', $third),
                    $this->q('cliente_ctps_serie', 'short_text', 'Série', $third),
                    $this->q('cliente_pis', 'pis', 'PIS', $third),
                    $this->q('cliente_nome_mae', 'short_text', 'Filho(a) de (nome completo da mãe)'),
                    $this->heading('Endereço'),
                    $this->q('cliente_cep', 'cep', 'CEP', $third),
                    $this->q('cliente_endereco', 'short_text', 'Endereço', ['width' => 'two_thirds']),
                    $this->q('cliente_numero', 'short_text', 'Número', $third),
                    $this->q('cliente_complemento', 'short_text', 'Complemento', $third),
                    $this->q('cliente_bairro', 'short_text', 'Bairro', $third),
                    $this->q('cliente_cidade', 'short_text', 'Cidade', ['width' => 'two_thirds']),
                    $this->q('cliente_uf', 'uf', 'UF', $third),
                    $this->heading('Contatos'),
                    $this->q('contato_tel_residencial', 'phone', 'Tel. residencial', $third),
                    $this->q('contato_celular', 'phone', 'Celular/WhatsApp', $third),
                    $this->q('contato_tel_terceiro', 'phone', 'Tel. terceiro', $third),
                    $this->q('contato_nome_terceiro', 'short_text', 'Nome do terceiro', $half),
                    $this->q('contato_grau_parentesco', 'short_text', 'Grau de parentesco', [...$half, 'placeholder' => 'Ex.: esposo(a), filhos, mãe']),
                ],
            ],
            [
                'title' => 'Empregador',
                'description' => 'Empresa que assinou a CTPS, grupo econômico e tomadoras.',
                'icon' => 'building-2',
                'questions' => [
                    $this->heading('Informações do empregador (empresa que assinou a CTPS)'),
                    $this->q('empregador_nome', 'short_text', 'Empregador', ['width' => 'two_thirds', 'required' => true]),
                    $this->q('empregador_cnpj', 'cpf_cnpj', 'CNPJ/CPF', $third),
                    $this->q('empregador_cep', 'cep', 'CEP', $third),
                    $this->q('empregador_endereco', 'short_text', 'Endereço', ['width' => 'two_thirds']),
                    $this->q('empregador_numero', 'short_text', 'Número', $third),
                    $this->q('empregador_complemento', 'short_text', 'Complemento', $third),
                    $this->q('empregador_bairro', 'short_text', 'Bairro', $third),
                    $this->q('empregador_cidade', 'short_text', 'Cidade', ['width' => 'two_thirds']),
                    $this->q('empregador_uf', 'uf', 'UF', $third),
                    $this->q('empregador_email', 'short_text', 'E-mail', $half),
                    $this->q('empregador_superior', 'short_text', 'Nome do superior hierárquico', $half),
                    $this->q('grupo_economico', 'repeater', 'Empresas do mesmo grupo econômico', ['options' => ['columns' => $this->companyColumns()]]),
                    $this->q('tomadoras', 'repeater', 'Empresas tomadoras de serviços (2ª reclamada)', ['options' => ['columns' => $this->companyColumns()]]),
                    $this->q('empregador_autoriza_polo', 'yes_no', 'O reclamante autoriza incluir no polo passivo todas as empresas que estiverem relacionadas com o contrato de trabalho?'),
                ],
            ],
            [
                'title' => 'Urgência e prazos',
                'description' => 'Urgência da ação, prescrição e benefícios recebidos.',
                'icon' => 'alarm-clock',
                'questions' => [
                    $this->q('urgencia_motivos', 'multiple_choice', 'Há urgência na ação?', ['options' => $this->choices([
                        'Rescisão indireta', 'Prescrição próxima', 'Limbo jurídico', 'Contrato ainda ativo',
                        'Salário atrasado ou ausência de pagamento', 'Estabilidade provisória (gestante/acidentado)',
                        'Reconhecimento de vínculo empregatício',
                    ], true)]),
                    $this->q('urgencia_prescricao_bienal', 'date', 'Prescrição bienal em', [...$third, 'help' => 'Verificar a data correta da dispensa na CTPS/TRCT.']),
                    $this->q('urgencia_seguro_beneficio', 'yes_no', 'Esteve recebendo seguro-desemprego ou algum benefício do INSS durante o contrato de trabalho?'),
                    $this->q('urgencia_qual_beneficio', 'short_text', 'Qual benefício?', ['show_if' => $this->when('urgencia_seguro_beneficio')]),
                ],
            ],
            [
                'title' => 'Contrato e funções',
                'description' => 'Período, modalidade, funções e local de trabalho.',
                'icon' => 'briefcase',
                'questions' => [
                    $this->heading('1. Período e modalidade'),
                    $this->q('contrato_admissao', 'date', 'Admissão', $third),
                    $this->q('contrato_dispensa', 'date', 'Dispensa', $third),
                    $this->q('contrato_registro_ctps', 'yes_no', 'Possuía registro em CTPS?', $third),
                    $this->q('contrato_modalidade', 'single_choice', 'Qual era a modalidade de contrato?', ['options' => $this->choices(['Prazo indeterminado', 'Contrato de experiência', 'Prazo determinado'])]),
                    $this->q('contrato_dias_experiencia', 'number', 'Contrato de experiência: quantos dias?', [...$third, 'show_if' => $this->when('contrato_modalidade', 'Contrato de experiência')]),
                    $this->q('contrato_dias_determinado', 'number', 'Prazo determinado: quantos dias?', [...$third, 'show_if' => $this->when('contrato_modalidade', 'Prazo determinado'), 'help' => 'Verificar se se trata de contrato por obra certa, contratação específica para o fim de ano, entre outros.']),
                    $this->heading('2. Funções'),
                    $this->q('funcoes', 'repeater', 'Quais foram as funções exercidas e os períodos de cada uma?', ['options' => ['columns' => [
                        ['key' => 'nome', 'label' => 'Função', 'type' => 'short_text'],
                        ['key' => 'de', 'label' => 'De', 'type' => 'date'],
                        ['key' => 'ate', 'label' => 'Até', 'type' => 'date'],
                    ]]]),
                    $this->q('funcoes_atividades', 'long_text', '2.1 Quais são as atividades relacionadas à função?'),
                    $this->heading('2.2 Se vigilante'),
                    $this->q('vigilante_arma', 'multiple_choice', 'Portava arma?', ['options' => $this->choices(['Arma de fogo', 'Cacetete'], true)]),
                    $this->q('vigilante_curso_pf', 'yes_no', 'Tem curso de vigilante pela Polícia Federal?'),
                    $this->heading('3. Local da prestação de serviço'),
                    $this->q('jornada_cidade', 'short_text', 'Qual era o local da prestação de serviço (cidade)?', ['width' => 'two_thirds']),
                    $this->q('jornada_uf', 'uf', 'Estado', $third),
                    $this->q('jornada_empresa_local', 'short_text', 'Nas dependências de qual empresa?', $half),
                    $this->q('jornada_setores', 'short_text', 'Quais foram os setores em que trabalhou na empresa?', $half),
                    $this->q('jornada_tomadoras', 'repeater', '3.3 Em caso de serviços terceirizados: empresas tomadoras e períodos de prestação de serviços', ['options' => ['columns' => [
                        ['key' => 'nome', 'label' => 'Empresa tomadora', 'type' => 'short_text'],
                        ['key' => 'de', 'label' => 'De', 'type' => 'date'],
                        ['key' => 'ate', 'label' => 'Até', 'type' => 'date'],
                    ]]]),
                ],
            ],
            [
                'title' => 'Jornada e ponto',
                'description' => 'Horários, folgas, intervalos e cartão de ponto.',
                'icon' => 'clock',
                'questions' => [
                    $this->heading('4. Jornada'),
                    $this->q('jornada_horario_contratual', 'short_text', 'Qual era o horário de trabalho contratual (o que foi combinado com a empresa)?', $half),
                    $this->q('jornada_horario_realizado', 'short_text', 'Qual era o horário realmente realizado?', $half),
                    $this->q('jornada_dias', 'multiple_choice', 'Dias de trabalho', ['options' => $this->choices(['De segunda a sexta-feira', 'De segunda-feira a sábado', 'Escala 6x1', 'Escala 6x2'], true)]),
                    $this->q('jornada_antes_depois', 'yes_no', 'Precisava chegar mais cedo ou sair mais tarde?'),
                    $this->q('jornada_minutos_extras', 'short_text', 'Quantos minutos antes ou depois?', [...$half, 'show_if' => $this->when('jornada_antes_depois')]),
                    $this->q('jornada_vezes_semana', 'short_text', 'Quantas vezes na semana isso ocorria?', [...$half, 'show_if' => $this->when('jornada_antes_depois')]),
                    $this->q('jornada_domingos_feriados', 'multiple_choice', '4.4 Trabalhou aos domingos e feriados?', ['options' => $this->choices(['Domingos', 'Feriados'])]),
                    $this->q('jornada_frequencia', 'short_text', 'Com qual frequência?', ['show_if' => $this->when('jornada_domingos_feriados', ['Domingos', 'Feriados'])]),
                    $this->q('jornada_folga_dias', 'short_text', '4.5 A folga era destinada a quais dias?'),
                    $this->q('jornada_folga_domingo', 'yes_no', '4.6 Havia folga ao menos em um domingo no mês?'),
                    $this->q('jornada_seis_dias', 'yes_no', '4.7 Lembra-se de ter trabalhado mais de 6 dias consecutivos, sem folga, em uma única semana?'),
                    $this->q('jornada_dias_consecutivos', 'number', 'Quantos dias?', [...$third, 'show_if' => $this->when('jornada_seis_dias')]),
                    $this->q('jornada_intervalo_refeicao', 'short_text', 'Quanto tempo de intervalo para refeição realizava?', ['help' => 'Intervalo efetivamente realizado. Se responder que não realizava intervalo, pergunte quantos minutos levava para se alimentar.']),
                    $this->q('jornada_intervalo_todos_dias', 'yes_no', 'Se o intervalo era inferior a 1 hora, isso acontecia todos os dias da semana?'),
                    $this->q('jornada_intervalo_vezes_semana', 'short_text', 'Se não, ocorria em média quantas vezes?', ['show_if' => $this->when('jornada_intervalo_todos_dias', 'Não')]),
                    $this->heading('5. Cartão de ponto'),
                    $this->q('ponto_tinha_controle', 'yes_no', 'Havia cartão de ponto?'),
                    $this->q('ponto_tipo', 'multiple_choice', 'Tipo de ponto', ['options' => $this->choices(['Biométrico', 'Manual', 'Facial', 'Aplicativo de celular']), 'show_if' => $this->when('ponto_tinha_controle')]),
                    $this->q('ponto_celular', 'single_choice', 'Caso seja por aplicativo de celular: celular pessoal ou da empresa?', ['options' => $this->choices(['Pessoal', 'Da empresa']), 'show_if' => $this->when('ponto_tipo', 'Aplicativo de celular')]),
                    $this->q('ponto_mais_20', 'yes_no', 'Havia mais de 20 funcionários na empresa?'),
                    $this->q('ponto_verdadeiro', 'yes_no', 'O cartão e os horários nele inseridos são verdadeiros?', ['show_if' => $this->when('ponto_tinha_controle')]),
                    $this->q('ponto_alteracao', 'yes_no', 'A empresa conseguia fazer alterações nos cartões de ponto?', ['show_if' => $this->when('ponto_tinha_controle')]),
                ],
            ],
            [
                'title' => 'Remuneração e transferência',
                'description' => 'Salário, pagamentos por fora, comissões, transferência e contratação.',
                'icon' => 'wallet',
                'questions' => [
                    $this->heading('6. Salário'),
                    $this->q('salario_ultimo', 'money', 'Qual foi o valor do último salário recebido?', $half),
                    $this->q('salario_por_fora', 'yes_no', 'Havia pagamento de salários “por fora” ou outras formas de remuneração não registradas?'),
                    $this->q('salario_valor_por_fora', 'money', 'Qual era o valor ou a média recebida?', [...$half, 'show_if' => $this->when('salario_por_fora')]),
                    $this->q('salario_forma_pagamento', 'single_choice', '6.3 Como era feito o pagamento?', ['options' => $this->choices(['PIX', 'Transferência bancária', 'Dinheiro em mãos']), 'show_if' => $this->when('salario_por_fora')]),
                    $this->q('salario_prova_por_fora', 'long_text', 'É possível comprovar através de testemunhas, áudios, fotos e/ou mensagens?', ['help' => 'Solicitar o envio de provas.', 'show_if' => $this->when('salario_por_fora')]),
                    $this->q('comissoes_recebia', 'yes_no', '6.4 Recebia comissão pelo cumprimento de metas?'),
                    $this->q('comissoes_metas', 'long_text', 'Quais eram as metas?', ['show_if' => $this->when('comissoes_recebia')]),
                    $this->q('comissoes_calculo', 'long_text', '6.5 Qual era o percentual da comissão? Como eram calculadas?', ['help' => 'É OBRIGATÓRIO SOLICITAR DETALHES.', 'show_if' => $this->when('comissoes_recebia')]),
                    $this->q('comissoes_media', 'money', '6.6 Qual era a média das comissões?', [...$half, 'show_if' => $this->when('comissoes_recebia')]),
                    $this->heading('7. Transferência'),
                    $this->q('transferencia_houve', 'yes_no', 'Já foi transferido para outra localidade?'),
                    $this->q('transferencia_data', 'date', 'Em qual data?', [...$third, 'show_if' => $this->when('transferencia_houve')]),
                    $this->q('transferencia_motivo', 'short_text', 'Qual foi o motivo da transferência?', ['width' => 'two_thirds', 'show_if' => $this->when('transferencia_houve')]),
                    $this->q('transferencia_mudanca_endereco', 'yes_no', 'Essa transferência acarretou a mudança de endereço?', ['show_if' => $this->when('transferencia_houve')]),
                    $this->q('transferencia_tipo', 'single_choice', '7.4 Foi provisória ou definitiva?', ['options' => $this->choices(['Provisória', 'Definitiva']), 'show_if' => $this->when('transferencia_houve')]),
                    $this->q('transferencia_despesas', 'yes_no', '7.5 As despesas da mudança foram pagas pela empresa?', ['show_if' => $this->when('transferencia_houve')]),
                    $this->q('transferencia_detalhes', 'long_text', 'Detalhe o que ocorreu', ['show_if' => $this->when('transferencia_despesas')]),
                    $this->heading('8. Local de contratação'),
                    $this->q('fora_ipatinga', 'yes_no', '8.1 Já trabalhou fora de Ipatinga?'),
                    $this->q('fora_ipatinga_localidade', 'short_text', 'Em qual localidade?', ['show_if' => $this->when('fora_ipatinga')]),
                    $this->q('contrato_por_telefone', 'single_choice', '8.2 Foi contratado por telefone?', ['options' => $this->choices(['Ligação', 'WhatsApp', 'Não'])]),
                    $this->notice('LEMBRAR DE SOLICITAR AO CLIENTE PRINTS, VÍDEOS, COMPROVANTES DE PASSAGEM E EXAME ADMISSIONAL FEITO EM IPATINGA.'),
                ],
            ],
            [
                'title' => 'Saúde e segurança',
                'description' => 'Insalubridade, EPIs, acidente, doença e afastamento pelo INSS.',
                'icon' => 'heart-pulse',
                'questions' => [
                    $this->heading('9. Agentes insalubres ou perigosos'),
                    $this->q('insalubridade_exposto', 'yes_no', 'Havia exposição a agentes insalubres/perigosos?'),
                    $this->q('insalubridade_agentes', 'multiple_choice', 'Quais agentes?', ['options' => $this->choices([
                        'Ruído', 'Calor excessivo', 'Radiação', 'Produtos químicos', 'Câmara fria', 'Umidade',
                        'Riscos biológicos (doenças infectocontagiosas)', 'Vibração', 'Recolhia lixos',
                        'Manuseava materiais perfurocortantes contaminados', 'Poeira mineral', 'Cabine de energia elétrica',
                        'Fiação de alta tensão', 'Explosivos', 'Motorista de caminhão com tanque de combustível de mais de 200 litros',
                        'Higienizava banheiros',
                    ], true), 'show_if' => $this->when('insalubridade_exposto')]),
                    $this->q('insalubridade_biologicos', 'short_text', 'Riscos biológicos: quais?', ['show_if' => $this->when('insalubridade_agentes', 'Riscos biológicos (doenças infectocontagiosas)')]),
                    $this->q('insalubridade_perfurocortantes', 'short_text', 'Materiais perfurocortantes: quais?', ['show_if' => $this->when('insalubridade_agentes', 'Manuseava materiais perfurocortantes contaminados')]),
                    $this->q('insalubridade_pessoas_banheiro', 'number', 'Banheiros: em média, quantas pessoas utilizavam?', ['show_if' => $this->when('insalubridade_agentes', 'Higienizava banheiros')]),
                    $this->q('insalubridade_adicional', 'single_choice', '9.1 Recebeu adicional de insalubridade em qual porcentagem?', ['options' => $this->choices(['10% (mínimo)', '20% (médio)', '40% (máximo)', 'Não recebia'])]),
                    $this->heading('10. EPIs'),
                    $this->q('epi_necessario', 'yes_no', 'Era necessário o uso de EPIs?'),
                    $this->q('epi_recebidos', 'multiple_choice', 'Recebeu quais?', ['options' => $this->choices([
                        'Luva', 'Óculos', 'Máscara', 'Botas', 'Protetor auricular', 'Avental', 'Toucas', 'Capacete', 'Cinto de segurança', 'Colete',
                    ], true), 'show_if' => $this->when('epi_necessario')]),
                    $this->q('epi_treinamento', 'yes_no', 'Recebeu treinamento para utilização dos EPIs?', ['show_if' => $this->when('epi_necessario')]),
                    $this->q('epi_substituidos', 'yes_no', 'Os EPIs eram substituídos?', ['show_if' => $this->when('epi_necessario')]),
                    $this->q('epi_usava', 'yes_no', '10.3 Você usava EPIs?', ['show_if' => $this->when('epi_necessario')]),
                    $this->q('epi_fiscalizava', 'yes_no', '10.4 A empresa exigia e fiscalizava o uso dos EPIs?', ['show_if' => $this->when('epi_necessario')]),
                    $this->heading('11. Acidente de trabalho'),
                    $this->q('acidente_sofreu', 'yes_no', 'Já sofreu algum acidente de trabalho?'),
                    $this->q('acidente_detalhes', 'long_text', 'Detalhe o ocorrido', ['help' => 'É OBRIGATÓRIO DETALHAR O OCORRIDO. Solicitar laudos médicos, fotos e provas.', 'show_if' => $this->when('acidente_sofreu')]),
                    $this->q('acidente_data', 'date', '11.1 Em qual data?', [...$third, 'show_if' => $this->when('acidente_sofreu')]),
                    $this->q('acidente_cat', 'yes_no', '11.2 Houve a emissão de CAT?', ['show_if' => $this->when('acidente_sofreu')]),
                    $this->q('acidente_cat_emissor', 'single_choice', 'A CAT foi emitida por', ['options' => $this->choices(['Empresa', 'Outra pessoa']), 'help' => 'Solicitar o envio da CAT.', 'show_if' => $this->when('acidente_cat')]),
                    $this->heading('12. Problema de saúde relacionado ao trabalho'),
                    $this->q('saude_problema', 'yes_no', 'Teve algum problema de saúde relacionado com o trabalho/profissão?'),
                    $this->q('saude_quais', 'long_text', 'Quais problemas?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_desenvolvimento', 'long_text', '12.1 O que ocorreu para o desenvolvimento da doença?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_periodo', 'short_text', 'Em qual período identificou os problemas?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_relatorio', 'yes_no', '12.3 Houve relatório médico em relação à doença?', ['help' => 'Solicitar TODOS os laudos e exames.', 'show_if' => $this->when('saude_problema')]),
                    $this->q('saude_situacoes_pessoais', 'yes_no', '12.4 Acha que situações pessoais ajudaram a desenvolver a doença?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_situacoes_pessoais_quais', 'short_text', 'Quais situações?', ['show_if' => $this->when('saude_situacoes_pessoais')]),
                    $this->q('saude_empresa_ciente', 'yes_no', '12.5 A empresa está ciente da doença desenvolvida?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_entregou_laudos', 'yes_no', 'Foram entregues à empresa laudos/atestados médicos que falam sobre a doença?', ['show_if' => $this->when('saude_empresa_ciente')]),
                    $this->q('saude_permanece', 'yes_no', '12.6 A doença permanece até os dias atuais?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_tratamento', 'yes_no', '12.7 Faz tratamento médico relacionado à doença?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_tratamento_tipo', 'multiple_choice', 'Qual tratamento?', ['options' => $this->choices(['Fisioterapia', 'Tratamento psiquiátrico'], true), 'show_if' => $this->when('saude_tratamento')]),
                    $this->q('saude_medicamentos', 'yes_no', '12.8 Toma medicamentos relacionados à doença?', ['show_if' => $this->when('saude_problema')]),
                    $this->q('saude_medicamentos_quais', 'short_text', 'Quais medicamentos?', ['show_if' => $this->when('saude_medicamentos')]),
                    $this->heading('13. Afastamento pelo INSS'),
                    $this->q('afastamento_houve', 'yes_no', 'Esteve afastado do trabalho com recebimento de benefício pago pelo INSS durante o período deste contrato?'),
                    $this->q('afastamento_motivo', 'short_text', '13.1 Qual o motivo?', ['show_if' => $this->when('afastamento_houve')]),
                    $this->q('afastamento_data_inicio', 'date', 'Início do afastamento', [...$third, 'show_if' => $this->when('afastamento_houve')]),
                    $this->q('afastamento_data_fim', 'date', 'Fim do afastamento', [...$third, 'show_if' => $this->when('afastamento_houve')]),
                    $this->notice('SE ACIDENTE DE TRABALHO OU DOENÇA OCUPACIONAL, entrar imediatamente, pelo computador, no portal do INSS do cliente e baixar: CNIS; laudo de perícia médica; extrato de pagamento de benefício. Digite as palavras no campo de pesquisa “Do que você precisa?” (ex.: “CNIS”, “Laudo Médico”).', ['show_if' => $this->when('afastamento_houve')]),
                    $this->q('afastamento_senha_gov', 'short_text', 'Senha GOV (para entrar no portal do INSS)', [...$half, 'send_to_ai' => false, 'help' => 'Não é enviada à IA.', 'show_if' => $this->when('afastamento_houve')]),
                    $this->q('afastamento_duas_etapas', 'yes_no', 'Tem confirmação em duas etapas?', ['help' => 'Se sim, solicitar ao cliente que, se possível, retire.', 'show_if' => $this->when('afastamento_houve')]),
                ],
            ],
            [
                'title' => 'FGTS e funções',
                'description' => 'FGTS, equiparação salarial, desvio e acúmulo de função.',
                'icon' => 'scale',
                'questions' => [
                    $this->q('fgts_correto', 'yes_no', '14. Sabe dizer se o empregador estava recolhendo corretamente os depósitos do FGTS?', ['help' => 'Solicitar o envio e verificar o extrato completo.']),
                    $this->heading('15. Equiparação salarial'),
                    $this->q('equiparacao_tem', 'yes_no', 'Tinha algum colega que exercia as mesmas atividades e recebia salário maior?'),
                    $this->q('equiparacao_nome_colega', 'short_text', '15.1 Qual é o nome completo do colega?', ['help' => 'Obrigatório nome completo ou ao menos um sobrenome. Sugerir ver em rede social.', 'show_if' => $this->when('equiparacao_tem')]),
                    $this->q('equiparacao_funcao_colega', 'short_text', '15.2 Qual era o nome da função do colega?', ['show_if' => $this->when('equiparacao_tem')]),
                    $this->q('equiparacao_atividades', 'long_text', '15.3 Quais eram as atividades desenvolvidas pelo colega?', ['show_if' => $this->when('equiparacao_tem')]),
                    $this->q('equiparacao_identicas', 'yes_no', '15.4 As atividades dos dois eram idênticas?', ['help' => 'Se não forem, não cabe equiparação.', 'show_if' => $this->when('equiparacao_tem')]),
                    $this->q('equiparacao_mais_dois_anos', 'yes_no', '15.5 Este colega foi contratado há mais de dois anos?', ['show_if' => $this->when('equiparacao_tem')]),
                    $this->heading('16. Desvio de função'),
                    $this->q('desvio_houve', 'yes_no', 'Já trabalhou em desvio de função?', ['help' => 'No desvio, o empregado deixa totalmente de exercer a função para a qual foi contratado, passando a exercer outra que não está registrada na CTPS.']),
                    $this->q('desvio_atividades', 'long_text', '16.1 Exercia quais atividades no desvio?', ['show_if' => $this->when('desvio_houve')]),
                    $this->q('desvio_periodo_de', 'date', '16.2 Período: de', [...$half, 'show_if' => $this->when('desvio_houve')]),
                    $this->q('desvio_periodo_ate', 'date', 'Até', [...$half, 'show_if' => $this->when('desvio_houve')]),
                    $this->heading('17. Acúmulo de função'),
                    $this->q('acumulo_houve', 'yes_no', 'Já trabalhou em acúmulo de função?', ['help' => 'O acúmulo ocorre quando o empregado exerce, ao mesmo tempo, mais de uma função.']),
                    $this->q('acumulo_atividades', 'long_text', '17.1 Exercia quais atividades em acúmulo?', ['show_if' => $this->when('acumulo_houve')]),
                    $this->q('acumulo_periodo_de', 'date', '17.2 Período: de', [...$half, 'show_if' => $this->when('acumulo_houve')]),
                    $this->q('acumulo_periodo_ate', 'date', 'Até', [...$half, 'show_if' => $this->when('acumulo_houve')]),
                ],
            ],
            [
                'title' => 'Rescisão',
                'description' => 'Causa da extinção, verbas rescisórias, guias e seguro-desemprego.',
                'icon' => 'log-out',
                'questions' => [
                    $this->q('extincao_causa', 'single_choice', '18. Qual a causa da extinção do contrato de trabalho?', ['options' => $this->choices([
                        'Sem justa causa', 'A pedido do empregado', 'Término do contrato conforme data acordada', 'Acordo entre as partes', 'Por justa causa',
                    ])]),
                    $this->q('extincao_termino_tipo', 'single_choice', 'Tipo de término', ['options' => $this->choices(['Obra certa', 'Contrato apenas para fim de ano', 'Contrato de experiência']), 'show_if' => $this->when('extincao_causa', 'Término do contrato conforme data acordada')]),
                    $this->q('extincao_experiencia_duracao', 'short_text', 'Duração do contrato de experiência', [...$half, 'show_if' => $this->when('extincao_termino_tipo', 'Contrato de experiência')]),
                    $this->q('extincao_forma_acordada', 'long_text', 'Descreva a forma acordada', ['show_if' => $this->when('extincao_causa', 'Acordo entre as partes')]),
                    $this->q('extincao_justa_causa_motivo', 'long_text', 'Descreva a motivação da justa causa', ['show_if' => $this->when('extincao_causa', 'Por justa causa')]),
                    $this->q('extincao_recebeu_verbas', 'yes_no', '18.1 Recebeu corretamente as verbas rescisórias?'),
                    $this->q('extincao_data_pagamento', 'date', '18.2 Recebeu o pagamento das verbas rescisórias em qual data?', $half),
                    $this->q('extincao_valor_pago', 'money', '18.3 Valor recebido', $half),
                    $this->q('extincao_forma_pagamento', 'single_choice', '18.4 Como se deu o pagamento?', ['options' => $this->choices(['Transferência bancária', 'PIX', 'Dinheiro em mãos'])]),
                    $this->q('extincao_data_entrega_guias', 'date', '18.5 A empresa entregou as guias rescisórias em qual data?', $half),
                    $this->q('extincao_erro_guias', 'yes_no', '18.6 Houve algum erro nas documentações (guias)?'),
                    $this->q('extincao_quais_erros', 'short_text', 'Quais erros?', ['show_if' => $this->when('extincao_erro_guias')]),
                    $this->q('extincao_guias', 'multiple_choice', '18.7 Guias entregues pela empresa', ['options' => $this->choices(['TRCT', 'Guias do CD-SD (seguro-desemprego)'])]),
                    $this->heading('19. Seguro-desemprego'),
                    $this->q('extincao_solicitou_seguro', 'yes_no', 'Realizou a solicitação do seguro-desemprego?'),
                    $this->notice('Solicitar ao cliente as guias CD-SD e o extrato de parcelas do seguro-desemprego (print do aplicativo da Carteira de Trabalho Digital).'),
                ],
            ],
            [
                'title' => 'Testemunhas e processos',
                'description' => 'Testemunhas, ações anteriores e participação em outros processos.',
                'icon' => 'users',
                'questions' => [
                    $this->q('testemunhas', 'repeater', '20. Nome das testemunhas e períodos de trabalho', ['options' => ['columns' => [
                        ['key' => 'nome', 'label' => 'Nome', 'type' => 'short_text'],
                        ['key' => 'periodo', 'label' => 'Período de trabalho', 'type' => 'short_text'],
                    ]]]),
                    $this->heading('21. Ação contra o empregador'),
                    $this->q('acao_anterior', 'yes_no', 'Já promoveu ou promove ação contra o empregador?'),
                    $this->q('acao_anterior_data', 'date', '21.1 Em qual data?', [...$third, 'show_if' => $this->when('acao_anterior')]),
                    $this->q('acao_anterior_processo', 'short_text', '21.2 Número do processo', ['width' => 'two_thirds', 'show_if' => $this->when('acao_anterior')]),
                    $this->q('acao_anterior_motivos', 'long_text', '21.3 Por quais motivos moveu esta ação?', ['show_if' => $this->when('acao_anterior')]),
                    $this->heading('22. Testemunha ou preposto'),
                    $this->q('testemunha_preposto', 'yes_no', 'Já foi testemunha ou preposto em processos envolvendo o empregador?'),
                    $this->q('testemunha_preposto_data', 'date', '22.1 Em qual data?', [...$third, 'show_if' => $this->when('testemunha_preposto')]),
                    $this->q('testemunha_preposto_processo', 'short_text', '22.2 Número do processo', ['width' => 'two_thirds', 'show_if' => $this->when('testemunha_preposto')]),
                    $this->q('testemunha_preposto_depoimento', 'long_text', '22.3 Qual foi o seu testemunho neste processo?', ['show_if' => $this->when('testemunha_preposto')]),
                    $this->notice('É fundamental que as informações prestadas em ambas as ações estejam em conformidade com as alegações do autor neste processo.', ['show_if' => $this->when('testemunha_preposto')]),
                ],
            ],
            [
                'title' => 'Benefícios',
                'description' => 'Benefícios da convenção coletiva e salário-família.',
                'icon' => 'gift',
                'questions' => [
                    $this->q('beneficios', 'multiple_choice', '23. Recebeu os benefícios estabelecidos na convenção coletiva?', ['options' => $this->choices([
                        'Vale-transporte', 'Plano de saúde', 'Cesta básica', 'Seguro de vida', 'Plano odontológico',
                        'Refeição no local (almoço/jantar)', 'PLR', 'Auxílio-creche', 'Auxílio-funeral',
                        'Adicional por tempo de serviço', 'Adicional de quebra de caixa', 'Transporte fretado', 'Ajuda de custo',
                        'Folga no dia do comerciário', 'Lanche', 'Vale-combustível', 'Abono', 'Vale-alimentação',
                        'Rescisão homologada no sindicato',
                    ])]),
                    $this->q('beneficio_lanche_composicao', 'short_text', 'Lanche: composto por?', ['show_if' => $this->when('beneficios', 'Lanche')]),
                    $this->q('beneficio_vale_combustivel_valor', 'money', 'Vale-combustível: valor', [...$half, 'show_if' => $this->when('beneficios', 'Vale-combustível')]),
                    $this->q('beneficio_abono_valor', 'money', 'Abono: valor', [...$half, 'show_if' => $this->when('beneficios', 'Abono')]),
                    $this->q('beneficio_vale_alimentacao_meio', 'single_choice', 'Vale-alimentação, por meio de', ['options' => $this->choices(['Cartão', 'Depósito em conta', 'Cartão Caju']), 'show_if' => $this->when('beneficios', 'Vale-alimentação')]),
                    $this->q('beneficio_vale_alimentacao_valor', 'money', 'Vale-alimentação: valor', [...$half, 'show_if' => $this->when('beneficios', 'Vale-alimentação')]),
                    $this->q('beneficio_outros', 'short_text', 'Outros benefícios recebidos'),
                    $this->heading('24. Salário-família'),
                    $this->q('salario_familia_filhos', 'yes_no', '24.1 Possuía filhos menores de 14 anos na época do contrato de trabalho?'),
                    $this->q('salario_familia_nomes', 'short_text', '24.2 Nome completo do(s) filho(s)', ['width' => 'two_thirds', 'show_if' => $this->when('salario_familia_filhos')]),
                    $this->q('salario_familia_idades', 'short_text', '24.3 Idade do(s) filho(s)', [...$third, 'show_if' => $this->when('salario_familia_filhos')]),
                    $this->q('salario_familia_recebeu', 'yes_no', '24.4 Recebeu salário-família?', ['show_if' => $this->when('salario_familia_filhos')]),
                    $this->q('salario_familia_valor', 'money', 'Valor', [...$half, 'show_if' => $this->when('salario_familia_recebeu')]),
                    $this->notice('Caso não tenha recebido o salário-família, apresentar: certidão de nascimento, RG/CPF, declaração escolar e cartão de vacina completo. É obrigatório o fornecimento desses documentos, com comprovação de frequência escolar e vacinação em dia — será verificado o direito ao benefício e, SE HOUVER, o valor será solicitado.', ['show_if' => $this->when('salario_familia_recebeu', 'Não')]),
                ],
            ],
            [
                'title' => 'Encerramento',
                'description' => 'Outras informações, documentos e declaração.',
                'icon' => 'clipboard-check',
                'questions' => [
                    $this->q('outras_informacoes', 'long_text', '25. Outras informações'),
                    $this->heading('26. Relação de documentos'),
                    $this->notice('Solicitar cópias dos seguintes documentos: RG, CPF, CTPS, recibos de salários, extrato do FGTS, recibos de férias, termo de rescisão do contrato de trabalho, capturas de tela (prints), comprovantes de pagamento, fotos e outros documentos que se mostrarem necessários. Caso o cliente tenha trazido os documentos, solicitar à recepção que realize a digitalização no ato ou após o atendimento.'),
                    $this->q('documentos_recebidos', 'long_text', 'Documentos recebidos'),
                    $this->notice('Declaro que são verdadeiras as informações prestadas acima. É obrigatória a assinatura deste documento por ambas as partes: ATENDENTE e CLIENTE. A assinatura do CLIENTE deve ser idêntica àquela constante no documento de identidade (RG e/ou CNH).'),
                ],
            ],
        ];
    }
}
