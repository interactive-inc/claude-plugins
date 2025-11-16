<?php

namespace Tests\Unit\Commands;

use App\Services\YourService;
use Carbon\CarbonImmutable;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Batch processing command test template
 *
 * Tests command with generator/yield patterns and table output
 */
class BatchProcessingCommandTest extends TestCase
{
    /**
     * @test
     */
    public function outputTableWithResults()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processRecords')
                ->andReturn((function () {
                    yield [
                        'id'     => '1',
                        'name'   => 'Test Record',
                        'status' => 'processed',
                    ];
                    yield [
                        'id'     => '2',
                        'name'   => 'Another Record',
                        'status' => 'processed',
                    ];
                })());
        });

        $this->artisan('job-antenna:batch-processing')
            ->expectsTable(
                ['id', 'name', 'status'],
                [
                    ['1', 'Test Record', 'processed'],
                    ['2', 'Another Record', 'processed'],
                ]
            )
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function noResultsYieldsEmptyGenerator()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processRecords')
                ->andReturn((function () {
                    yield from [];
                })());
        });

        $this->artisan('job-antenna:batch-processing')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function optionDateParsedCorrectly()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processForDate')
                ->withArgs(function ($date) {
                    return CarbonImmutable::parse('2024-01-15', 'Asia/Tokyo')
                        ->startOfDay()
                        ->equalTo($date);
                })
                ->andReturn((function () {
                    yield from [];
                })())
                ->once();
        });

        $this->artisan('job-antenna:batch-processing --date=2024/01/15')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function defaultDateIsYesterday()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processForDate')
                ->withArgs(function ($date) {
                    return CarbonImmutable::parse('yesterday', 'Asia/Tokyo')
                        ->startOfDay()
                        ->equalTo($date);
                })
                ->andReturn((function () {
                    yield from [];
                })())
                ->once();
        });

        $this->artisan('job-antenna:batch-processing')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function dryRunDoesNotPersist()
    {
        $this->mock(YourService::class, function (MockInterface $mock) {
            $mock->shouldReceive('processRecords')
                ->withArgs(function ($dryRun) {
                    return $dryRun === true;
                })
                ->andReturn((function () {
                    yield [
                        'id'     => '1',
                        'name'   => 'Preview Only',
                        'status' => 'dry-run',
                    ];
                })())
                ->once();
        });

        $this->artisan('job-antenna:batch-processing --dry-run')
            ->expectsOutput('Dry-run mode: No actual processing')
            ->assertExitCode(0);
    }
}
