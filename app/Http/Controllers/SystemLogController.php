<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $search   = strtolower($request->query('search', ''));
        $page     = max(1, (int)$request->query('page', 1));
        $perPage  = max(1, (int)$request->query('per_page', 50));

        $path = storage_path('logs/laravel.log');

        if (!File::exists($path)) {
            return response()->json([
                "data" => [],
                "current_page" => 1,
                "per_page" => $perPage,
                "total" => 0,
                "last_page" => 1
            ]);
        }

        $lines = explode("\n", File::get($path));

        $lines = array_reverse($lines);

        $parsed = [];
        foreach ($lines as $idx => $line) {
            if (trim($line) === '') continue;

            if ($search && !str_contains(strtolower($line), $search)) {
                continue;
            }

            $parsed[] = [
                'id'   => sha1($line . '|' . $idx),
                'type' => $this->detectLogType($line),
                'msg'  => substr($line, 0, 250),
                'full' => $line,
                'time' => $this->extractTime($line),
            ];
        }

        $total = count($parsed);
        $lastPage = max(1, ceil($total / $perPage));

        $data = array_slice($parsed, ($page - 1) * $perPage, $perPage);

        return response()->json([
            "data" => $data,
            "current_page" => $page,
            "per_page" => $perPage,
            "total" => $total,
            "last_page" => $lastPage
        ]);
    }

    private function detectLogType($line)
    {
        return match (true) {
            str_contains($line, 'ERROR')   => 'ERROR',
            str_contains($line, 'WARNING') => 'WARNING',
            str_contains($line, 'DEBUG')   => 'DEBUG',
            default                        => 'INFO',
        };
    }

    private function extractTime($line)
    {
        if (preg_match('/\[(.*?)\]/', $line, $m)) {
            return $m[1];
        }
        return now()->format('Y-m-d H:i:s');
    }
}
