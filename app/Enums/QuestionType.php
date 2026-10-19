<?php

namespace App\Enums;

enum QuestionType: string
{
    case Heading = 'heading';
    case Notice = 'notice';
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case YesNo = 'yes_no';
    case Date = 'date';
    case Number = 'number';
    case Money = 'money';
    case Cpf = 'cpf';
    case CpfCnpj = 'cpf_cnpj';
    case Pis = 'pis';
    case Cep = 'cep';
    case Phone = 'phone';
    case Uf = 'uf';
    case Repeater = 'repeater';

    public function label(): string
    {
        return match ($this) {
            self::Heading => 'Subtítulo',
            self::Notice => 'Texto de orientação',
            self::ShortText => 'Texto curto',
            self::LongText => 'Texto longo',
            self::SingleChoice => 'Múltipla escolha (uma resposta)',
            self::MultipleChoice => 'Caixas de seleção (várias respostas)',
            self::YesNo => 'Sim / Não',
            self::Date => 'Data',
            self::Number => 'Número',
            self::Money => 'Valor em R$',
            self::Cpf => 'CPF',
            self::CpfCnpj => 'CPF ou CNPJ',
            self::Pis => 'PIS',
            self::Cep => 'CEP (preenche o endereço)',
            self::Phone => 'Telefone',
            self::Uf => 'Estado (UF)',
            self::Repeater => 'Lista repetível',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Heading => 'Título que separa um grupo de perguntas. Não é uma pergunta.',
            self::Notice => 'Aviso ou lembrete para quem faz o atendimento. Não é uma pergunta.',
            self::ShortText => 'Resposta em uma linha.',
            self::LongText => 'Resposta com várias linhas.',
            self::SingleChoice => 'Escolher uma opção da lista.',
            self::MultipleChoice => 'Marcar uma ou mais opções da lista.',
            self::YesNo => 'Responder Sim ou Não.',
            self::Date => 'Escolher uma data.',
            self::Number => 'Somente números.',
            self::Money => 'Valor em reais, com formato R$.',
            self::Cpf => 'CPF com formato 000.000.000-00.',
            self::CpfCnpj => 'CPF ou CNPJ, com o formato conforme a quantidade de números.',
            self::Pis => 'PIS com formato 000.00000.00-0.',
            self::Cep => 'Ao informar o CEP, preenche endereço, bairro, cidade e UF da mesma pergunta-base.',
            self::Phone => 'Telefone com DDD.',
            self::Uf => 'Lista com as siglas dos estados.',
            self::Repeater => 'Várias linhas com as mesmas colunas, como funções e períodos ou empresas do grupo.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Heading => 'heading',
            self::Notice => 'info',
            self::ShortText => 'type',
            self::LongText => 'align-left',
            self::SingleChoice => 'circle-dot',
            self::MultipleChoice => 'square-check',
            self::YesNo => 'toggle-left',
            self::Date => 'calendar',
            self::Number => 'hash',
            self::Money => 'banknote',
            self::Cpf, self::CpfCnpj, self::Pis => 'id-card',
            self::Cep => 'map-pin',
            self::Phone => 'phone',
            self::Uf => 'map',
            self::Repeater => 'rows-3',
        };
    }

    /**
     * Whether the item collects an answer (headings and notices do not).
     */
    public function isAnswerable(): bool
    {
        return ! in_array($this, [self::Heading, self::Notice], true);
    }

    public function hasChoices(): bool
    {
        return in_array($this, [self::SingleChoice, self::MultipleChoice], true);
    }

    /**
     * Types whose answer can be used in "show only when".
     */
    public function canControlVisibility(): bool
    {
        return in_array($this, [self::SingleChoice, self::MultipleChoice, self::YesNo], true);
    }

    /**
     * Types allowed as columns of a repeatable list.
     *
     * @return list<self>
     */
    public static function columnTypes(): array
    {
        return [self::ShortText, self::Date, self::Number, self::Money, self::Cpf, self::CpfCnpj, self::Cep, self::Phone, self::Uf];
    }

    /**
     * Input mask used by the attendance form, when the type has one.
     */
    public function mask(): ?string
    {
        return match ($this) {
            self::Money => 'money',
            self::Cpf => 'cpf',
            self::CpfCnpj => 'cpf_cnpj',
            self::Pis => 'pis',
            self::Cep => 'cep',
            self::Phone => 'phone',
            default => null,
        };
    }
}
