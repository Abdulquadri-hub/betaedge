<?php

namespace App\Http\Controllers\Tenant;

use App\Contracts\Services\TenantPageServiceInterface;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PublicPageController extends Controller
{
    public function __construct(
        protected TenantPageServiceInterface $service,
    ){}

    public function landing(Request $request) {

        $data = $this->service->getLanding($request);
        
        return Inertia::render('School/Public', $data);
    }

    public function about(Request $request) {

        $data = $this->service->getAbout($request);
        
        return Inertia::render('Tenant/About', $data);
    }

    public function terms() {}

    public function privacy() {}

    public function registerStudent(Request $request) {
        $data = $this->service->getRegisterStudent($request);
        
        return Inertia::render('Tenant/Register/Student', $data);
    }

    public function storeStudent() {}

    public function registerParent(Request $request) {
         $data = $this->service->getRegisterParent($request);
        
        return Inertia::render('Tenant/Register/Parent', $data);
    }

    public function storeParent() {}
}
