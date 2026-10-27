<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPedidoValuesRequest;
use App\Models\Petition;
use App\Services\BackgroundGeneration;
use App\Services\GenerationFailed;
use App\Services\Pedidos\PedidoValues;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * "Valores dos pedidos": the estimated amount of each pedido, with how it was reached,
 * for the lawyer to check and adjust before the Word is generated.
 */
class PetitionValuesController extends Controller
{
    public function __construct(
        protected PedidoValues $values,
    ) {}

    public function show(Petition $petition): View|RedirectResponse
    {
        $petition->load('items');

        if (! $petition->isFullyReviewed()) {
            return redirect()
                ->route('petition.review', $petition)
                ->with('status', 'Revise todos os tópicos antes de conferir os valores.');
        }

        return view('petitions.values', [
            'petition' => $petition,
            'needsCalculation' => $this->values->isStale($petition),
            'hasCaseData' => $petition->appointment_id !== null,
            'total' => PedidoValues::total($petition->pedidos ?? []),
        ]);
    }

    /**
     * Runs the AI and the calculator in the background (the AI takes up to a minute).
     */
    public function calculate(Request $request, Petition $petition, BackgroundGeneration $generations): JsonResponse
    {
        if (! $petition->isFullyReviewed()) {
            return response()->json(['message' => 'Revise todos os tópicos antes de conferir os valores.'], 422);
        }

        $petitionId = $petition->id;

        $token = $generations->start($request->user()->id, function () use ($petitionId): array {
            $petition = Petition::query()->findOrFail($petitionId);

            try {
                $this->values->calculate($petition);
            } catch (Throwable $exception) {
                report($exception);

                throw new GenerationFailed('Não foi possível calcular os valores agora. Tente novamente em instantes.');
            }

            return ['redirect' => route('petition.values.show', $petition)];
        });

        return response()->json(['status_url' => route('generation.status', $token)], 202);
    }

    public function update(ConfirmPedidoValuesRequest $request, Petition $petition): RedirectResponse
    {
        if ($this->values->isStale($petition)) {
            return redirect()
                ->route('petition.values.show', $petition)
                ->with('status', 'Os tópicos ou a ficha mudaram. Confira os valores recalculados.');
        }

        $this->values->confirm($petition, $request->input('pedidos', []));

        if ($request->user()->can('petitions.generate')) {
            return redirect()->route('petition.generate.show', $petition);
        }

        return redirect()
            ->route('petition.values.show', $petition)
            ->with('status', 'Valores confirmados. A geração do documento fica com quem tem essa permissão no perfil.');
    }
}
