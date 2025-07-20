<?php

namespace App\Http\Controllers\Setting;

use App\Functions\FlashMessages\Toast;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class DockerController extends Controller
{
    public function index()
    {
        return view('dashboard.setting.docker.index');
    }

    public function restartExchangeListen()
    {
        try {
            // اجرای دستور ریستارت کانتینر exchange-listen
            $result = Process::run('docker restart exchange-listen');

            if ($result->successful()) {
                Toast::message('کانتینر exchange-listen با موفقیت ریستارت شد')->success()->notify();
            } else {
                Toast::message('خطا در ریستارت کانتینر: ' . $result->errorOutput())->danger()->notify();
            }
        } catch (\Exception $e) {
            Toast::message('خطا در اجرای دستور: ' . $e->getMessage())->danger()->notify();
        }

        return redirect()->back();
    }

    public function getContainerStatus()
    {
        try {
            $result = Process::run('docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"');

            if ($result->successful()) {
                return response()->json([
                    'success' => true,
                    'containers' => $result->output()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result->errorOutput()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getExchangeListenLogs()
    {
        try {
            // دریافت آخرین 100 خط لاگ از کانتینر exchange-listen
            $result = Process::run('docker logs --tail 100 exchange-listen');

            if ($result->successful()) {
                return response()->json([
                    'success' => true,
                    'logs' => $result->output()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result->errorOutput()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
