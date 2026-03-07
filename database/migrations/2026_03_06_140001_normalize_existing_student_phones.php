<?php

use App\Models\Student;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Student::query()->chunk(100, function ($students) {
            foreach ($students as $student) {
                $updates = [];
                if ($student->whatsapp_phone_primary) {
                    $n = Student::normalizeIndianPhone($student->whatsapp_phone_primary);
                    if ($n !== null) {
                        $updates['whatsapp_phone_primary'] = $n;
                    }
                }
                if ($student->whatsapp_phone_secondary) {
                    $n = Student::normalizeIndianPhone($student->whatsapp_phone_secondary);
                    if ($n !== null) {
                        $updates['whatsapp_phone_secondary'] = $n;
                    }
                }
                if (! empty($updates)) {
                    $student->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        // Cannot reverse normalization
    }
};
