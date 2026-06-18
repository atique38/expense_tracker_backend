<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Throwable;

class DashboardController extends Controller
{
    public function show(User $user): JsonResponse
    {
        try {
            $now = Carbon::now();
            $transactions = $user->transactions();

            $incomeTotal = (float) (clone $transactions)
                ->where('transaction_type', 'income')
                ->sum('amount');

            $expenseTotal = (float) (clone $transactions)
                ->where('transaction_type', 'expense')
                ->sum('amount');

            $accounts = $this->buildAccounts($user);
            $weeklyFlow = $this->buildWeeklyFlow($user, $now);
            $budgets = $this->buildBudgets($user, $now);
            $recentTransactions = $this->buildRecentTransactions($user);

            return response()->json([
                'status' => 200,
                'message' => 'Dashboard summary fetched successfully.',
                'data' => [
                    'summary' => [
                        'income' => $incomeTotal,
                        'expense' => $expenseTotal,
                        'balance' => (float) $accounts->sum('balance'),
                        'net_change' => $incomeTotal - $expenseTotal,
                        'savings_rate' => $incomeTotal > 0
                            ? round((($incomeTotal - $expenseTotal) / $incomeTotal) * 100, 2)
                            : 0,
                    ],
                    'counts' => [
                        'accounts' => $user->accounts()->count(),
                        'categories' => $user->categories()->count(),
                        'budgets' => $user->budgets()->count(),
                        'transactions' => $transactions->count(),
                    ],
                    'weekly_flow' => $weeklyFlow,
                    'accounts' => $accounts->values(),
                    'recent_transactions' => $recentTransactions->values(),
                    'budgets' => $budgets->values(),
                ],
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to fetch dashboard summary.');
        }
    }

    private function buildAccounts(User $user): Collection
    {
        $incomeTotals = $user->transactions()
            ->selectRaw('account_id, SUM(amount) as total')
            ->where('transaction_type', 'income')
            ->whereNotNull('account_id')
            ->groupBy('account_id')
            ->pluck('total', 'account_id')
            ->all();

        $expenseTotals = $user->transactions()
            ->selectRaw('account_id, SUM(amount) as total')
            ->where('transaction_type', 'expense')
            ->whereNotNull('account_id')
            ->groupBy('account_id')
            ->pluck('total', 'account_id')
            ->all();

        $accounts = $user->accounts()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(function ($account) use ($incomeTotals, $expenseTotals) {
                $income = (float) ($incomeTotals[$account->id] ?? 0);
                $expense = (float) ($expenseTotals[$account->id] ?? 0);
                $balance = (float) $account->opening_balance + $income - $expense;

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'currency' => $account->currency,
                    'opening_balance' => (float) $account->opening_balance,
                    'balance' => $balance,
                    'current_balance' => $balance,
                    'is_default' => (bool) $account->is_default,
                    'notes' => $account->notes,
                    'income_total' => $income,
                    'expense_total' => $expense,
                ];
            });

        $positiveBalanceBase = max(0, (float) $accounts->sum(fn (array $account) => max(0, $account['balance'])));

        return $accounts->map(function (array $account) use ($positiveBalanceBase) {
            $account['usage'] = $positiveBalanceBase > 0
                ? round((max(0, $account['balance']) / $positiveBalanceBase) * 100, 1)
                : 0;

            return $account;
        });
    }

    private function buildRecentTransactions(User $user): Collection
    {
        return $user->transactions()
            ->with([
                'account:id,name,type,currency',
                'category:id,name,type',
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Transaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'title' => $transaction->title,
                    'transaction_type' => $transaction->transaction_type,
                    'amount' => (float) $transaction->amount,
                    'transaction_date' => $transaction->transaction_date?->toDateString(),
                    'description' => $transaction->description,
                    'reference' => $transaction->reference,
                    'account' => $transaction->account ? [
                        'id' => $transaction->account->id,
                        'name' => $transaction->account->name,
                        'type' => $transaction->account->type,
                        'currency' => $transaction->account->currency,
                    ] : null,
                    'category' => $transaction->category ? [
                        'id' => $transaction->category->id,
                        'name' => $transaction->category->name,
                        'type' => $transaction->category->type,
                    ] : null,
                ];
            });
    }

    private function buildWeeklyFlow(User $user, Carbon $referenceDate): Collection
    {
        $startDate = $referenceDate->copy()->subDays(6)->startOfDay();
        $endDate = $referenceDate->copy()->endOfDay();

        $transactionsByDate = $user->transactions()
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['transaction_type', 'amount', 'transaction_date'])
            ->groupBy(fn (Transaction $transaction) => $transaction->transaction_date->toDateString());

        return collect(range(0, 6))->map(function (int $offset) use ($startDate, $transactionsByDate) {
            $date = $startDate->copy()->addDays($offset);
            $dayTransactions = $transactionsByDate->get($date->toDateString(), collect());

            return [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'income' => (float) $dayTransactions->where('transaction_type', 'income')->sum('amount'),
                'expense' => (float) $dayTransactions->where('transaction_type', 'expense')->sum('amount'),
            ];
        });
    }

    private function buildBudgets(User $user, Carbon $referenceDate): Collection
    {
        $expenseTransactions = $user->transactions()
            ->where('transaction_type', 'expense')
            ->get(['category_id', 'amount', 'transaction_date']);

        return $user->budgets()
            ->with('category:id,name,type')
            ->latest()
            ->get()
            ->map(function (Budget $budget) use ($expenseTransactions, $referenceDate) {
                [$startDate, $endDate] = $this->resolveBudgetWindow($budget, $referenceDate);

                $spent = (float) $expenseTransactions
                    ->filter(function (Transaction $transaction) use ($budget, $startDate, $endDate) {
                        if ($transaction->transaction_date->lt($startDate) || $transaction->transaction_date->gt($endDate)) {
                            return false;
                        }

                        if ($budget->category_id !== null && $transaction->category_id !== $budget->category_id) {
                            return false;
                        }

                        return true;
                    })
                    ->sum(fn (Transaction $transaction) => (float) $transaction->amount);

                $amount = (float) $budget->amount;
                $progress = $amount > 0 ? round(min(100, ($spent / $amount) * 100), 1) : 0;

                return [
                    'id' => $budget->id,
                    'amount' => $amount,
                    'spent' => round($spent, 2),
                    'remaining' => round($amount - $spent, 2),
                    'period' => $budget->period,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'progress' => $progress,
                    'status' => $spent > $amount ? 'overspent' : 'on_track',
                    'category' => $budget->category ? [
                        'id' => $budget->category->id,
                        'name' => $budget->category->name,
                        'type' => $budget->category->type,
                    ] : null,
                ];
            })
            ->sortByDesc('progress')
            ->values();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveBudgetWindow(Budget $budget, Carbon $referenceDate): array
    {
        $startDate = match ($budget->period) {
            'weekly' => $referenceDate->copy()->startOfWeek(),
            'monthly' => $referenceDate->copy()->startOfMonth(),
            'yearly' => $referenceDate->copy()->startOfYear(),
            default => $budget->start_date
                ? Carbon::parse($budget->start_date)
                : $referenceDate->copy()->startOfMonth(),
        };

        $endDate = match ($budget->period) {
            'weekly' => $referenceDate->copy()->endOfWeek(),
            'monthly' => $referenceDate->copy()->endOfMonth(),
            'yearly' => $referenceDate->copy()->endOfYear(),
            default => $budget->end_date
                ? Carbon::parse($budget->end_date)
                : $referenceDate->copy()->endOfDay(),
        };

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [
            $startDate->copy()->startOfDay(),
            $endDate->copy()->endOfDay(),
        ];
    }
}
