<?php

namespace App\Models;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use Carbon\Carbon;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'transaction_date',
        'type',
        'location',
        'description',
        'amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'type' => TransactionType::class,
            'location' => TransactionLocation::class,
            'amount' => 'integer',
        ];
    }

    /**
     * Get the aggregate balances for dashboard-style summaries.
     *
     * @return array{cash_balance:int, bank_balance:int, net_movement:int}
     */
    public static function totals(): array
    {
        return [
            'cash_balance' => self::query()
                ->where('location', TransactionLocation::Cash->value)
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as balance',
                    [TransactionType::Credit->value, TransactionType::Debit->value],
                )
                ->value('balance') ?? 0,
            'bank_balance' => self::query()
                ->where('location', TransactionLocation::Bank->value)
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as balance',
                    [TransactionType::Credit->value, TransactionType::Debit->value],
                )
                ->value('balance') ?? 0,
            'net_movement' => self::query()
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as balance',
                    [TransactionType::Credit->value, TransactionType::Debit->value],
                )
                ->value('balance') ?? 0,
        ];
    }

    /**
     * Get debit and credit totals for the last seven days.
     *
     * @return list<array{label:string, debit:int, credit:int}>
     */
    public static function chartSeries(): array
    {
        $startDay = now()->startOfDay()->subDays(6);
        $endDay = now()->endOfDay();

        /** @var Collection<int, self> $transactions */
        $transactions = self::query()
            ->whereBetween('transaction_date', [$startDay, $endDay])
            ->get([
                'transaction_date',
                'type',
                'amount',
            ]);

        $series = collect(range(0, 6))
            ->mapWithKeys(function (int $offset) use ($startDay): array {
                $day = $startDay->copy()->addDays($offset);

                return [
                    $day->format('Y-m-d') => [
                        'label' => $day->translatedFormat('d M'),
                        'debit' => 0,
                        'credit' => 0,
                    ],
                ];
            });

        foreach ($transactions as $transaction) {
            $monthKey = $transaction->transaction_date instanceof Carbon
                ? $transaction->transaction_date->format('Y-m-d')
                : Carbon::parse($transaction->transaction_date)->format('Y-m-d');

            if (! $series->has($monthKey)) {
                continue;
            }

            $bucket = $series->get($monthKey);
            $bucket[$transaction->type->value] += $transaction->amount;
            $series->put($monthKey, $bucket);
        }

        return array_values($series->all());
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
