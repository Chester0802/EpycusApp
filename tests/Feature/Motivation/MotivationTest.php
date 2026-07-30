<?php

declare(strict_types=1);

namespace Tests\Feature\Motivation;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Motivation\Infrastructure\Models\MotivationalQuoteModel;
use App\Modules\Motivation\Infrastructure\Models\UsageTipModel;
use App\Shared\Domain\Services\NoRepeatPicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MotivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_repeat_picker_cycles_items(): void
    {
        $picker = new NoRepeatPicker();
        $pool = [1, 2, 3];
        $alreadyShown = [1, 2];

        $selected = $picker->pick($pool, $alreadyShown);
        $this->assertEquals(3, $selected);

        // Cuando todos se mostraron, se reinicia el ciclo
        $alreadyShownAll = [1, 2, 3];
        $selectedAfterReset = $picker->pick($pool, $alreadyShownAll);
        $this->assertContains($selectedAfterReset, [1, 2, 3]);
    }

    public function test_user_gets_login_quote(): void
    {
        MotivationalQuoteModel::create([
            'text' => 'Prueba de frase motivacional',
            'author' => 'Autor Test',
            'is_verified' => true,
        ]);

        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('motivationalQuote')
            ->where('motivationalQuote.text', 'Prueba de frase motivacional')
        );
    }

    public function test_user_can_fetch_and_dismiss_module_tip(): void
    {
        $tip = UsageTipModel::create([
            'module_key' => 'habits',
            'content' => 'Tip de prueba para hábitos',
        ]);

        $user = UserModel::factory()->create();

        $getTipResponse = $this->actingAs($user)->get(route('motivation.tip', 'habits'));
        $getTipResponse->assertStatus(200);
        $getTipResponse->assertJson([
            'success' => true,
            'data' => [
                'id' => $tip->id,
                'content' => 'Tip de prueba para hábitos',
            ],
        ]);

        $dismissResponse = $this->actingAs($user)->postJson(route('motivation.dismiss-tip'), [
            'tip_id' => $tip->id,
        ]);

        $dismissResponse->assertStatus(200);
        $dismissResponse->assertJson(['success' => true]);
    }
}
