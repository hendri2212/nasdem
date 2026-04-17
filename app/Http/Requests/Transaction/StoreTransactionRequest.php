<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionLocation;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'location' => ['required', Rule::enum(TransactionLocation::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateDebitBalance($validator),
        ];
    }

    /**
     * Ensure debit transactions do not exceed the available balance.
     */
    private function validateDebitBalance(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        if ($this->string('type')->toString() !== TransactionType::Debit->value) {
            return;
        }

        $location = $this->string('location')->toString();

        if (! in_array($location, TransactionLocation::values(), true)) {
            return;
        }

        $availableBalance = $this->availableBalanceFor($location);
        $locationLabel = $location === TransactionLocation::Cash->value ? 'cash' : 'bank';

        if ($availableBalance <= 0) {
            $validator->errors()->add('amount', "Saldo {$locationLabel} saat ini 0 sehingga transaksi debit tidak bisa dibuat.");

            return;
        }

        if ((int) $this->input('amount') > $availableBalance) {
            $validator->errors()->add('amount', "Jumlah debit melebihi saldo {$locationLabel} yang tersedia.");
        }
    }

    /**
     * Get the available balance for the requested location, excluding the current transaction on updates.
     */
    private function availableBalanceFor(string $location): int
    {
        $totals = Transaction::totals();
        $balance = $location === TransactionLocation::Cash->value
            ? $totals['cash_balance']
            : $totals['bank_balance'];

        $currentTransaction = $this->route('transaction');

        if (! $currentTransaction instanceof Transaction || $currentTransaction->location->value !== $location) {
            return $balance;
        }

        $currentEffect = $currentTransaction->type === TransactionType::Credit
            ? $currentTransaction->amount
            : -$currentTransaction->amount;

        return $balance - $currentEffect;
    }
}
