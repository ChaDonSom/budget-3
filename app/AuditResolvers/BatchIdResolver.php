<?php

namespace App\AuditResolvers;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class BatchIdResolver implements Resolver
{
    public static function resolve(Auditable $auditable)
    {
        return cache("account-batch-update-batch-{$auditable->pivot?->id}-batch-id");
    }
}
