<?php

namespace Tests\Unit\Commands;

use App\Services\YourService;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Basic command test template
 *
 * Tests command execution with service mocking
 */
class BasicCommandTest extends TestCase
{
    /**
     * @test
     */
    public function commandExecutesSuccessfully()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn(['count' => 10]);
        });

        $this->artisan('job-antenna:your-command')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commandWithDryRunOption()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('process')
                ->withArgs(function ($dryRun) {
                    return $dryRun === true;
                })
                ->once()
                ->andReturn(['count' => 0]);
        });

        $this->artisan('job-antenna:your-command --dry-run')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commandWithArguments()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processIds')
                ->withArgs(function ($ids) {
                    return $ids === ['1', '2', '3'];
                })
                ->once()
                ->andReturn(['count' => 3]);
        });

        $this->artisan('job-antenna:your-command 1 2 3')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function commandWithLimitOption()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('process')
                ->withArgs(function ($dryRun, $limit) {
                    return $dryRun === false && $limit === 100;
                })
                ->once()
                ->andReturn(['count' => 100]);
        });

        $this->artisan('job-antenna:your-command --limit=100')
            ->assertExitCode(0);
    }
}
