<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_table_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('transactions'));
        $this->assertSame(
            [
                'id',
                'user_id',
                'transaction_date',
                'type',
                'location',
                'description',
                'amount',
                'created_at',
                'updated_at',
            ],
            Schema::getColumnListing('transactions'),
        );

        $this->assertSame('datetime', Schema::getColumnType('transactions', 'transaction_date'));
        $this->assertSame('varchar', Schema::getColumnType('transactions', 'type'));
        $this->assertSame('varchar', Schema::getColumnType('transactions', 'location'));
        $this->assertSame('varchar', Schema::getColumnType('transactions', 'description'));
        $this->assertSame('integer', Schema::getColumnType('transactions', 'amount'));
    }

    public function test_transactions_table_uses_the_expected_enum_values_and_defaults(): void
    {
        $tableDefinition = DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'transactions')
            ->value('sql');

        $this->assertIsString($tableDefinition);
        $this->assertStringContainsString('"type" varchar check ("type" in (\'debit\', \'credit\'))', $tableDefinition);
        $this->assertStringContainsString('"location" varchar check ("location" in (\'cash\', \'bank\')) not null default \'cash\'', $tableDefinition);

        DB::table('transactions')->insert([
            'transaction_date' => now(),
            'type' => 'debit',
            'description' => 'Initial cash transaction',
        ]);

        $transaction = DB::table('transactions')->first();

        $this->assertSame('cash', $transaction->location);
        $this->assertSame(0, $transaction->amount);

        $this->expectException(QueryException::class);

        DB::table('transactions')->insert([
            'transaction_date' => now(),
            'type' => 'withdrawal',
            'location' => 'vault',
            'description' => 'Invalid transaction values',
            'amount' => 1000,
        ]);
    }
}
