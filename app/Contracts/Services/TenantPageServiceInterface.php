<?php

namespace App\Contracts\Services;

use Illuminate\Http\Request;

interface TenantPageServiceInterface
{
    public function getLanding(Request $request): ?array;

    public function getAbout(Request $request): ?array;

    public function getRegisterStudent(Request $request): ?array;

    public function saveStudent();

    public function getRegisterParent(Request $request): ?array;

    public function saveParent();
}
