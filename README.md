It's example of code that change default connection in Eloquent models.
It replaces it dynamically if old connection name was "default" and user is under experiment.
The entry point: \Package\Database\Listeners\ChangeConnectionOnEloquentModelBooted
If connection was changed some additional logic will be used in: \Package\Database\MySqlConnection