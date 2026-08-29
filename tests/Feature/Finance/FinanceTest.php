<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Modules\Finance\Infrastructure\Models\FinanceBudgetModel;
use App\Modules\Finance\Infrastructure\Models\FinanceSavingsGoalModel;
use App\Modules\Finance\Infrastructure\Models\FinanceTransactionModel;
use App\Modules\Gamification\Infrastructure\Models\UserProgressModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_finance_overview(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('finance.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Index')
            ->has('overview.summary')
            ->has('overview.transactions')
            ->has('overview.budgets')
            ->has('overview.savings_goals')
        );
    }

    public function test_user_can_create_and_delete_transaction(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now('America/Lima')->toDateString();

        // Crear gasto
        $createRes = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'type' => 'expense',
            'amount' => 15.50,
            'category' => 'alimentacion',
            'date' => $today,
            'notes' => 'Menú universitario',
        ]);

        $createRes->assertRedirect();

        $transaction = FinanceTransactionModel::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(15.50, $transaction->amount);
        $this->assertEquals('alimentacion', $transaction->category);

        // Eliminar gasto
        $deleteRes = $this->actingAs($user)->delete(route('finance.transactions.destroy', ['id' => $transaction->id]));
        $deleteRes->assertRedirect();
        $this->assertDatabaseMissing('finance_transactions', ['id' => $transaction->id]);
    }

    public function test_user_can_set_monthly_budget(): void
    {
        $user = UserModel::factory()->create();

        $budgetRes = $this->actingAs($user)->post(route('finance.budgets.save'), [
            'month' => 8,
            'year' => 2026,
            'category' => 'transporte',
            'monthly_limit' => 120.00,
        ]);

        $budgetRes->assertRedirect();

        $budget = FinanceBudgetModel::where('user_id', $user->id)
            ->where('month', 8)
            ->where('year', 2026)
            ->where('category', 'transporte')
            ->firstOrFail();

        $this->assertEquals(120.00, $budget->monthly_limit);
    }

    public function test_user_can_create_savings_goal_and_deposit_to_earn_xp(): void
    {
        $user = UserModel::factory()->create();

        UserProgressModel::create([
            'user_id' => $user->id,
            'total_xp' => 100,
            'current_level' => 1,
            'current_phase' => 1,
            'current_streak' => 1,
            'longest_streak' => 1,
            'grace_days_left' => 2,
            'coins' => 10,
        ]);

        // 1. Crear meta
        $goalRes = $this->actingAs($user)->post(route('finance.savings.store'), [
            'title' => 'Audífonos Bluetooth',
            'target_amount' => 100.00,
            'current_amount' => 50.00,
            'reward_xp' => 150,
        ]);

        $goalRes->assertRedirect();

        $goal = FinanceSavingsGoalModel::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(50.00, $goal->current_amount);
        $this->assertFalse($goal->is_completed);

        // 2. Depositar y completar meta
        $depositRes = $this->actingAs($user)->post(route('finance.savings.deposit', ['id' => $goal->id]), [
            'amount' => 50.00,
        ]);

        $depositRes->assertRedirect();

        $goal->refresh();
        $this->assertEquals(100.00, $goal->current_amount);
        $this->assertTrue($goal->is_completed);

        $progress = UserProgressModel::find($user->id);
        $this->assertEquals(250, $progress->total_xp); // 100 + 150
    }
}
