<?php

namespace Package\MetricsService\Metrics;

interface Database
{
    public const SELECT_FALLBACK_USE = 'database.select_fallback_use';
    public const SELECT_LAG_PROBLEM = 'database.select_lag_problem';
    public const SELECT_READ_ENDPOINT_WEB = 'database.select_read_endpoint_web';
    public const SELECT_WRITE_ENDPOINT_WEB = 'database.select_write_endpoint_web';
    public const SELECT_READ_ENDPOINT_QLESS = 'database.select_read_endpoint_qless';
    public const SELECT_WRITE_ENDPOINT_QLESS = 'database.select_write_endpoint_qless';
}
