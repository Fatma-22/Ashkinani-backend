<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerPayment;
use App\Services\MediaService;
use Illuminate\Http\Request;

class PlayerPaymentController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index(Player $player)
    {
        $payments = $player->payments()->orderBy('due_date', 'desc')->orderBy('created_at', 'desc')->get();
        return $this->success($payments);
    }

    public function store(Request $request, Player $player)
    {
        $validated = $this->validatePayload($request);

        unset($validated['receipt'], $validated['remove_receipt']);

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $this->mediaService->upload($request->file('receipt'), 'player-payments');
        }

        $payment = $player->payments()->create($validated + ['created_by' => auth()->id()]);

        return $this->success($payment, 'Payment created successfully', 201);
    }

    public function update(Request $request, Player $player, PlayerPayment $payment)
    {
        if ($payment->player_id !== $player->id) {
            return $this->error('Payment does not belong to this player', 404);
        }

        $validated = $this->validatePayload($request);

        unset($validated['receipt'], $validated['remove_receipt']);

        if ($request->hasFile('receipt')) {
            $this->mediaService->delete($payment->receipt_path);
            $validated['receipt_path'] = $this->mediaService->upload($request->file('receipt'), 'player-payments');
        } elseif ($request->boolean('remove_receipt')) {
            $this->mediaService->delete($payment->receipt_path);
            $validated['receipt_path'] = null;
        }

        $payment->update($validated);

        return $this->success($payment, 'Payment updated successfully');
    }

    public function destroy(Player $player, PlayerPayment $payment)
    {
        if ($payment->player_id !== $player->id) {
            return $this->error('Payment does not belong to this player', 404);
        }

        $this->mediaService->delete($payment->receipt_path);
        $payment->delete();

        return $this->success(null, 'Payment deleted successfully');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => 'nullable|string|max:255',
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'due_date' => 'nullable|date',
            'payment_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'remove_receipt' => 'nullable|boolean',
        ]);
    }
}
