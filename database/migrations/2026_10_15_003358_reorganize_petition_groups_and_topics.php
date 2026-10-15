<?php

use App\Models\Group;
use App\Models\Topic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reorganize groups and topics following the "Grupos de pedidos" document.
     *
     * Integer references point to topics that already exist in production;
     * string references are created from newTopics() when missing.
     */
    public function up(): void
    {
        if (! Topic::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $newTopics = $this->newTopics();

            foreach ($this->layout() as $groupIndex => $definition) {
                $group = $this->resolveGroup($definition, $groupIndex + 1);

                foreach ($definition['topics'] as $topicIndex => $reference) {
                    $topic = is_int($reference)
                        ? Topic::query()->find($reference)
                        : Topic::query()->firstOrCreate(
                            ['title' => $reference],
                            ['group_id' => $group->id, 'content' => $newTopics[$reference], 'active' => true],
                        );

                    $topic?->update([
                        'group_id' => $group->id,
                        'sort_order' => $topicIndex + 1,
                    ]);
                }
            }

            Group::query()->where('name', 'x')->doesntHave('topics')->delete();
        });
    }

    public function down(): void
    {
        //
    }

    /**
     * @param  array{id?: int, name: string, topics: array<int, int|string>}  $definition
     */
    private function resolveGroup(array $definition, int $sortOrder): Group
    {
        $group = isset($definition['id']) ? Group::query()->find($definition['id']) : null;
        $group ??= Group::query()->firstOrCreate(['name' => $definition['name']]);

        $group->update([
            'name' => $definition['name'],
            'sort_order' => $sortOrder,
            'active' => true,
        ]);

        return $group;
    }

    /**
     * @return array<int, array{id?: int, name: string, topics: array<int, int|string>}>
     */
    private function layout(): array
    {
        return [
            [
                'id' => Group::PRELIMINARES_ID,
                'name' => 'PRELIMINARES',
                'topics' => [1, 2],
            ],
            [
                'name' => 'RESPONSABILIDADE E PRESCRIÇÃO',
                'topics' => [3, 4, 5, 'SUSPENSÃO DA PRESCRIÇÃO QUINQUENAL'],
            ],
            [
                'id' => 2,
                'name' => 'HORAS EXTRAS',
                'topics' => [
                    'DA ATIVIDADE EM TURNOS ININTERRUPTOS DE REVEZAMENTO. AUSÊNCIA DE AUTORAIZAÇÃO PRÉVIA DO MTE PARA PRORROGAÇÃO DA JORNADA EM AMBIENTE INSALUBRE',
                    12,
                    11,
                    8,
                    6,
                    7,
                    'HORAS EXTRAS EXCEDENTES A 8ª DIÁRIA E 44ª SEMANAL. AUSÊNCIA DE AUTORIZAÇÃO PRÉVIA DO MTE PARA PRORROGAÇÃO DA JORNADA EM AMBIENTE INSALUBRE – jornada 12x36 ambiente insalubre',
                    9,
                    14,
                    13,
                    15,
                    16,
                    56,
                    10,
                ],
            ],
            [
                'id' => 3,
                'name' => 'ADICIONAIS',
                'topics' => [
                    17,
                    18,
                    'DAS DIFERENÇAS DO ADICIONAL DE INSALUBRIDADE - PAGAMENTO PROPORCIONAL - IMPOSSIBILIDADE',
                    19,
                    'DIFERENÇAS DO ADICIONAL NOTURNO – modelo jornada 22h as 05h',
                    'DIFERENÇAS DO ADICIONAL NOTURNO – caso em que a jornada APÓS as 05h da manhã',
                ],
            ],
            [
                'id' => 4,
                'name' => 'RECONHECIMENTO DE VÍNCULO E UNICIDADE CONTRATUAL',
                'topics' => [
                    25,
                    26,
                    'DO RECONHECIMENTO DO VÍNCULO EMPREGATÍCIO. VERBAS RESCISÓRIAS DEVIDAS. doméstica',
                    'PEDIDO DE DSR em CASO de RECONHECIMENTO DE VÍNCULO – CLIENTE QUE RECEBE POR DIA/SEMANA',
                    'DO RECONHECIMENTO DE VÍNCULO EMPREGATÍCIO',
                    24,
                ],
            ],
            [
                'id' => 5,
                'name' => 'DOENÇA OCUPACIONAL E ACIDENTE DE TRABALHO',
                'topics' => [
                    27,
                    28,
                    29,
                    'DA DOENÇA OCUPACIONAL - burnout',
                    'DO ACIDENTE DE TRABALHO – INDENIZAÇÃO POR DANOS MORAIS E ESTÉTICOS – corte com perfuro cortante',
                    52,
                    30,
                    51,
                ],
            ],
            [
                'id' => 10,
                'name' => 'ESTABILIDADES',
                'topics' => [22, 50],
            ],
            [
                'id' => 6,
                'name' => 'VERBAS RESCISÓRIAS',
                'topics' => [
                    31,
                    53,
                    32,
                    33,
                    34,
                    'DA REVERSÃO DA JUSTA CAUSA',
                    'DA CONVERSÃO DO PEDIDO DE DEMISSÃO PARA RESCISÃO INDIRETA',
                    'RESCISÃO INDIRETA',
                    'DO PAGAMENTO PARCIAL DAS VERBAS RESCISÓRIAS. Periculosidade proporcional',
                    'AUSÊNCIA DE PAGAMENTO INTEGRAL DAS VERBAS RECISÓRIAS. INSALUBRIDADE PROPORCIONAL – VEDAÇÃO',
                    'DO PAGAMENTO PARCIAL DAS VERBAS RESCISÓRIAS',
                    58,
                ],
            ],
            [
                'id' => 8,
                'name' => 'COMISSÕES E REMUNERAÇÃO',
                'topics' => [20, 21, 38, 39, 40, 41, 42, 43, 44, 45, 46, 57],
            ],
            [
                'name' => 'MULTAS E TRINTÍDIO',
                'topics' => [
                    'MULTA DO ART. 477, § 8º DA CLT',
                    'MULTA DO ARTIGO 467 DA CLT',
                    'TRINTÍDIO QUE ANTECEDE A DATA BASE',
                ],
            ],
            [
                'id' => 7,
                'name' => 'DANOS MORAIS',
                'topics' => [35, 36, 37, 55],
            ],
            [
                'id' => 9,
                'name' => 'BENEFÍCIOS E OUTROS',
                'topics' => [47, 48, 49, 'DO PAGAMENTO DA PLR PROPORCIONAL DE 2026'],
            ],
            [
                'id' => 11,
                'name' => 'HONORÁRIOS E OUTROS',
                'topics' => [54, 23],
            ],
        ];
    }

    /**
     * Topics missing in production, copied from the developer's environment (gbadvocacia.italoferreira.dev).
     *
     * @return array<string, string>
     */
    private function newTopics(): array
    {
        return [
            'SUSPENSÃO DA PRESCRIÇÃO QUINQUENAL' => 'MMº Juiz, requer seja aplicado à hipótese vertente a Lei nº 14.010/2020, que suspendeu os prazos prescricionais no seu art. 3º, em razão da emergência de saúde pública causada pela pandemia da Covid 19, nos seguintes termos:

"Art. 3º Os prazos prescricionais consideram-se impedidos ou suspensos, conforme o caso, a partir da entrada em vigor desta Lei até 30 de outubro de 2020."

Pois bem, cumpre ressaltar que a Lei 14.010/2020, que dispõe sobre o Regime Jurídico Emergencial e Transitório das Relações Jurídicas de Direito Privado (RJET) no período da pandemia do coronavírus (Covid-19), estabeleceu a suspensão dos prazos prescricionais em curso no interregno entre 20.03.2020 e 30.10.2020.

Isso porque, conforme o disposto no art. 3°, da mencionada lei, "Os prazos prescricionais consideram-se impedidos ou suspensos, conforme o caso, a partir da entrada em vigor desta Lei até 30 de outubro de 2020".

Muito embora publicada em 12.06.2020, verifica-se que seus efeitos retroagem à publicação do Decreto Legislativo nº 6, considerando o parágrafo único do art. 1° da mesma lei que estabeleceu que: "considera-se 20 de março de 2020, a data da publicação do Decreto Legislativo nº 6, como termo inicial dos eventos derivados da pandemia do coronavírus (Covid-19)".

Sendo assim, considera-se suspensa a prescrição de todos os direitos, desde o dia 20 de março de 2020 até o dia 30 de outubro de 2020, totalizando um período de suspensão de 225 dias.

Assim, considerando que a presente ação fora ajuizada em 31.08.2026, e deduzidos os 225 dias de suspensão estabelecida na Lei 14.010/2020, tem-se que a ação foi ajuizada em 05/01/2026, portanto, é de se fixar o marco prescricional quinquenal em 05/01/2021. Neste sentido é o entendimento deste Regional:

“PRESCRIÇÃO. LEI N. 14.010/20. SUSPENSÃO DOS PRAZOS PRESCRICIONAIS. A Lei n. 14.010/2020, que dispõe sobre o Regime Jurídico Emergencial e Transitório das Relações Jurídicas de Direito Privado (RJET) no período da pandemia do coronavírus (Covid-19), estabeleceu a suspensão dos prazos prescricionais em curso no interregno entre 20/03/2020 e 30/10/2020. Isso porque, conforme o disposto no art. 3º, da mencionada lei, "Os prazos prescricionais consideram-se impedidos ou suspensos, conforme o caso, a partir da entrada em vigor desta Lei até 30 de outubro de 2020". Muito embora publicada em 12/06/2020, verifica-se que seus efeitos retroagem à publicação do Decreto Legislativo n. 6, considerando o parágrafo único do art. 1º da mesma Lei, a estabelecer que: "Para os fins desta Lei, considera-se 20 de março de 2020, a data da publicação do Decreto Legislativo nº 6, como termo inicial dos eventos derivados da pandemia do coronavírus (Covid-19)". Sendo assim, considera-se suspensa a prescrição de todos os direitos, desde o dia 20 de março de 2020 até o dia 30 de outubro de 2020, totalizando um período de 225 dias. (TRT da 3.ª Região; PJe: 0011080-46.2020.5.03.0035 (ROT); Disponibilização: 05/09/2022, DEJT/TRT3/Cad.Jud, Página 1357; Órgão Julgador: Segunda Turma; Relator(a)/Redator(a): Sebastiao Geraldo de Oliveira). Grifo nosso.

No mesmo sentido, cita-se os seguintes precedentes da Terceira Turma deste Regional: processo n. 0010601-03.2020.5.03.0181, disponível desde 25/10/2022, Relator Desembargador Milton V. Thibau de Almeida; processo n. 0010200-34.2021.5.03.0095, disponível desde 05/09/2022, Relator Desembargador Danilo Siqueira de C. Faria. Nesse sentido, a tese fixada quando do julgamento do RR - 0020738-17.2022.5.04.061, pelo TST, Tema 46 do IRR do TST.

Diante do exposto, requer seja determinada a suspensão do prazo prescricional em 225 dias, nos termos da fundamentação exposta, sendo considerada a presente ação ajuizada em 05/01/2026, afastando eventual prescrição bienal e fixando o marco para prescrição quinquenal em 05.01.2021. 

DE FORMA SUCESSIVA, requer seja aplicada a suspensão da prescrição por 140 dias, do qual estabelece a Lei nº 14.010/2020, redação do art. 21, que menciona a suspensão dos prazos processuais entre 12/06/2020 (data da publicação) e 30/10/2020, o que totaliza 140 dias, devendo tal suspensão ser considerada no prazo estabelecido no art. 7º, XXIX, da Constituição.',
            'DA ATIVIDADE EM TURNOS ININTERRUPTOS DE REVEZAMENTO. AUSÊNCIA DE AUTORAIZAÇÃO PRÉVIA DO MTE PARA PRORROGAÇÃO DA JORNADA EM AMBIENTE INSALUBRE' => 'Conforme descrito no tópico 01, a reclamante laborou em turnos ininterruptos de revezamento por todo o contrato de trabalho, em jornadas diárias superiores a 06 horas e 36 horas semanais. 

Inicialmente, cumpre ressaltar que a redução da jornada para seis horas diárias em regime de turnos ininterruptos de revezamento foi instituída pela Constituição Federal, em seu artigo 7º, inciso XIV.

Todavia, não se deve pensar que esta redução foi aleatória. O regime especial de jornada para o trabalho realizado em turnos ininterruptos de revezamento tem como objetivo precípuo tentar proteger a saúde do empregado, vez que este tipo de jornada de trabalho, abrangendo várias horas do dia e da noite, ou seja, ora na parte da manhã, ora na parte da tarde, e ora na parte da noite, traz um desgaste muito maior para o trabalhador, que as jornadas convencionais.

Se olharmos atentamente a Constituição Federal, veremos: 

“Art. 7º São direitos dos trabalhadores urbanos e rurais, além de outros que visem à melhoria de sua condição social:
...
XIV - jornada de seis horas para o trabalho realizado em turnos ininterruptos de revezamento, salvo negociação coletiva;” (grifo nosso).

O regime de turnos ininterruptos de revezamento é mais desgastante, por conseguinte, tem potencial maior de prejuízo à saúde do trabalhador. No caso dos autos, havia prestação de horas extras habituais, o que por certo, invalida a adoção desse regime, o que desde já requer.

Ademais, não existem no caso dos autos, Convenções Coletivas ou Acordo Coletivo, autorizando a prorrogação de jornada em ambiente insalubre, seja em turno fixo ou em turno ininterrupto de revezamento, nos exatos termos do art. 60/CLT.

Há necessidade de prévia inspeção da DRT para validade do elastecimento de jornada eventualmente previsto em cláusula coletiva, nos termos do art. 60 da CLT. Nem se diga que o julgamento proferido pelo STF do ARE 1121633, Tema nº 1.046 socorre a parte reclamada, considerando que a duração da jornada de trabalho se trata de matéria constitucional (Artigo 7º Incisos XII, XIV, sendo inválida eventual Convenção Coletiva autorizando a prorrogação de jornada em ambiente insalubre sem a inspeção prévia do Ministério do Trabalho.

Como ressalvado pelo próprio STF no Tema 1046:

"São constitucionais os acordos e as convenções coletivos que, ao considerarem a adequação setorial negociada, pactuam limitações ou afastamentos de direitos trabalhistas, independentemente da explicitação especificada de vantagens compensatórias, desde que respeitados os direitos absolutamente indisponíveis".

Mas, adiante, o mesmo texto constitucional estabelece, no inciso XXII, regra que impõe a "redução dos riscos inerentes ao trabalho, por meio normas de saúde, higiene e segurança", tal como se dá, então, com a previsão contida no artigo 60 da CLT, que exige, como dito, prévia inspeção da DRT para validade dessa extensão de jornada em ambientes insalubres. 

Ademais, se trata de norma de saúde, higiene e segurança do trabalho, sendo ilícita eventual previsão Convencional nos termos do art. 611-B, inciso XVII, da CLT. Requer seja declarada ilícita eventual autorização convencional, para prorrogação de jornada em ambiente insalubre sem a inspeção prévia do Ministério do Trabalho, nos termos do art. 611-B, inciso XVII, da CLT.

Assim sendo, deve a reclamada pagar as horas laboradas além da 06ª diária e 36ª semanal, com acréscimo do adicional convencional/legal, divisor 180, remuneração da Autora (Súmula 264/TST) e reflexos no 13º salário, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.

SUBSIDIARIAMENTE, caso Vossa Excelência entenda pela inaplicabilidade do turno ininterrupto de revezamento, requer o pagamento das horas extras além da 08ª diária e 44ª semanal, pois restou claramente apontado que o limite legal restou habitualmente ultrapassado, sem que as horas extras fossem pagas ou compensadas. 

E mais, diante da ausência de licença prévia do Ministério do Trabalho e Emprego para a prorrogação do horário de trabalho em ambiente insalubre, além da prática habitual de horas extraordinárias, deve a reclamada pagar as horas laboradas além da 08ª diária e 44ª semanal, com adicional convencional/{legal}, divisor 220, remuneração da Autora (Súmula 264/TST) e reflexos em 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.',
            'HORAS EXTRAS EXCEDENTES A 8ª DIÁRIA E 44ª SEMANAL. AUSÊNCIA DE AUTORIZAÇÃO PRÉVIA DO MTE PARA PRORROGAÇÃO DA JORNADA EM AMBIENTE INSALUBRE – jornada 12x36 ambiente insalubre' => 'Conforme descrito no tópico 01, o reclamante sempre trabalhou em jornadas de 12h diárias, em escalas {03x02 e 04x02}. Ocorre que, conforme entendimento já pacificado pelo TST, a adoção de tal jornada é INVÁLIDA, eis que a legislação atual autoriza o labor por 12h ao dia apenas diante da concessão de 36 horas subsequentes de descanso, o que não ocorria no caso em tela. Vejamos os julgados:

AGRAVO DE INSTRUMENTO HORAS EXTRAS. JORNADA DE 12 HORAS NAS ESCALAS 4X2, 5X2, 5X1 E 6X1. INVALIDADE. Demonstrada a afronta ao artigo 7º, XIII, da Constituição da Republica, dá-se provimento ao Agravo de Instrumento, a fim de determinar o processamento do recurso de revista . RECURSO DE REVISTA. HORAS EXTRAS. JORNADA DE 12 HORAS NAS ESCALAS 4X2, 5X2, 5X1 E 6X1. INVALIDADE . 1. Nos termos da jurisprudência pacífica desta Corte superior, consagrada na Súmula n.º 444, admite-se, excepcionalmente, a jornada diária de doze horas de trabalho, desde que na escala de 12 por 36 e somente se adotada mediante norma coletiva ou por força de previsão legal, porquanto considerada, nestes termos, deveras benéfica ao trabalhador. 2 . Inválida, por conseguinte, a referida jornada de doze horas de trabalho diário se não observados tais requisitos. 3. Num tal contexto, a jornada diária de trabalho , em quaisquer outras escalas, ainda que submetida a regime de labor extraordinário, de compensação de jornadas ou até mesmo de banco de horas, deve se restringir ao máximo de dez horas diárias, haja vista a existência de expressa limitação legal, nos termos do artigo 59, cabeça, da Consolidação das Leis do Trabalho, ao acréscimo de horas suplementares "em número não excedente de 2 (duas)". 4 . Acrescente-se, ademais, que a jornada de trabalho de doze horas nas escalas 4x2, 5x2, 5x1 e 6x1, ainda que estivesse prevista em norma coletiva, tampouco encontra respaldo no artigo 7º, XIII, da Constituição da Republica, porquanto sempre extrapola a jornada diária e semanal sem haver compensação. 5. Recurso de Revista conhecido e provido. (TST - RR: 1243008120075020010, Data de Julgamento: 12/08/2015, Data de Publicação: DEJT 21/08/2015) (g.n)


HORAS EXTRAS. ESCALA 4X2. JORNADA DE TRABALHO DE 12 HORAS DIÁRIAS. INVALIDADE . HORAS EXTRAS A PARTIR DA 8ª DIÁRIA. A fixação de jornada diária de 12 horas de trabalho constitui medida excepcional, e somente é admitida em lei, caso seguida de 36 horas consecutivas de descanso, na clara interpretação do art. 59-A da CLT. A prestação de labor por 12 horas diárias na escala 4x2, ainda que negociada em norma coletiva, viola o instituto da compensação, por exceder tanto, o limite de 8 horas diárias, quanto aquele de 44 semanais, previstos no art . 7º, XIII, da CF. Em tais casos, o trabalhador faz jus às horas extras excedentes da 8ª diária e 44ª semanal. Recurso autoral provido. (TRT-2 10010321320205020011 SP, Relator.: SILVANE APARECIDA BERNARDES, 8ª Turma - Cadeira 2, Data de Publicação: 27/04/2022) (g.n)

E tendo em vista que a parte reclamante desenvolvia seu trabalho em ambiente insalubre e que não há notícia de que a reclamada obteve licença prévia das autoridades competentes em matéria de higiene do trabalho para entabular acordo de prorrogação de jornada; fato que, por si só, enseja a nulidade do regime pactuado. Logo, requer seja declarado inválido eventual regime de compensação em escala 12x36 e condenar a reclamada ao pagamento das horas extras laboradas além da 8ª diária e 44ª semanal, cujo pagamento deverá ser realizado nos moldes da Súmula nº 85, VI do C. TST.  Assim, as jornadas estabelecidas pela Reclamada são inválidas, e todo o labor excedente à 08ª hora diária e/ou 44ª semanal deve ser quitada como hora extra. 

Assim, requer seja deferido à parte autora o pagamento das horas extras além da 8ª diária e 44ª semanal, acrescidas do adicional convencional/{legal}, divisor 210, devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST, devendo ainda ser pagos os reflexos sobre os 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR’s.',
            'DIFERENÇAS DO ADICIONAL NOTURNO – modelo jornada 22h as 05h' => 'Comentário: DIFERENÇAS ADICIONAL NOTURNO QUANDO LABOR NÃO ULTRAPASSAVA AS 05H. 

A parte reclamante trabalhou, também, em jornada noturna, nos horários citados no item 01. Ocorre que a parte reclamada não adimpliu a totalidade do adicional noturno, além de que desconsiderou a redução ficta da hora noturna. 

Nos termos do art. 7º, IX, da CR/88, são direitos dos trabalhadores, além de outros, remuneração do trabalho noturno superior à do diurno, sendo, assim, alçado à direito fundamental constitucional o art. 73, da CLT. Extrai-se da lei que o trabalho noturno – labor executado entre às 22 horas de um dia e as 5 horas do dia seguinte - terá remuneração superior à do diurno e, para esse efeito, sua remuneração terá um acréscimo de 20%, sobre a hora diurna, e as horas noturnas serão computadas como de 52 minutos e 30 segundos (redução ficta). 

Desse modo, faz jus a reclamante: (I) ao adicional noturno legal, por hora trabalhada em período noturno, observada a redução ficta da hora noturna; e (II) a 07 minutos e 30 segundos extras, pela inobservância da redução ficta da hora noturna, acrescidas do adicional convencional/legal, ambos com reflexos nos 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.',
            'DIFERENÇAS DO ADICIONAL NOTURNO – caso em que a jornada APÓS as 05h da manhã' => 'Comentário: ADICIONAL NOTURNO PARA O LABOR QUE ULTRAPASSOU AS 05H.


A parte reclamante trabalhou, também, em jornada noturna, nos horários citados no item 01. Ocorre que a parte reclamada não adimpliu a totalidade do adicional noturno, além de que o quitou somente sobre as horas laboradas até às 05h00 da manhã, desconsiderando as prorrogações para além desse horário.

Nos termos do art. 7º, IX, da CR/88, são direitos dos trabalhadores, além de outros, remuneração do trabalho noturno superior à do diurno, sendo, assim, alçado à direito fundamental constitucional o art. 73, da CLT. Extrai-se da lei que o trabalho noturno – labor executado entre às 22 horas de um dia e as 5 horas do dia seguinte - terá remuneração superior à do diurno e, para esse efeito, sua remuneração terá um acréscimo de 20% (adicional legal), sobre a hora diurna, e as horas noturnas serão computadas como de 52 minutos e 30 segundos (redução ficta). 

Além disso, sabe-se que cumprida a jornada integralmente no horário noturno, sobre as horas trabalhadas na sequência, também é devido o adicional noturno, por representarem prorrogação do horário noturno. Inteligência do § 5º do art. 73 da CLT e da Súmula 60, II, do TST. 

Desse modo, faz jus a reclamante: (I) ao adicional noturno legal, por hora trabalhada em período noturno e em prorrogação do labor noturno, após às 05h, observada a redução ficta da hora noturna; e (II) a 07 minutos e 30 segundos extras, pela inobservância da redução ficta da hora noturna por cada hora em prorrogação ao labor após as 05h, acrescidas do adicional convencional/legal, ambos com reflexos nos 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.',
            'DAS DIFERENÇAS DO ADICIONAL DE INSALUBRIDADE - PAGAMENTO PROPORCIONAL - IMPOSSIBILIDADE' => 'Incontroverso nos autos que a parte reclamada reconhecia o ambiente de trabalho da reclamante como insalubre em grau {médio}, consoante se observa dos contracheques anexos (rubrica {1531}). Porém, por diversos meses, a ex-empregadora efetuou o pagamento da parcela de forma proporcional às horas trabalhadas. Veja por amostragem:  

Comentário: Inserir print contracheque

Registre-se que, apesar da natureza condicional do adicional de insalubridade, o seu pagamento se dá de forma mensal e, sendo calculado com base no salário mínimo, possui valor fixo, independentemente da jornada de trabalho e dos dias de efetiva exposição ao agente insalubre.

Nesse sentido são os seguintes precedentes: 

ADICIONAL DE INSALUBRIDADE. APURAÇÃO PROPORCIONAL. INDEVIDA. Não obstante a natureza condicional do adicional de insalubridade, o seu pagamento se dá de forma mensal e, sendo calculado com base no salário mínimo, tem valor fixo, independente da jornada de trabalho e dos dias de efetiva exposição ao agente insalubre. Recurso não provido. (TRT da 3.ª Região; PJe: 0010749-15.2019.5.03.0095 (APPS); Disponibilização: 12/05/2020; Órgão Julgador: Primeira Turma; Relator: Convocado Marcio Toledo Goncalves).

ADICIONAL DE INSALUBRIDADE - PAGAMENTO PROPORCIONAL AOS DIAS EFETIVAMENTE LABORADOS - IMPOSSIBILIDADE. É indevida a limitação do pagamento de adicional de insalubridade aos dias efetivamente laborados, uma vez que não há previsão legal que permita o pagamento da parcela de forma proporcional. (TRT da 3.ª Região; PJe: 0010824-68.2017.5.03.0016 (APPS); Disponibilização: 31/01/2020; Órgão Julgador: Terceira Turma; Relator: Emilia Facchini).

Dessa forma, verifica-se que existem diferenças em favor da autora, pelo que requer o pagamento das diferenças do adicional de insalubridade, pago mês a mês, e seus reflexos em 13º salários, férias + 1/3 constitucional, FGTS+40%, aviso prévio, RSR e horas extras.',
            'DO RECONHECIMENTO DO VÍNCULO EMPREGATÍCIO. VERBAS RESCISÓRIAS DEVIDAS. doméstica' => 'A Reclamante laborou por {11 meses} sem ter a carteira de trabalho assinada. No entanto, o trabalho por ela prestado sempre se deu com o cumprimento de todos os requisitos elencados pelo art. 3º, CLT. 

Não se deve perder de vista que, no direito do trabalho, incide o princípio da primazia da realidade sobre a forma, de modo que, se os atos foram praticados com o único escopo de fraudar, desvirtuar e impedir a aplicação dos preceitos da lei trabalhista, serão considerados nulos para todos os efeitos (inteligência do art. 9º da CLT).

No caso em tela, diante da natureza do serviço prestado, trata-se a Reclamante de empregada doméstica, cujo labor é regulado pela Lei Complementar 150/2015. Assim, nos moldes do art. 1º do mencionado dispositivo, é empregado doméstico “aquele que presta serviços de forma contínua, subordinada, onerosa e pessoal e de finalidade não lucrativa à pessoa ou à família, no âmbito residencial destas, por mais de 2 (dois) dias por semana”.

Dessa forma, passa-se à análise de tais requisitos na relação havida entre as partes: 

SERVIÇO DE FORMA CONTÍNUA: A Reclamante trabalhou de {três a quatro vezes na semana}, em horário fixo, determinado pela Reclamada. Assim, conclui-se que a prestação de serviços não era esporádica, mas, sim, habitual e contínua, pois em todas as semanas, o ritmo e horário de trabalho se repetiam. 
  
SUBORDINAÇÃO: A Reclamante recebia ordens da Reclamada, a quem se reportava como sua superiora, acolhendo e executando os serviços delegados por ela, sujeitando-se integralmente aos seus comandos.

ONEROSIDADE: A Reclamada ajustou com a obreira o valor de R$ 170,00/dia, que perfazia R$ {2.720,00}/mês.

PESSOALIDADE: A Reclamante não podia se fazer substituir por outra pessoa na execução dos serviços, tendo cumprindo jornada diária durante todo o pacto laboral.

FINALIDADE NÃO LUCRATIVA: Como narrado ao tópico “1”, os serviços da obreira eram destinados unicamente aos cuidados domésticos, não tendo, portanto, nenhuma finalidade lucrativa.  Vejamos a jurisprudência:

EMPREGADA DOMÉSTICA. ÔNUS DA PROVA. Para caracterização do vínculo de empregada doméstica, é indispensável que a prestação de serviços se dê em mais de dois dias por semana, com a presença concomitante dos demais requisitos previstos no artigo 1º da Lei Complementar 150/2015. Admitida a prestação de serviço e não comprovando a reclamada que o labor se revestiu de outra forma, fato impeditivo do direito vindicado pela autora, o reconhecimento da relação de emprego é medida que se impõe. Inteligência do art. 818, II, da CLT. (TRT da 3.ª Região; PJe: 0010904-42.2023.5.03.0074 (ROT); Disponibilização: 14/10/2024; Órgão Julgador: Decima Primeira Turma; Relator(a)/Redator(a) Convocado Flavio Vilson da Silva Barbosa)

VÍNCULO EMPREGATÍCIO. EMPREGADA DOMÉSTICA. Uma vez comprovada a prestação de serviços por mais de 02 (dois) dias por semana, de forma contínua, subordinada, onerosa e pessoal e de finalidade não lucrativa à pessoa ou à família, no âmbito residencial destas, é devido o reconhecimento do vínculo empregatício, sob a definição legal dada pela legislação (artigo 1º da Lei Complementar n. 150/2015). Recurso da reclamada não provido. (TRT da 3.ª Região; PJe: 0010208-73.2021.5.03.0139 (ROT); Disponibilização: 01/11/2023; Órgão Julgador: Primeira Turma; Relator(a)/Redator(a) Convocada Angela C.Rogedo Ribeiro)

Pois bem, ante o preenchimento de todos os requisitos, requer a declaração da existência de vínculo empregatício entre as partes, durante toda a contratualidade.

O registro do contrato de trabalho deverá ser realizado pela reclamada na CTPS da obreira, fazendo constar a data de admissão: {03/02/2024} e dispensa: {16/02/2025} já computada a projeção do aviso prévio indenizado de 30 dias (OJ 82 da SDI-1 do TST); função exercida: Empregada Doméstica, com salário admissional de {R$ 2.720,00/mês}, nos termos do art. 29 da CLT, sob pena de multa diária de R$ 100,00 (cem reais), a ser revertida em favor da Reclamante.

Assim, é devido à parte autora o pagamento das seguintes verbas rescisórias:

•	{30 dias de aviso prévio indenizado;}
•	{11/12 de 13º salário de 2024;}
•	{02/12 de 13º salário proporcional de 2025, já considerando a projeção do aviso prévio indenizado;}
•	{12/12 de férias + 1/3 constitucional, relativo ao período aquisitivo de 03/02/2024 a 16/02/2025, ou seja, já considerando a projeção do aviso prévio indenizado;}
•	{Depósitos não realizados de FGTS;}
•	{Multa de 40% sobre o saldo total de FGTS;}
•	{Entrega dos documentos rescisórios – TRCT e Chave Conectividade, sob pena de multa diária de R$ 100,00 (cem reais), a ser revertido em prol da autora.}
•	{Multa do art. 467/CLT e art. 477, § 8ª da CLT (calculado sobre a remuneração – IRR n° 142 e 168 do TST).}

1.1 – DANOS MORAIS DEVIDO AUSÊNCIA DE ANOTAÇÃO DA CTPS - Um dos casos típicos de dano moral no âmbito trabalhista é a ausência do registro na CTPS do empregado, o que acarreta o prejuízo do recebimento de benefícios previdenciários, do seguro desemprego e até mesmo dos benefícios da sua categoria profissional. É inegável que o trabalhador é prejudicado pela ausência de contrato de trabalho em sua CTPS.
 
A anotação representa a efetiva participação do trabalhador no mercado de trabalho, além da indicação das suas qualidades de profissional e da vinculação a um determinado oficio ou atividade econômica, como também da sua participação em várias áreas da vida social e jurídica, tais como: abertura de conta bancária, obtenção de crédito junto à instituição financeira e no comércio em geral, participação junto ao FGTS e à Seguridade Social, participação nos direitos da categoria e etc.

Assim, temos que a falta de anotação na CTPS acarretou à obreira a sua exclusão da sociedade e a Reclamada violou a declaração universal dos direitos do homem e a dignidade da pessoa humana (Art. 1 CF/88, inciso III). 

O ato patronal de ocultar a relação de emprego configura ilícito trabalhista, previdenciário e, até mesmo penal. O sentimento de clandestinidade vivenciado pela empregada teve repercussão na sua vida familiar e merece ser reparado. 

Por todos esses motivos, a Reclamada deve ser condenada ao pagamento de uma indenização por dano moral para que sua atitude não se torne corriqueira com os demais empregados. Como pode a Reclamada violar a legislação trabalhista contratando um empregado sem registro na CTPS e sem o pagamento de seus direitos trabalhistas?

É justamente pela extrapolação do poder diretivo do empregador, violando a dignidade da pessoa humana da Reclamante, que deve ser a Reclamada condenada ao pagamento de indenização por dano moral em valor não inferior a R$ 10.000,00 (dez mil reais), como medida pedagógica para que a reclamada não pratique tais condutas com mais nenhum colaborador. 

1.2 – DA INDENIZAÇÃO SUBSTITUTIVA AO SEGURO DESEMPREGO - A Reclamante não teve sua CTPS assinada durante toda a prestação de serviços. Sendo assim, perante o Ministério do Trabalho e Emprego, ela não laborou entre fevereiro de 2024 e janeiro de 2025, motivo pelo qual perdeu 11 meses de trabalho que seriam imprescindíveis para que obtivesse seguro desemprego.

Por tal motivo, tem-se que a Reclamante só não logrou êxito na obtenção de seguro desemprego em razão de conduta ilícita da Reclamada. Assim, pleiteia indenização substitutiva ao mencionado benefício, a qual deve ser paga pela parte ré.

Ante o fato de a obreira ter trabalhado por onze meses, faria jus a três parcelas de seguro desemprego, as quais devem ser apuradas tendo por base de cálculo todas as verbas de natureza salarial, nos moldes do art. 5º, §1º, da Lei nº 7.998/90.
_______________________________________________________________________________

1.3 – INDENIZAÇÃO SUBSTITUTIVA DO PIS – MMº Juiz, em conformidade com o art. 9ª, itens I e II, da Lei nº 7.998/90, tem direito ao recebimento do abono anual relativo ao PIS os empregados que tenham percebido até dois salários mínimos, tenham exercido atividade remunerada pelo menos por 30 dias durante o ano-base e estejam cadastrados há pelo menos cinco anos no Fundo de Participação Pis-Pasep ou no Cadastro Nacional do Trabalhador. 

Assim, tendo a conduta da Reclamada ocasionado a impossibilidade de percepção do abono salarial, deverá responder pelo prejuízo causado, mediante o pagamento de indenização substitutiva correspondente ao valor do abono salarial do PIS que deixou de ser recebido pelo Reclamante, relativamente aos anos em que preenchidos os requisitos legais. Uma vez que a parte reclamante reúne as condições acima e tendo em vista que sequer possuía CTPS anotada, é devido o pagamento da indenização substitutiva do abono nos anos de 2024 e 2025 no valor de 01 salário mínimo (cada ano) vigente a época dos fatos.',
            'PEDIDO DE DSR em CASO de RECONHECIMENTO DE VÍNCULO – CLIENTE QUE RECEBE POR DIA/SEMANA' => 'DO DSR - Conforme narrado ao item 01, o obreiro recebia pagamento por dia de trabalho, no importe de R$ 100,00 diários. Assim, a Reclamada não efetuava o pagamento do repouso semanal remunerado, limitando-se ao pagamento das diárias correspondentes aos dias efetivamente trabalhados.

Desta forma, tem direito o obreiro ao recebimento dos RSR’s, bem como sua integração ao salário para todos os fins e reflexos em 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio. Ato contínuo, pugna pela declaração do salário mensal (aqui considerando o DRS), requerendo que se determine à Reclamada a anotação na CTPS obreira o referido valor, a título de salário admissional, no sob pena de multa diária de R$ 100,00 (cem reais), a ser revertida em favor do reclamante.',
            'DO RECONHECIMENTO DE VÍNCULO EMPREGATÍCIO' => 'A Reclamante embora tenha sido contratada para laborar como empregada, não teve sua CTPS assinada pela Reclamada. No entanto, atentando-se ao quadro fático, observa-se que os requisitos do artigo 3º da CLT se encontram presentes na relação havida entre as partes. 

É importante destacar que de acordo com a norma insculpida nos arts. 2º e 3º da CLT, configura-se a relação de emprego quando comprovada a prestação de serviços por pessoa física, de forma não eventual, mediante pessoalidade, onerosidade e subordinação jurídica.

Todavia, predomina no Direito do Trabalho o princípio da irrenunciabilidade, mediante o qual não é permitido às partes renunciar aos direitos trabalhistas inerentes à relação de emprego existente. Importante trazer aos autos que a parte reclamada chegou a solicitar a CTPS da autora para anotação, porém não chegou a cumprir com tal obrigação.

No caso em tela, verifica-se que a relação de trabalho se desenvolveu sob o prisma do artigo 3º da Consolidação das Leis do Trabalho - o notório regime de emprego. Veja-se:

NÃO EVENTUALIDADE: A Reclamante trabalhava de segunda a sexta-feira, em horário determinado pela Reclamada, o que demonstra que a prestação de serviços não era esporádica, mas, sim, habitual.

 SUBORDINAÇÃO JURÍDICA: A Reclamante recebia ordens da proprietária da Reclamada, na pessoa da S{ra. Maria Aparecida,} a quem se reportava como chefe, acolhendo e executando os serviços delegados por ela, sujeitando-se integralmente ao corpo produtivo, bem como ao comando empresarial; 

ONEROSIDADE: A Reclamada ajustou com a obreira o pagamento de um salário mínimo por mês a título de remuneração. Restou estabelecido o pagamento independentemente de ter ou não serviço no mês, revelando que o autor não suportava os riscos do empreendimento; 

PESSOALIDADE: A Reclamante não podia se fazer substituir por outra pessoa na execução dos serviços, tendo cumprido jornada diária durante todo o pacto laboral. 

Portanto, resta patente a existência de fraude aos dispositivos e normas de proteção ao trabalho, porquanto demonstrado que os serviços da reclamante eram prestados em favor reclamada, inserido em sua atividade-fim, de forma subordinada, através da pessoa do reclamante, situação que caracteriza a fraude à legislação trabalhista vedada pelo artigo 9º da CLT.

Sendo assim, a parte autora requer que seja reconhecido o vínculo empregatício havido com as empresas-rés, durante toda a contratualidade.

O registro do contrato de trabalho deverá ser realizado na CTPS da obreira pela Reclamada, fazendo constar a data de admissão: 24/04/2022 e dispensa: 21/02/2023 (já com a projeção dos 30 dias de aviso prévio indenizado), função exercida: Vendedora, transferência para a 2ª Ré em 26/08/2022, salário de R$ 1.212,00/mês e de R$ 1.320,00/mês a partir de 01/01/2023, nos termos do art. 29 da CLT, sob pena de multa diária de R$ 100,00 (cem reais), a ser revertida em favor da Reclamante.

Dessa forma, devem ser pagas as seguintes verbas rescisórias:

•	{21 dias de saldo de salário de janeiro/2023;}
•	{30 dias de aviso prévio indenizado;}
•	{8/12 de 13º salário de 2022;}
•	{2/12 de 13º salário de 2023, já contabilizando a projeção do aviso prévio até 18/02/2023;}
•	{10/12 de férias proporcionais + 1/3 constitucional, já contabilizando a projeção do aviso prévio até 18/02/2023;}
•	{Depósitos de FGTS de todo o período contratual + multa de 40% sobre os mesmos;}
•	{Obrigação de fazer - Entrega das guias TRCT e chave de conectividade, sob pena de indenização substitutiva revertida em favor da parte autora.}
•	{Multa do art. 467/CLT e art. 477, § 8ª da CLT (calculado sobre a remuneração – IRR nº 142 e 168 do TST).}',
            'DA DOENÇA OCUPACIONAL - burnout' => 'O Reclamante em razão de seu trabalho, desenvolveu {Burnout}, bem como transtorno ansioso/depressivo, conforme demonstram laudos médicos anexos. Durante a prestação de serviços, estava sujeito a sobrecarga de trabalho, cobranças excessivas e ameaças constantes de dispensa das supervisoras {Heloísa e Patrícia, as quais exerciam muita pressão sobre o obreiro}.

Em razão do Burnout, começou a apresentar sintomas como insônia, nervosismo, ansiedade, angústia, pessimismo, desmotivação, dentre outros. Dessa forma, tornou-se incapaz para permanecer laborando e viu-se obrigado a solicitar afastamento ao INSS para cuidar da saúde. Vejamos laudo do perito:
Comentário: Inserir print e laudos médicos ou do perícia do INSS

A saúde do Reclamante foi tão afetada que ele permanece afastado até os dias atuais, tendo perspectiva de alta médica apenas em {setembro de 2026}.

É evidente o nexo causal entre labor, em favor da Reclamada, e o desenvolvimento dos mencionados transtornos psicológicos, como constatados pelos próprios peritos e médicos com os quais o obreiro se consultou. 

Nesse sentido, o extenso número de horas extras, sobrecarga de trabalho, cobranças excessivas e ameaças de dispensa, levaram o Reclamante ao esgotamento mental; ou seja, evidente a culpa da empregadora. 

O meio ambiente equilibrado está intimamente ligado à saúde do trabalhador, devendo o empregador priorizá-lo no quadro de ações da empresa, na forma do art. 5, XXII e XXIII, art. 170, II e III, da CF, o que não se verificou no caso em tela.

A   ordem   jurídica   tutela   não   apenas   bens   materiais   ou   aqueles patrimoniais, mas também aqueles bens de índole moral, impalpáveis, afetos à esfera psíquica, sentimental, à privacidade da pessoa, tal como a honra e imagem (Constituição da República, artigo 5º, incisos V e X; Código Civil, artigo 186). 

São princípios fundamentais do Estado brasileiro os direitos à dignidade da pessoa humana, aos valores sociais do trabalho e a valorização do trabalho humano (Constituição da República, artigo 1º, incisos III e IV e art. 170 caput), que foram violados pela reclamada.

Diante dos fatos acima narrados, é inequívoco que a doença do Autor se trata de doença OCUPACIONAL. Vale ressaltar que, com base na Lei 8.213/91 (Lei da Previdência Social), entende-se que a caracterização da doença profissional prescinde de que as atividades tenham agido como causa única da patologia. Basta, para tanto, que o trabalho tenha contribuído para a sua deflagração ou potencialização. 

Assim, considerando os parâmetros acima transcritos, a condição econômica da Reclamada, a hipossuficiência da Reclamante, o grau do risco da atividade explorada pela Ré, requer a condenação da reclamada em danos morais pela doença ocupacional desenvolvida, cujo valor ficará a cargo de Vossa Excelência, o que pedimos vênia, é que o arbitramento seja de, no mínimo, {R$ 45.000,00 (}Quarenta e cinco mil reais).

1.1 – DANOS MATERIAIS – MMº Juiz, caso reste comprovado nos autos, através de laudo médico pericial, a incapacidade do reclamante, ainda que no período de afastamento previdenciário ({janeiro de 2023 até o momento atual, com perspectiva de alta apenas em setembro de 2026}), faz jus o obreiro aos danos materiais.

Pois bem, é flagrante a incapacidade da parte autora em decorrência de doença ocupacional da qual foi acometido, sendo plenamente cabível a fixação de pensionamento em tal caso.

Os critérios de incapacitação aqui definidos não se confundem com a incapacitação definida pelo INSS, tanto assim que o pensionamento eventualmente determinado não acarreta em prejuízo da percepção do benefício previdenciário (auxílio doença).

Registre-se, por fim, que a parte autora pretende o pagamento da pensão mensal do qual deve perdurar enquanto permanecer a incapacidade laborativa do trabalhador ou até que alcance a aposentadoria junto ao INSS, cabendo à ré ajuizar ação revisional numa eventual alteração do quadro de sua incapacidade laborativa.

Dessa forma, sem prejuízo da percepção do benefício previdenciário (auxílio doença e, posteriormente, se for o caso, aposentadoria por invalidez), requer a condenação da reclamada ao pagamento de indenização por danos materiais decorrentes dos lucros cessantes, mês a mês, consistente em pensão mensal, em 100% da remuneração paga ao autor, aqui considerando ainda a globalidade da remuneração, inclusive aquelas reconhecidas nessa demanda, ou seja, ao somatório do salário base mais eventuais parcelas quitadas e deferidas a título de remuneração variável, inclusive, {horas extras, adicional noturno e de insalubridade pagas}, somando-se, ainda, mês a mês, 01/12 de gratificação natalina, 01/12 de férias acrescidas de 1/3 constitucional e FGTS, a partir do 16º dia de afastamento até o dia em que a autora receber a alta previdenciária.

1.2 - DOS RECOLHIMENTOS DO FGTS do PERÍODO DE AFASTAMENTO – MMº Juiz, a partir do momento em que o obreiro se afastou do trabalho e passou a receber benefício previdenciário, a reclamada deixou de realizar os depósitos do FGTS de forma regular. Acontece que o afastamento por motivo de doença ocupacional não afasta a obrigação legal do empregador de recolhimento dos depósitos do FGTS, a teor do que dispõe o art. 15, § 5º, da Lei nº 8.036/90.

Feitas essas considerações, requer a condenação da reclamada ao recolhimento, diretamente na conta vinculada do FGTS do reclamante, dos valores não depositados no período em que o empregado passou a receber benefício previdenciário, qual seja, {janeiro/2023 – atual.}

1.3 - ESTABILIDADE PROVISÓRIA - Reconhecida a doença ocupacional que se equipara a acidente de trabalho, faz jus o autor à estabilidade acidentária, estabelecida pelo art. 118 da Lei nº 8.213/91 e Súmula 378 do TST, pelo prazo de 12 meses, haja vista que ficou afastado de suas atividades por período superior a 15 dias com a consequente percepção do auxílio-doença acidentário. Portanto, requer seja reconhecida a garantida a estabilidade provisória de emprego ao reclamante, a partir do momento em que ocorrer a alta previdenciária, até 12 meses após referenciada alta.',
            'DO ACIDENTE DE TRABALHO – INDENIZAÇÃO POR DANOS MORAIS E ESTÉTICOS – corte com perfuro cortante' => 'No dia {16/07/2024}, a Reclamante sofreu acidente de trabalho, conforme demonstra CAT anexa: ao preparar um corpo para ser velado, cortou seu dedo com bisturi, o que lhe gerou perfuração profunda, como pode ser visto nas fotos e vídeos anexos:

   Comentário: Inserir fotos, se possível

Em razão do corte, a Reclamante passou a sentir fortes dores, e até os dias atuais, está com a mobilidade de seu dedo REDUZIDA, não conseguindo flexioná-lo. A sequela, que tem grandes chances de ser DEFINITIVA, pode ser constatada por meio dos laudos e resultados de exames anexos, dentre os quais se destacam os abaixo colacionados:

Comentário: Inserir laudos médicos

O nexo causal entre o acidente de trabalho sofrido e a negligência da Reclamada é evidente, pois:

	A empresa não fornecia todos os EPIs necessários, como luva adequada para manuseio de {bisturi}; 
	A empregadora determinava que a Autora realizasse quantidade muito extensa de horas extras, inclusive com alternância de turnos, o que certamente prejudicava sua capacidade de concentração; 
	A Ré não fornecia os treinamentos apropriados para manejo de tais itens (bisturis e outros materiais cortantes). 

Destacamos, ainda, outros elementos culposos atribuíveis à reclamada: 

I.	A empresa não cumpria todas as normas de segurança e prevenção indicadas na legislação e outras normas técnicas aplicáveis, especialmente as NRs da Portaria nº 3.214/78 do MTE. 

II.	A empresa não manteve em dia e, efetivamente, cumpre os programas preventivos da saúde do trabalhador e sobre meio ambiente de trabalho (PCMSO, PPP, PPRA, PCMAT etc.).

III.	 Não havia programa de orientação e prevenção a acidentes do trabalho.

IV.	 A autora não foi orientada e treinada sobre os riscos ergonômicos relativos às suas funções, e da mesma forma não houve treinamento para o exercício da função.

V.	A reclamada não concede cursos, palestras, orientação de prevenção de acidentes de trabalho e nem realizou teste de aptidão da autora.

Conclui-se, assim, pelo nexo causal, ressaltando a ausência de treinamento quanto ao uso e riscos dos equipamentos. Evidencia-se a presença irrefutável da culpa da reclamada, consistente no ato omissivo de descumprir obrigação legal de observar as normas de segurança e medicina no trabalho (artigo 157/CLT).

Assim, considerando os parâmetros acima transcritos, a condição econômica da Reclamada, a hipossuficiência da Reclamante, o grau do risco da atividade explorada pela Ré, requer a condenação da reclamada em danos morais, cujo valor ficará a cargo de Vossa Excelência, o que pedimos vênia, é que o arbitramento seja de, no mínimo, R$ 15.000,00 (quinze mil reais).

Ademais, no que se refere à sequela de redução de mobilidade no dedo afetado, tem-se que é inegável a ocorrência de dano estético, consoante laudos e exames médicos anexados. Caso Vossa Excelência entenda ser necessário, pleiteia, de antemão, realização de perícia técnica para avaliar a sequela ensejada pelo acidente, a fim de sanar quaisquer dúvidas acerca do grau da lesão. Por tal motivo, requer condenação da parte Ré ao pagamento de indenização pelos danos estéticos, em montante não inferior a R$ 30.000,00 (trinta mil reais).',
            'DA REVERSÃO DA JUSTA CAUSA' => 'A Reclamante foi dispensada por justa causa, em razão da {postagem de um vídeo, na rede social Tiktok, nas dependências da empresa}. No entanto, conforme será explicado a seguir, a penalidade foi aplicada com rigor excessivo.

Primeiramente, vale ressaltar que, ao contrário do que consta no aviso de dispensa da obreira, a Reclamante NÃO era reincidente na falta, sendo que ela NUNCA havia sido advertida por tal conduta. Ademais, outros funcionários, tal qual o sr. Matheus, já haviam postado vídeos em condições idênticas (nas dependências da empresa) e não foram SEQUER advertidos. É ver:

 Ademais, o fundamento no qual se embasou a dispensa por justa causa é totalmente desarrazoado, pois elenca as alíneas “e” e “h” do art. 482, CLT, como respaldos para a modalidade de extinção contratual. 

Contudo, nenhuma das duas alíneas se amolda ao caso em tela. A alínea {“e”,} de “desídia no desempenho das respectivas funções” não revela a realidade laboral da obreira, que sempre cumpria de forma tempestiva com suas obrigações, e gravou o vídeo em pouquíssimos minutos de tempo livre, após TODAS as suas obrigações já terem sido cumpridas. 

Ora, Excelência, caso a Autora laborasse com desídia, certamente a empresa já teria a advertido anteriormente. Mas, como dito, ela nunca havia recebido nenhuma advertência da empregadora. 

Prosseguindo, a alínea “h”, que versa sobre “ato de indisciplina ou de insubordinação” também não se aplica in casu, pois ele ocorre quando o empregado desobedece a ordens diretas de seu supervisor, o que não ocorreu no caso em tela. 

Ademais, vale ressaltar {que o vídeo não prejudicou a imagem da empresa, tampouco difamou quaisquer de seus funcionários, pois tratava-se de vídeo de dança, e não de falas relacionadas ao ambiente de trabalho.} 

{De igual forma, não merece respaldo a alegação da empresa, constada no aviso de dispensa, de que a conduta da empregada “quebrou a confiança entre empregadora e empregada”, pois nenhuma informação sigilosa foi demonstrada no vídeo.} 

Dessa forma, é inequívoco o tratamento com rigor excessivo, eis que a justa causa, por ser a penalidade máxima aplicável a um empregado, deve ser proporcional à falta cometida. É o que dispõe a jurisprudência:

RECURSO ORDINÁRIO. ATO DE IMPROBIDADE. INCONTINÊNCIA DE CONDUTA OU MAU PROCEDIMENTO. NÃO CONFIGURAÇÃO. REVERSÃO DA JUSTA CAUSA. CABIMENTO. A resolução do contrato de trabalho por justa causa constitui-se pena máxima a ser aplicada pelo empregador, exigindo, portanto, elementos eficazes a demonstrar cabalmente que a falta cometida pelo empregado recebeu punição adequada. Conceitua-se justa causa como todo ato faltoso do empregado que faz desaparecer a confiança e a boa-fé existentes entre as partes, tornando indesejável o prosseguimento da relação empregatícia. O ônus da prova da dispensa por justa causa é do empregador, consoante dispõe o art. 818 da CLT. Trata-se de fato impeditivo do direito do autor às verbas rescisórias, que deve ser provado pela empresa (art. 373, II, do CPC). Não se desincumbindo a empregadora do ônus de provar cabalmente a ocorrência da falta grave ensejadora da dispensa por justa causa, impõe-se a sua reversão para dispensa imotivada com o pagamento das verbas rescisórias decorrentes desta modalidade de ruptura do contrato de trabalho. (TRT-1 - RO: 01005697920185010016, Relator: JORGE ORLANDO SERENO RAMOS, Data de Julgamento: 24/05/2023, Quinta Turma, Data de Publicação: DEJT 2023-06-15) (g.n)

Há de se ter em mente que a caracterização da justa causa, ensejadora do rompimento do vínculo empregatício, demanda a presença de certos requisitos, quais sejam: a tipicidade da conduta antijurídica do empregado, a autoria e a culpa, o nexo causal entre a falta e a punição, a imediaticidade da aplicação da pena, como também sua adequação, gradação e proporcionalidade, além da ausência de perdão tácito.

Frise-se que a ausência de um desses elementos já é o bastante para se afastar a legitimidade da incidência da referida punição.

Além disso, por se tratar da máxima penalidade a ser aplicada no contrato de trabalho, a conduta apontada como causa da dispensa motivada - forma de ruptura contratual que, além de deixar sequelas na vida profissional do trabalhador, obsta a percepção de inúmeros direitos trabalhistas - deve ser robustamente comprovada pelo empregador (art. 818 da CLT e art. 333, II, do CPC/15), à luz do princípio da continuidade da relação de emprego, sob pena de ser revertida em juízo, com a consequente condenação da empresa ao pagamento das verbas rescisórias próprias da dispensa imotivada.

No presente caso, é evidente que a dispensa ocorreu de forma abusiva, eis que aplicada com rigor excessivo.

Sendo assim, requer a reversão da justa causa e, consequentemente, o pagamento das seguintes verbas rescisórias: 

•	  {33 dias de aviso prévio indenizado} 
•	  {9/12 de 13º salário de 2024, já considerando a projeção do aviso prévio até 28/09/2024;}
•	  {12/12 de férias + 1/3 constitucional, já considerando a projeção do aviso prévio até 28/09/2024 (período aquisitivo de 09/10/2023 a 28/09/2024);}
•	  {Multa de 40% sobre saldo de FGTS;}
•	  {Retificação da CTPS obreira para constar a data de projeção do aviso prévio como extinção contratual.}
•	emissão de novas guias rescisórias (TRCT, CD/SD e chave de conectividade), considerando a projeção do aviso prévio, tudo sob pena de indenização substitutiva revertida a parte autora.

1.1– MULTA DO ART. 477, § 8º DA CLT – “A reversão da justa causa em juízo enseja, por si só, a condenação ao pagamento da multa prevista no § 8º do art. 477 da CLT”, consoante Súmula 36 do Eg. TRT-3”. No mesmo sentido é a IRR nº 71 do TST, cujo teor se aplica no caso dos autos.

Sendo assim, requer seja a Ré condenada ao pagamento da multa em tela, destacando-se que, em caso de acolhimento do pedido do autor, que a multa prevista no art. 477, § 8º, da CLT, incida sobre a remuneração do obreiro, considerando todas as parcelas de natureza salarial percebidas pelo empregado, assim definido pelo art. 457, §1º, da CLT, que é o somatório de todas as parcelas pagas habitualmente ao empregado em contraprestação ao trabalho, compreendendo todas as verbas de natureza salarial, e não sobre o salário básico, o que desde já requer.


Comentário: Conversão do pedido de demissão em rescisão indireta: O tópico abaixo contém trecho que tbm pode ser usado quando solicitamos apenas a rescisão indireta, que é o “basta o inadimplemento do FGTS para ensejar a rescisão indireta”. 

02 – DA CONVERSÃO DO PEDIDO DE DEMISSÃO PARA RESCISÃO INDIRETA 

 Conforme narrado ao item “1”, o Reclamante pediu demissão em {17/01/2025}. Porém, como também já demonstrado ao longo desta petição, a Reclamada descumpria diversas obrigações contratuais, quais sejam:

•	Depósitos de FGTS e recolhimentos de INSS;
•	Ausência de fornecimento de EPIs eficazes e pagamento de adicional de insalubridade;
•	Ausência de pagamento de horas extras;
•	Ausência de pagamento dos benefícios da CCT;

{Vale ressaltar, ainda, que, embora a Reclamada tivesse fornecido alojamento para que o Autor e demais empregados residissem, apresentou ordem de despejo aos obreiros em janeiro de 2025, vide Anexo 11. Questionada pelos empregados sobre qual seria o local do novo alojamento, a empresa quedou-se inerte e não apresentou qualquer solução ao impasse.} 

{Isso demonstra o total descaso da Reclamada em relação ao empregado, bem como representa mais um descumprimento obrigacional, pois, no momento da contratação, havia sido pactuada a concessão de moradia ao Autor.}

Dessa forma, as faltas graves do empregador deixam nítido que as condições de trabalho tornaram a continuação da execução do contrato laboral impraticável, ensejando a rescisão contratual pelo empregado, nos termos do artigo 483, alínea d, da CLT. 

Em outras palavras, em razão dos descumprimentos obrigacionais da Reclamada, o obreiro não viu outra alternativa senão pedir o desligamento da empresa. Assim, pode-se concluir por um vício em sua manifestação de vontade no momento do pedido de demissão, eis que o desejo de encerrar o vínculo decorreu tão somente das irregularidades ocorridas, que se tornaram insustentáveis. 

Dessa forma, o pedido de demissão encontrou-se maculado, motivo pelo qual se torna nulo. Portanto, pugna pela conversão do pedido de demissão em rescisão indireta. Vale ressaltar que o inadimplemento de FGTS, por si só, já configura ilícito capaz de ensejar a rescisão indireta, como vastamente corroborado pela jurisprudência.

RECURSO DE REVISTA. LEIS Nos 13.015/2014 E 13.467/2017. IRREGULARIDADE NO RECOLHIMENTO DOS DEPÓSITOS DO FGTS. FALTA GRAVE. JUSTA CAUSA DO EMPREGADOR. RESCISÃO INDIRETA. TRANSCENDÊNCIA POLÍTICA RECONHECIDA. A jurisprudência desta Corte firmou-se no sentido de que a irregularidade no recolhimento dos depósitos do FGTS (mora contumaz e/ou ausência de recolhimento), por si só, constitui falta grave suficiente para a caracterização da rescisão indireta disciplinada no art. 483, d, da CLT. Dessa forma, reconhecida a irregularidade no recolhimento do FGTS pelo Tribunal Regional, resta configurado o descumprimento, pelo empregador, das suas obrigações contratuais. Precedentes. Recurso de revista de que se conhece e a que se dá provimento. (TST - RR: 00204608520215040664, Relator.: Alberto Bastos Balazeiro, Data de Julgamento: 22/03/2023, 3ª Turma, Data de Publicação: 24/03/2023) (g.n)

Portanto, requer a conversão do pedido de demissão para rescisão indireta do contrato de trabalho, devendo ocorrer o pagamento das seguintes verbas rescisórias complementares:

•	30 dias de aviso prévio indenizado;
•	1/12 de 13º salário proporcional de 2025, relativo à projeção dos 30 dias de aviso prévio;
•	1/12 de férias proporcionais + 1/3 constitucional, relativo à projeção dos 30 dias de aviso prévio;
•	Depósito de FGTS relativo à competência de fevereiro/2025;
•	Multa de 40% sobre o FGTS pleiteado.
•	Multa do art. 477, § 8ª da CLT (calculado sobre a remuneração – IRR nº 52 e 142 do TST);',
            'DA CONVERSÃO DO PEDIDO DE DEMISSÃO PARA RESCISÃO INDIRETA' => 'Conforme narrado ao item “1”, o Reclamante pediu demissão em {17/01/2025}. Porém, como também já demonstrado ao longo desta petição, a Reclamada descumpria diversas obrigações contratuais, quais sejam:

•	Depósitos de FGTS e recolhimentos de INSS;
•	Ausência de fornecimento de EPIs eficazes e pagamento de adicional de insalubridade;
•	Ausência de pagamento de horas extras;
•	Ausência de pagamento dos benefícios da CCT;

{Vale ressaltar, ainda, que, embora a Reclamada tivesse fornecido alojamento para que o Autor e demais empregados residissem, apresentou ordem de despejo aos obreiros em janeiro de 2025, vide Anexo 11. Questionada pelos empregados sobre qual seria o local do novo alojamento, a empresa quedou-se inerte e não apresentou qualquer solução ao impasse.} 

{Isso demonstra o total descaso da Reclamada em relação ao empregado, bem como representa mais um descumprimento obrigacional, pois, no momento da contratação, havia sido pactuada a concessão de moradia ao Autor.}

Dessa forma, as faltas graves do empregador deixam nítido que as condições de trabalho tornaram a continuação da execução do contrato laboral impraticável, ensejando a rescisão contratual pelo empregado, nos termos do artigo 483, alínea d, da CLT. 

Em outras palavras, em razão dos descumprimentos obrigacionais da Reclamada, o obreiro não viu outra alternativa senão pedir o desligamento da empresa. Assim, pode-se concluir por um vício em sua manifestação de vontade no momento do pedido de demissão, eis que o desejo de encerrar o vínculo decorreu tão somente das irregularidades ocorridas, que se tornaram insustentáveis. 

Dessa forma, o pedido de demissão encontrou-se maculado, motivo pelo qual se torna nulo. Portanto, pugna pela conversão do pedido de demissão em rescisão indireta. Vale ressaltar que o inadimplemento de FGTS, por si só, já configura ilícito capaz de ensejar a rescisão indireta, como vastamente corroborado pela jurisprudência.

RECURSO DE REVISTA. LEIS Nos 13.015/2014 E 13.467/2017. IRREGULARIDADE NO RECOLHIMENTO DOS DEPÓSITOS DO FGTS. FALTA GRAVE. JUSTA CAUSA DO EMPREGADOR. RESCISÃO INDIRETA. TRANSCENDÊNCIA POLÍTICA RECONHECIDA. A jurisprudência desta Corte firmou-se no sentido de que a irregularidade no recolhimento dos depósitos do FGTS (mora contumaz e/ou ausência de recolhimento), por si só, constitui falta grave suficiente para a caracterização da rescisão indireta disciplinada no art. 483, d, da CLT. Dessa forma, reconhecida a irregularidade no recolhimento do FGTS pelo Tribunal Regional, resta configurado o descumprimento, pelo empregador, das suas obrigações contratuais. Precedentes. Recurso de revista de que se conhece e a que se dá provimento. (TST - RR: 00204608520215040664, Relator.: Alberto Bastos Balazeiro, Data de Julgamento: 22/03/2023, 3ª Turma, Data de Publicação: 24/03/2023) (g.n)

Portanto, requer a conversão do pedido de demissão para rescisão indireta do contrato de trabalho, devendo ocorrer o pagamento das seguintes verbas rescisórias complementares:

•	30 dias de aviso prévio indenizado;
•	1/12 de 13º salário proporcional de 2025, relativo à projeção dos 30 dias de aviso prévio;
•	1/12 de férias proporcionais + 1/3 constitucional, relativo à projeção dos 30 dias de aviso prévio;
•	Depósito de FGTS relativo à competência de fevereiro/2025;
•	Multa de 40% sobre o FGTS pleiteado.
•	Multa do art. 477, § 8ª da CLT (calculado sobre a remuneração – IRR nº 52 e 142 do TST);',
            'RESCISÃO INDIRETA' => 'MMº Juiz, a parte autora pretende a Rescisão indireta do contrato de trabalho, na data de 10.02.2025 (último dia laborado), tendo em vista os descumprimentos com diversas obrigações, a saber:

- horas extras sem o pagamento e compensação das mesmas – violação do IRR nº 85 do TST.
- descumprimento de obrigações impostas pela CCT da categoria (Reajuste salarial e auxílio alimentação);
- Atrasos no pagamento de salários: Cito mês 11/2024 – autor recebeu R$ 1.172,00 no dia 07.11.2024 e o restante do salário só dia 11.11.2024 no valor de R$ 469,18. O mesmo se repetiu em 01/2025.
- Atrasos (cito mês 07/2024) e ausência nos recolhimentos de FGTS (só recolheu mês 04 a 07/2024), além da ausência de recolhimento do INSS do autor – violação do IRR nº 70 do TST.

A parte autora manifesta que na data de 10.02.2025 apresentou por escrito a reclamada sua intenção de apresentar em Juízo a rescisão indireta do contrato de trabalho, conforme documento anexo.
   
O pedido principal da demanda é a rescisão indireta do contrato de trabalho devido ao descumprimento do contrato de trabalho. Acontece que após a propositura da ação (autos: 0010163-54.2025.5.03.0034 – anteriormente arquivada), a reclamada começou a fazer os recolhimentos do FGTS do autor (documento anexo).

Nota-se do extrato de FGTS que foram vários os meses que foram recolhidos em atraso (cito os meses 08 a 12.2024), reforçando assim a tese do autor.
 
Ademais, manifestamos que no dia 24.02.2025 o TST consolidou sua jurisprudência em 21 temas sobre os quais não há divergência entre os órgãos julgadores do Tribunal. Os casos foram julgados como incidentes de recursos de revista repetitivos, com fixação de teses jurídicas de caráter vinculante.
 
Dentre os temas está: Rescisão indireta por atraso no FGTS - “A irregularidade no  recolhimento  dos  depósitos  de  FGTS  revela  descumprimento  de obrigação contratual, nos termos do art. 483, "d", da CLT, de gravidade suficiente para configurar  a  rescisão  indireta  do  contrato  de  trabalho,  sendo  desnecessária  a imediatidade  na  reação  do  empregado  ao  descumprimento  contratual.”  Processo: RRAg-1000063-90.2024.5.02.0032.

O reclamante fez uso da prerrogativa prevista no art. 483, §3º da CLT, tendo prestado serviços até o dia 10/02/2025. Portanto, requer seja declarada a rescisão indireta do contrato de trabalho do autor, com a consequente condenação da reclamada ao pagamento das verbas rescisórias daí decorrentes, quais sejam:

- saldo de salário de 10 dias de fevereiro/2025;
- aviso prévio indenizado de 30 dias;
- 02/12 de 13º salário de 2025;
- 11/12 férias proporcionais + 1/3 constitucional;
- Recolhimento do FGTS faltante
- Multa de 40% sobre FGTS;
- multa do art. 467/CLT;
- multa do art. 477, § 8ª da CLT (a incidir sobre a remuneração IRR nº 52 e 142 do TST);
- entrega de guias CD/SD, TRCT, chave de conectividade e baixa na CTPS em 10.03.2025, considerando a projeção do aviso prévio.',
            'DO PAGAMENTO PARCIAL DAS VERBAS RESCISÓRIAS. Periculosidade proporcional' => 'Apesar da dispensa promovida pela Ré na data acima informada, verifica-se no TRCT do obreiro que a ex-empregadora não efetuou o pagamento de “Saldo de salário” e do “adicional de periculosidade” de forma correta. Vejamos:  

•	Saldo de salário: O valor referente a 13 dias de labor deveria ser de R{$ 780,00}, e foi quitado apenas R$ {452,91};

•	Adicional de periculosidade: O devido seriam R$ {540,00}, e foi pago somente R{$ 226,45}.

Vale mencionar que o adicional de periculosidade não é fracionável, ou seja, basta a exposição ao agente periculoso por um único dia, para que ele seja devido em sua integralidade, de 30% sobre o salário base do empregado. Assim, em outras palavras, não se admite seu pagamento proporcional à “quantidade” de exposição. É o que dispõe a Súmula 361 do TST, bem como a jurisprudência, vejamos:

SÚMULA Nº 361 - ADICIONAL DE PERICULOSIDADE. ELETRICITÁRIOS. EXPOSIÇÃO INTERMITENTE - O trabalho exercido em condições perigosas, embora de forma intermitente, dá direito ao empregado a receber o adicional de periculosidade de forma integral, porque a Lei nº 7.369, de 20.09.1985, não estabeleceu nenhuma proporcionalidade em relação ao seu pagamento. (g.n)

ADICIONAL DE PERICULOSIDADE. PAGAMENTO PROPORCIONAL. O adicional de periculosidade não pode ser pago de forma proporcional, mesmo que a exposição aos riscos seja por tempo parcial. (TRT-1 - RO: 00205007020085010029 RJ, Relator.: Marcos Cavalcante, Décima Turma, Data de Publicação: 06/12/2017) (g.n)

Dessa forma, surgem diferenças devidas, a título de saldo de salário e de adicional de periculosidade, as quais foram inadimplidas, motivo pelo qual pleiteia-se seu pagamento.

2.1 - MULTA DO ARTIGO 467 DA CLT - O art. 467 da CLT estabelece que: "em caso de rescisão de contrato de trabalho, motivada pelo empregador ou pelo empregado, havendo controvérsia sobre o montante das verbas rescisórias, o empregador é obrigado a pagar ao trabalhador, na data do comparecimento à Justiça do Trabalho, a parte incontroversa dessas verbas, sob pena de pagá-las acrescidas de 50% (cinquenta por cento)".

Sendo assim, tendo em vista que as diferenças devidas a título de saldo de salário e adicional de periculosidade são incontroversas, pleiteia quitação das mesmas no ato da audiência inaugural, sob pena de aplicação da multa disposta ao art. 467, CLT.',
            'AUSÊNCIA DE PAGAMENTO INTEGRAL DAS VERBAS RECISÓRIAS. INSALUBRIDADE PROPORCIONAL – VEDAÇÃO' => 'Da análise do TRCT do Reclamante, nota-se que uma verba foi paga de forma minorada:

•	Adicional de insalubridade: o Autor recebia adicional de insalubridade em grau máximo. Entretanto, na ocasião de sua dispensa, quitaram a verba de maneira proporcional aos dias trabalhados, o que é vedado em nossa legislação. 

Registre-se que, apesar da natureza condicional do adicional de insalubridade, o seu pagamento se dá de forma mensal e, sendo calculado com base no salário mínimo, possui valor fixo, independentemente da jornada de trabalho e dos dias de efetiva exposição ao agente insalubre. 

Nesse sentido, são os seguintes precedentes: 

ADICIONAL DE INSALUBRIDADE. APURAÇÃO PROPORCIONAL. INDEVIDA. Não obstante a natureza condicional do adicional de insalubridade, o seu pagamento se dá de forma mensal e, sendo calculado com base no salário mínimo, tem valor fixo, independente da jornada de trabalho e dos dias de efetiva exposição ao agente insalubre. Recurso não provido. (TRT da 3.ª Região; PJe: 0010749-15.2019.5.03.0095 (APPS); Disponibilização: 12/05/2020; Órgão Julgador: Primeira Turma; Relator: Convocado Marcio Toledo Goncalves)


ADICIONAL DE INSALUBRIDADE - PAGAMENTO PROPORCIONAL AOS DIAS EFETIVAMENTE LABORADOS - IMPOSSIBILIDADE. É indevida a limitação do pagamento de adicional de insalubridade aos dias efetivamente laborados, uma vez que não há previsão legal que permita o pagamento da parcela de forma proporcional. (TRT da 3.ª Região; PJe: 0010824-68.2017.5.03.0016 (APPS); Disponibilização: 31/01/2020; Órgão Julgador: Terceira Turma; Relator: Emilia Facchini)

Logo, verifica-se que existem diferenças em favor do autor, no que se refere ao adicional de insalubridade pago na rescisão, motivo pelo qual requer seu pagamento, bem como de seus reflexos em 13º salários, férias + 1/3 constitucional, FGTS + 40% e RSR’s.',
            'DO PAGAMENTO PARCIAL DAS VERBAS RESCISÓRIAS' => 'Apesar da dispensa promovida pela Ré na data acima informada, verifica-se no TRCT do obreiro que a ex-empregadora não efetuou o pagamento de “aviso prévio trabalhado” e do “adicional de periculosidade” de forma correta. Vejamos:  

Comentário: Inserir print de TRCT

•	Aviso prévio indenizado: o aviso prévio indenizado deve ser calculado sobre a totalidade da remuneração do obreiro, e não somente pelo salário base. Assim, a empregadora deve considerar também o adicional de periculosidade pago ao longo do contrato, e deveria ter adimplido o aviso no valor de R$ 3.123,12 (salário base de R$ 2.402,40 + 30% de adicional de periculosidade). É o que dispõe a jurisprudência:

AVISO PRÉVIO. BASE DE CÁLCULO. A recomposição pecuniária mediante indenização do aviso prévio deve incluir todas as verbas tidas como de natureza salarial, e não apenas o salário base, haja vista que o trabalhador tem de perceber a mesma contraprestação que receberia caso trabalhasse no período. (TRT-3 - AP: 00109742920165030131 0010974-29 .2016.5.03.0131, Relator.: Cristiana M. Valadares Fenelon, Sétima Turma)',
            'MULTA DO ART. 477, § 8º DA CLT' => 'O pagamento das verbas rescisórias e as guias rescisórias se deram em 20/05/2026, qual seja, 13 dias depois do desligamento. Registre-se que, para as rescisões ocorridas a partir de 11/11/17, passou a ser necessária a realização de dois atos no prazo de 10 dias: (I) o pagamento das verbas rescisórias; e (II) a entrega ao empregado dos documentos comprobatórios da comunicação da extinção contratual aos órgãos competentes, o que não foi observado pela Ré.

Descumprido qualquer um desses requisitos, passou a ser aplicável a multa do § 8º do mesmo dispositivo, cuja redação permaneceu intocada. 

Sendo assim, requer seja a Ré condenada ao pagamento da multa em tela, destacando-se que, em caso de acolhimento do pedido da parte autora, que a multa prevista no art. 477, § 8º, da CLT, incida sobre a remuneração, assim considerada todas as parcelas de natureza salarial percebidas pela empregada, assim definido pelo art. 457, §1º, da CLT.',
            'MULTA DO ARTIGO 467 DA CLT' => 'O art. 467 da CLT estabelece que: "em caso de rescisão de contrato de trabalho, motivada pelo empregador ou pelo empregado, havendo controvérsia sobre o montante das verbas rescisórias, o empregador é obrigado a pagar ao trabalhador, na data do comparecimento à Justiça do Trabalho, a parte incontroversa dessas verbas, sob pena de pagá-las acrescidas de 50% (cinquenta por cento)".',
            'TRINTÍDIO QUE ANTECEDE A DATA BASE' => 'MMº Juiz, a data base da categoria foi em 01 de janeiro de 2025, e tendo autor sido dispensado sem justa causa em 19.12.2024, tem-se que a empresa não obedeceu a indenização adicional. Para fins de incidência do art. 9º da Lei 7.238/84, considera-se a projeção do aviso prévio, sendo devido o pagamento da indenização adicional quando a rescisão contratual ocorre nos 30 dias que antecedem a data-base da categoria, o que desde já requer.',
            'DO PAGAMENTO DA PLR PROPORCIONAL DE 2026' => 'Conforme previsão expressa nas cláusulas econômicas dos Acordos Coletivos de Trabalho aplicáveis à categoria ferroviária, os empregados da Reclamada fazem jus ao pagamento da Participação nos Lucros e Resultados (PLR), observados os critérios estabelecidos nos instrumentos normativos firmados entre a empresa e a entidade sindical representativa da categoria profissional.

No caso concreto, o Reclamante permaneceu ativo até 09.05.2026 (considerando o aviso prévio indenizado), circunstância que também lhe assegura o direito ao recebimento proporcional da Participação nos Lucros e Resultados referente ao exercício de 2026, observados os critérios previstos no ACT aplicável à categoria.

A dispensa sem justa causa do trabalhador antes do encerramento do exercício financeiro não afasta o direito ao pagamento proporcional da parcela, sobretudo porque o Reclamante efetivamente contribuiu para os resultados operacionais e produtivos da Reclamada durante o período trabalhado. Nos termos da Lei nº 10.101/2000 e da jurisprudência consolidada do Colendo Tribunal Superior do Trabalho, a participação proporcional nos lucros e resultados é devida ao empregado dispensado sem justa causa antes do encerramento do exercício de apuração. Nesse sentido:

EMENTA: PLR.	DISPENSA SEM JUSTA CAUSA. PAGAMENTO PROPORCIONAL. DEVIDO. “É devida a parcela da Participação nos Lucros e Resultados de forma proporcional ao período trabalhado no exercício-base, ainda que o empregado tenha sido dispensado sem justa causa antes do encerramento do ano.” (TST – RR nº 1000112-68.2019.5.03.0091 – 4ª Turma  Rel. Min. Alexandre Luiz Ramos – DEJT 17/05/2022).

Dessa forma, requer o Reclamante o pagamento proporcional da PLR referente ao exercício de 2026, considerando o período laborado até 09.05.2026 (aqui considerada a projeção do aviso prévio indenizado); observando-se os critérios, metas e valores previstos nos instrumentos normativos aplicáveis à categoria ferroviária. Requer, ainda, sejam observadas todas as diferenças eventualmente existentes em favor do Reclamante, inclusive decorrentes de pagamento incorreto ou proporcionalidade equivocada, com integração nas demais verbas contratuais e rescisórias legalmente pertinentes.',
        ];
    }
};
