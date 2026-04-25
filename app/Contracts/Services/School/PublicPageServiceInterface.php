<?php

namespace App\Contracts\Services\School;

interface PublicPageServiceInterface
{
    public function landing(string $tenantSlug): array;
}
