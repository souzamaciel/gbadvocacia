<?php

namespace App\Services\Pedidos;

use App\Services\MoneyInWords;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Contract data the estimates are based on, read straight from the attendance form
 * answers (no AI involved): salary, admission, dismissal and how the contract ended.
 */
class CaseFacts
{
    public function __construct(
        public readonly ?int $salaryCents,
        public readonly ?int $offTheBooksCents,
        public readonly ?Carbon $admission,
        public readonly ?Carbon $dismissal,
        public readonly bool $dismissalIsToday,
        public readonly ?string $termination,
        public readonly Carbon $filing,
        public readonly int $divisor,
    ) {}

    /**
     * @param  array<string, mixed>  $answers  raw attendance form answers
     */
    public static function fromAnswers(array $answers, ?int $divisor = null): self
    {
        $salary = MoneyInWords::parseCents(self::text($answers['salario_ultimo'] ?? null));
        $offTheBooks = self::text($answers['salario_por_fora'] ?? null) === 'Sim'
            ? MoneyInWords::parseCents(self::text($answers['salario_valor_por_fora'] ?? null))
            : null;
        $dismissal = self::date($answers['contrato_dispensa'] ?? null);

        return new self(
            salaryCents: $salary,
            offTheBooksCents: $offTheBooks,
            admission: self::date($answers['contrato_admissao'] ?? null),
            dismissal: $dismissal ?? today(),
            dismissalIsToday: $dismissal === null,
            termination: self::text($answers['extincao_causa'] ?? null),
            filing: today(),
            divisor: $divisor !== null && $divisor >= 100 && $divisor <= 240 ? $divisor : (int) config('calculos.divisor'),
        );
    }

    /**
     * Monthly pay used as base: the registered salary plus what was paid off the books.
     */
    public function remuneration(): ?int
    {
        if ($this->salaryCents === null) {
            return null;
        }

        return $this->salaryCents + ($this->offTheBooksCents ?? 0);
    }

    public function remunerationText(): string
    {
        $text = MoneyInWords::formatCents((int) $this->remuneration());

        return $this->offTheBooksCents ? "{$text} (salário + valor “por fora”)" : $text;
    }

    public function hourly(): ?float
    {
        return $this->remuneration() === null ? null : $this->remuneration() / $this->divisor;
    }

    /**
     * Start of the period not reached by the five-year prescription (art. 7º, XXIX, CF).
     */
    public function claimableStart(): ?Carbon
    {
        if ($this->admission === null) {
            return null;
        }

        $limit = $this->filing->copy()->subYears((int) config('calculos.anos_prescricao'));

        return $this->admission->greaterThan($limit) ? $this->admission->copy() : $limit;
    }

    /**
     * Months of the claimable period, with fractions (e.g. 26.5).
     */
    public function claimableMonths(): ?float
    {
        $start = $this->claimableStart();

        return $start === null || $this->dismissal === null || $this->dismissal->lessThanOrEqualTo($start)
            ? null
            : round($start->diffInDays($this->dismissal) / 30.4375, 2);
    }

    public function contractMonths(): ?float
    {
        return $this->admission === null || $this->dismissal === null || $this->dismissal->lessThanOrEqualTo($this->admission)
            ? null
            : round($this->admission->diffInDays($this->dismissal) / 30.4375, 2);
    }

    public function isDismissalWithoutCause(): bool
    {
        return $this->termination === null || $this->termination === 'Sem justa causa';
    }

    /**
     * 30 days plus 3 for each complete year of service, up to 90 (Lei 12.506/2011).
     */
    public function noticeDays(): ?int
    {
        if ($this->admission === null || $this->dismissal === null) {
            return null;
        }

        return min(90, 30 + 3 * (int) floor($this->admission->diffInYears($this->dismissal)));
    }

    /**
     * End of the contract counting the indemnified notice (OJ 82 da SDI-1).
     */
    public function projectedEnd(): ?Carbon
    {
        if ($this->dismissal === null) {
            return null;
        }

        return $this->isDismissalWithoutCause() && ! $this->dismissalIsToday
            ? $this->dismissal->copy()->addDays((int) $this->noticeDays())
            : $this->dismissal->copy();
    }

    /**
     * Twelfths ("avos") between two dates: each month counts, and a remainder of 15 days or more counts as one.
     */
    public static function twelfths(Carbon $start, Carbon $end): int
    {
        if ($end->lessThan($start)) {
            return 0;
        }

        $months = 0;
        $cursor = $start->copy();

        while ($cursor->copy()->addMonthNoOverflow()->subDay()->lessThanOrEqualTo($end)) {
            $months++;
            $cursor->addMonthNoOverflow();
        }

        if ($cursor->diffInDays($end) + 1 >= 15) {
            $months++;
        }

        return min(12, $months);
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private static function date(mixed $value): ?Carbon
    {
        $text = self::text($value);

        if ($text === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $text)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
