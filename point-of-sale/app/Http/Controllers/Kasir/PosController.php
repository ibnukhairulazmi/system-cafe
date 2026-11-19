<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Services\Kasir\PosCatalogueService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly PosCatalogueService $catalogueService
    ) {}

    public function index(Request $request): View
    {
        return view('kasir.pos', [
            'shift'  => $request->attributes->get('shift'),
            'config' => $this->catalogueService->getCatalogueConfig(),
        ]);
    }
}
