<?php

namespace App\Services;

/**
 * Opening of the petition built from the Library (court, client and employer), reviewed
 * like any topic: the lawyer fills its [fields] on the review screen. Wording from the
 * office's model (PETIÇÃO.doc).
 */
class PetitionQualification
{
    public const TITLE = 'QUALIFICAÇÃO DAS PARTES';

    public const GROUP = 'ABERTURA DA PETIÇÃO';

    /**
     * Fields whose value is written in capital letters in the document.
     */
    public const UPPERCASE_FIELDS = ['NOME DO RECLAMANTE', 'NOME DA RECLAMADA', 'CIDADE DA VARA', 'ESTADO DA VARA'];

    public static function content(): string
    {
        return '<p><strong>AO DOUTO JUÍZO DA ___ª VARA DO TRABALHO DE [CIDADE DA VARA]/[ESTADO DA VARA]</strong></p>'
            .'<p><strong>[NOME DO RECLAMANTE]</strong>, [nacionalidade], [estado civil], [profissão], RG [RG], [órgão expedidor do RG], '
            .'CPF [CPF], residente e domiciliado na [endereço do reclamante], nº [número do reclamante], bairro [bairro do reclamante], '
            .'CEP [CEP do reclamante], [cidade do reclamante]/[UF do reclamante], CTPS: [CTPS], série [série da CTPS], PIS/PASEP [PIS/PASEP], '
            .'por seus advogados Grimaldo Bruno Fernandes Botelho, inscrito na OAB/MG 120.920, Helen Cristina Ribeiro Soares Real, '
            .'inscrita na OAB/MG 139.212 e Igor Felippe Nascimento Firmino de Oliveira, inscrito na OAB/MG 191.603, mandato incluso, '
            .'ambos com endereço profissional na Rua Jaspe, nº 290, Bairro: Iguaçu – Cidade: Ipatinga/MG – CEP: 35.162-077, '
            .'local onde recebem intimações/notificações, vem, respeitosamente, à presença de Vossa Excelência, '
            .'nos termos do Artigo 840 da CLT propor:</p>'
            .'<p class="ql-align-center"><strong>AÇÃO TRABALHISTA</strong>, em face de:</p>'
            .'<p><strong>[NOME DA RECLAMADA]</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº [CNPJ], '
            .'com sede na [endereço da reclamada], nº [número da reclamada], [bairro da reclamada], '
            .'[cidade da reclamada]/[UF da reclamada], CEP [CEP da reclamada], de acordo com as razões a seguir aduzidas:</p>';
    }
}
