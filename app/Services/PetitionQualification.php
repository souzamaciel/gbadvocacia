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

    /**
     * The opening text; each extra defendant (service taker from the form) gets its own
     * paragraph after the employer, and only the last defendant closes the sentence.
     */
    public static function content(int $extraDefendants = 0): string
    {
        $defendants = ['<p><strong>[NOME DA RECLAMADA]</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº [CNPJ], '
            .'com sede na [endereço da reclamada], nº [número da reclamada], [bairro da reclamada], '
            .'[cidade da reclamada]/[UF da reclamada], CEP [CEP da reclamada]'];

        for ($number = 2; $number <= $extraDefendants + 1; $number++) {
            $defendants[] = "<p><strong>[NOME DA {$number}ª RECLAMADA]</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº [CNPJ da {$number}ª reclamada], "
                ."com sede na [endereço da {$number}ª reclamada], [bairro da {$number}ª reclamada], "
                ."[cidade da {$number}ª reclamada]/[UF da {$number}ª reclamada], CEP [CEP da {$number}ª reclamada]";
        }

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
            .implode(';</p>', $defendants).', de acordo com as razões a seguir aduzidas:</p>';
    }

    /**
     * Whether a field's value is written in capital letters in the document.
     */
    public static function isUppercase(string $field): bool
    {
        return in_array($field, self::UPPERCASE_FIELDS, true) || preg_match('/^NOME DA \d+ª RECLAMADA$/u', $field) === 1;
    }

    /**
     * Number of defendants after the employer in an opening text.
     */
    public static function extraDefendants(string $content): int
    {
        return preg_match_all('/\[NOME DA \d+ª RECLAMADA\]/u', $content);
    }
}
