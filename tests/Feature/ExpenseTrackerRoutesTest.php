<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTrackerRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shell_routes_are_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Expense Tracker');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Expense Tracker');
    }

    public function test_dashboard_api_endpoint_returns_summary_for_user(): void
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

        $incomeCategory = Category::create([
            'user_id' => $user->id,
            'name' => 'Salary',
            'type' => 'income',
        ]);

        $expenseCategory = Category::create([
            'user_id' => $user->id,
            'name' => 'Groceries',
            'type' => 'expense',
        ]);

        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'transaction_type' => 'income',
            'amount' => 500,
            'transaction_date' => today()->toDateString(),
            'title' => 'Monthly salary',
        ]);

        $user->transactions()->create([
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'transaction_type' => 'expense',
            'amount' => 200,
            'transaction_date' => today()->toDateString(),
            'title' => 'Supermarket',
        ]);

        $response = $this->getJson("/api/users/{$user->id}/dashboard");

        $response->assertOk()
            ->assertJsonPath('data.totals.income', 500)
            ->assertJsonPath('data.totals.expense', 200)
            ->assertJsonPath('data.totals.balance', 1300)
            ->assertJsonPath('data.counts.accounts', 1)
            ->assertJsonPath('data.counts.categories', 2)
            ->assertJsonPath('data.counts.transactions', 2)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'totals' => ['income', 'expense', 'balance'],
                    'counts' => ['accounts', 'categories', 'budgets', 'transactions'],
                    'recent_transactions' => [
                        '*' => ['id', 'account', 'category', 'transaction_type', 'amount', 'transaction_date'],
                    ],
                ],
            ]);
    }
}
