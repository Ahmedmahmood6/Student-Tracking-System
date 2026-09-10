<?php

namespace App\Filament\Resources\ClassSessions\Pages;

use App\Filament\Resources\ClassSessions\ClassSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassSession extends CreateRecord
{
    protected static string $resource = ClassSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        $studentId = request()->query('student_id');
        $subjectId = request()->query('subject_id');

        if ($studentId || $subjectId) {
            $this->form->fill([
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'date' => now()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:30',
            ]);
        }
    }
}
