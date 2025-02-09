<?php

namespace App\Http\Controllers\User;

use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class UserRegistrationReportController extends Controller
{
    public function index()
    {
        $year = Jalalian::now()->getYear(); // Get current Jalali year
        $month = Jalalian::now()->getMonth(); // Get current Jalali month

        $startOfMonth = new Jalalian($year, $month, 1);
        $endOfMonth = $startOfMonth->addMonths(1)->subDays(1);


        $registrations = $this->getRegistrationData($startOfMonth->toCarbon(), $endOfMonth->toCarbon());
        $inactiveRegistrations = $this->getRegistrationData($startOfMonth->toCarbon(), $endOfMonth->toCarbon(), UserStatusEnum::INACTIVE->value);

        $daysInMonth = $endOfMonth->getDay();
        $totalRegistrationData = array_fill(1, $daysInMonth, 0);
        $totalInActiveRegistrationData = array_fill(1, $daysInMonth, 0);

        foreach ($registrations as $reg) {
            $totalRegistrationData[(int)$reg['day']] = $reg['total'];
        }

        foreach ($inactiveRegistrations as $reg) {
            $totalInActiveRegistrationData[(int)$reg['day']] = $reg['total'];
        }

        return view('dashboard.user.user-registration-report', [
            'totalRegistrationData' => $totalRegistrationData,
            'totalInActiveRegistrationData' => $totalInActiveRegistrationData,
        ]);
    }
    public function getUserRegistrationState(Request $request)
    {
        $persianMonths = [
            'فروردین' => 1, 'اردیبهشت' => 2, 'خرداد' => 3,
            'تیر' => 4, 'مرداد' => 5, 'شهریور' => 6,
            'مهر' => 7, 'آبان' => 8, 'آذر' => 9,
            'دی' => 10, 'بهمن' => 11, 'اسفند' => 12
        ];

        $selectedMonth = $request->query('month', Jalalian::now()->format('%B')); // Get month name if not provided
        $month = $persianMonths[$selectedMonth] ?? Jalalian::now()->getMonth(); // Convert to number

        $year = Jalalian::now()->getYear();

        // Convert Jalali month to Gregorian date range
        $startOfMonth = new Jalalian($year, $month, 1);
        $endOfMonth = $startOfMonth->addMonths(1)->subDays(1);

        // Get total days in selected Jalali month
        $daysInMonth = $endOfMonth->getDay();

        // Initialize arrays
        $totalRegistrationData = array_fill(1, $daysInMonth, 0);
        $totalInActiveRegistrationData = array_fill(1, $daysInMonth, 0);

        // Fetch and process active registrations
        $registrations = $this->getRegistrationData($startOfMonth->toCarbon(), $endOfMonth->toCarbon());
        foreach ($registrations as $reg) {
            $totalRegistrationData[(int)$reg['day']] = $reg['total'];
        }

        // Fetch and process inactive registrations
        $inactiveRegistrations = $this->getRegistrationData($startOfMonth->toCarbon(), $endOfMonth->toCarbon(), UserStatusEnum::INACTIVE->value);
        foreach ($inactiveRegistrations as $reg) {
            $totalInActiveRegistrationData[(int)$reg['day']] = $reg['total'];
        }

        return response()->json([
            'totalRegistrations' => $totalRegistrationData,
            'totalInactiveRegistrations' => $totalInActiveRegistrationData
        ]);
    }
    protected function getRegistrationData($startOfMonth, $endOfMonth, $status = null)
    {
        $query = User::query()
            ->whereBetween('registration_date', [$startOfMonth, $endOfMonth]);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get()
            ->map(function ($reg) {
                return [
                    'day' => Jalalian::fromDateTime($reg->registration_date)->getDay(),
                    'total' => 1
                ];
            })
            ->groupBy('day')
            ->map(function ($items, $day) {
                return [
                    'day' => (int)$day,
                    'total' => $items->sum('total')
                ];
            })
            ->values();
    }

}
