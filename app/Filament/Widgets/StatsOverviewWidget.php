<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Enums\MonthlyFeeStatus;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\MonthlyFee;
use App\Models\Student;
use App\Models\Subject;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->toDateString();

        $totalStudents = Student::count();
        $activeStudents = Student::where('active', true)->count();

        $totalSubjects = Subject::count();
        $activeSubjects = Subject::where('active', true)->count();

        $classesToday = ClassSession::whereDate('date', $today)->count();

        $presentToday = Attendance::whereHas('classSession', fn ($q) => $q->whereDate('date', $today))
            ->where('status', AttendanceStatus::Present)
            ->count();

        $absentToday = Attendance::whereHas('classSession', fn ($q) => $q->whereDate('date', $today))
            ->where('status', AttendanceStatus::Absent)
            ->count();

        $outstandingFees = MonthlyFee::whereIn('status', [MonthlyFeeStatus::Unpaid, MonthlyFeeStatus::PartiallyPaid])
            ->sum('amount');

        return [
            Stat::make('Total Students', $totalStudents)
                ->description("{$activeStudents} active students")
                ->descriptionIcon(Heroicon::OutlinedUserGroup)
                ->color('primary'),

            Stat::make('Total Subjects', $totalSubjects)
                ->description("{$activeSubjects} active subjects")
                ->descriptionIcon(Heroicon::OutlinedAcademicCap)
                ->color('info'),

            Stat::make('Classes Today', $classesToday)
                ->description('Scheduled for today')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('warning'),

            Stat::make('Present Today', $presentToday)
                ->description('Attended sessions')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),

            Stat::make('Absent Today', $absentToday)
                ->description('Missed sessions')
                ->descriptionIcon(Heroicon::OutlinedXCircle)
                ->color('danger'),

            Stat::make('Outstanding Fees', '$'.number_format((float) $outstandingFees, 2))
                ->description('Unpaid & partial fees')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color($outstandingFees > 0 ? 'danger' : 'success'),
        ];
    }
}
