<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (\Throwable) {
            $checks['database'] = false;
        }

        try {
            $probe = '.readiness-probe-'.Str::random();
            $disk = Storage::disk('local');
            try {
                $disk->put($probe, 'ok');
                $checks['storage'] = $disk->exists($probe);
            } finally {
                $disk->delete($probe);
            }
        } catch (\Throwable) {
            $checks['storage'] = false;
        }

        $siap = ! in_array(false, $checks, true);

        return response()->json(['status' => $siap ? 'siap' : 'tidak_siap', 'checks' => $checks], $siap ? 200 : 503);
    }
}
