<?php

namespace App\Http\Controllers;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Show the transaction page.
     */
    public function index(): Response
    {
        $transactions = Transaction::query()
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

        return to_route('transactions');
    }
}
