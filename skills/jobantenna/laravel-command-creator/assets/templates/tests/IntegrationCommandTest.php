<?php

namespace Tests\Command;

use App\Models\YourModel;
use App\Notifications\YourNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Integration command test template
 *
 * Tests command with database interactions, factories, and notifications
 *
 * @group your-command
 */
class IntegrationCommandTest extends TestCase
{
    use WithFaker;

    protected function beforeTest()
    {
        $this->seedOnce([
            \Database\Seeders\YourSeeder::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up test data before each test
        YourModel::query()->delete();
    }

    /**
     * @test
     */
    public function commandProcessesRecordsSuccessfully()
    {
        // Arrange: Create test data using factories
        $models = YourModel::factory(3)->create([
            'status' => 'pending',
        ]);

        // Act: Execute the command
        $this->artisan('job-antenna:your-command')
            ->assertSuccessful();

        // Assert: Verify database state
        $models->each(function ($model) {
            $this->assertDatabaseHas('your_table', [
                'id'     => $model->id,
                'status' => 'processed',
            ]);
        });
    }

    /**
     * @test
     */
    public function commandSendsNotifications()
    {
        Notification::fake();

        // Arrange: Create test data
        $models = YourModel::factory(2)->create();

        // Act: Execute the command
        $this->artisan('job-antenna:your-command')
            ->assertSuccessful();

        // Assert: Verify notifications were sent
        Notification::assertTimesSent(
            $models->count(),
            YourNotification::class
        );
    }

    /**
     * @test
     */
    public function commandSkipsRecordsWithMailSettingOff()
    {
        Notification::fake();

        // Arrange: Create models with different settings
        $modelWithMailOn = YourModel::factory()->create();
        $modelWithMailOff = YourModel::factory()->create();

        $modelWithMailOff->setting()->update([
            'email' => ['notifications' => false],
        ]);
        $modelWithMailOff->refresh();

        // Act: Execute the command
        $this->artisan('job-antenna:your-command')
            ->assertSuccessful();

        // Assert: Mail-off user should receive notification with empty channels
        Notification::assertSentTo(
            $modelWithMailOff,
            YourNotification::class,
            fn ($notification, $channels) => empty($channels)
        );
    }

    /**
     * @test
     */
    public function commandHandlesEmptyDataSet()
    {
        Notification::fake();

        // No test data created

        // Act: Execute the command
        $this->artisan('job-antenna:your-command')
            ->assertSuccessful();

        // Assert: No notifications sent
        Notification::assertTimesSent(
            0,
            YourNotification::class
        );
    }

    /**
     * @test
     */
    public function commandWithSpecificIds()
    {
        // Arrange: Create multiple records
        $models = YourModel::factory(5)->create(['status' => 'pending']);
        $targetIds = $models->take(2)->pluck('id')->toArray();

        // Act: Execute command with specific IDs
        $this->artisan('job-antenna:your-command ' . implode(' ', $targetIds))
            ->assertSuccessful();

        // Assert: Only specified records were processed
        foreach ($targetIds as $id) {
            $this->assertDatabaseHas('your_table', [
                'id'     => $id,
                'status' => 'processed',
            ]);
        }

        // Other records remain unchanged
        $this->assertEquals(
            3,
            YourModel::where('status', 'pending')->count()
        );
    }

    /**
     * @test
     */
    public function dryRunDoesNotModifyDatabase()
    {
        // Arrange: Create test data
        $models = YourModel::factory(3)->create(['status' => 'pending']);

        // Act: Execute command in dry-run mode
        $this->artisan('job-antenna:your-command --dry-run')
            ->assertSuccessful();

        // Assert: Database remains unchanged
        $this->assertEquals(
            3,
            YourModel::where('status', 'pending')->count()
        );
    }
}
