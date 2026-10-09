-- ============================================
-- BANCO DE DADOS: TÓPICOS PARA PETIÇÃO INICIAL
-- ============================================

-- Criação do banco de dados (opcional)
-- CREATE DATABASE IF NOT EXISTS juridico_peticoes;
-- USE juridico_peticoes;

-- ============================================
-- TABELA DE GRUPOS
-- ============================================
DROP TABLE IF EXISTS topicos;
DROP TABLE IF EXISTS grupos;

CREATE TABLE grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ordem INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA DE TÓPICOS
-- ============================================
CREATE TABLE topicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    conteudo LONGTEXT NOT NULL,
    observacoes TEXT,
    ordem INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DOS GRUPOS
-- ============================================
INSERT INTO grupos (id, nome, descricao, ordem) VALUES
(1, 'PRELIMINARES', 'Tópicos preliminares da petição inicial, como gratuidade de justiça, responsabilidade subsidiária, grupo econômico, etc.', 1),
(2, 'HORAS EXTRAS', 'Tópicos relacionados a horas extras, jornadas de trabalho, turnos ininterruptos, etc.', 2),
(3, 'ADICIONAIS', 'Adicionais de insalubridade, periculosidade, noturno, etc.', 3),
(4, 'RECONHECIMENTO DE VÍNCULO', 'Tópicos sobre reconhecimento de vínculo empregatício, unicidade contratual, etc.', 4),
(5, 'DOENÇA OCUPACIONAL E ACIDENTE DE TRABALHO', 'Tópicos sobre doenças ocupacionais, acidentes de trabalho, estabilidade, danos morais e materiais', 5),
(6, 'VERBAS RESCISÓRIAS', 'Tópicos sobre verbas rescisórias, reversão de justa causa, rescisão indireta, etc.', 6),
(7, 'DANOS MORAIS', 'Tópicos sobre indenização por danos morais diversos', 7),
(8, 'COMISSÕES E REMUNERAÇÃO', 'Tópicos sobre comissões, equiparação salarial, acúmulo de função, etc.', 8),
(9, 'BENEFÍCIOS E CCT', 'Benefícios previstos em convenção coletiva, vale alimentação, plano de saúde, etc.', 9),
(10, 'ESTABILIDADES', 'Estabilidade gravídica, acidentária, etc.', 10),
(11, 'OUTROS', 'Outros tópicos diversos', 11);

-- ============================================
-- INSERT DOS TÓPICOS
-- ============================================

-- ============================================
-- GRUPO 1: PRELIMINARES
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(1, 'JUÍZO 100% DIGITAL RESOLUÇÃO CONJUNTA GP/GCR/GVCR N. 204 TRT/MG', 
'A parte autora requer que o processo tramite no Juízo 100% digital, nos termos do Artigo 5º da RESOLUÇÃO CONJUNTA GP/GCR/GVCR N. 204 do TRT/3, informando, nessa oportunidade, que o e-mail e telefone de contatos estão no timbrado da presente petição.

Na mesma toada, nos termos do Artigo 6º da citada resolução, requer seja intimada a parte requerida para manifestar sobre a petição no prazo de 5 dias, sob pena do silêncio entender-se como concordância tácita.', 
NULL, 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(1, 'DA GRATUIDADE DE JUSTIÇA', 
'Requer a parte autora os benefícios da gratuidade de justiça, nos termos da Legislação Pátria, tendo em vista estar impossibilitada de arcar com as despesas processuais sem prejuízo do próprio sustento e de sua família, conforme afirmação de hipossuficiência anexa e artigo 4º e seguintes da Lei nº 1.060/50 e artigo 5º, LXXIV, da Constituição da República. Nesse sentido, aplica-se o IRR n° 21 do TST, o que desde já requer.

Ademais, destaca-se que o obreiro recebe, atualmente, rendimento inferior a 40% do limite máximo dos benefícios do RGPS, nos termos do art. 790, § 3º da CLT, de modo que não possui condições de arcar com os custos do processo.', 
NULL, 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(1, 'DA RESPONSABILIDADE SUBSIDIÁRIA DA 2ª RECLAMADA', 
'Embora tenha sido admitido pela 1ª Reclamada ([NOME DA 1ª RECLAMADA]), o Reclamante prestou serviços exclusivos em benefício da 2ª Ré ([NOME DA 2ª RECLAMADA]), através de contrato de prestação de serviços havido entre as empresas.

Nos termos da diretriz traçada pela Súmula 331, IV, do TST, o inadimplemento das obrigações trabalhistas contraídas pela prestadora de serviços justifica a condenação subsidiária da tomadora, pois beneficiária da força de trabalho do obreiro contratado por empresa interposta.

Desse modo, requer seja declarada a responsabilidade subsidiária da 2ª Reclamada ([NOME DA 2ª RECLAMADA]), por todos os créditos deferidos na presente demanda, nos termos da Súmula 331, IV do TST.', 
'Alterar os nomes das reclamadas conforme o caso', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(1, 'DA RESPONSABILIDADE SUBSIDIÁRIA DA 2ª RECLAMADA - ENTE ADMINISTRAÇÃO PÚBLICA DIRETA', 
'A Reclamante foi admitida pela 1ª Reclamada ([NOME DA 1ª RECLAMADA]), para prestar serviços em benefício da 2ª Reclamada ([NOME DO MUNICÍPIO/ENTE PÚBLICO]), através de contrato de prestação de serviços havido entre ambas.

Impõe à Administração a adoção de medidas concretas, como as exemplificadas no item 4 da tese do Tema 1118 do STF, para assegurar o adimplemento das obrigações trabalhistas pela contratada. Houve inércia frente a notificações formais de descumprimento (item 2), além da falha em garantir condições de trabalho seguras e salubres em suas dependências (item 3), pelo que configuram a conduta negligente apta a ensejar a responsabilidade. A falta de vigilância se reforça pelo fato de não terem sido aplicadas penalidades efetivas à primeira reclamada, relacionadas ao inadimplemento dos direitos dos empregados terceirizados.

Ocorre que a 2ª Reclamada integra a administração pública direta, e por esse motivo deveria ter fiscalizado rigorosamente o cumprimento do contrato de prestação de serviços e garantido a observância dos direitos trabalhistas dos empregados da 1ª Reclamada, para evitar que sua omissão caracterize culpa por ''in vigilando'', conforme o artigo 67 da Lei nº 8.666/93 e inciso V da Súmula 331, do Tribunal Superior do Trabalho (TST).

Dessa forma, o [Nome do Município] (2ª Reclamada) responde subsidiariamente na hipótese de inadimplemento devido à inexistência de fiscalização do contrato.

O inadimplemento das obrigações trabalhistas contraídas pela prestadora de serviços justifica a condenação subsidiária da tomadora, pois beneficiária da força de trabalho da obreira contratada por empresa interposta.

Desse modo, tendo sido o trabalho da Reclamante sido prestado em benefício da 2ª Reclamada e, como exposto, estando comprovada a culpa "in contrahendo" e "in vigilando", justifica-se a responsabilização patrimonial subsidiária pelas obrigações trabalhistas inadimplidas.

Logo, pleiteia o reconhecimento da responsabilidade subsidiária da 2ª Reclamada pelos direitos vindicados pela Reclamante, como a consequente condenação ao pagamento das verbas devidas.', 
'Usar quando a 2ª reclamada for ente da administração pública direta (Município, Estado, etc.)', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(1, 'DO GRUPO ECONÔMICO FORMADO ENTRE AS RECLAMADAS - RESPONSABILIDADE SOLIDÁRIA', 
'As Reclamadas, conforme será demonstrado a seguir, formam grupo econômico, tendo a parte Autora prestado serviço em benefício de ambas.

As duas empresas possuem identidade de sócio (sr. [NOME DO SÓCIO]), bem como têm atuação conjunta, no mesmo ramo comercial, a qual pode ser demonstrada, por exemplo, pela igualdade de telefones indicados no ato da inscrição do CNPJ. Vejamos:

[Inserir print dos cartões CNPJ]

Ademais, a atuação financeira conjunta, assim como a confusão patrimonial entre as empresas, é demonstrada por meio do comprovante da rescisão, em que verbas devidas ao Reclamante por uma das empresas era adimplida pelo sócio de ambas as rés. É ver:

[Inserir print de comprovante de pagamento de salário / verbas rescisórias]

A jurisprudência consolidada pelo Tribunal Superior do Trabalho (TST) reconhece a responsabilidade solidária nos casos em que há indícios de interdependência econômica e atuação conjunta das empresas, conforme disposto na Súmula 129 do TST, que trata do desvirtuamento de contratos de trabalho e da solidariedade no grupo econômico.

Portanto, demonstrado interesse integrado, havendo estreitas ligações comerciais entre as reclamadas, utilização comum da mão-de-obra e o pagamento do salário em contraprestação ao labor prestado em prol de ambas as empresas, resta caracterizado o grupo econômico. Reconhecido o grupo econômico, nos termos do art. 2º, §2º e 3§º da CLT, impõe-se a responsabilização das reclamadas de forma solidária pelos créditos devidos.', 
'Inserir prints dos cartões CNPJ e comprovantes de pagamento que demonstrem a confusão patrimonial', 5);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(1, 'SUSPENSÃO DA PRESCRIÇÃO QUINQUENAL', 
'MMº Juiz, requer seja aplicado à hipótese vertente a Lei nº 14.010/2020, que suspendeu os prazos prescricionais no seu art. 3º, em razão da emergência de saúde pública causada pela pandemia da Covid 19, nos seguintes termos:

"Art. 3º Os prazos prescricionais consideram-se impedidos ou suspensos, conforme o caso, a partir da entrada em vigor desta Lei até 30 de outubro de 2020."

Pois bem, cumpre ressaltar que a Lei 14.010/2020, que dispõe sobre o Regime Jurídico Emergencial e Transitório das Relações Jurídicas de Direito Privado (RJET) no período da pandemia do coronavírus (Covid-19), estabeleceu a suspensão dos prazos prescricionais em curso no interregno entre 20.03.2020 e 30.10.2020.

Isso porque, conforme o disposto no art. 3°, da mencionada lei, "Os prazos prescricionais consideram-se impedidos ou suspensos, conforme o caso, a partir da entrada em vigor desta Lei até 30 de outubro de 2020".

Muito embora publicada em 12.06.2020, verifica-se que seus efeitos retroagem à publicação do Decreto Legislativo nº 6, considerando o parágrafo único do art. 1° da mesma lei que estabeleceu que: "considera-se 20 de março de 2020, a data da publicação do Decreto Legislativo nº 6, como termo inicial dos eventos derivados da pandemia do coronavírus (Covid-19)".

Sendo assim, considera-se suspensa a prescrição de todos os direitos, desde o dia 20 de março de 2020 até o dia 30 de outubro de 2020, totalizando um período de suspensão de 225 dias.

Assim, considerando que a presente ação fora ajuizada em [DATA DO AJUIZAMENTO], e deduzidos os 225 dias de suspensão estabelecida na Lei 14.010/2020, tem-se que a ação foi ajuizada em [DATA RETROATIVA], portanto, é de se fixar o marco prescricional quinquenal em [DATA DO MARCO PRESCRICIONAL].

Neste sentido é o entendimento deste Regional:

"PRESCRIÇÃO. LEI N. 14.010/20. SUSPENSÃO DOS PRAZOS PRESCRICIONAIS. A Lei n. 14.010/2020, que dispõe sobre o Regime Jurídico Emergencial e Transitório das Relações Jurídicas de Direito Privado (RJET) no período da pandemia do coronavírus (Covid-19), estabeleceu a suspensão dos prazos prescricionais em curso no interregno entre 20/03/2020 e 30/10/2020. Isso porque, conforme o disposto no art. 3º, da mencionada lei, "Os prazos prescricionais consideram-se impedidos ou suspensos, conforme o caso, a partir da entrada em vigor desta Lei até 30 de outubro de 2020". Muito embora publicada em 12/06/2020, verifica-se que seus efeitos retroagem à publicação do Decreto Legislativo n. 6, considerando o parágrafo único do art. 1º da mesma Lei, a estabelecer que: "Para os fins desta Lei, considera-se 20 de março de 2020, a data da publicação do Decreto Legislativo nº 6, como termo inicial dos eventos derivados da pandemia do coronavírus (Covid-19)". Sendo assim, considera-se suspensa a prescrição de todos os direitos, desde o dia 20 de março de 2020 até o dia 30 de outubro de 2020, totalizando um período de 225 dias. (TRT da 3.ª Região; PJe: 0011080-46.2020.5.03.0035 (ROT); Disponibilização: 05/09/2022, DEJT/TRT3/Cad.Jud, Página 1357; Órgão Julgador: Segunda Turma; Relator(a)/Redator(a): Sebastiao Geraldo de Oliveira). Grifo nosso.

No mesmo sentido, cita-se os seguintes precedentes da Terceira Turma deste Regional: processo n. 0010601-03.2020.5.03.0181, disponível desde 25/10/2022, Relator Desembargador Milton V. Thibau de Almeida; processo n. 0010200-34.2021.5.03.0095, disponível desde 05/09/2022, Relator Desembargador Danilo Siqueira de C. Faria.

Diante do exposto, requer seja determinada a suspensão do prazo prescricional em 225 dias, nos termos da fundamentação exposta, sendo considerada a presente ação ajuizada em [DATA RETROATIVA], afastando eventual prescrição bienal e fixando o marco para prescrição quinquenal em [DATA DO MARCO PRESCRICIONAL].

DE FORMA SUCESSIVA, requer seja aplicada a suspensão da prescrição por 140 dias, do qual estabelece a Lei nº 14.010/2020, redação do art. 21, que menciona a suspensão dos prazos processuais entre 12/06/2020 (data da publicação) e 30/10/2020, o que totaliza 140 dias, devendo tal suspensão ser considerada no prazo estabelecido no art. 7º, XXIX, da Constituição.', 
'Calcular as datas corretamente: data do ajuizamento, data retroativa (ajuizamento - 225 dias) e marco prescricional (data retroativa - 5 anos). NÃO inserir mais o tópico de contratos firmados antes de 2017, conforme orientação do dr. Grimaldo.', 6);

-- ============================================
-- GRUPO 2: HORAS EXTRAS
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'HORAS EXTRAS ALÉM DA 08ª DIÁRIA E 44ª SEMANAL - PEDIDO COMUM', 
'De acordo com a jornada de trabalho descrita no tópico 01, durante o contrato de trabalho o reclamante ultrapassava a jornada legalmente permitida, sem receber corretamente ou compensar as horas realizadas. Restam desde já impugnados eventuais acordos de compensação, diante da habitualidade das horas extras praticadas.

Assim sendo, faz jus às horas extras além da 08ª diária e 44ª semanal, acrescidas do adicional [convencional de 100% (cl. XX da CCT)/legal], devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST, divisor 220, devendo ainda ser pagos os reflexos sobre os 13° salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR''s.', 
'Verificar na CCT qual o adicional convencional aplicável. Se não houver CCT, usar adicional legal de 50%.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DA ATIVIDADE EM TURNOS ININTERRUPTOS DE REVEZAMENTO - AUSÊNCIA DE AUTORIZAÇÃO PRÉVIA DO MTE PARA PRORROGAÇÃO DA JORNADA EM AMBIENTE INSALUBRE', 
'Conforme descrito no tópico 01, a reclamante laborou em turnos ininterruptos de revezamento por todo o contrato de trabalho, em jornadas diárias superiores a 06 horas e 36 horas semanais.

Inicialmente, cumpre ressaltar que a redução da jornada para seis horas diárias em regime de turnos ininterruptos de revezamento foi instituída pela Constituição Federal, em seu artigo 7º, inciso XIV.

Todavia, não se deve pensar que esta redução foi aleatória. O regime especial de jornada para o trabalho realizado em turnos ininterruptos de revezamento tem como objetivo precípuo tentar proteger a saúde do empregado, vez que este tipo de jornada de trabalho, abrangendo várias horas do dia e da noite, ou seja, ora na parte da manhã, ora na parte da tarde, e ora na parte da noite, traz um desgaste muito maior para o trabalhador, que as jornadas convencionais.

Se olharmos atentamente a Constituição Federal, veremos:

"Art. 7º São direitos dos trabalhadores urbanos e rurais, além de outros que visem à melhoria de sua condição social:
...
XIV - jornada de seis horas para o trabalho realizado em turnos ininterruptos de revezamento, salvo negociação coletiva;" (grifo nosso).

O regime de turnos ininterruptos de revezamento é mais desgastante, por conseguinte, tem potencial maior de prejuízo à saúde do trabalhador. No caso dos autos, havia prestação de horas extras habituais, o que por certo, invalida a adoção desse regime, consoante o entendimento consubstanciado na Súmula 423 do TST e na Súmula 38 do TRT/MG, o que desde já requer.

Ademais, não existem no caso dos autos, Convenções Coletivas ou Acordo Coletivo, autorizando a prorrogação de jornada em ambiente insalubre, seja em turno fixo ou em turno ininterrupto de revezamento, nos exatos termos do art. 60/CLT.

Há necessidade de prévia inspeção da DRT para validade do elastecimento de jornada eventualmente previsto em cláusula coletiva, nos termos do art. 60 da CLT. Nem se diga que o julgamento proferido pelo STF do ARE 1121633, Tema nº 1.046 socorre a parte reclamada, considerando que a duração da jornada de trabalho se trata de matéria constitucional (Artigo 7º Incisos XII, XIV, sendo inválida eventual Convenção Coletiva autorizando a prorrogação de jornada em ambiente insalubre sem a inspeção prévia do Ministério do Trabalho.

Como ressalvado pelo próprio STF no Tema 1046:

"São constitucionais os acordos e as convenções coletivos que, ao considerarem a adequação setorial negociada, pactuam limitações ou afastamentos de direitos trabalhistas, independentemente da explicitação especificada de vantagens compensatórias, desde que respeitados os direitos absolutamente indisponíveis".

Mas, adiante, o mesmo texto constitucional estabelece, no inciso XXII, regra que impõe a "redução dos riscos inerentes ao trabalho, por meio normas de saúde, higiene e segurança", tal como se dá, então, com a previsão contida no artigo 60 da CLT, que exige, como dito, prévia inspeção da DRT para validade dessa extensão de jornada em ambientes insalubres.

Ademais, se trata de norma de saúde, higiene e segurança do trabalho, sendo ilícita eventual previsão Convencional nos termos do art. 611-B, inciso XVII, da CLT.

Requer seja declarada ilícita eventual autorização convencional, para prorrogação de jornada em ambiente insalubre sem a inspeção prévia do Ministério do Trabalho, nos termos do art. 611-B, inciso XVII, da CLT.

Assim sendo, deve a reclamada pagar as horas laboradas além da 06ª diária e 36ª semanal, com acréscimo do adicional convencional/legal, divisor 180, remuneração da Autora (Súmula 264/TST) e reflexos no 13º salário, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.

SUBSIDIARIAMENTE, caso Vossa Excelência entenda pela inaplicabilidade do turno ininterrupto de revezamento, requer o pagamento das horas extras além da 08ª diária e 44ª semanal, pois restou claramente apontado que o limite legal restou habitualmente ultrapassado, sem que as horas extras fossem pagas ou compensadas.

E mais, diante da ausência de licença prévia do Ministério do Trabalho e Emprego para a prorrogação do horário de trabalho em ambiente insalubre, além da prática habitual de horas extraordinárias, deve a reclamada pagar as horas laboradas além da 08ª diária e 44ª semanal, com adicional convencional/legal, divisor 220, remuneração da Autora (Súmula 264/TST) e reflexos em 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.', 
'Usar quando houver turno ininterrupto de revezamento e ambiente insalubre. Sempre pedir subsidiariamente além da 8ª/44ª.', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DAS HORAS EXTRAS ALÉM DA 08ª DIÁRIA E 44ª SEMANAL - JORNADA 12X36 COM HORAS EXTRAS HABITUAIS', 
'Conforme descrito no tópico 01, o reclamante laborou em jornadas que ultrapassavam o limite de 08h diárias e 44h semanais. Vale ressaltar que a prestação de horas extras habituais ([média de XX h/dia, por X vezes na semana]) desconstitui a validade da jornada 12x36, por ensejar um descanso inferior a 36 horas após o labor. Vejamos a jurisprudência consolidada no TST:

AGRAVO. RECURSO DE REVISTA COM AGRAVO. REGIDO PELA LEI 13.467/2017. PROCEDIMENTO SUMARÍSSIMO. JORNADA DE TRABALHO. ESCALA 12X36. PRESTAÇÃO HABITUAL DE HORAS EXTRAS. INVALIDADE. SÚMULA 85, IV, DO TST. DESCUMPRIMENTO DA NORMA COLETIVA. TRANSCENDÊNCIA NÃO RECONHECIDA NA DECISÃO AGRAVADA. 1. Hipótese em que o Tribunal Regional, com amparo nas provas dos autos, declarou inválida a escala 12x36 adotada pela Reclamada e deferiu horas extras, considerando como extraordinárias as horas laboradas além da 8ª diária e da 44ª semanal, ante a habitualidade do trabalho suplementar e a inobservância do intervalo mínimo de 36 horas de descanso entre as jornadas. Registrou que, "dos recibos de pagamento verifico, além do já mencionado pagamento do Adicional de Intervalo, o adimplemento habitual de horas extras. Tanto a supressão do intervalo intrajornada, quanto a prestação habitual de horas extras em supressão parcial das 36h de descanso, desvirtuam a finalidade do sistema compensatório, o que acarreta a sua nulidade". Destacou que, "... com relação ao sistema 5x2, além de não concedido o intervalo de 36 horas entre as duas jornadas, a carga horária semanal ultrapassava 44h, não havendo cogitar da validade de aludido sistema compensatório". 2. O acórdão regional encontra-se em consonância com o entendimento consolidado nesta Corte Superior, no sentido de que a prestação de horas extras habituais e a inobservância do intervalo mínimo de 36 horas de descanso entre as jornadas descaracterizam o regime 12x36, considerando-se como extraordinárias as horas laboradas além da 8ª diária e 44ª semanal. Além disso, considerando o entendimento assente na Subseção I Especializada em Dissídios Individuais desta Corte Superior, uma vez considerada inválida a escala 12x36 pela prestação habitual de horas extras, são consideradas, como extras, as horas laboradas além da 8ª diária e 44ª semanal, não havendo falar em pagamento apenas do adicional quanto às horas destinadas à compensação, porquanto configurada a invalidade do regime adotado. (TST - Ag-RRAg: 0020177-22.2019.5.04.0021, Relator.: Douglas Alencar Rodrigues, Data de Julgamento: 07/02/2024, 5ª Turma, Data de Publicação: 16/02/2024) (g.n)

Não havia o pagamento correto das horas prestadas e sequer a compensação das mesmas.

Assim, requer seja deferido à parte autora o pagamento das horas extras além da 8ª diária e 44ª semanal, acrescidas do adicional convencional/legal, divisor 220, devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST, devendo ainda ser pagos os reflexos sobre os 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR''s.

SUBSIDIARIAMENTE, caso Vossa Excelência entenda pela inocorrência da desconstituição da jornada 12x36, requer o pagamento das horas extras além da 12ª diária, pois restou claramente apontado que o limite pactuado restou habitualmente ultrapassado, sem que as horas extras fossem pagas ou compensadas.

Portanto, requer seja deferido à parte autora o pagamento das horas extras além da 12ª diária, acrescidas do adicional convencional/legal, divisor 210, devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST, devendo ainda ser pagos os reflexos sobre os 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR''s.', 
'Preencher a média de horas extras habituais. Sempre pedir subsidiariamente além da 12ª diária.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'HORAS EXTRAS EXCEDENTES A 8ª DIÁRIA E 44ª SEMANAL - JORNADA 12H EM ESCALAS 03X02, 04X02 E AFINS - INVALIDADE', 
'Conforme descrito no tópico 01, o reclamante sempre trabalhou em jornadas de 12h diárias, em escalas [03x02 e 04x02]. Ocorre que, conforme entendimento já pacificado pelo TST, a adoção de tal jornada é INVÁLIDA, eis que a legislação atual autoriza o labor por 12h ao dia apenas diante da concessão de 36 horas subsequentes de descanso, o que não ocorria no caso em tela. Vejamos os julgados:

AGRAVO DE INSTRUMENTO HORAS EXTRAS. JORNADA DE 12 HORAS NAS ESCALAS 4X2, 5X2, 5X1 E 6X1. INVALIDADE. Demonstrada a afronta ao artigo 7º, XIII, da Constituição da Republica, dá-se provimento ao Agravo de Instrumento, a fim de determinar o processamento do recurso de revista . RECURSO DE REVISTA. HORAS EXTRAS. JORNADA DE 12 HORAS NAS ESCALAS 4X2, 5X2, 5X1 E 6X1. INVALIDADE . 1. Nos termos da jurisprudência pacífica desta Corte superior, consagrada na Súmula n.º 444, admite-se, excepcionalmente, a jornada diária de doze horas de trabalho, desde que na escala de 12 por 36 e somente se adotada mediante norma coletiva ou por força de previsão legal, porquanto considerada, nestes termos, deveras benéfica ao trabalhador. 2. Inválida, por conseguinte, a referida jornada de doze horas de trabalho diário se não observados tais requisitos. 3. Num tal contexto, a jornada diária de trabalho, em quaisquer outras escalas, ainda que submetida a regime de labor extraordinário, de compensação de jornadas ou até mesmo de banco de horas, deve se restringir ao máximo de dez horas diárias, haja vista a existência de expressa limitação legal, nos termos do artigo 59, cabeça, da Consolidação das Leis do Trabalho, ao acréscimo de horas suplementares "em número não excedente de 2 (duas)". 4. Acrescente-se, ademais, que a jornada de trabalho de doze horas nas escalas 4x2, 5x2, 5x1 e 6x1, ainda que estivesse prevista em norma coletiva, tampouco encontra respaldo no artigo 7º, XIII, da Constituição da Republica, porquanto sempre extrapola a jornada diária e semanal sem haver compensação. 5. Recurso de Revista conhecido e provido. (TST - RR: 1243008120075020010, Data de Julgamento: 12/08/2015, Data de Publicação: DEJT 21/08/2015) (g.n)

HORAS EXTRAS. ESCALA 4X2. JORNADA DE TRABALHO DE 12 HORAS DIÁRIAS. INVALIDADE. HORAS EXTRAS A PARTIR DA 8ª DIÁRIA. A fixação de jornada diária de 12 horas de trabalho constitui medida excepcional, e somente é admitida em lei, caso seguida de 36 horas consecutivas de descanso, na clara interpretação do art. 59-A da CLT. A prestação de labor por 12 horas diárias na escala 4x2, ainda que negociada em norma coletiva, viola o instituto da compensação, por exceder tanto, o limite de 8 horas diárias, quanto aquele de 44 semanais, previstos no art. 7º, XIII, da CF. Em tais casos, o trabalhador faz jus às horas extras excedentes da 8ª diária e 44ª semanal. Recurso autoral provido. (TRT-2 10010321320205020011 SP, Relator.: SILVANE APARECIDA BERNARDES, 8ª Turma - Cadeira 2, Data de Publicação: 27/04/2022) (g.n)

E tendo em vista que a parte reclamante desenvolvia seu trabalho em ambiente insalubre e que não há notícia de que a reclamada obteve licença prévia das autoridades competentes em matéria de higiene do trabalho para entabular acordo de prorrogação de jornada; fato que, por si só, enseja a nulidade do regime pactuado. Logo, requer seja declarado inválido eventual regime de compensação em escala 12x36 e condenar a reclamada ao pagamento das horas extras laboradas além da 8ª diária e 44ª semanal, cujo pagamento deverá ser realizado nos moldes da Súmula nº 85, VI do C. TST. Assim, as jornadas estabelecidas pela Reclamada são inválidas, e todo o labor excedente à 08ª hora diária e/ou 44ª semanal deve ser quitada como hora extra.

Assim, requer seja deferido à parte autora o pagamento das horas extras além da 8ª diária e 44ª semanal, acrescidas do adicional convencional/legal, divisor 220, devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST, devendo ainda ser pagos os reflexos sobre os 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR''s.', 
'Usar para escalas 03x02, 04x02, 05x02, 05x01, 06x01 com jornada de 12h. Preencher o tipo de escala.', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DAS HORAS EXTRAS - JORNADA INFERIOR A 6H', 
'O horário de trabalho da Reclamante, previsto no ato da contratação, era de [HORÁRIO CONTRATUAL], sem pausa alimentar. Dessa forma, conclui-se que a jornada contratual perfazia [Xh/dia e Xh/semana] (escala 06x01). Assim, tudo que ultrapassar a [Xª hora diária e Xª semanal] deve ser considerada como extra.

De acordo com a jornada de trabalho descrita no tópico 01, durante o contrato de trabalho, a reclamante ultrapassava a jornada pactuada no momento da contratação, pois laborava diariamente até às [HORÁRIO REAL DE SAÍDA], sem receber corretamente ou compensar as horas realizadas. Restam desde já impugnados eventuais acordos de compensação, diante da habitualidade das horas extras praticadas.

Desta forma, deve a reclamada pagar as horas laboradas além da [Xª diária e/ou Xª semanal] por todo o período contratual, acrescidas do adicional convencional de 100%/adicional legal, devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST, divisor 220, devendo ainda ser pagos os reflexos sobre os 13° salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR''s.', 
'Usar quando a jornada contratual era inferior a 6h/dia. Preencher os horários e calcular a jornada contratual.', 5);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DA SUPRESSÃO DO DSR', 
'Embora a Autora tenha sido contratada para trabalhar em escala 06x01, teve seu DSR suprimido em algumas ocasiões, pois, [descrever as ocasiões em que houve labor por mais de 6 dias consecutivos].

Assim, pleiteia pagamento, com adicional de 100%, dos dias de DSR suprimidos, em que houve labor por mais de 06 dias consecutivos, globalidade da remuneração (Súm. 264/TST) bem como seus reflexos sobre 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.', 
'Descrever especificamente as ocasiões em que houve labor por mais de 6 dias consecutivos sem folga.', 6);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'TEMPO À DISPOSIÇÃO DO EMPREGADOR - ART. 4º DA CLT', 
'O atual artigo 4º da CLT, em seu caput, expressamente estatui que "considera-se como de serviço efetivo o período em que o empregado esteja à disposição do empregador, aguardando ou executando ordens, salvo disposição especial expressamente consignada". No parágrafo 2º, o artigo 4º da CLT excepciona as hipóteses em que o tempo gasto pelo empregado não deve ser considerado como à disposição do empregador, enumerando as de forma taxativa:

"Por não se considerar tempo à disposição do empregador, não será computado como período extraordinário o que exceder a jornada normal, ainda que ultrapasse o limite de cinco minutos previsto no § 1º do art. 58 desta Consolidação, quando o empregado, por escolha própria, buscar proteção pessoal, em caso de insegurança nas vias públicas ou más condições climáticas, bem como adentrar ou permanecer nas dependências da empresa para exercer atividades particulares, entre outras:
I - práticas religiosas;
II - descanso;
III - lazer;
IV - estudo;
V - alimentação;
VI - atividades de relacionamento social;
VII - higiene pessoal; e
VIII - troca de roupa ou uniforme, quando não houver obrigatoriedade de realizar a troca na empresa."

No caso em tela, tem-se que o DESLOCAMENTO INTERNO NÃO consta do rol acima mencionado, além de não se realizar em benefício do empregado, por escolha própria, mas sim em atendimento às necessidades da empregadora, sendo imprescindível ao desenvolvimento da atividade econômica empresarial.

No caso em tela, por determinação expressa da parte ré, o Reclamante entrava pela portaria da empresa antes do início de sua jornada de trabalho, e percorria cerca [X minutos] entre a entrada da empresa e o local de registro do ponto que se dava no setor até a portaria, tanto no início quanto do fim da jornada.

Nesse período de percurso, o autor estava a disposição da empresa, uma vez que tinha que obedecer às regras de segurança da empresa, como: não falar no celular, andar na calçada, não poderia correr, dentre outras medidas implementadas pela ré. A parte autora poderia ser punida com advertência ou suspensão caso não respeitasse a regra fixada pela empresa.

Entretanto tal período, mesmo estando à disposição da reclamada, não era computado no cartão de ponto. Deste modo, a conduta da Reclamada contrariou o disposto no §1º do Art. 58 da CLT e ART. 4ª da CLT.

Em síntese, existem [X minutos antes e X minutos] após o registro de ponto, ou seja, cerca de [X] minutos diários à disposição da empresa (art. 4º da CLT) sem registro. Posto isso, requer a condenação da reclamada ao pagamento de [X] minutos, por cada dia efetivamente trabalhado, com adicional convencional de 60%/legal, com reflexos em férias + 1/3 constitucional, 13º salário, FGTS+40%, aviso prévio e RSR''s.', 
'Preencher os minutos de deslocamento interno antes e depois do registro de ponto.', 7);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DA TROCA DE UNIFORME EXIGIDA PELA EMPRESA', 
'O Autor após chegar no setor de trabalho, diariamente, tinha que realizar troca de uniforme ao chegar na empresa, vez que as vestimentas próprias para adentrar na área industrial ficavam exclusivamente nas dependências da Reclamada, a qual era responsável por higienizá-las.

Contudo, por determinação de seus supervisores, o obreiro realizava a troca de roupas em horário não abrangido pelo registro de ponto, além da conferência de EPI''s, e materiais de trabalho, ou seja: na chegada, vestia o uniforme para depois registrar ponto, e, na saída, primeiro registrava o fim da jornada, para, só depois, retirar o uniforme.

Isso fazia surgir 20 minutos não contabilizados nos cartões de ponto (10 minutos ao início e 10 ao final do expediente), destinados exclusivamente à troca de roupa.

Vale ressaltar que o tempo despendido com a troca de uniforme deve ser contabilizado DENTRO da jornada, caso seja exigido, pela empregadora, que ela ocorra nas dependências da empresa. É o que dispõe a jurisprudência:

TROCA DE UNIFORME. TEMPO À DISPOSIÇÃO DO EMPREGADOR. O tempo despendido pelo empregado com a troca de uniforme de forma obrigatória na empresa deve ser considerado tempo à disposição do empregador, conforme a exceção prevista no art. 4º, § 2º, VIII da CLT. Portanto, constatado que no horário de entrada era obrigatória a troca de uniforme na empresa, os minutos despendidos com essa destinação devem ser acrescidos à jornada do trabalhador, para fim de apuração das horas extras. Recurso ordinário da ré a que se dá provimento parcial, no particular. (TRT-9 - ROT: 00006471620215090088, Relator: BENEDITO XAVIER DA SILVA, Data de Julgamento: 27/01/2023, 7ª Turma, Data de Publicação: 02/02/2023)

Portanto, em síntese, existem 10 minutos antes e 10 minutos após o registro de ponto, ou seja, cerca de 20 minutos diários à disposição da empresa (para troca de uniforme) sem registro nos cartões de ponto. Posto isso, requer a condenação da reclamada ao pagamento de 20 minutos, por cada dia efetivamente trabalhado, com adicional convencional/legal, com reflexos em férias + 1/3 constitucional, 13º salário, FGTS+40%, aviso prévio e RSR.', 
'Ajustar os minutos conforme o caso concreto.', 8);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DAS HORAS EXTRAS ALÉM DA 06ª DIÁRIA E 36ª SEMANAL - EMPREGADO EM CALL CENTER - ART. 227, CLT', 
'Conforme exposto no tópico 01, o Reclamante foi admitido pela reclamada para laborar na função de Vendedor, e para tanto, fazia uso de cabine dotada de computador e aparelho telefônico, realizando o atendimento ativo e receptivo de chamadas telefônicas, realizando vendas de "assinaturas de saúde", além de cobranças por meio telemático de clientes da Reclamada, tudo via telefone.

A legislação prevê jornada de trabalho de 06 horas para as empresas que exploram serviço de telefonia, como se estampa no art. 227 da CLT. Coadunando com o entendimento legal, O TST fixou o IRR nº 176 quanto o tema aqui discutido. Além do mais, o Item 5.3 do Anexo II da Norma Regulamentadora 17, MTE, também verbera na mesma esteira.

Assim sendo, requer a condenação da Reclamada ao pagamento das horas laboradas além da 06ª diária e 36ª semanal, com adicional legal, divisor 180, devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST, bem como os reflexos sobre 13° salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.', 
'Usar para empregados de call center, telemarketing, telefonistas.', 9);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'RECÁLCULO / DIFERENÇAS DO USO DO DIVISOR DE 220 PARA 180', 
'Conforme descrito no tópico 01, a Reclamante durante o contrato de trabalho, laborava na empresa em turno ininterrupto de revezamento. Sendo assim, o divisor aplicável nesse caso é o de 180, tendo em vista a redução da jornada máxima diária e semanal para, respectivamente, 6 diárias e 36 horas semanais.

Logo, a reclamada deverá pagar a autora as diferenças de todas as parcelas de natureza salarial entre o divisor 220 para 180 (abaixo listadas), quitadas durante o pacto laboral, eis que foram contabilizadas com o divisor incorreto:

- [Listar as rubricas dos contracheques que foram calculadas com divisor incorreto]

Conforme restará evidenciado pelos recibos de pagamento que serão juntados aos autos, as parcelas salariais da parte Reclamante eram calculadas com base no divisor 220. Sendo assim, tendo em vista a constatação da jornada da obreira no que diz respeito ao turno ininterrupto de revezamento, sua jornada deve ser a estabelecida na Constituição Federal, de maneira que haja, no máximo, 6 horas diárias de labor e 36 horas semanais, da qual surge o divisor 180 para delimitar o valor da hora trabalhada.

A questão está pacificada no Colendo Tribunal Superior, por meio da OJ 396 de sua SDI-I. É que a mera aplicação do divisor 180 já revela, por si só, a existência de distorção entre os valores das parcelas pagas com divisor 220 e aquele devido, qual seja, parcelas com divisor 180.

Destarte, requer a procedência do pedido para determinar o pagamento das diferenças/recálculo entre o divisor 220 para 180 em relação a todas as parcelas acima listadas, das quais surgirão diferenças a que a Autora faz jus. Pleiteia, ainda, os reflexos de tais diferenças em 13º salários, férias + 1/3 constitucional, FGTS +40%, aviso prévio e RSR''s.', 
'Listar as rubricas específicas dos contracheques que foram calculadas com divisor incorreto.', 10);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'INTERVALO INTERJORNADA', 
'Em razão da extensa quantidade de horas extras prestadas, por diversas vezes, a parte Reclamante não realizava o intervalo interjornada de no mínimo 11 horas entre o fim de uma jornada e o início de outra (art. 66 da CLT). Assim sendo, nessas ocasiões, requer indenização pelas horas suprimidas de intervalo interjornada, divisor 180 ou 220, com adicional convencional de 100%/legal, remuneração do autor (Súmula 264/TST).', 
'Verificar qual divisor aplicar conforme o regime de trabalho.', 11);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DA SUPRESSÃO DO INTERVALO INTRAJORNADA', 
'Conforme descrito no tópico 01, devido à alta demanda de trabalho, a reclamante não usufruiu corretamente de seu intervalo intrajornada, pois só almoçava e retornava ao labor, fazendo no máximo [X minutos] por dia de pausa alimentar.

Estabelece o art. 71, § 4º da CLT a obrigatoriedade da concessão de 1h de intervalo intrajornada para jornadas que excederem 6 horas, sendo o empregador obrigado a remunerar o período correspondente, caso não o conceda, com um acréscimo do adicional legal, sobre o valor da remuneração da hora normal.

Assim sendo, de acordo com as argumentações supra, e o que será provado em instrução processual, faz jus a Autora ao recebimento de indenização pelos [X minutos] suprimidos do intervalo intrajornada, devidamente acrescidos do adicional convencional/legal, divisor 220, devendo ser observada em sua base de cálculo todas as parcelas salariais recebidas e deferidas, nos termos da súmula 264/TST.', 
'Preencher os minutos efetivamente usufruídos e calcular a diferença para 1 hora.', 12);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DAS HORAS DE SOBREAVISO', 
'Do mês [MÊS/ANO] até a dispensa, a autora passou a exercer a função de [FUNÇÃO]. Com a alteração de função e as responsabilidades atribuídas à obreira, ela prestava apoio diário aos colegas, após o fim da jornada e aos finais de semana, por meio de seu celular pessoal, esclarecendo dúvidas e fornecendo informações. Além disso, participava de reuniões conforme as solicitações de sua superior, que cobrava seu apoio e interesse.

As jornadas de sobreaviso ocorriam nos seguintes moldes: durante a semana, após o término da jornada de trabalho, com encerramento às 07h00 do dia seguinte. Nos finais de semana, o período de sobreaviso se iniciava ao término da jornada de sexta-feira e se encerrava no início da jornada de segunda-feira, estando a obreira à disposição dos empregados todos os dias, a qualquer horário (segunda-feira a domingo). Destaca-se que a reclamante deveria manter seu celular ligado 24 horas/dia para eventuais chamadas, fato que lhe implicava limitações, como não poder se dirigir a local sem sinal telefônico.

Durante as horas de sobreaviso, a obreira efetivamente laborava por cerca de [X]h/dia, horas essas que já estão inclusas no pedido de item "XX" e não se confundem com as horas em que a Reclamante estava de sobreaviso.

E para atender as necessidades da empresa, e da equipe, a reclamada determinava que a autora ficasse de sobreaviso, não podendo desligar o celular, ou se recusar a atender chamadas da empresa.

No caso em tela, a trabalhadora tinha restrição à sua liberdade, ao direito ao lazer e à desconexão do trabalho, pois se não atendesse aos chamados da empresa poderia ser penalizada. No mais, nos dias e horários em que a reclamante estava de sobreaviso, ela deveria ficar aguardando em casa o chamado da empresa.

Nos termos em que era imposto pela empresa, a autora permanecia em estado de expectativa durante o seu descanso, aguardando ser chamado a qualquer momento para prestar serviço ao empregador. Ou seja, havia um estado de disponibilidade ou de alerta total para a empresa, em regime de sobreaviso.

Dessa forma, é devido a parte autora o recebimento do sobreaviso nos moldes do art. 244 da CLT, à razão de 1/3 das horas em sobreaviso, durante todo o contrato de trabalho, bem como seus reflexos sobre 13º salários, férias + 1/3 constitucional, FGTS+40%, aviso prévio e RSR´s.', 
'Preencher o período em que passou a ficar de sobreaviso e as horas efetivamente trabalhadas durante o sobreaviso.', 13);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(2, 'DO LABOR AOS DOMINGOS E FERIADOS - DESCANSO DOMINICAL DA MULHER - REVEZAMENTO QUINZENAL - ART. 386 DA CLT', 
'Durante a vigência do contrato de trabalho, a autora laborava na empresa aos domingos e feriados. No que toca do labor aos domingos, a reclamada não observava a escala de revezamento do art. 386/CLT. O repouso semanal remunerado é direito social trabalhista garantido constitucionalmente, que deve ser concedido, preferencialmente, aos domingos, como estabelece o art. 7º, XV, da Constituição da República (CR). Na mesma linha, dispõe o art. 67 da CLT.

A empregada mulher goza da norma protetiva prevista no art. 386 da CLT, que obriga o empregador a implantar escala de revezamento quinzenal, de modo a permitir à trabalhadora usufruir de uma folga aos domingos a cada quinze dias, nos seguintes termos: "Art. 386. Havendo trabalho aos domingos, será organizada uma escala de revezamento quinzenal, que favoreça o repouso dominical".

O referido artigo foi recepcionado pela Constituição da República, de acordo com o entendimento da SDI-1 do colendo Tribunal Superior do Trabalho, manifestado, ilustrativamente, nos julgamentos dos processos E-ED-RR-982-80.2017.5.12.0059, DEJT 17/06/2022 e E-ED-RR-554-39.2017.5.12.0014, DEJT 08/09/2023. Assim, se descumprida a norma cogente pelo empregador, torna-se devido o pagamento da dobra pelo labor prestado aos domingos em desacordo com o art. 386 da CLT.

Assim, considerando a inexistência de compensação ou pagamento do adicional de 100% devido (cem por cento) sobre a hora normal trabalhada aos domingos e feriados, é devido à autora o recebimento do labor nos respectivos dias, na forma dobrada, nos termos das Súmulas 461 do STF e 146 do TST, durante todo o contrato de trabalho, bem como seus reflexos sobre 13º salários, férias + 1/3 constitucional, FGTS +40%, aviso prévio e RSR´s.', 
'Usar para empregadas mulheres que trabalhavam aos domingos sem revezamento quinzenal.', 14);

-- ============================================
-- GRUPO 3: ADICIONAIS
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(3, 'DO ADICIONAL DE INSALUBRIDADE E EMISSÃO DO PPP - SÓ INSALUBRIDADE', 
'O Reclamante foi admitido na função de [FUNÇÃO], e no exercício de suas atribuições, esteve diariamente exposto a agentes [descrever os agentes insalubres: químicos, físicos, biológicos, etc.].

Em contrapartida, não lhe era pago adicional de insalubridade, tampouco fornecidos todos os EPIs hábeis a eliminar a nocividade dos agentes aos quais estava exposto.

Portanto, requer realização de perícia técnica, a fim de comprovar que o reclamante faz jus ao recebimento do adicional de insalubridade em grau máximo, ou seja, 40% (quarenta por cento) sobre o salário mínimo da época, bem como seus reflexos em 13º salários, férias + 1/3 constitucional, FGTS+40%, aviso prévio e RSR.

EMISSÃO DO PPP: Constatado que o Reclamante trabalhava em condições insalubres, é necessária a emissão de formulário do PPP (Perfil Profissiográfico Previdenciário) contendo todas as informações relativas ao empregado, como por exemplo, a atividade que exerce, o agente nocivo ao qual é exposto, a intensidade e a concentração do agente, exames médicos clínicos, além de dados referentes à empresa.

O formulário deve ser preenchido pelas empresas que exercem atividades que exponham seus empregados a agentes nocivos químicos, físicos, biológicos ou associação de agentes prejudiciais à saúde ou à integridade física (origem da concessão de aposentadoria especial após 15, 20 ou 25 anos de contribuição).

Assim sendo, o Reclamante requer a emissão de formulário PPP de todo o período laborado na reclamada, sob pena de multa diária no importe de R$ 100,00 (cem reais), a ser revertida em favor da parte autora.', 
'Preencher a função e os agentes insalubres específicos do caso.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(3, 'DO ADICIONAL DE INSALUBRIDADE/PERICULOSIDADE E EMISSÃO DE PPP - INSALUBRIDADE E PERICULOSIDADE', 
'O Reclamante durante todo o período de contrato de trabalho, esteve exposto a agentes insalubres, como [descrever os agentes insalubres: ruídos, vibrações, incidência de luz solar, calor, etc.].

De acordo com o art. 189 da CLT, considera-se insalubre aquela atividade, que por sua natureza expõe o trabalhador a risco, através de algum agente nocivo à saúde. In casu, o reclamante possuía contato direto com agentes insalubres.

Outros agentes periculosos - a parte autora trabalhava em locais onde era exposta a gases nocivos, além de locais no setor que tinham grande riscos de explosão, tudo conforme placas indicativas fornecidas pela própria ré. Além do mais, trabalhava próximo a painéis elétricos dos quais tinha que realizar serviços no seu dia a dia.

Ademais, o motorista de veículo equipado com tanque de combustível cuja capacidade exceda 200 litros faz jus a adicional de periculosidade, em razão do risco de explosão. No caso em tela, o ônibus conduzido pelo Reclamante possuía capacidade de cerca de [X] litros, motivo pelo qual deveria receber o referido adicional. É o que preceitua a jurisprudência:

Ementa: RECURSO DE REVISTA. ADICIONAL DE PERICULOSIDADE. MOTORISTA DE CAMINHÃO. TANSPORTE DE COMBUSTÍVEL. TANQUES COM CAPACIDADE SUPERIOR A 200 LITROS. CONSUMO PRÓPRIO. TRANSCENDÊNCIA POLÍTICA RECONHECIDA. 1. A jurisprudência dessa Corte orienta que o adicional de periculosidade é devido em razão do simples fato de o veículo possuir tanque único, extra ou reserva, com capacidade superior a 200 (duzentos) litros, mesmo para consumo do próprio veículo. 2. Conforme quadro fático exposto pelo Regional, insuscetível de reexame, o reclamante laborava em caminhões com tanque de combustível com capacidade superior a 200l, fazendo jus, assim, ao adicional. Recurso de revista de que se conhece e a que se dá provimento. (0010221-78.2021.5.03.0040. Data do Julgamento: 10/04/2024) (g.n)

Assim sendo, mostra-se indiferente se o combustível é armazenado em tanques originais de fábrica, suplementares ou alterados para ampliar a capacidade do tanque original, pois o que submete o motorista à situação de periculosidade é a capacidade volumétrica total dos tanques, acima de 200 litros, nos termos do artigo 193,I, da CLT e do item 16.6 da NR 16.

Nesse sentido, há de ser reconhecida a presença dos agentes insalubres e periculosos no ambiente de trabalho do autor e, consequentemente, faz jus ao recebimento do adicional de insalubridade em grau máximo, ou seja, 40% (quarenta por cento) sobre o salário mínimo vigente à época dos fatos, durante todo o pacto laboral, e seus devidos reflexos em 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio, RSR e horas extras (OJ 47 da SDI-1 do TST), observada a hipótese da Súmula 293 do TST.

Faz jus ainda ao recebimento do adicional de periculosidade, ou seja, 30% (trinta por cento) sobre o salário base, durante todo o pacto laboral, e seus devidos reflexos em 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio, RSR e horas extras (OJ 47 da SDI-1 do TST), prevalecendo dentre os adicionais aquele mais benéfico financeiramente a parte autora.

EMISSÃO DE PPP: Constatado que o reclamante laborou em condições insalubres e/ou periculosas, é necessária a emissão do formulário do PPP (Perfil Profissiográfico Previdenciário) contendo todas as informações relativas ao empregado, como por exemplo, a atividade que exerce, o agente nocivo ao qual é exposto, a intensidade e a concentração do agente, exames médicos clínicos, além de dados referentes à empresa. O formulário deve ser preenchido pelas empresas que exercem atividades que exponham seus empregados a agentes nocivos químicos, físicos, biológicos ou associação de agentes prejudiciais à saúde ou à integridade física (origem da concessão de aposentadoria especial após 15, 20 ou 25 anos de contribuição).

Assim sendo, o reclamante requer a emissão do PPP de todo o período laborado na reclamada, sob pena de multa diária de R$ 100,00 (cem reais) revertido em favor da parte autora.', 
'Lembrar de conferir se o veículo conduzido pelo cliente tinha tanque de combustível com capacidade de mais de 200 litros para incluir esse tópico.', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(3, 'DAS DIFERENÇAS DO ADICIONAL DE INSALUBRIDADE - PAGAMENTO PROPORCIONAL - IMPOSSIBILIDADE', 
'Incontroverso nos autos que a parte reclamada reconhecia o ambiente de trabalho da reclamante como insalubre em grau [médio/máximo], consoante se observa dos contracheques anexos (rubrica [NÚMERO DA RUBRICA]). Porém, por diversos meses, a ex-empregadora efetuou o pagamento da parcela de forma proporcional às horas trabalhadas. Veja por amostragem:

[Inserir print contracheque]

Registre-se que, apesar da natureza condicional do adicional de insalubridade, o seu pagamento se dá de forma mensal e, sendo calculado com base no salário mínimo, possui valor fixo, independentemente da jornada de trabalho e dos dias de efetiva exposição ao agente insalubre.

Nesse sentido são os seguintes precedentes:

ADICIONAL DE INSALUBRIDADE. APURAÇÃO PROPORCIONAL. INDEVIDA. Não obstante a natureza condicional do adicional de insalubridade, o seu pagamento se dá de forma mensal e, sendo calculado com base no salário mínimo, tem valor fixo, independente da jornada de trabalho e dos dias de efetiva exposição ao agente insalubre. Recurso não provido. (TRT da 3.ª Região; PJe: 0010749-15.2019.5.03.0095 (APPS); Disponibilização: 12/05/2020; Órgão Julgador: Primeira Turma; Relator: Convocado Marcio Toledo Goncalves).

ADICIONAL DE INSALUBRIDADE - PAGAMENTO PROPORCIONAL AOS DIAS EFETIVAMENTE LABORADOS - IMPOSSIBILIDADE. É indevida a limitação do pagamento de adicional de insalubridade aos dias efetivamente laborados, uma vez que não há previsão legal que permita o pagamento da parcela de forma proporcional. (TRT da 3.ª Região; PJe: 0010824-68.2017.5.03.0016 (APPS); Disponibilização: 31/01/2020; Órgão Julgador: Terceira Turma; Relator: Emilia Facchini).

Dessa forma, verifica-se que existem diferenças em favor da autora, pelo que requer o pagamento das diferenças do adicional de insalubridade, pago mês a mês, e seus reflexos em 13º salários, férias + 1/3 constitucional, FGTS+40%, aviso prévio, RSR e horas extras.', 
'Inserir o grau de insalubridade reconhecido pela empresa e o número da rubrica no contracheque.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(3, 'DO ADICIONAL DE INSALUBRIDADE E EMISSÃO DO PPP - HIGIENIZAÇÃO DE BANHEIRO', 
'A Reclamante durante todo o contrato trabalhou exposta a agentes insalubres, pois, diariamente fazia a limpeza e higienização de banheiros, além do recolhimento de lixos dos sanitários. As reclamadas trata-se de [TIPO DE ESTABELECIMENTO - ex: ACADEMIA de ginástica], local com grande circulação de pessoas (em média mais de 500 alunos), o que por certo incide no caso o texto da Súm. 448, II do TST.

Importante salientar que após a saída da reclamante, a reclamada terceirizou os serviços que antes a autora fazia (Auxiliar de serviços gerais). Os funcionários da empresa ([NOME DA EMPRESA TERCEIRIZADA - CNPJ]) que passou a executar as tarefas da autora recebe adicional de insalubridade em grau máximo 40% sobre o salário mínimo.

Assim sendo, pleiteia realização de perícia técnica, a fim de que seja reconhecido o direito ao recebimento do adicional de insalubridade em grau máximo, ou seja, 40% (quarenta por cento) sobre o salário mínimo vigente à época dos fatos. Além disso, são devidos seus reflexos em 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR.

EMISSÃO DE PPP - Constatado que a Reclamante laborou em condições, é necessária a emissão de formulário do PPP (Perfil Profissiográfico Previdenciário), contendo todas as informações relativas ao empregado, como por exemplo, a atividade que exerce, o agente nocivo ao qual é exposto, a intensidade e a concentração do agente, exames médicos clínicos, além de dados referentes à empresa. O formulário deve ser preenchido pelas empresas que exercem atividades que exponham seus empregados a agentes nocivos químicos, físicos, biológicos ou associação de agentes prejudiciais à saúde ou à integridade física (origem da concessão de aposentadoria especial após 15, 20 ou 25 anos de contribuição).

Assim sendo, a Reclamante requer a emissão de PPP de todo o período laborado na reclamada, sob pena de multa diária de R$ 100,00 (cem reais) revertido em favor da parte autora.', 
'Preencher o tipo de estabelecimento e, se aplicável, os dados da empresa terceirizada que atualmente executa as mesmas funções com adicional de insalubridade.', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(3, 'DIFERENÇAS DO ADICIONAL NOTURNO - LABOR QUE NÃO ULTRAPASSAVA AS 05H', 
'A parte reclamante trabalhou, também, em jornada noturna, nos horários citados no item 01. Ocorre que a parte reclamada não adimpliu a totalidade do adicional noturno, além de que desconsiderou a redução ficta da hora noturna.

Nos termos do art. 7º, IX, da CR/88, são direitos dos trabalhadores, além de outros, remuneração do trabalho noturno superior à do diurno, sendo, assim, alçado à direito fundamental constitucional o art. 73, da CLT. Extrai-se da lei que o trabalho noturno - labor executado entre às 22 horas de um dia e as 5 horas do dia seguinte - terá remuneração superior à do diurno e, para esse efeito, sua remuneração terá um acréscimo de 20%, sobre a hora diurna, e as horas noturnas serão computadas como de 52 minutos e 30 segundos (redução ficta).

Desse modo, faz jus a reclamante: (I) ao adicional noturno legal, por hora trabalhada em período noturno, observada a redução ficta da hora noturna; e (II) a 07 minutos e 30 segundos extras, pela inobservância da redução ficta da hora noturna, acrescidas do adicional convencional/legal, ambos com reflexos nos 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.', 
'Usar quando o labor noturno NÃO ultrapassava as 05h da manhã.', 5);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(3, 'DIFERENÇAS DO ADICIONAL NOTURNO - LABOR QUE ULTRAPASSAVA AS 05H', 
'A parte reclamante trabalhou, também, em jornada noturna, nos horários citados no item 01. Ocorre que a parte reclamada não adimpliu a totalidade do adicional noturno, além de que o quitou somente sobre as horas laboradas até às 05h00 da manhã, desconsiderando as prorrogações para além desse horário.

Nos termos do art. 7º, IX, da CR/88, são direitos dos trabalhadores, além de outros, remuneração do trabalho noturno superior à do diurno, sendo, assim, alçado à direito fundamental constitucional o art. 73, da CLT. Extrai-se da lei que o trabalho noturno - labor executado entre às 22 horas de um dia e as 5 horas do dia seguinte - terá remuneração superior à do diurno e, para esse efeito, sua remuneração terá um acréscimo de 20% (adicional legal), sobre a hora diurna, e as horas noturnas serão computadas como de 52 minutos e 30 segundos (redução ficta).

Além disso, sabe-se que cumprida a jornada integralmente no horário noturno, sobre as horas trabalhadas na sequência, também é devido o adicional noturno, por representarem prorrogação do horário noturno. Inteligência do § 5º do art. 73 da CLT e da Súmula 60, II, do TST.

Desse modo, faz jus a reclamante: (I) ao adicional noturno legal, por hora trabalhada em período noturno e em prorrogação do labor noturno, após às 05h, observada a redução ficta da hora noturna; e (II) a 07 minutos e 30 segundos extras, pela inobservância da redução ficta da hora noturna por cada hora em prorrogação ao labor após as 05h, acrescidas do adicional convencional/legal, ambos com reflexos nos 13º salários, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR´s.', 
'Usar quando o labor noturno ULTRAPASSAVA as 05h da manhã.', 6);

-- ============================================
-- GRUPO 8: COMISSÕES E REMUNERAÇÃO
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'DO ACÚMULO DE FUNÇÃO', 
'O Reclamante foi contratado como [FUNÇÃO CONTRATUAL]. No entanto, além de ter que concluir todas as tarefas e obrigações inerentes a essa função, após um mês de contrato, a reclamada impôs-lhe a realização de atividades associadas às funções de [OUTRAS FUNÇÕES EXERCIDAS].

[Descrever detalhadamente as atividades extras que eram realizadas]

Pois bem. O acúmulo de função pressupõe um desequilíbrio quantitativo ou qualitativo das atribuições inicialmente pactuadas na contratação, o que se verifica suficientemente no caso em tela.

Note-se que, no presente caso, resta evidente que houve um desequilíbrio entre as prestações anteriormente ajustadas entre as partes no âmbito da relação de emprego, pois a empregadora passou a exigir do obreiro, atividades alheias ao pactuado.

Desse modo, evidenciando-se que o autor assumiu responsabilidades qualitativa e/ou quantitativamente diversas daquelas originalmente ajustadas, merece o deferimento do pagamento de contraprestação adicional, a fim de restabelecer o equilíbrio inerente ao caráter comutativo, sinalagmático e oneroso do contrato de trabalho.

Sendo assim, deve-se aplicar analogicamente os arts. 8º da Lei 3.207/57 e 13 da Lei 6.615/78, motivo pelo qual é devido o aditivo remuneratório de não menos que 30% do salário-base pelo acúmulo das atribuições estranhas ao exercício de sua função, com reflexos do aditivo salarial sobre 13° salários, férias + 1/3 constitucional, FGTS + 40% + aviso prévio e RSR''s.', 
'Preencher a função contratual, as outras funções exercidas e descrever detalhadamente as atividades extras.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'DA EQUIPARAÇÃO SALARIAL', 
'O Autor trabalhou juntamente com o sr. [NOME DO PARADIGMA], o qual fica, desde já, indicado como paradigma. Ele exercia as mesmas funções do reclamante ([FUNÇÃO]), com igual produtividade e a mesma perfeição técnica. No entanto, o paradigma percebia salário mensal superior ao do Autor, sendo a diferença em torno de R$ [VALOR]/mês a mais ao longo do contrato de trabalho. Tal circunstância configura discriminação, nos exatos termos do art. 461, caput, CLT.

Assim sendo, requer equiparação salarial entre Reclamante e paradigma mencionado, com a consequente condenação da empresa ao pagamento das diferenças salariais devidas, bem como os reflexos em 13º salário, férias + 1/3 constitucional, FGTS + 40%, aviso prévio e RSR. Obrigação de fazer - a reclamada deverá realizar retificação da CTPS para constar o real salário devido, além da entrega de novas guias rescisórias (TRCT, chave de conectividade e CD/SD), constando o novo salário, tudo sob pena de indenização substitutiva revertida em favor da parte autora.', 
'Preencher o nome do paradigma, a função e o valor da diferença salarial.', 2);

-- Continuando com mais tópicos importantes...

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'DA REVERSÃO DA JUSTA CAUSA', 
'A Reclamante foi dispensada por justa causa, em razão da [descrever o motivo alegado pela empresa]. No entanto, conforme será explicado a seguir, a penalidade foi aplicada com rigor excessivo.

Primeiramente, vale ressaltar que, ao contrário do que consta no aviso de dispensa da obreira, a Reclamante NÃO era reincidente na falta, sendo que ela NUNCA havia sido advertida por tal conduta. Ademais, outros funcionários, tal qual o sr. [NOME], já haviam praticado condutas idênticas e não foram SEQUER advertidos. É ver:

[Inserir provas, se houver]

Ademais, o fundamento no qual se embasou a dispensa por justa causa é totalmente desarrazoado, pois elenca as alíneas [ALÍNEAS CITADAS] do art. 482, CLT, como respaldos para a modalidade de extinção contratual.

Contudo, nenhuma das alíneas se amolda ao caso em tela. [Explicar por que as alíneas citadas não se aplicam ao caso]

Há de se ter em mente que a caracterização da justa causa, ensejadora do rompimento do vínculo empregatício, demanda a presença de certos requisitos, quais sejam: a tipicidade da conduta antijurídica do empregado, a autoria e a culpa, o nexo causal entre a falta e a punição, a imediaticidade da aplicação da pena, como também sua adequação, gradação e proporcionalidade, além da ausência de perdão tácito.

Frise-se que a ausência de um desses elementos já é o bastante para se afastar a legitimidade da incidência da referida punição.

No presente caso, é evidente que a dispensa ocorreu de forma abusiva, eis que aplicada com rigor excessivo.

Sendo assim, requer a reversão da justa causa e, consequentemente, o pagamento das seguintes verbas rescisórias:

- [X] dias de aviso prévio indenizado;
- X/12 de 13º salário de [ANO], já considerando a projeção do aviso prévio;
- X/12 de férias + 1/3 constitucional, já considerando a projeção do aviso prévio;
- Multa de 40% sobre saldo de FGTS;
- Retificação da CTPS obreira para constar a data de projeção do aviso prévio como extinção contratual;
- Emissão de novas guias rescisórias (TRCT, CD/SD e chave de conectividade), considerando a projeção do aviso prévio, tudo sob pena de indenização substitutiva revertida a parte autora.

MULTA DO ART. 477, § 8º DA CLT - "A reversão da justa causa em juízo enseja, por si só, a condenação ao pagamento da multa prevista no § 8º do art. 477 da CLT", consoante Súmula 36 do Eg. TRT-3". No mesmo sentido é a IRR nº 71 do TST, cujo teor se aplica no caso dos autos.

Sendo assim, requer seja a Ré condenada ao pagamento da multa em tela, destacando-se que, em caso de acolhimento do pedido do autor, que a multa prevista no art. 477, § 8º, da CLT, incida sobre a remuneração do obreiro, considerando todas as parcelas de natureza salarial percebidas pelo empregado, assim definido pelo art. 457, §1º, da CLT, que é o somatório de todas as parcelas pagas habitualmente ao empregado em contraprestação ao trabalho, compreendendo todas as verbas de natureza salarial, e não sobre o salário básico, o que desde já requer.', 
'Preencher o motivo alegado pela empresa, as alíneas citadas, calcular as verbas rescisórias devidas.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'DA RESCISÃO INDIRETA', 
'MMº Juiz, a parte autora pretende a Rescisão indireta do contrato de trabalho, na data de [DATA DO ÚLTIMO DIA LABORADO], tendo em vista os descumprimentos com diversas obrigações, a saber:

- [Listar os descumprimentos contratuais - ex: horas extras sem pagamento, descumprimento de CCT, atrasos salariais, ausência de recolhimentos de FGTS, etc.]

A parte autora manifesta que na data de [DATA] apresentou por escrito a reclamada sua intenção de apresentar em Juízo a rescisão indireta do contrato de trabalho, conforme documento anexo.

Ademais, manifestamos que no dia 24.02.2025 o TST consolidou sua jurisprudência em 21 temas sobre os quais não há divergência entre os órgãos julgadores do Tribunal. Dentre os temas está: Rescisão indireta por atraso no FGTS - "A irregularidade no recolhimento dos depósitos de FGTS revela descumprimento de obrigação contratual, nos termos do art. 483, "d", da CLT, de gravidade suficiente para configurar a rescisão indireta do contrato de trabalho, sendo desnecessária a imediatidade na reação do empregado ao descumprimento contratual."

O reclamante fez uso da prerrogativa prevista no art. 483, §3º da CLT, tendo prestado serviços até o dia [DATA]. Portanto, requer seja declarada a rescisão indireta do contrato de trabalho do autor, com a consequente condenação da reclamada ao pagamento das verbas rescisórias daí decorrentes, quais sejam:

- saldo de salário de [X] dias de [MÊS/ANO];
- aviso prévio indenizado de [X] dias;
- X/12 de 13º salário de [ANO];
- X/12 férias proporcionais + 1/3 constitucional;
- Recolhimento do FGTS faltante;
- Multa de 40% sobre FGTS;
- multa do art. 467/CLT;
- multa do art. 477, § 8ª da CLT (a incidir sobre a remuneração IRR nº 52 e 142 do TST);
- entrega de guias CD/SD, TRCT, chave de conectividade e baixa na CTPS em [DATA], considerando a projeção do aviso prévio.', 
'Listar todos os descumprimentos contratuais e calcular as verbas rescisórias devidas.', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(10, 'DA ESTABILIDADE GRAVÍDICA', 
'A Reclamante encontra-se grávida, possuindo, à época da dispensa, [X meses e X semanas] de gestação. Logo, ela é abrangida pela estabilidade alínea b do inciso II do art. 10 do Ato das Disposições Constitucionais Transitórias.

Porém, tendo em vista a dispensa ocorrida em [DATA DA DISPENSA], a Reclamante passou a ter o direito de optar entre a reintegração e a indenização substitutiva ao período de estabilidade gravídica. Contudo, a obreira não possui interesse em retornar ao emprego, o que não se confunde com a renúncia da estabilidade nos exatos termos da IRR nº 134 do TST, que assim descreve: A recusa da empregada gestante em retornar ao trabalho, mesmo diante de oferta de emprego pelo empregador, não configura renúncia à garantia prevista no art. 10, II, "b", do Ato das Disposições Constitucionais Transitórias (ADCT), subsistindo o direito à indenização substitutiva em relação ao período de estabilidade gestacional.

Dessa forma, pleiteia indenização substitutiva à estabilidade a que faz jus, pedido esse que encontra amparo na jurisprudência do TST.

Destaca-se que a data prevista para o parto é de [DATA PREVISTA DO PARTO]. Logo, pleiteia que a indenização se estenda até [DATA - 5 meses após o parto], data em que se completam os cinco meses subsequentes ao parto. Vale mencionar que, após essa data, é que serão contabilizados os 30 dias de aviso prévio, o que resulta no termo final do contrato de trabalho somente em [DATA FINAL DO CONTRATO].

Ademais, considerando-se a projeção ficta do contrato de trabalho até essa data, serão devidos o pagamento de salários vencidos e vincendos desde a data da dispensa até [DATA - 5 meses após o parto], e a repercussão da verba em 13º salário, férias + 1/3 constitucional e depósitos de FGTS +40% de todo o período. Como obrigação de fazer, a reclamada deverá fazer a entrega das guias TRCT, chave de conectividade e guias CD/SD.

Pugna, ainda, pela anotação, em CTPS, da projeção ficta do contrato até [DATA FINAL]. Tal data deve ser registrada, pois é a data-fim do contrato de trabalho e possui relevância, principalmente, para fins previdenciários.', 
'Calcular: data prevista do parto, data de 5 meses após o parto, data final considerando aviso prévio.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(11, 'DOS HONORÁRIOS ADVOCATÍCIOS', 
'Requer seja a reclamada condenada ao pagamento dos honorários advocatícios, a serem apurados em liquidação de sentença e devidos aos procuradores do autor. Veja-se que a Lei nº 13.467/2017 acrescentou ao artigo 791-A a Consolidação das Leis do Trabalho autorizando a condenação em sucumbência a parte perdedora da ação, ou em relação a parte da ação em que foi vencida, o que fica requerido, pugnando pelo arbitramento em 15% do valor da condenação.', 
NULL, 1);

-- ============================================
-- GRUPO 4: RECONHECIMENTO DE VÍNCULO
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(4, 'RECONHECIMENTO DE VÍNCULO EMPREGATÍCIO - EMPREGADA DOMÉSTICA', 
'A Reclamante embora tenha sido contratada para laborar como empregada, não teve sua CTPS assinada pela Reclamada. No entanto, atentando-se ao quadro fático, observa-se que os requisitos do artigo 1º da LC nº 150/2015 se encontram presentes na relação havida entre as partes.

Para caracterização do vínculo de empregada doméstica, é indispensável que a prestação de serviços se dê em mais de dois dias por semana, com a presença concomitante dos demais requisitos previstos no artigo 1º da Lei Complementar 150/2015:

NÃO EVENTUALIDADE: A Reclamante trabalhava de segunda a [sexta-feira], em horário determinado pela Reclamada, o que demonstra que a prestação de serviços não era esporádica, mas, sim, habitual.

SUBORDINAÇÃO JURÍDICA: A Reclamante recebia ordens da Reclamada, sujeitando-se integralmente ao comando empresarial;

ONEROSIDADE: A Reclamada ajustou com a obreira o pagamento de [valor] por mês a título de remuneração.

PESSOALIDADE: A Reclamante não podia se fazer substituir por outra pessoa na execução dos serviços.

FINALIDADE NÃO LUCRATIVA: Os serviços da obreira eram destinados unicamente aos cuidados domésticos, não tendo, portanto, nenhuma finalidade lucrativa.

Pois bem, ante o preenchimento de todos os requisitos, requer a declaração da existência de vínculo empregatício entre as partes, durante toda a contratualidade.

O registro do contrato de trabalho deverá ser realizado pela reclamada na CTPS da obreira, fazendo constar a data de admissão: [DATA ADMISSÃO] e dispensa: [DATA DISPENSA] já computada a projeção do aviso prévio indenizado de 30 dias (OJ 82 da SDI-1 do TST); função exercida: Empregada Doméstica, com salário admissional de R$ [VALOR]/mês, nos termos do art. 29 da CLT, sob pena de multa diária de R$ 100,00 (cem reais), a ser revertida em favor da Reclamante.

Assim, é devido à parte autora o pagamento das seguintes verbas rescisórias:
- 30 dias de aviso prévio indenizado;
- X/12 de 13º salário de [ANO];
- X/12 de 13º salário proporcional de [ANO], já considerando a projeção do aviso prévio indenizado;
- 12/12 de férias + 1/3 constitucional, relativo ao período aquisitivo;
- Depósitos não realizados de FGTS;
- Multa de 40% sobre o saldo total de FGTS;
- Entrega dos documentos rescisórios – TRCT e Chave Conectividade, sob pena de multa diária de R$ 100,00 (cem reais);
- Multa do art. 467/CLT e art. 477, § 8ª da CLT (calculado sobre a remuneração – IRR n° 142 e 168 do TST).', 
'Preencher datas de admissão/dispensa, salário e calcular verbas rescisórias. Aplicável apenas a trabalho doméstico em âmbito residencial.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(4, 'RECONHECIMENTO DE VÍNCULO EMPREGATÍCIO', 
'A Reclamante embora tenha sido contratada para laborar como empregada, não teve sua CTPS assinada pela Reclamada. No entanto, atentando-se ao quadro fático, observa-se que os requisitos do artigo 3º da CLT se encontram presentes na relação havida entre as partes.

É importante destacar que de acordo com a norma insculpida nos arts. 2º e 3º da CLT, configura-se a relação de emprego quando comprovada a prestação de serviços por pessoa física, de forma não eventual, mediante pessoalidade, onerosidade e subordinação jurídica.

Todavia, predomina no Direito do Trabalho o princípio da irrenunciabilidade, mediante o qual não é permitido às partes renunciar aos direitos trabalhistas inerentes à relação de emprego existente.

No caso em tela, verifica-se que a relação de trabalho se desenvolveu sob o prisma do artigo 3º da Consolidação das Leis do Trabalho - o notório regime de emprego. Veja-se:

NÃO EVENTUALIDADE: A Reclamante trabalhava de segunda a [sexta-feira/sábado], em horário determinado pela Reclamada, o que demonstra que a prestação de serviços não era esporádica, mas, sim, habitual.

SUBORDINAÇÃO JURÍDICA: A Reclamante recebia ordens do proprietário da Reclamada, a quem se reportava como chefe, acolhendo e executando os serviços delegados, sujeitando-se integralmente ao corpo produtivo, bem como ao comando empresarial;

ONEROSIDADE: A Reclamada ajustou com a obreira o pagamento de [valor] por mês a título de remuneração. Restou estabelecido o pagamento independentemente de ter ou não serviço no mês, revelando que o autor não suportava os riscos do empreendimento;

PESSOALIDADE: A Reclamante não podia se fazer substituir por outra pessoa na execução dos serviços, tendo cumprido jornada diária durante todo o pacto laboral.

Portanto, resta patente a existência de fraude aos dispositivos e normas de proteção ao trabalho, porquanto demonstrado que os serviços da reclamante eram prestados em favor reclamada, inserido em sua atividade-fim, de forma subordinada, situação que caracteriza a fraude à legislação trabalhista vedada pelo artigo 9º da CLT.

Sendo assim, a parte autora requer que seja reconhecido o vínculo empregatício havido com a empresa-ré, durante toda a contratualidade.

O registro do contrato de trabalho deverá ser realizado na CTPS da obreira pela Reclamada, fazendo constar a data de admissão: [DATA], dispensa: [DATA COM PROJEÇÃO DO AVISO], função exercida: [FUNÇÃO], salário de R$ [VALOR]/mês, nos termos do art. 29 da CLT, sob pena de multa diária de R$ 100,00 (cem reais).

Dessa forma, devem ser pagas as seguintes verbas rescisórias:
- saldo de salário de [X] dias de [MÊS/ANO];
- 30 dias de aviso prévio indenizado;
- X/12 de 13º salário de [ANO];
- X/12 de 13º salário de [ANO], já contabilizando a projeção do aviso prévio;
- X/12 de férias proporcionais + 1/3 constitucional, já contabilizando a projeção do aviso prévio;
- Depósitos de FGTS de todo o período contratual + multa de 40% sobre os mesmos;
- Obrigação de fazer - Entrega das guias TRCT e chave de conectividade, sob pena de indenização substitutiva;
- Multa do art. 467/CLT e art. 477, § 8ª da CLT (calculado sobre a remuneração – IRR nº 142 e 168 do TST).', 
'Preencher datas, função, salário e calcular verbas rescisórias.', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(4, 'RECONHECIMENTO DE VÍNCULO EMPREGATÍCIO NO PERÍODO DE ESTÁGIO', 
'Conforme narrado, a Reclamante foi contratada em [DATA] como estagiária em [ÁREA], ou seja, sem que fosse paga nenhuma remuneração (ou com bolsa-auxílio). Porém, a relação de estágio foi desvirtuada, pois, ao exercer a jornada [12x36/superior a 6h], o limite de 06h/dia foi extrapolado.

Tal circunstância, por si só, já desconstitui a relação de estágio e, por consequência, forma relação de emprego, pois, nesse tipo de contrato, já existem todos os demais requisitos impostos pelo art. 3º, CLT, para configuração de vínculo empregatício: subordinação, habitualidade, onerosidade e pessoalidade. Vejamos o que dispõe a jurisprudência:

NULIDADE DE CONTRATO DE ESTÁGIO. JORNADA SUPERIOR A 30 HORAS SEMANAIS. RECONHECIMENTO DE VÍNCULO EMPREGATÍCIO. Dessumindo-se dos autos o descumprimento do termo de compromisso de estágio no tocante ao horário para desenvolvimento das atividades, bem como a prestação de labor superior a 6 horas diárias e 30 horas semanais, fica configurada a afronta à previsão legal contida no artigo 10 da Lei 11.788. Tal circunstância, por si só, desvirtua o contrato de estágio e atrai a incidência do § 2º, do artigo 3º e do artigo 15 da Lei 11.788/2008.

Dessa forma, requer seja reconhecido o vínculo empregatício no período de estágio, com a anotação na CTPS da data de admissão: [DATA INÍCIO ESTÁGIO], dispensa: [DATA FIM], função exercida: [FUNÇÃO], salário de R$ [VALOR]/mês (salário mínimo vigente à época ou piso da categoria), nos termos do art. 29 da CLT, sob pena de multa diária.

Consequentemente, são devidas as verbas rescisórias próprias de dispensa sem justa causa.', 
'Verificar se houve extrapolação da jornada de 6h diárias ou 30h semanais para caracterizar desvirtuamento do estágio.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(4, 'UNICIDADE CONTRATUAL - TRANSFERÊNCIA DENTRO DO GRUPO ECONÔMICO', 
'A parte autora, após ter sido contratada pela 1ª Ré, foi transferida para a 2ª reclamada em [DATA], sem realização de acerto rescisório.

Porém, não tendo ocorrido nenhum período de inatividade, e sendo as empresas do mesmo grupo econômico, pode-se afirmar que houve unicidade contratual.

Nesse viés, em conformidade com a Súmula 129 do TST, é certa a existência de um único contrato, sendo as empresas do grupo econômico consideradas uma mesma empregadora.

É o que dispõe a jurisprudência:

GRUPO ECONÔMICO. EMPREGADOR ÚNICO. SOLIDARIEDADE PASSIVA. A transferência do empregado para trabalhar em outra empresa do mesmo grupo econômico, mantendo os benefícios do contrato de emprego, não forma novo liame contratual, sendo a típica hipótese de empregador único (Súmula 129 do TST), em que o labor do obreiro se reverte em benefício do grupo econômico, cujos integrantes respondem solidariamente, nos termos do art. 2º, § 2º da CLT.

Dessa forma, requer o reconhecimento da unicidade contratual para que seja considerado que o contrato firmado entre as partes seja aquele iniciado em [DATA INÍCIO] e findado em [DATA FIM], a fim de que os demais pedidos formulados nesta exordial partam dessa premissa. Consequentemente, faz jus retificação da CTPS obreira para constar contrato único, bem como a emissão de novas guias rescisórias (TRCT, CD/SD e chave de conectividade), tudo sob pena de indenização substitutiva revertida a parte autora.', 
'Verificar se há comprovação de grupo econômico entre as empresas (sócios em comum, mesma atividade, atuação conjunta).', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(4, 'UNICIDADE CONTRATUAL - RECONTRATAÇÃO EM MENOS DE 6 MESES', 
'Conforme narrado, o Reclamante fora contratado em [DATA PRIMEIRA ADMISSÃO], tendo sido dispensado, sem justa causa, em [DATA PRIMEIRA DISPENSA]. Ocorre que pouco mais de [X] meses depois (em [DATA RECONTRATAÇÃO]), o obreiro foi recontratado, para exercer a mesma função.

De acordo com o art. 452 da CLT, a recontratação do empregado em prazo inferior a seis meses enseja declaração de unicidade contratual entre os períodos, conforme ratificado pela jurisprudência:

UNICIDADE CONTRATUAL. Evidencia-se fraude trabalhista perpetrada com o escopo de reduzir os salários e sonegar direitos do empregado, na hipótese de dispensa e readmissão em tempo inferior a seis meses. (TRT-3 - ROT: 00102438120215030026)

UNICIDADE DA RELAÇÃO LABORAL. O art. 452 da CLT estabelece que o prazo inferior a seis meses entre uma admissão e outra, envolvendo as mesmas partes, representa unicidade laboral. (TRT-4 - ROT: 0020952-67.2019.5.04.0205)

Assim, requer declaração de unicidade entre os contratos narrados, a fim de que a data de admissão, para fins de cálculo de reajuste salarial e outros direitos, seja considerada como [DATA PRIMEIRA ADMISSÃO]. Consequentemente, faz jus retificação da CTPS obreira para constar contrato único, bem como a emissão de novas guias rescisórias (TRCT, CD/SD e chave de conectividade), tudo sob pena de indenização substitutiva revertida a parte autora.', 
'Calcular se o intervalo entre dispensa e recontratação foi inferior a 6 meses.', 5);

-- ============================================
-- GRUPO 5: DOENÇA OCUPACIONAL E ACIDENTE DE TRABALHO
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(5, 'DOENÇA OCUPACIONAL - BURNOUT', 
'O Reclamante em razão de seu trabalho, desenvolveu Burnout, bem como transtorno ansioso/depressivo, conforme demonstram laudos médicos anexos. Durante a prestação de serviços, estava sujeito a sobrecarga de trabalho, cobranças excessivas e ameaças constantes de dispensa dos supervisores.

Em razão do Burnout, começou a apresentar sintomas como insônia, nervosismo, ansiedade, angústia, pessimismo, desmotivação, dentre outros. Dessa forma, tornou-se incapaz para permanecer laborando e viu-se obrigado a solicitar afastamento ao INSS para cuidar da saúde.

É evidente o nexo causal entre labor, em favor da Reclamada, e o desenvolvimento dos mencionados transtornos psicológicos. O extenso número de horas extras, sobrecarga de trabalho, cobranças excessivas e ameaças de dispensa, levaram o Reclamante ao esgotamento mental; ou seja, evidente a culpa da empregadora.

O meio ambiente equilibrado está intimamente ligado à saúde do trabalhador, devendo o empregador priorizá-lo no quadro de ações da empresa, na forma do art. 5, XXII e XXIII, art. 170, II e III, da CF.

Diante dos fatos acima narrados, é inequívoco que a doença do Autor se trata de doença OCUPACIONAL. Vale ressaltar que, com base na Lei 8.213/91, entende-se que a caracterização da doença profissional prescinde de que as atividades tenham agido como causa única da patologia. Basta, para tanto, que o trabalho tenha contribuído para a sua deflagração ou potencialização.

Assim, requer a condenação da reclamada em danos morais pela doença ocupacional desenvolvida, cujo valor ficará a cargo de Vossa Excelência. O que pedimos vênia, é que o arbitramento seja de, no mínimo, R$ 45.000,00.

DANOS MATERIAIS – MMº Juiz, caso reste comprovado nos autos, através de laudo médico pericial, a incapacidade do reclamante, faz jus o obreiro aos danos materiais, consistente em pensão mensal, em 100% da remuneração paga ao autor, a partir do 16º dia de afastamento até o dia em que a autora receber a alta previdenciária.

DOS RECOLHIMENTOS DO FGTS DO PERÍODO DE AFASTAMENTO – O afastamento por motivo de doença ocupacional não afasta a obrigação legal do empregador de recolhimento dos depósitos do FGTS, a teor do que dispõe o art. 15, § 5º, da Lei nº 8.036/90.

ESTABILIDADE PROVISÓRIA - Reconhecida a doença ocupacional que se equipara a acidente de trabalho, faz jus o autor à estabilidade acidentária, estabelecida pelo art. 118 da Lei nº 8.213/91 e Súmula 378 do TST, pelo prazo de 12 meses.', 
'Anexar laudos médicos. Verificar período de afastamento pelo INSS. Calcular danos materiais e período de estabilidade.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(5, 'DOENÇA OCUPACIONAL - INDENIZAÇÃO SUBSTITUTIVA À ESTABILIDADE', 
'O Reclamante, a partir de [DATA] começou a padecer com [DESCREVER SINTOMAS/PROBLEMAS DE SAÚDE]. O exame médico anexo demonstra [alterações constatadas].

Em razão [das lesões/da doença], o Autor tornou-se incapaz temporariamente para o labor, tendo recebido auxílio-doença por doença ocupacional (equiparado, para fins previdenciários, a acidente de trabalho).

Vale ressaltar que o próprio INSS enquadrou a incapacidade como advinda de doença ocupacional, tanto que classificou o auxílio doença como "acidente de trabalho".

Com base na Lei 8.213/91, entende-se que a caracterização da doença profissional prescinde de que as atividades tenham agido como causa única da patologia. Basta, para tanto, que o trabalho tenha contribuído para a sua deflagração ou potencialização.

O item II da Súmula 378 do TST fixa o seguinte entendimento: "São pressupostos para a concessão da estabilidade o afastamento superior a 15 dias e a consequente percepção do auxílio-doença acidentário, salvo se constatada, após a despedida, doença profissional que guarde relação de causalidade com a execução do contrato de emprego".

Dessa forma, ante o atendimento de todos os requisitos, o Autor faria jus à estabilidade provisória de 12 meses, a contar da retomada de sua capacidade laboral, que ocorreu em [DATA]. Todavia, [poucos dias após retornar ao trabalho, foi dispensado sem justa causa / já foi dispensado antes da alta].

Portanto, em razão do transcurso temporal dos 12 meses de estabilidade provisória a que faria jus, pleiteia pagamento de indenização substitutiva, a qual, por possuir natureza salarial, gerará reflexos em 13° salários, férias proporcionais + 1/3 constitucional, FGTS + 40%, aviso prévio e RSRs.

Além disso, pleiteia retificação da CTPS, a fim de que conste, como data-fim do contrato, o dia [DATA FIM ESTABILIDADE] (data em que se finda a estabilidade provisória).', 
'Usar quando o empregado já foi dispensado e não poderá ser reintegrado. Calcular data fim da estabilidade (12 meses após alta).', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(5, 'ACIDENTE DE TRABALHO - DANOS MORAIS E ESTÉTICOS (PERFURO CORTANTE)', 
'No dia [DATA], a Reclamante sofreu acidente de trabalho, conforme demonstra CAT anexa: [DESCREVER O ACIDENTE - ex: ao preparar um corpo para ser velado, cortou seu dedo com bisturi, o que lhe gerou perfuração profunda].

Em razão do corte, a Reclamante passou a sentir fortes dores, e até os dias atuais, está com a mobilidade de seu dedo REDUZIDA, não conseguindo flexioná-lo. A sequela, que tem grandes chances de ser DEFINITIVA, pode ser constatada por meio dos laudos e resultados de exames anexos.

O nexo causal entre o acidente de trabalho sofrido e a negligência da Reclamada é evidente, pois:
- A empresa não fornecia todos os EPIs necessários, como luva adequada para manuseio de [material];
- A empregadora determinava que a Autora realizasse quantidade muito extensa de horas extras, inclusive com alternância de turnos, o que certamente prejudicava sua capacidade de concentração;
- A Ré não fornecia os treinamentos apropriados para manejo de tais itens.

Destacamos, ainda, outros elementos culposos atribuíveis à reclamada:
I. A empresa não cumpria todas as normas de segurança e prevenção indicadas na legislação, especialmente as NRs da Portaria nº 3.214/78 do MTE.
II. A empresa não manteve em dia os programas preventivos da saúde do trabalhador (PCMSO, PPP, PPRA, PCMAT etc.).
III. Não havia programa de orientação e prevenção a acidentes do trabalho.
IV. A autora não foi orientada e treinada sobre os riscos ergonômicos relativos às suas funções.

Conclui-se, assim, pelo nexo causal, ressaltando a ausência de treinamento quanto ao uso e riscos dos equipamentos. Evidencia-se a presença irrefutável da culpa da reclamada, consistente no ato omissivo de descumprir obrigação legal de observar as normas de segurança e medicina no trabalho (artigo 157/CLT).

Requer a condenação da reclamada em danos morais, cujo valor pedimos vênia para o arbitramento em R$ 15.000,00.

REPARAÇÃO POR DANOS ESTÉTICOS - Os danos morais e estéticos são independentes, podendo ocorrer simultaneamente. No que toca aos danos estéticos, tem-se que houve a quebra da harmonia corporal representadas pela presença de cicatrizes. Requer fixação de indenização por danos estéticos no valor de R$ 15.000,00.', 
'Anexar CAT, fotos, laudos médicos. Adaptar descrição do acidente ao caso concreto.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(5, 'ACIDENTE DE TRABALHO - AUSÊNCIA DE CAT (ACIDENTE DE TRAJETO)', 
'O obreiro em razão de sua atividade laboral, veio a sofrer acidente de trabalho, conforme demonstram notícia e boletim de ocorrência anexados. O acidente de trabalho ocorreu em [DATA], às [HORA], no momento em que o autor estava [dirigindo para / retornando de] [LOCAL].

[DESCREVER O ACIDENTE]

Em razão do ocorrido, o Reclamante ficou afastado por [X] dias, não tendo, nesse período, recebido nenhum amparo da empregadora.

Destaque-se que, embora o reclamante tenha sofrido acidente de trabalho, a reclamada NÃO procedeu com a devida emissão da Comunicação de Acidente de Trabalho (CAT) à época, não tendo, ainda, prestado nenhuma assistência ao obreiro.

Ocorrendo o acidente de trabalho, independentemente de afastamento ou não, é obrigatória a emissão da CAT por parte do empregador, sob pena de multa pelo Ministério do Trabalho, nos termos do art. 336 do Decreto 3.048/99.

Vale ressaltar que, como o Autor [atendia clientes em diversas cidades / utilizava veículo fornecido pela empresa], a viagem era considerada parte da jornada, motivo pelo qual se trata de acidente de trabalho típico.

Contudo, tendo em vista o fornecimento de veículo pela empresa, esta se equipara ao "transportador", assumindo responsabilidade objetiva para os danos gerados ao empregado em caso de acidente de trabalho, nos termos dos artigos 734, 735 e 927 do Código Civil.

Assim, considerando os parâmetros acima transcritos, a condição econômica da Reclamada, a hipossuficiência do Reclamante, levando em consideração a falta de assistência da empresa, requer a condenação da reclamada em danos morais, ocasionado pelo acidente de trabalho, o que pedimos vênia para o arbitramento em R$ 20.000,00.', 
'Aplicável quando empresa fornecia veículo. Anexar BO, notícias, laudos médicos. Verificar se CAT foi emitida.', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(5, 'ACIDENTE DE TRABALHO - DANOS MORAIS (ACIDENTE NAS DEPENDÊNCIAS)', 
'No dia [DATA], o Autor sofreu acidente de trabalho: [DESCREVER O ACIDENTE - ex: ao grampear ripas de madeira para montar caixas, grampeou a mão esquerda, o que resultou em ferimento profundo que necessitou de pontos].

Em razão do ocorrido, necessitou de [X] dias de afastamento, tendo permanecido com dores e sensibilidade pelo período subsequente.

A Reclamada, inclusive, emitiu CAT em virtude do ocorrido (que se encontra anexa), o que comprova o reconhecimento, por parte da empregadora, da ocorrência do mencionado acidente de trabalho.

A culpa da reclamada é evidente, pois o Reclamante não utilizava nenhum equipamento de segurança no momento no acidente. Além disso, a ausência de concessão, por parte da empresa, de treinamentos eficazes e periódicos, contribuiu para a ocorrência do infortúnio.

Destacamos, ainda, outros elementos culposos atribuíveis a reclamada:
I. A empresa não cumpria todas as normas de segurança e prevenção indicadas na legislação.
II. A empresa não manteve em dia os programas preventivos da saúde do trabalhador.
III. Não havia programa de orientação e prevenção de acidentes do trabalho.
IV. A autora não foi orientada e treinada sobre os riscos relativos às suas funções.

Conclui-se, assim, pelo nexo causal, ressaltando a ausência de treinamento quanto ao uso e riscos dos equipamentos. Evidencia-se a presença irrefutável da culpa da reclamada (artigo 157/CLT).

Contudo, não se deve perder de vista que a responsabilidade civil da empregadora, em caso de acidentes de trabalho típicos, é objetiva, ou seja, prescinde de culpa.

Requer a condenação da reclamada em danos morais, cujo valor pedimos vênia para o arbitramento em R$ 15.000,00.', 
'Anexar CAT, laudos, fotos. Adaptar descrição do acidente.', 5);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(5, 'DIFERENÇAS DO BENEFÍCIO PREVIDENCIÁRIO', 
'Conforme narrado, o Reclamante necessitou de percepção de auxílio-doença, o qual se iniciou em [DATA] e [persiste até o momento atual / cessou em DATA].

O afastamento, que se deu pela doença ocupacional narrada anteriormente, lhe gera benefício previdenciário no valor atual de R$ [VALOR]/mês.

Ocorre que, como descrito ao longo desta petição inicial, a Reclamada deixou de pagar a integralidade das verbas salariais devidas ao longo do contrato, como [diferenças no adicional noturno, adicional de insalubridade, horas extras, diferenças salariais em razão do piso da categoria, etc.].

Portanto, o cálculo do auxílio doença foi prejudicado, pois os salários de contribuição deveriam estar majorados. Dessa forma, pleiteia que a Reclamada seja condenada ao pagamento de indenização substitutiva ao valor das diferenças do mencionado benefício previdenciário.', 
'Anexar CNIS, laudo perícia médica, extrato de pagamento do benefício previdenciário. Especificar verbas não pagas que afetaram o cálculo.', 6);

-- ============================================
-- GRUPO 6: VERBAS RESCISÓRIAS (continuação)
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'INDENIZAÇÃO PELO LABOR EM AVISO PRÉVIO EXCEDENTE A 30 DIAS', 
'Conforme se vê no TRCT, a dispensa foi comunicada ao Reclamante em [DATA], tendo o obreiro, por determinação da empresa, cumprido aviso prévio trabalhado até [DATA], ou seja, por [X] dias.

Ocorre que, embora o cálculo da proporcionalidade do aviso prévio esteja correto, em razão dos [X anos e X meses] completos de contrato de trabalho, o aviso prévio trabalhado não pode exceder 30 dias, sendo o restante dos dias indenizados ao empregado. Caso tal limite seja desrespeitado, os dias que ultrapassam os trinta devem ser "novamente" indenizados, pois ocorre uma recontagem ficta do aviso.

É o que dispõe a jurisprudência pacífica do TST:

AVISO PRÉVIO PROPORCIONAL TRABALHADO. PERÍODO SUPERIOR A 30 DIAS. RESILIÇÃO PELO EMPREGADOR. DIREITO EXCLUSIVO DO EMPREGADO. O aviso prévio proporcional previsto na Lei nº 12.506/2011 aplica-se exclusivamente aos empregados. Assim, na hipótese de resilição unilateral de iniciativa patronal, somente poderá ser exigido do empregado o cumprimento de 30 dias de labor. Logo, o período do aviso prévio que o reclamante trabalhou excedente a 30 dias deve ser indenizado.

Assim, tendo em vista o período laborado, requer a indenização pelos [X] dias excedentes ao 30º dia de aviso prévio.', 
'Calcular quantos dias excederam os 30 de aviso prévio trabalhado. Verificar tempo de contrato para confirmar proporcionalidade.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'PAGAMENTO PARCIAL DAS VERBAS RESCISÓRIAS - PERICULOSIDADE PROPORCIONAL', 
'Apesar da dispensa promovida pela Ré na data acima informada, verifica-se no TRCT do obreiro que a ex-empregadora não efetuou o pagamento de "Saldo de salário" e do "adicional de periculosidade" de forma correta. Vejamos:

- Saldo de salário: O valor referente a [X] dias de labor deveria ser de R$ [VALOR CORRETO], e foi quitado apenas R$ [VALOR PAGO];
- Adicional de periculosidade: O devido seriam R$ [VALOR CORRETO], e foi pago somente R$ [VALOR PAGO].

Vale mencionar que o adicional de periculosidade NÃO é fracionável, ou seja, basta a exposição ao agente periculoso por um único dia, para que ele seja devido em sua integralidade, de 30% sobre o salário base do empregado. Assim, em outras palavras, não se admite seu pagamento proporcional à "quantidade" de exposição. É o que dispõe a Súmula 361 do TST:

SÚMULA Nº 361 - ADICIONAL DE PERICULOSIDADE. ELETRICITÁRIOS. EXPOSIÇÃO INTERMITENTE - O trabalho exercido em condições perigosas, embora de forma intermitente, dá direito ao empregado a receber o adicional de periculosidade de forma integral.

Dessa forma, surgem diferenças devidas, a título de saldo de salário e de adicional de periculosidade, as quais foram inadimplidas, motivo pelo qual pleiteia-se o pagamento das verbas.', 
'Anexar TRCT. Calcular diferenças devidas. O adicional de periculosidade não pode ser pago proporcionalmente aos dias trabalhados.', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'CONTRATO POR TEMPO INDETERMINADO - ULTRAPASSOU DATA-FIM', 
'Conforme se extrai da CTPS do Reclamante, o contrato havia sido pactuado, inicialmente, como tempo determinado, com previsão de término para [DATA].

Porém, tendo em vista a continuidade da relação empregatícia após essa data, tem-se a alteração tácita para contrato por tempo indeterminado, conforme dispõe a jurisprudência:

CONTRATO DE EXPERIÊNCIA. CONVERSÃO EM CONTRATO POR TEMPO INDETERMINADO. O contrato de experiência é uma espécie de contrato individual de trabalho por prazo determinado e sua prorrogação pode ocorrer de modo tácito ou expresso uma única vez e desde que respeitado o limite de 90 dias. (artigos 445, parágrafo único, e 451 da CLT c/c Súmula nº 188 do TST). Assim, persistindo o labor após o término do prazo da prorrogação, o contrato passa a ser regido pelas normas pertinentes aos contratos com prazo indeterminado.

Assim, requer a declaração, por Vossa Excelência, de que o contrato firmado entre as partes foi pactuado por tempo indeterminado, com pagamento das verbas rescisórias correspondentes e obrigação de fazer (entrega de guias TRCT, chave de conectividade e guias CD/SD, baixa na CTPS).', 
'Verificar na CTPS a data-fim do contrato por prazo determinado e se houve continuidade do labor após essa data.', 5);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'CONVERSÃO DO PEDIDO DE DEMISSÃO PARA RESCISÃO INDIRETA', 
'Conforme narrado, o Reclamante pediu demissão em [DATA]. Porém, a Reclamada descumpria diversas obrigações contratuais, quais sejam:

- Depósitos de FGTS e recolhimentos de INSS;
- Ausência de fornecimento de EPIs eficazes e pagamento de adicional de insalubridade;
- Ausência de pagamento de horas extras;
- Ausência de pagamento dos benefícios da CCT;
[Listar outros descumprimentos]

Dessa forma, as faltas graves do empregador deixam nítido que as condições de trabalho tornaram a continuação da execução do contrato laboral impraticável, ensejando a rescisão contratual pelo empregado, nos termos do artigo 483, alínea d, da CLT.

Em outras palavras, em razão dos descumprimentos obrigacionais da Reclamada, o obreiro não viu outra alternativa senão pedir o desligamento da empresa. Assim, pode-se concluir por um vício em sua manifestação de vontade no momento do pedido de demissão.

Vale ressaltar que o inadimplemento de FGTS, por si só, já configura ilícito capaz de ensejar a rescisão indireta, como vastamente corroborado pela jurisprudência e consolidado pelo TST em 24.02.2025: "A irregularidade no recolhimento dos depósitos de FGTS revela descumprimento de obrigação contratual, nos termos do art. 483, "d", da CLT, de gravidade suficiente para configurar a rescisão indireta do contrato de trabalho."

Portanto, requer a conversão do pedido de demissão para rescisão indireta do contrato de trabalho, devendo ocorrer o pagamento das seguintes verbas rescisórias complementares:
- 30 dias de aviso prévio indenizado;
- 1/12 de 13º salário proporcional, relativo à projeção dos 30 dias de aviso prévio;
- 1/12 de férias proporcionais + 1/3 constitucional, relativo à projeção dos 30 dias de aviso prévio;
- Depósito de FGTS relativo à competência faltante;
- Multa de 40% sobre o FGTS pleiteado;
- Multa do art. 477, § 8ª da CLT (calculado sobre a remuneração – IRR nº 52 e 142 do TST).', 
'Listar todos os descumprimentos contratuais que justificam a conversão. Verificar se há FGTS em atraso.', 6);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'PAGAMENTO PARCIAL DAS VERBAS RESCISÓRIAS - BASE DE CÁLCULO AVISO PRÉVIO', 
'Apesar da dispensa promovida pela Ré na data acima informada, verifica-se no TRCT do obreiro que a ex-empregadora não efetuou o pagamento de "aviso prévio" de forma correta.

Aviso prévio indenizado: o aviso prévio indenizado deve ser calculado sobre a totalidade da REMUNERAÇÃO do obreiro, e não somente pelo salário base. Assim, a empregadora deve considerar também o adicional de [periculosidade/insalubridade/noturno] pago ao longo do contrato, e deveria ter adimplido o aviso no valor de R$ [VALOR CORRETO] (salário base de R$ [VALOR] + [X]% de adicional).

É o que dispõe a jurisprudência:

AVISO PRÉVIO. BASE DE CÁLCULO. A recomposição pecuniária mediante indenização do aviso prévio deve incluir todas as verbas tidas como de natureza salarial, e não apenas o salário base, haja vista que o trabalhador tem de perceber a mesma contraprestação que receberia caso trabalhasse no período.

Dessa forma, surgem diferenças devidas, as quais foram inadimplidas, motivo pelo qual pleiteia-se o pagamento das verbas.', 
'Anexar TRCT. Verificar se aviso prévio foi calculado apenas sobre salário base, sem considerar adicionais habituais.', 7);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(6, 'MORA SALARIAL - INDENIZAÇÃO POR DANOS MORAIS', 
'A Reclamada, de maneira frequente, efetuava o pagamento do salário mensal após o quinto dia útil, quitando-o, geralmente, entre os dias [X] e [X] de cada mês. Porém, havia meses em que o pagamento acontecia somente no dia [X], circunstância que constitui completo desrespeito ao trabalhador e ao caráter alimentar do salário.

Tal situação é passível de indenização por danos morais, por ofender a honra do empregado e inseri-lo em dificuldades financeiras. É o que dispõe a jurisprudência:

DANO EXTRAPATRIMONIAL. ATRASO REITERADO NO PAGAMENTO DE SALÁRIOS. ATO ILÍCITO. DANO À PERSONALIDADE. "DAMNUM IN RE IPSA". A jurisprudência pacífica desta Corte Superior é firme no sentido de que o reiterado atraso no pagamento dos salários gera dano moral in re ipsa ao empregado. (TST - RR: 01008657720175010003)

Portanto, tratando-se de hipótese de dano presumido (in re ipsia), pleiteia pagamento de indenização por danos morais, em montante não inferior a R$ 15.000,00 (quinze mil reais).', 
'Verificar contracheques e extratos bancários para comprovar atrasos habituais no pagamento.', 8);

-- ============================================
-- GRUPO 7: DANOS MORAIS
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(7, 'ASSÉDIO MORAL', 
'[NOME DO ASSEDIADOR] era muito ríspido(a) e humilhava constantemente a parte autora, pois [DESCREVER AS CONDUTAS - ex: a chamava de "gozada", "gorda", "esquisita", tratava com desdém, afirmava que não teria direito a verbas trabalhistas, etc.].

Ora, as situações vivenciadas pelo autor são CONSTRANGEDORAS, VEXATÓRIAS E HUMILHANTES, não podendo deixar de receber a proteção do Poder Judiciário, haja vista que o Reclamante foi exposto a todas estas situações sem qualquer proteção do empregador.

A ocorrência de tais fatos fere a personalidade, dignidade, alma, honra, intimidade, reputação e autoestima do trabalhador, porquanto estão revestidas de ilegalidade.

INDENIZAÇÃO POR DANOS MORAIS. ASSÉDIO MORAL. A indenização por danos morais somente se faz devida diante da ocorrência de conduta ilícita que cause danos aos direitos da personalidade. No que diz respeito ao assédio moral, este se trata, no campo das relações trabalhistas, de uma conduta ilícita e abusiva, caracterizada pela prática de atos constantes e reiterados de perseguição, constrangimento e/ou humilhação ao empregado.

A Constituição da República, em seu artigo 5°, inciso X, estabelece que "são invioláveis a intimidade, a vida privada, a honra e a imagem das pessoas, assegurado o direito a indenização pelo dano material ou moral decorrente de sua violação".

Destarte, não há dúvida de que, no caso em tela, faz-se presente a conduta abusiva, de forma reiterada e prolongada, comprometendo o ambiente laboral e abalando psicologicamente o trabalhador, por meio de constrangimentos e humilhações.

Sendo assim, requer a condenação do Reclamado ao pagamento de indenização por danos morais, cujo valor pedimos vênia para o arbitramento em R$ 15.000,00.', 
'Descrever as condutas do assediador com detalhes. Indicar testemunhas que possam confirmar.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(7, 'CONDIÇÕES DEGRADANTES DO LOCAL DE TRABALHO', 
'As condições do local de trabalho da autora eram extremamente precárias. Isso porque não havia lugar para que os trabalhadores realizassem seu horário de almoço e descanso, forçando os obreiros a comerem e se deitarem na [calçada/meio da rua/local inadequado].

Ademais, [a cozinha do local não contava com microondas / ficava situada no interior do galpão, suscetível a poeira e ruídos / possuía pouquíssimas cadeiras]. Não havia condições de instalações sanitárias e local adequado para o autora realizar suas refeições.

Nesse sentido é o IRR nº 54 do TST – que fixou que a ausência de instalações sanitárias adequadas e de local apropriado para alimentação autoriza a condenação do empregador ao pagamento de indenização por danos morais, pois desrespeitados os padrões mínimos de higiene e segurança do trabalho, necessários e exigíveis ao ambiente de trabalho (NR-24 do MTE, CLT, art. 157, Lei nº 8.213/91, art. 19, e CRFB, art. 7º, XXII).

Tal conjuntura ofende a dignidade da trabalhadora e enseja condenação ao pagamento de indenização por danos morais, conforme dispõe a jurisprudência:

DANO MORAL. CONDIÇÕES DE TRABALHO PRECÁRIAS. AUSÊNCIA DE LOCAL APROPRIADO PARA REFEIÇÃO E DESCANSO. VIOLAÇÃO AO PRINCÍPIO DA DIGNIDADE DA PESSOA.

Assim, requer a condenação da reclamada em danos morais pelas condições degradantes do seu ambiente de trabalho, cujo valor pedimos vênia para o arbitramento em R$ 10.000,00.', 
'Anexar fotos do local de trabalho, se disponíveis. Descrever condições precárias.', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(7, 'JORNADA DE TRABALHO EXCESSIVA - DANO EXISTENCIAL', 
'A Reclamante ultrapassava a jornada contratual, sendo, ainda, que trabalhava usualmente em horário noturno. Nesse aspecto, tem-se, ainda, que laborava por [X] horas consecutivas [exposta a diversos agentes insalubres].

Ademais, como narrado no tópico de "horas extras", em algumas ocasiões, a obreira [dobrava sua jornada e laborava por 24 horas consecutivas / trabalhava em dias destinados a folga compensatória / não tinha descanso adequado].

Ora, Excelência, trata-se de condição abusiva e que, obviamente, trouxe impactos para a saúde do trabalhador. A conduta da Reclamada configura ato ilícito trabalhista capaz de ensejar reparação por danos morais. Isso porque a jornada exacerbada gera ao trabalhador cansaço excessivo, bem como a supressão do convívio com a família, amigos e sociedade em geral, sacrificando, ainda, o direito ao descanso e ao lazer.

No âmbito das relações de trabalho, verifica-se a existência de dano existencial quando o empregador impõe um volume excessivo de trabalho ao empregado, impossibilitando-o de estabelecer a prática de um conjunto de atividades culturais, sociais, recreativas, esportivas, afetivas, familiares ou de desenvolver seus projetos de vida.

O dano existencial é o prejuízo sofrido em razão do sobre labor excessivo imposto pelo empregador, que impossibilita o trabalhador de desempenhar suas atividades cotidianas e prejudica a manutenção de suas relações sociais externas ao ambiente de trabalho.

É importante observar que o bem jurídico atingido é a própria sociabilidade e identidade da parte trabalhadora, tendo sido desconsiderados direitos constitucionais tais como de convivência familiar (art. 226 e 227 da CRFB), participação sindical (art. 8º da CRFB), qualificação profissional e educacional (art. 205 da CRFB).

Daí a justificativa para o pagamento da indenização por danos morais, no montante pleiteado de no mínimo R$ 10.000,00.', 
'Demonstrar a jornada excessiva habitual através dos cartões de ponto.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(7, 'DANOS MORAIS - AUSÊNCIA DE ASSENTOS', 
'MMº Juiz, ao longo do contrato de trabalho, a empresa não forneceu assentos para o autor, que laborava em pé ao longo de toda a jornada de trabalho, exceto para refeições.

Dessa forma, e uma vez evidenciando-se que a parte autora era compelida a permanecer longas horas em pé na execução do mister para o qual fora contratado ([FUNÇÃO]) sem opção de alternância do trabalho nessa posição com o trabalho na condição sentada, em face da ausência de assentos no setor, situação que perdurou ao longo do contrato por [X] anos, é de se reconhecer que a conduta patronal é manifestamente afrontosa à dignidade do trabalhador.

Compete ao empregador oferecer ambiente adequado de trabalho àqueles que viabilizam a exploração da atividade econômica, com estrita observância das normas de higiene, saúde e segurança (art. 157, I, da CLT; art. 7º, XXII, da CRFB).

A Constituição estabelece, como fundamento da República e da ordem econômica (arts. 1º, IV, e 170 da CRFB), em paridade com a livre iniciativa, a dignidade e o valor do trabalho, de forma que a produtividade/eficiência do negócio não pode ser promovida às custas da degradação/precarização das condições de ordem laborativa.

Acima do lucro jaz a dignidade da pessoa humana (art. 1º, III, da CRFB), princípio fundamental que confere base à ordem constitucional vigente.

Pelo exposto, faz jus o reclamante no pagamento de indenização por danos morais, o que pedimos o arbitramento no importe de R$ 15.000,00 devido o labor em condição degradante de laborar em pé, sem o assento disponibilizado pela ré.', 
'Aplicável especialmente a vendedores, caixas e outras funções que exigem permanência em pé.', 4);

-- ============================================
-- GRUPO 8: COMISSÕES E REMUNERAÇÃO (continuação)
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'DAS COMISSÕES', 
'INTEGRAÇÃO NA REMUNERAÇÃO - O autor foi contratado para exercer a função de vendedor, tendo a parte reclamada ajustado uma remuneração mensal no valor de R$ [VALOR], acrescido de comissões, que variavam entre [X]% com relação a [produtos], e [X]% com relação aos [outros produtos].

A remuneração mensal do autor se dava por meio de depósito bancário, ao passo que as comissões eram pagas [em mãos / por depósito bancário]. Em razão de tais fatos, requer seja retificada a CTPS do autor, para fazer constar o correto patamar salarial, bem como seja realizada a integração à remuneração do reclamante, com os respectivos reflexos em 13º salário, férias + 1/3 constitucional, aviso prévio, RSR, FGTS + 40%.

RSRs das COMISSÕES QUITADAS – A parte autora também denuncia que a reclamada não quitava os RSR sobre as comissões quitadas mês a mês, não respeitando sequer a quantidade de domingos e feriados no mês. Assim sendo, é devido ao reclamante o pagamento dos valores dos RSRs incidentes sobre as comissões recebidas no curso do período imprescrito do contrato de trabalho, com reflexos em horas extras, férias + 1/3 constitucional, 13º salário, FGTS + 40%, aviso prévio e RSR.

COMISSÃO SOBRE VENDAS CANCELADAS/CLIENTES INADIMPLENTES - Durante todo o contrato de trabalho, o Reclamante mensalmente ao conferir o valor recebido a título de comissões apurava diferença a menor no importe médio de [X]% (por cento), considerando o correto valor que deveria auferir. Levada ao conhecimento da Reclamada, era apenas informado que deveria se tratar de vendas canceladas ou de clientes inadimplentes.

Contudo, jamais foram apresentados pela Reclamada quaisquer documentos demonstrando quais comissões não foram adimplidas. Não se pode perder de vista que o fato de as vendas não serem faturadas ou serem canceladas não autoriza o não pagamento das comissões, nos exatos termos do IRR nº 65 do TST.

EMPREGADO COMISSIONISTA – Tendo em vista que a parte autora era empregada comissionista, ela tem direito a correção monetária prevista na OJ 181 do TST, a qual estabelece que o valor das comissões deve ser corrigido monetariamente para em seguida obter-se a média para efeito de cálculo de férias, 13º salário e verbas rescisórias.

VENDAS PARCELADAS – COMISSÕES SOBRE PREÇO A VISTA - Nas vendas de produtos através de parcelamento, a parte Reclamante sempre recebeu comissões no valor a vista do produto. Porém, a majoração do valor decorrente dos juros também configurava obtenção de lucros pela Reclamada. Requer o recebimento da parcela referente às comissões sobre o valor total da venda (IRR nº 57 do TST).', 
'Verificar contracheques e comprovantes de comissões. Calcular diferenças e RSRs. Variar percentual de vendas canceladas entre 10-20% para evitar padronização.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'AJUDA DE CUSTO HABITUAL - NATUREZA SALARIAL', 
'A Reclamante recebia, todos os meses o valor de R$ [VALOR], a título de "ajuda de custo". Ocorre que a parcela era paga habitualmente, sem que fosse ressarcimento de algum gasto específico e comprovado.

Nessas situações, tem-se que a ajuda de custo assume natureza salarial, eis que ela, na realidade, constitui uma contrapartida às atividades laborais prestadas, e recebe essa nomenclatura com o único intuito de mascarar seu caráter salarial.

Vejamos o que diz a jurisprudência, que é consolidada nesse aspecto:

AJUDA DE CUSTO. FIXA E HABITUAL. DESPESA NÃO COMPROVADA. DESVIO DE FINALIDADE. NATUREZA SALARIAL. REFLEXOS DEVIDOS. A ajuda de custo típica é aquela que tem por finalidade ressarcir determinada despesa necessária à consecução do serviço, ostentando, nesses casos, caráter indenizatório. Entretanto, se o pagamento efetuado pela empresa for habitual, de forma fixa e desvinculado da efetiva comprovação de despesas oriundas da prestação de serviços, a parcela deixa de ostentar natureza indenizatória e passa a ser eminentemente salarial.

Portanto, requer a declaração da natureza salarial do valor de R$ [VALOR], pago mensalmente, com a consequente condenação da Reclamada ao pagamento de seus reflexos em 13º salário, férias + 1/3 constitucional, aviso prévio, RSR, FGTS + 40%. Obrigação de fazer – requer a retificação da CTPS e entrega de novas guias rescisórias.', 
'Verificar contracheques para comprovar pagamento habitual de ajuda de custo fixa.', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'INDENIZAÇÃO PELO USO DO VEÍCULO E COMBUSTÍVEL', 
'A parte reclamante se utilizava de veículo próprio ([MARCA/MODELO, ANO, PLACA]) para se deslocar entre [LOCAIS - ex: sede da Reclamada, clientes, etc.].

Os deslocamentos ocorriam diversas vezes por dia, sem que a empregadora ajudasse a custear gasolina ou qualquer outro gasto com o veículo, como manutenção mecânica, elétrica, troca de óleo e pneus, desgaste em geral e etc.

Ora, não pode o empregador transferir os riscos e custos do empreendimento para o trabalhador, visto que decorrem do próprio desenvolvimento da atividade econômica, e, por tal razão, são de responsabilidade exclusiva da empresa, conforme disposto no art. 2º da CLT.

Assim sendo, requer a condenação da parte reclamada a realizar o pagamento de ajuda de custo mensal, em razão do uso e desgaste do veículo, bem como pelo combustível gasto, em valor a ser arbitrado por Vossa Excelência, sugerindo-se, entretanto, o valor de R$ 300,00 (trezentos reais) por mês durante o contrato de trabalho.', 
'Indicar dados do veículo utilizado e deslocamentos realizados.', 5);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'DIFERENÇAS NO FORNECIMENTO DE COMBUSTÍVEL', 
'A Reclamada fornecia auxílio combustível para que a reclamante realizasse as atividades em campo. A parte autora utilizava veículo de sua propriedade ([MARCA/MODELO, ANO, PLACA]) para atender os interesses da reclamada, no desempenho das atividades diárias.

A reclamada pagava R$ [VALOR]/mês de auxílio combustível, valor esse sem reajuste desde o ano de [ANO]. Ora, considerando o preço da gasolina no ano de [ANO] (R$ [VALOR]/litro) e o valor nos dias atuais de [ANO] de R$ [VALOR]/litro, é patente que a reclamante arcava com os gastos mensais da gasolina para atender os interesses da empresa.

A autora rodava em média de [X] a [X] km mês (apenas para os serviços na empresa), pelo que faz jus as diferenças no valor do combustível.

Dessa forma, levando em consideração a distância mensal percorrida ([X] km), consumo do carro ([X]km por litro) e preço da gasolina (R$ [VALOR]/L), entende a parte autora que lhe é devido a média mensal de R$ [VALOR]/mês, para o ressarcimento dos valores gastos a título de combustível, valor esse devido mês a mês, durante todo o contrato de trabalho.', 
'Calcular: km rodados × preço atual / consumo do veículo. Comparar com valor pago pela empresa.', 6);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'FIXAÇÃO DE VERBA DE ALUGUEL DO VEÍCULO', 
'MMº Juiz, a parte autora utilizava veículo de sua propriedade ([MARCA/MODELO, ANO, PLACA]) para atender os interesses da reclamada, no desempenho das atividades diárias, sendo que o reclamante teve que arcar, durante todo o contrato de trabalho, com os gastos de manutenção, troca de óleo e depreciação, o que por certo trouxe prejuízos materiais.

[A empresa pagava seguro do veículo da trabalhadora, além da importância de R$ [VALOR]/ano para eventuais despesas de manutenção. OU A empresa não pagava nada a título de manutenção/depreciação.]

A utilização de veículo próprio, para viabilizar a prestação de serviços, implica o ressarcimento de custos diretos e indiretos relacionados à manutenção e depreciação do bem assim empregado em benefício da empresa, proporcionalmente ao respectivo uso como instrumento de trabalho.

Solução diversa implicaria transferir à parte obreira parte dos encargos próprios do empreendimento, repercutindo no indébito enriquecimento sem causa do empregador (art. 884 do CC/02), que assim se desvencilha dos custos/despesas inerentes à aquisição/locação/manutenção de uma frota de veículos.

Desta forma, requer seja a reclamada condenada no pagamento de valor referente a aluguel do veículo da reclamante, abrangendo indenização pelos gastos com desgaste e manutenção do veículo, pelo que requer seja fixada em R$ 900,00 (Novecentos reais) por mês durante todo o período imprescrito.

Faz jus ainda, seja fixado indenização pelo uso e desgaste do veículo da autora, o que pedimos vênia para o arbitramento em R$ 15.000,00.', 
'Indicar dados do veículo e se empresa pagava algo a título de manutenção.', 7);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'INDENIZAÇÃO PELO USO DO APARELHO CELULAR', 
'A parte reclamante utilizava de aparelho celular próprio para contato com [fornecedores/gerência/clientes], além de utilizá-lo para efetuar [vendas/atendimentos] e contato direto com clientes. Em razão disso, embora [a loja contasse com Wi-Fi], a obreira necessitava possuir um número de telefone para que tivesse acesso ao WhatsApp.

Por meio do referido aplicativo, ela atendia, diariamente, a média de [X] clientes, respondendo mensagens e atendendo ligações.

Dessa forma, para manter seu chip com número de telefone ativo, para que acessasse o WhatsApp, precisava realizar recargas mensais, as quais perfaziam a média de R$ [VALOR]/mês. Todavia, tais despesas não eram co-custeadas pela Reclamada.

Ora, não pode o empregador transferir os riscos e custos do empreendimento para o trabalhador, visto que decorrem do próprio desenvolvimento da atividade econômica, e, por tal razão, são de responsabilidade exclusiva da empresa, conforme disposto no art. 2º da CLT.

Assim sendo, requer a condenação da parte reclamada a realizar o pagamento de indenização pelo uso, desgaste e depreciação do aparelho celular do reclamante, bem como que seja condenada a restituir para o obreiro sua quota-parte no custeio da manutenção de número de telefone ativo, em valor a ser arbitrado por Vossa Excelência, sugerindo-se o valor de R$ 25,00 (vinte e cinco reais) por mês durante o contrato de trabalho.', 
'Verificar se empregado usava celular pessoal para trabalho.', 8);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'PISO DA ENFERMAGEM - DIFERENÇAS SALARIAIS', 
'MMº Juiz, a Lei nº 14.434/2022 instituiu o piso salarial nacional para enfermeiras, técnicas em enfermagem, auxiliares de enfermagem e parteiras. Vejamos os valores do piso:

Enfermeiros: R$ 4.750,00
Técnicos de Enfermagem: R$ 3.325,00
Auxiliares de Enfermagem e parteiras: R$ 2.375,00

Pois bem, a parte autora por todo o contrato de trabalho realizou a função de [FUNÇÃO], tendo recebido o salário de R$ [VALOR]/mês, enquanto o certo seria de R$ [VALOR DO PISO]/mês de acordo com a Lei nº 14.434/22.

Dessa forma, a partir de agosto de 2022 (data de início da vigência da mencionada lei), a obreira faz jus às diferenças salariais, mês a mês, e seus reflexos em 13º salário, férias + 1/3 constitucional, FGTS+40%, aviso prévio, RSRs, adicional noturno, hora noturna reduzida e horas extras.

A reclamada deverá retificar a CTPS da autora para fazer constar a nova remuneração, sob pena de multa diária no importe de R$ 100,00 (Cem reais) revertida a parte autora.', 
'Verificar função exercida e salário recebido. Comparar com piso da categoria.', 9);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'SALÁRIO SUBSTITUIÇÃO', 
'O Reclamante, entre os dias [DATA INÍCIO] e [DATA FIM], laborou como [FUNÇÃO SUBSTITUÍDA], em mesmo horário de trabalho, em substituição ao funcionário usual, que se encontrava de férias. Nessa ocasião, desempenhou atividades de [DESCREVER ATIVIDADES].

Todavia, embora o [CARGO SUBSTITUÍDO] da empresa recebesse salário superior ao seu (cerca de R$ [VALOR]/mês), o obreiro não obteve nenhum acréscimo em seu salário referente ao mencionado período.

Assim, nos moldes da Súmula 159 do TST, o empregado faz jus ao salário do funcionário substituído, pelo tempo que perdurar a substituição.

Dessa forma, requer pagamento de diferença salarial do período compreendido entre [DATA INÍCIO] a [DATA FIM]. Faz jus ainda nos reflexos em 13º salário, férias + 1/3 constitucional, FGTS+40%, aviso prévio e RSR.', 
'Indicar período de substituição, função e salário do substituído.', 10);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(8, 'DESCONTOS INDEVIDOS - FALTA COM ATESTADO', 
'Analisando o contracheque da parte Reclamante, nota-se um desconto referente a "Faltas não justificadas", o qual não possui a concordância do trabalhador.

O Reclamante apresentou atestado médico para justificar sua ausência, contudo, a empresa procedeu ao desconto dos dias correspondentes, violando o disposto no §1º do artigo 6º, da Lei nº 605/1949 e a Súmula 282 do TST.

Dessa forma, o desconto efetuado pela empresa nos dias abonados por atestado médico é indevido, violando o direito do trabalhador e ensejando a restituição dos valores descontados.

Assim sendo, requer a restituição do montante descontado no salário do reclamante, no mês de [MÊS/ANO], dos quais entendemos tratar-se de descontos indevidos.', 
'Anexar contracheque com desconto e atestado médico apresentado.', 11);

-- ============================================
-- GRUPO 9: BENEFÍCIOS E CCT
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(9, 'NATUREZA SALARIAL DO VALE ALIMENTAÇÃO PAGO EM DINHEIRO', 
'A Reclamada durante todo o contrato de trabalho, quitou "vale-alimentação" para o obreiro. Da admissão até [DATA], a verba perfazia o montante de R$ [VALOR]/mês; ao passo que, a partir de [DATA], foi majorada para R$ [VALOR]/mês.

No entanto, o valor era pago em dinheiro, em contracheque, junto ao salário mensal.

Quando o auxílio-alimentação é pago em dinheiro, reveste-se de natureza salarial, despindo-se, portanto, da natureza indenizatória que lhe era peculiar. Vejamos o que dispõe a jurisprudência:

AUXÍLIO-ALIMENTAÇÃO. O Tribunal Regional reconheceu a natureza salarial do auxílio-alimentação, determinando a sua integração ao salário e consequentes reflexos.

Assim sendo, requer a declaração da natureza salarial do vale-alimentação pago em dinheiro, com a integração à remuneração e seus reflexos em 13º salário, férias + 1/3 constitucional, aviso prévio, RSR, FGTS + 40%, horas extras. Obrigação de fazer – requer a retificação da CTPS e entrega de novas guias rescisórias.', 
'Verificar contracheques para comprovar pagamento em dinheiro e não em cartão/ticket.', 1);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(9, 'BENEFÍCIOS DA NORMA COLETIVA - COMÉRCIO', 
'O local de prestação de serviços da Reclamante se deu na cidade de [CIDADE]/MG, e a descrição da atividade econômica principal desenvolvida pela empresa é o "[DESCRIÇÃO CNAE]".

E tendo a reclamante laborado na função de [FUNÇÃO], na loja da reclamada, necessário se faz reconhecer todos os direitos da categoria profissional dos empregados do comércio, convencionados nos instrumentos coletivos anexos à petição inicial.

Assim sendo, a parte autora traz à baila alguns benefícios previstos nos instrumentos coletivos anexos, que não lhe foram concedidos ao longo do contrato de trabalho:

CORREÇÃO SALARIAL – A CCT de classe aponta na cl. [X] uma tabela de reajustes fixados pelo Sindicato, o que não foi respeitado pela empresa. Consequentemente, faz jus a parte autora na diferença da correção salarial, mês a mês, ao longo de todo o pacto laboral, com reflexos em 13ª salário, férias + 1/3 constitucional, FGTS+40%, aviso prévio e RSR.

ABONO - A referida cláusula garante a todos os empregados o pagamento do abono, no valor de R$ [VALOR], que deve ser quitado no mês janeiro, referente ao ano anteriormente trabalhado, respeitando a proporcionalidade de 1/12 para cada mês laborado.

AUXÍLIO ALIMENTAÇÃO - A CCT de classe assegura aos empregados o recebimento do valor diário R$ [VALOR], a título de auxílio alimentação/lanche. Não tendo a parte reclamante recebido o benefício, requer o pagamento da indenização substitutiva.

PLANO DE SAÚDE - De acordo com as CCTs anexas, as empresas devem contratar para todos os funcionários uma operadora de plano de saúde. Acontece que a empresa não forneceu tal benefício, fazendo jus a reclamante à multa específica por descumprimento.

HOMOLOGAÇÃO - A CCT reza que a homologação da rescisão obrigatoriamente deve passar pelo sindicato de classe. No caso dos autos, a reclamada não realizou a homologação no sindicato, pelo que requer a parte autora a indenização substitutiva.

PENALIDADES POR DESCUMPRIMENTO - O descumprimento das cláusulas da presente Convenção acarretará multa no valor referente a um piso salarial vigente da categoria por empregado. Requer a parte autora o recebimento das multas por descumprimento das cláusulas acima descritas.

DESCUMPRIMENTO DE CONVENÇÕES COLETIVAS ADITIVAS - A parte autora também destaca que no período imprescrito o sindicato de classe instituiu CCTs Aditivas para feriados e datas comemorativas, as quais não foram respeitadas pela reclamada. Requer o pagamento de todas as multas convencionais das CCTs aditivas.', 
'Anexar todas as CCTs aplicáveis. Verificar quais cláusulas foram descumpridas. Adaptar à categoria do cliente.', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(9, 'HIGIENIZAÇÃO DO UNIFORME DE TRABALHO - INDENIZAÇÃO', 
'Por determinação da Reclamada, a parte Reclamante fazia uso de uniforme fornecido pela empresa. Sabe-se que os hospitais e demais serviços de saúde são obrigados a processarem as roupas provenientes dos atendimentos a pacientes em Lavanderia Hospitalar ou similar, de modo a prevenirem e controlarem os riscos de contaminações por doenças.

Sendo assim, é obrigação da Reclamada proporcionar aos seus empregados as condições necessárias à correta higienização das roupas utilizadas no trabalho. Contudo, a Reclamada não providenciou a higienização das vestimentas da parte Reclamante, sendo ela obrigada a lavar o seu uniforme em casa.

De acordo com a NR-32, item 32.2.4.6, é do empregador o fornecimento de vestimentas limpas todos os dias antes do início dos trabalhos, devendo o empregado deixar as usadas, após o expediente, em local apropriado.

Importante frisar que o risco da atividade econômica é da Reclamada, nos termos do art. 2º da CLT, sendo que esta não pode repassar os seus custos a parte Reclamante, impondo-lhe a obrigação de lavar o uniforme de trabalho, especificamente por se tratar de roupa hospitalar que necessita de tratamento diferenciado.

No caso dos autos, a obrigação da Reclamada é ainda maior, pois além de obrigar o Reclamante a utilizar o uniforme, trata-se de roupa utilizada em ambiente comprovadamente insalubre que não poderia ser lavada conjuntamente com as roupas de outros membros da sua família.

Desta forma, a Reclamada deverá indenizar a parte Reclamante por todos os valores que foi obrigada a suportar com a lavagem do uniforme, o que pedimos vênia para o arbitramento mensal de R$ 150,00 (Cento e cinquenta reais) mensais.', 
'Aplicável especialmente a profissionais de saúde. Verificar NR-32.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(9, 'DIFERENÇAS DO SEGURO-DESEMPREGO', 
'A base de cálculo do seguro-desemprego deverá ser composta pelas parcelas de natureza salarial, ou seja, verbas abrangidas pela expressão "salários", constante do art. 5º, §1º, da Lei nº 7.998/90.

Prejudicado o cálculo do benefício por culpa da ex-empregadora, que não pagou todos os direitos devidos durante a vigência contratual, impõe-se sua responsabilização ao pagamento das diferenças havidas.

No caso dos autos, sendo deferidas verbas de caráter salarial que integram a remuneração para fins de cálculo ou enquadramento do benefício do seguro-desemprego, as quais não foram consideradas pela Reclamada na informação da remuneração mensal dos últimos três meses na CD/SD, resta prejudicado o trabalhador.

Assim, tendo em vista que o Reclamante recebeu o benefício do seguro-desemprego em valor inferior ao que seria devido, deverá a Reclamada arcar com a indenização substitutiva das diferenças do benefício, por aplicação subsidiária do disposto nos artigos 186, 927 e 932, III, do CC/02, a teor do art. 8º da CLT.', 
'Aplicável quando verbas salariais deferidas impactam o cálculo do seguro-desemprego já recebido.', 4);

-- ============================================
-- GRUPO 10: ESTABILIDADES (continuação)
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(10, 'ESTABILIDADE PROVISÓRIA - DOENÇA OCUPACIONAL', 
'Reconhecida a doença ocupacional que se equipara a acidente de trabalho, faz jus o autor à estabilidade acidentária, estabelecida pelo art. 118 da Lei nº 8.213/91 e Súmula 378 do TST, pelo prazo de 12 meses, haja vista que ficou afastado de suas atividades por período superior a 15 dias com a consequente percepção do auxílio-doença acidentário.

Portanto, requer seja reconhecida e garantida a estabilidade provisória de emprego ao reclamante, a partir do momento em que ocorrer a alta previdenciária, até 12 meses após referenciada alta.

[Caso já tenha sido dispensado:]
Em razão da dispensa ocorrida durante o período de estabilidade, requer seja o reclamante reintegrado ao emprego ou, alternativamente, o pagamento de indenização substitutiva ao período estabilitário, com todos os direitos e vantagens.', 
'Verificar se houve afastamento superior a 15 dias e percepção de auxílio-doença acidentário (B91).', 2);

-- ============================================
-- GRUPO 11: OUTROS (continuação)
-- ============================================

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(11, 'LIMBO PREVIDENCIÁRIO', 
'O Reclamante, após ter recebido alta do INSS em [DATA], compareceu à empresa para retomar suas atividades. Contudo, foi considerado inapto pelo médico do trabalho da Reclamada, que não reativou o contrato de trabalho do autor, tampouco tentou readaptá-lo em outra função.

Dessa forma, o obreiro ficou sem poder trabalhar, e sem receber seu benefício previdenciário ou salário da empresa, caracterizando o chamado "limbo previdenciário".

A jurisprudência do TST é pacífica quanto à responsabilidade do empregador pelo pagamento dos salários do empregado, a partir da alta previdenciária, ainda que considerado inapto pela junta médica da empresa. Nesse sentido é o IRR nº 88 do TST.

Pois bem, dessa forma requer o Autor o pagamento dos salários das competências de [MESES], relativos ao período de limbo previdenciário, bem como suas repercussões em 13º salário, férias + 1/3 constitucional, FGTS+40%, aviso prévio e RSR.

DANOS MORAIS – LIMBO JURÍDICO – A situação narrada, além de abalo financeiro, gerou forte abalo psicológico ao Reclamante, o qual se viu sem poder trabalhar, e sem saber se teria seu benefício previdenciário deferido. Tal conjuntura é passível de indenização por danos morais, o qual é considerado in re ipsa, ou seja, presumido.

Por tal motivo, pleiteia o obreiro indenização pelos danos morais sofridos em montante não inferior a R$ 10.000,00.', 
'Verificar data da alta previdenciária e se empresa recusou retorno. Indicar meses sem pagamento.', 2);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(11, 'INADIMPLEMENTO SALARIAL - TEMPO DE ESPERA PARA PERÍCIA INSS', 
'Embora o auxílio doença da obreira tenha cessado em [DATA], após a obreira retornar ao labor, percebeu que [as fortes dores a impediriam de permanecer trabalhando / estava inapta]. Assim, apresentou novo pedido de auxílio doença ao INSS em [DATA].

Ocorre que a perícia médica foi realizada somente em [DATA], ou seja, [X DIAS/MESES] após o pedido. [Descrever resultado da perícia e desdobramentos]

No entanto, esse período em que a Reclamante aguardava a ocorrência de perícia médica do INSS não lhe foi pago pela Reclamada, muito embora sua ausência estivesse amparada por atestado médico previamente fornecido e pelo aguardo do desenrolar do pedido previdenciário.

Nesses casos, em que o empregado aguarda a realização de perícia médica, ainda que o pedido de benefício seja posteriormente indeferido, o lapso temporal é considerado tempo à disposição do empregador, e, por isso, deve ensejar o pagamento de salário. É o que dispõe a jurisprudência:

LIMBO PREVIDENCIÁRIO. ÔNUS DO EMPREGADOR. Enquanto o trabalhador aguarda a resposta do órgão previdenciário, permanece à disposição do empregador. Assim, na hipótese de o benefício ser negado, cabe ao empregador arcar com o pagamento dos salários nesse interregno.

Portanto, pleiteia pagamento do salário relativo ao período de [MÊS/ANO], no qual a Reclamante aguardava a realização de perícia médica do INSS, bem como sua repercussão em 13º salário, férias + 1/3 constitucional, FGTS+40%, aviso prévio e RSR.', 
'Verificar datas: cessação do benefício, novo pedido, data da perícia. Calcular período sem pagamento.', 3);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(11, 'INDENIZAÇÃO SUBSTITUTIVA DO SEGURO DESEMPREGO', 
'MMº Juiz, conforme narrado acima, a parte reclamada não realizou a entrega das guias de CD/SD à trabalhadora [em razão do pedido de demissão / por omissão / após a dispensa].

No entanto, caso as guias sejam entregues apenas após o trânsito em julgado da lide, estas se tornarão inábeis ao pedido de seguro desemprego pela obreira, ante o transcurso de tempo. Assim, torna-se devido o pagamento, pela Reclamada, de indenização substitutiva ao seguro desemprego.

Como cediço, é obrigação do empregador, nas hipóteses de dispensa do empregado sem justa causa, o fornecimento das guias para requerimento do seguro-desemprego, sendo que o ato omissivo do empregador ou, até mesmo, o insucesso no recebimento dos valores em razão da conduta culposa da empresa, ensejam a condenação patronal ao pagamento de indenização substitutiva, nos termos do item II da Súmula nº 389 do TST.

INDENIZAÇÃO SUBSTITUTIVA DO SEGURO DESEMPREGO. A Súmula nº 389, II, do TST, assim dispõe: "O não-fornecimento pelo empregador da guia necessária para o recebimento do seguro-desemprego dá origem ao direito à indenização".

Dessa forma, faz jus à indenização substitutiva do benefício seguro desemprego ([X] parcelas), já que contava com [X meses] de contrato, tudo conforme faixa salarial do Governo Federal.', 
'Calcular número de parcelas conforme tempo de contrato e tabela do seguro-desemprego.', 4);

INSERT INTO topicos (grupo_id, titulo, conteudo, observacoes, ordem) VALUES
(11, 'INDENIZAÇÃO SUBSTITUTIVA DO PIS', 
'MMº Juiz, em conformidade com o art. 9ª, itens I e II, da Lei nº 7.998/90, tem direito ao recebimento do abono anual relativo ao PIS os empregados que tenham percebido até dois salários mínimos, tenham exercido atividade remunerada pelo menos por 30 dias durante o ano-base e estejam cadastrados há pelo menos cinco anos no Fundo de Participação PIS-PASEP ou no Cadastro Nacional do Trabalhador.

Uma vez que a parte reclamante reúne as condições acima e tendo em vista que sequer possuía CTPS anotada, é devido o pagamento da indenização substitutiva do abono nos anos de [ANO] e [ANO] no valor de 01 salário mínimo (cada ano) vigente a época dos fatos.', 
'Aplicável quando não houve anotação da CTPS. Verificar se empregado recebia até 2 salários mínimos.', 5);

-- ============================================
-- ÍNDICES PARA MELHOR PERFORMANCE
-- ============================================
CREATE INDEX idx_topicos_grupo ON topicos(grupo_id);
CREATE INDEX idx_topicos_ativo ON topicos(ativo);
CREATE INDEX idx_grupos_ativo ON grupos(ativo);

-- ============================================
-- VIEW PARA CONSULTA FACILITADA
-- ============================================
CREATE OR REPLACE VIEW vw_topicos_completos AS
SELECT 
    t.id AS topico_id,
    t.titulo,
    t.conteudo,
    t.observacoes,
    t.ordem AS topico_ordem,
    g.id AS grupo_id,
    g.nome AS grupo_nome,
    g.descricao AS grupo_descricao,
    g.ordem AS grupo_ordem
FROM topicos t
INNER JOIN grupos g ON t.grupo_id = g.id
WHERE t.ativo = TRUE AND g.ativo = TRUE
ORDER BY g.ordem, t.ordem;
