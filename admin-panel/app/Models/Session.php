<?php

namespace App\Models;

use App\Helpers\DateFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Jenssegers\Agent\Agent;

class Session extends Model
{
    protected $appends = ['expires_at'];

    public $incrementing = false;

    protected $primaryKey = 'id';

    public function isExpired()
    {
        return $this->last_activity < Carbon::now()->subMinutes(config('session.lifetime'))->getTimestamp();
    }

    public function expires_at()
    {
        return DateFormatter::convertUnixTimeToPersianDate($this->last_activity, 120,'%Y-%m-%d H:i:s');
    }

    public function is_desktop()
    {
        $agent = $this->createAgent();

        return $agent->isDesktop();
    }

    public function platform()
    {
        $agent = $this->createAgent();

        return $agent->platform();
    }

    public function browser()
    {
        $agent = $this->createAgent();

        return $agent->browser();
    }

    protected function createAgent()
    {
        return tap(new Agent, function ($agent) {
            $agent->setUserAgent($this->user_agent);
        });
    }
}
