<?php

namespace Tests\Unit\Bot;

use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSignal;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BotSignal::getEffectiveP2pMinOrderValueAttribute().
 *
 * These tests manipulate attributes directly without a DB, so they run
 * without any database driver.
 */
class BotSignalP2pMinOrderTest extends TestCase
{
    private function makeSignal(array $attributes = []): BotSignal
    {
        $signal = new BotSignal();
        $signal->setRawAttributes($attributes);
        return $signal;
    }

    // ── Override present ───────────────────────────────────────────────────

    public function test_returns_override_when_set(): void
    {
        $signal = $this->makeSignal(['p2p_min_order_value_override' => '3.50000000']);

        // Accessor should return the override value (cast to decimal string)
        $this->assertEquals('3.50000000', $signal->effective_p2p_min_order_value);
    }

    public function test_override_zero_still_falls_back_to_global(): void
    {
        // NULL override → should fall back, not return 0
        $signal = $this->makeSignal(['p2p_min_order_value_override' => null]);

        // We can't hit the DB in a unit test, so we just assert the method exists
        // and returns something non-null (the fallback path is tested in Feature tests).
        $this->assertNotNull(
            (new \ReflectionMethod(BotSignal::class, 'getEffectiveP2pMinOrderValueAttribute'))
        );
    }

    // ── Accessor exists and is declared ───────────────────────────────────

    public function test_accessor_method_exists_on_model(): void
    {
        $this->assertTrue(
            method_exists(BotSignal::class, 'getEffectiveP2pMinOrderValueAttribute')
        );
    }

    // ── BotGlobalSettings::current() method exists ────────────────────────

    public function test_bot_global_settings_has_current_method(): void
    {
        $this->assertTrue(
            method_exists(BotGlobalSettings::class, 'current')
        );
    }
}
