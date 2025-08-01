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
        // تشخیص سیستم عامل
        $isWindows = PHP_OS_FAMILY === 'Windows';
  
        if ($isWindows) {
            // مسیرهای معمول Docker در Windows
            $possiblePaths = [
                'C:\\Program Files\\Docker\\Docker\\resources\\bin\\docker.exe',
                'C:\\Program Files\\Docker\\Docker\\resources\\docker.exe',
                'docker' // اگر در PATH باشه
            ];
            
            foreach ($possiblePaths as $path) {
                if ($path !== 'docker' && file_exists($path)) {
                    return '"' . $path . '"'; // برای مسیرهای دارای فاصله
                }
            }
            
            // تست docker در PATH
            try {
                $result = Process::run('docker --version');
                if ($result->successful()) {
                    return 'docker';
                }
            } catch (\Exception $e) {
                // ادامه
            }
            
            return 'docker';
        } else {
            // مسیرهای معمول Docker در Linux/Unix
            $possiblePaths = [
                '/usr/bin/docker',
                '/usr/local/bin/docker',
                '/opt/homebrew/bin/docker',
                '/snap/bin/docker',
                'docker'
            ];

            // ابتدا بررسی کنیم که فایل‌ها وجود دارند و قابل اجرا هستند
            foreach ($possiblePaths as $path) {
                if ($path !== 'docker' && file_exists($path) && is_executable($path)) {
                    return $path;
                }
            }

            // اگر فایل‌ها پیدا نشدند، از which استفاده کن
            $whichCommands = ['which', 'command -v', 'type -p'];
            
            foreach ($whichCommands as $whichCmd) {
                foreach ($possiblePaths as $path) {
                    try {
                        $result = Process::run("$whichCmd $path 2>/dev/null");
                        if ($result->successful() && !empty(trim($result->output()))) {
                            $foundPath = trim($result->output());
                            if (file_exists($foundPath) && is_executable($foundPath)) {
                                return $foundPath;
                            }
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            // تلاش برای پیدا کردن docker در PATH
            try {
                $result = Process::run('docker --version 2>/dev/null');
                if ($result->successful()) {
                    return 'docker';
                }
            } catch (\Exception $e) {
                // ادامه
            }

            // اگر هیچ کدام پیدا نشد، از مسیر پیش‌فرض Ubuntu استفاده کن
            return '/usr/bin/docker';
        }
    }

    private function findDockerComposePath()
    {
        // تشخیص سیستم عامل
        $isWindows = PHP_OS_FAMILY === 'Windows';
        
        if ($isWindows) {
            // در Windows، معمولاً از docker compose استفاده می‌شود
            try {
                $result = Process::run('docker compose version');
                if ($result->successful()) {
                    return 'docker compose';
                }
            } catch (\Exception $e) {
                // ادامه
            }
            
            // تست docker-compose در PATH
            try {
                $result = Process::run('docker-compose --version');
                if ($result->successful()) {
                    return 'docker-compose';
                }
            } catch (\Exception $e) {
                // ادامه
            }
            
            return 'docker compose';
        } else {
            // مسیرهای معمول Docker Compose در Linux/Unix
            $possiblePaths = [
                '/usr/bin/docker-compose',
                '/usr/local/bin/docker-compose',
                '/opt/homebrew/bin/docker-compose',
                '/snap/bin/docker-compose',
                'docker-compose'
            ];

            // ابتدا بررسی کنیم که فایل‌ها وجود دارند و قابل اجرا هستند
            foreach ($possiblePaths as $path) {
                if ($path !== 'docker-compose' && file_exists($path) && is_executable($path)) {
                    return $path;
                }
            }

            // اگر فایل‌ها پیدا نشدند، از which استفاده کن
            $whichCommands = ['which', 'command -v', 'type -p'];
            
            foreach ($whichCommands as $whichCmd) {
                foreach ($possiblePaths as $path) {
                    try {
                        $result = Process::run("$whichCmd $path 2>/dev/null");
                        if ($result->successful() && !empty(trim($result->output()))) {
                            $foundPath = trim($result->output());
                            if (file_exists($foundPath) && is_executable($foundPath)) {
                                return $foundPath;
                            }
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            // تلاش برای پیدا کردن docker-compose در PATH
            try {
                $result = Process::run('docker-compose --version 2>/dev/null');
                if ($result->successful()) {
                    return 'docker-compose';
                }
            } catch (\Exception $e) {
                // ادامه
            }

            // بررسی docker compose (بدون خط تیره)
            try {
                $result = Process::run('docker compose version 2>/dev/null');
                if ($result->successful()) {
                    return 'docker compose';
                }
            } catch (\Exception $e) {
                // ادامه
            }

            // اگر هیچ کدام پیدا نشد، از مسیر پیش‌فرض Ubuntu استفاده کن
            return '/usr/bin/docker-compose';
        }
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

    public function debugDockerPaths()
    {
        try {
            $debug = [];
            
            // اطلاعات سیستم عامل
            $debug['system'] = [
                'os_family' => PHP_OS_FAMILY,
                'os' => PHP_OS,
                'is_windows' => PHP_OS_FAMILY === 'Windows'
            ];
            
            // بررسی مسیرهای مختلف Docker بر اساس سیستم عامل
            if (PHP_OS_FAMILY === 'Windows') {
                $possiblePaths = [
                    'C:\\Program Files\\Docker\\Docker\\resources\\bin\\docker.exe',
                    'C:\\Program Files\\Docker\\Docker\\resources\\docker.exe'
                ];
            } else {
                $possiblePaths = [
                    '/usr/bin/docker',
                    '/usr/local/bin/docker',
                    '/opt/homebrew/bin/docker',
                    '/snap/bin/docker'
                ];
            }

            foreach ($possiblePaths as $path) {
                $debug['paths'][$path] = [
                    'exists' => file_exists($path),
                    'executable' => file_exists($path) ? is_executable($path) : false
                ];
            }

            // بررسی دستورات which بر اساس سیستم عامل
            if (PHP_OS_FAMILY === 'Windows') {
                $whichCommands = ['where docker'];
            } else {
                $whichCommands = ['which docker', 'command -v docker', 'type -p docker'];
            }
            
            foreach ($whichCommands as $cmd) {
                try {
                    if (PHP_OS_FAMILY === 'Windows') {
                        $result = Process::run($cmd);
                    } else {
                        $result = Process::run($cmd . ' 2>/dev/null');
                    }
                    $debug['which_commands'][$cmd] = [
                        'successful' => $result->successful(),
                        'output' => trim($result->output()),
                        'error' => $result->errorOutput()
                    ];
                } catch (\Exception $e) {
                    $debug['which_commands'][$cmd] = [
                        'error' => $e->getMessage()
                    ];
                }
            }

            // بررسی docker --version
            try {
                if (PHP_OS_FAMILY === 'Windows') {
                    $result = Process::run('docker --version');
                } else {
                    $result = Process::run('docker --version 2>/dev/null');
                }
                $debug['docker_version'] = [
                    'successful' => $result->successful(),
                    'output' => trim($result->output()),
                    'error' => $result->errorOutput()
                ];
            } catch (\Exception $e) {
                $debug['docker_version'] = [
                    'error' => $e->getMessage()
                ];
            }

            // بررسی docker compose version
            try {
                if (PHP_OS_FAMILY === 'Windows') {
                    $result = Process::run('docker compose version');
                } else {
                    $result = Process::run('docker compose version 2>/dev/null');
                }
                $debug['docker_compose_version'] = [
                    'successful' => $result->successful(),
                    'output' => trim($result->output()),
                    'error' => $result->errorOutput()
                ];
            } catch (\Exception $e) {
                $debug['docker_compose_version'] = [
                    'error' => $e->getMessage()
                ];
            }

            // مسیر پیدا شده
            $debug['found_docker_path'] = $this->findDockerPath();
            $debug['found_docker_compose_path'] = $this->findDockerComposePath();

            return response()->json([
                'success' => true,
                'debug' => $debug
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
