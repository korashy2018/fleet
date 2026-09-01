<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

final class OpenApiController extends Controller
{
    public function ui(): View
    {
        return view('swagger');
    }

    public function spec(): Response
    {
        $path = base_path('docs/openapi.yaml');

        abort_unless(is_file($path), 404);

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ]);
    }
}
