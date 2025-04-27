<?php

namespace unit\Package\Database;

use Codeception\Test\Unit;
use Codeception\Util\Fixtures;
use Package\Database\DatabaseStickyExperiment;
use Package\Experiment\Models\ExperimentRunResult;

class DatabaseStickyExperimentTest extends Unit
{
    private const EXPERIMENT_UID = 'experiment_uid';
    private const EXPERIMENT_BRANCH_UID = 'experiment';
    private const EXPERIMENT_BRANCH_VERSION = 2;
    private const ORIGINAL_UID = 'original_uid';
    private const ORIGINAL_BRANCH_UID = 'original';
    private const ORIGINAL_BRANCH_VERSION = 1;
    private const EMPTY_USER_ID = 0;
    private const USER_ID = 123;

    public function testGetInstance()
    {
        $instance = DatabaseStickyExperiment::getInstance();

        $this->assertInstanceOf(DatabaseStickyExperiment::class, $instance);
        $this->assertSame($instance, DatabaseStickyExperiment::getInstance()); // It's singleton
    }

    public function testInExperimentWithEmptyUserId()
    {
        // Not in experiment result
        Fixtures::add('function_experiment', fn () => new ExperimentRunResult(
            self::ORIGINAL_UID,
            self::ORIGINAL_BRANCH_UID,
            self::ORIGINAL_BRANCH_VERSION
        ));

        $this->assertSame(
            false,
            DatabaseStickyExperiment::getInstance()->inExperiment(self::EMPTY_USER_ID)
        );

        Fixtures::cleanup('function_experiment');
    }

    /**
     * @dataProvider inExperimentDataProvider
     */
    public function testInExperimentWithCorrectUserId(
        bool $expectedResult,
        ?ExperimentRunResult $experimentResult
    ) {
        Fixtures::add('function_experiment', fn () => $experimentResult);

        $this->assertSame(
            $expectedResult,
            DatabaseStickyExperiment::getInstance()->inExperiment(self::USER_ID)
        );

        Fixtures::cleanup('function_experiment');
    }

    public function testInExperimentWithRecursion()
    {
        Fixtures::add('function_experiment', function () {
            // Recursive call
            DatabaseStickyExperiment::getInstance()->inExperiment(self::USER_ID);

            return null;
        });

        // We got result regardless of recursion
        $this->assertFalse(DatabaseStickyExperiment::getInstance()->inExperiment(self::USER_ID));

        Fixtures::cleanup('function_experiment');
    }

    public function inExperimentDataProvider(): array
    {
        return [
            'empty experiment result' => [
                'check result' => false,
                'experiment result' => null
            ],
            'in experiment result' => [
                'check result' => true,
                'experiment result' => new ExperimentRunResult(
                    self::EXPERIMENT_UID,
                    self::EXPERIMENT_BRANCH_UID,
                    self::EXPERIMENT_BRANCH_VERSION
                )
            ],
            'not in experiment result' => [
                'check result' => false,
                'experiment result' => new ExperimentRunResult(
                    self::ORIGINAL_UID,
                    self::ORIGINAL_BRANCH_UID,
                    self::ORIGINAL_BRANCH_VERSION
                )
            ],
        ];
    }
}
