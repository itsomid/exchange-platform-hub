<?php

namespace App\ExternalServices;

use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ReportService
{
    private static function getServiceUrl()
    {
        return config('assistant.report.path');
    }

    public static function getAllReports($body)
    {
        $response = Request::get(static::getServiceUrl(), $body);
        $response = new Paginator($response->data->data, $response->data->total, $body['limit'], $response->data->page, [
            'path' => route('reports.index'),
            'query' => \request()->query(),
        ]);

        return $response;
    }

    public static function setScore($report_id, $score)
    {
        return Request::patch(static::getServiceUrl().$report_id.'/score_set', [
            'score' => $score,
        ]);
    }

    public static function deleteReport($report_id)
    {
        return Request::delete(static::getServiceUrl().$report_id);
    }
}
