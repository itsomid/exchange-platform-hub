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
            // تلاش برای پیدا کردن مسیر Docker
            $dockerPath = $this->findDockerPath();

            // اجرای دستور ریستارت کانتینر exchange-listen
            $result = Process::run($dockerPath . ' restart exchange-listen');

            if ($result->successful()) {
                Toast::message('کانتینر exchange-listen با موفقیت ریستارت شد')->success()->notify();
            } else {
                // اگر Docker مستقیم کار نکرد، از Docker Compose استفاده کن
                $dockerComposePath = $this->findDockerComposePath();
                $result = Process::run($dockerComposePath . ' restart exchange-listen');

                if ($result->successful()) {
                    Toast::message('کانتینر exchange-listen با موفقیت ریستارت شد (via Docker Compose)')->success()->notify();
                } else {
                    Toast::message('خطا در ریستارت کانتینر: ' . $result->errorOutput())->danger()->notify();
                }
            }
        } catch (\Exception $e) {
            Toast::message('خطا در اجرای دستور: ' . $e->getMessage())->danger()->notify();
        }

        return redirect()->back();
    }

    private function findDockerPath()
    {
        // مسیرهای معمول Docker
        $possiblePaths = [
            '/usr/bin/docker',
            '/usr/local/bin/docker',
            '/opt/homebrew/bin/docker',
            'docker' // اگر در PATH باشه
        ];

        foreach ($possiblePaths as $path) {
            $result = Process::run("which $path");
            if ($result->successful() && !empty(trim($result->output()))) {
                return $path;
            }
        }

        // اگر هیچ کدام پیدا نشد، از docker استفاده کن
        return 'docker';
    }

    private function findDockerComposePath()
    {
        // مسیرهای معمول Docker Compose
        $possiblePaths = [
            '/usr/bin/docker-compose',
            '/usr/local/bin/docker-compose',
            '/opt/homebrew/bin/docker-compose',
            'docker-compose' // اگر در PATH باشه
        ];

        foreach ($possiblePaths as $path) {
            $result = Process::run("which $path");
            if ($result->successful() && !empty(trim($result->output()))) {
                return $path;
            }
        }

        // اگر هیچ کدام پیدا نشد، از docker-compose استفاده کن
        return 'docker-compose';
    }

    private function isRunningInDocker()
    {
        // چک کردن اینکه آیا در محیط Docker هستیم
        return file_exists('/.dockerenv') ||
            file_exists('/proc/1/cgroup') && strpos(file_get_contents('/proc/1/cgroup'), 'docker') !== false;
    }

    private function getDockerCommand()
    {
        // اگر در محیط Docker هستیم، از Docker socket استفاده کن
        if ($this->isRunningInDocker()) {
            return 'docker -H unix:///var/run/docker.sock';
        }

        return $this->findDockerPath();
    }

    public function getContainerStatus()
    {
        try {
            $dockerCommand = $this->getDockerCommand();
            $result = Process::run($dockerCommand . ' ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"');

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
            $dockerCommand = $this->getDockerCommand();
            // دریافت آخرین 100 خط لاگ از کانتینر exchange-listen
            $result = Process::run($dockerCommand . ' logs --tail 100 exchange-listen');

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
