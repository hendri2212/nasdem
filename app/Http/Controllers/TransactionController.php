<?php

namespace App\Http\Controllers;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Show the transaction page.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $type = $request->string('type')->toString();
        $location = $request->string('location')->toString();

        $transactions = Transaction::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('description', 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $searchQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->when(in_array($type, TransactionType::values(), true), function ($query) use ($type): void {
                $query->where('type', $type);
            })
            ->when(in_array($location, TransactionLocation::values(), true), function ($query) use ($location): void {
                $query->where('location', $location);
            })
            ->latest('transaction_date')
            ->get([
                'id',
                'transaction_date',
                'type',
                'location',
                'description',
                'amount',
            ]);

        return Inertia::render('Transactions', [
            'transactions' => $transactions->map(fn (Transaction $transaction): array => [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date?->toIso8601String(),
                'type' => $transaction->type->value,
                'location' => $transaction->location->value,
                'description' => $transaction->description,
                'amount' => $transaction->amount,
            ]),
            'totals' => [
                'cash_balance' => Transaction::query()
                    ->where('location', TransactionLocation::Cash->value)
                    ->selectRaw(
                        'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as balance',
                        [TransactionType::Credit->value, TransactionType::Debit->value],
                    )
                    ->value('balance') ?? 0,
                'bank_balance' => Transaction::query()
                    ->where('location', TransactionLocation::Bank->value)
                    ->selectRaw(
                        'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as balance',
                        [TransactionType::Credit->value, TransactionType::Debit->value],
                    )
                    ->value('balance') ?? 0,
                'net_movement' => Transaction::query()
                    ->selectRaw(
                        'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as balance',
                        [TransactionType::Credit->value, TransactionType::Debit->value],
                    )
                    ->value('balance') ?? 0,
            ],
            'types' => TransactionType::values(),
            'locations' => TransactionLocation::values(),
            'filters' => [
                'search' => $search,
                'type' => in_array($type, TransactionType::values(), true) ? $type : '',
                'location' => in_array($location, TransactionLocation::values(), true) ? $location : '',
            ],
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        Transaction::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()?->id,
        ]);

        return $this->redirectToIndex($request);
    }

    /**
     * Update the given transaction.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $transaction->update($request->validated());

        return $this->redirectToIndex($request);
    }

    /**
     * Remove the given transaction.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return $this->redirectToIndex($request);
    }

    /**
     * Redirect the user back to the transaction index.
     */
    private function redirectToIndex(Request $request): RedirectResponse
    {
        $previousUrl = $request->headers->get('referer');

        if (is_string($previousUrl) && $previousUrl !== '') {
            return redirect()->to($previousUrl);
        }

        return to_route('transactions');
    }
}
