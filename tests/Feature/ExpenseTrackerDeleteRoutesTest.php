<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTrackerDeleteRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_destroy_route_hard_deletes_the_account(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Main Wallet',
            'type' => 'cash',
            'currency' => 'BDT',
            'opening_balance' => 1000,
            'is_default' => true,
        ]);

        $this->deleteJson("/api/users/{$user->id}/accounts/{$account->id}")
            ->assertOk();

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_category_destroy_route_hard_deletes_the_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Groceries',
            'type' => 'expense',
        ]);

        $this->deleteJson("/api/users/{$user->id}/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_budget_destroy_route_hard_deletes_the_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
        ]);
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 5000,
            'period' => 'monthly',
            'start_date' => today()->startOfMonth()->toDateString(),
            'end_date' => today()->endOfMonth()->toDateString(),
        ]);

        $this->deleteJson("/api/users/{$user->id}/budgets/{$budget->id}")
            ->assertOk();

        $this->assertDatabaseMissing('budgets', [
            'id' => $budget->id,
        ]);
    }

    public function test_transaction_destroy_route_hard_deletes_the_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'currency' => 'BDT',
            'opening_balance' => 0,
            'is_default' => true,
        ]);
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Salary',
            'type' => 'income',
        ]);
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_type' => 'income',
            'amount' => 2500,
            'transaction_date' => today()->toDateString(),
            'title' => 'Paycheck',
        ]);

        $this->deleteJson("/api/users/{$user->id}/transactions/{$transaction->id}")
            ->assertOk();

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }
}
