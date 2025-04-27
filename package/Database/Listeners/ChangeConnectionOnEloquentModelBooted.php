<?php

declare(strict_types=1);

namespace Package\Database\Listeners;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Connections;
use Package\Database\DatabaseStickyExperiment;
use Package\Database\Logger\LoggerTrait;
use Package\User\Provider\UserIdProvider;
use Throwable;

class ChangeConnectionOnEloquentModelBooted
{
    use LoggerTrait;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    public function handle(string $eventName, array $eventData): void
    {
        // The model could have different parents:
        // - \TEloquentModel
        // - \Package\OrganizationV2\Domain\Entity\BaseEntity
        // - \Illuminate\Database\Eloquent\Model
        // To include them all we use their basic parent class
        if (empty($eventData[0]) || !$eventData[0] instanceof Model) {
            return;
        }

        $model = $eventData[0];

        // Only models with set to "default" class property $connection are affected
        if ($model->getConnectionName() !== Connections::CONNECTION_DEFAULT) {
            return;
        }

        try {
            /** @var UserIdProvider $idProvider */
            $idProvider = app(UserIdProvider::class);
            $userId = $idProvider->getUserId();

            if (DatabaseStickyExperiment::getInstance()->inExperiment($userId)) {
                // TODO: Set connection directly on baseline
                $model->setConnection(Connections::CONNECTION_MAIN);
            }
        } catch (Throwable $t) {
            $this->logger->error(
                'Cannot change connection',
                [
                    'error' => $t,
                ]
            );
        }
    }
}
