<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Notifications\DatabaseNotification;

class NotificationRecord extends DatabaseNotification
{
    use Filterable;
    public $filterNameSpace = 'App\Filters\NotificationRecordFilters';
}
