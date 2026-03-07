<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentImport;
use App\Models\StudentImportColumn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentImportController extends Controller
{
    public function index()
    {
        $imports = StudentImport::with('school', 'academicSession')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('crm.student-imports.index', compact('imports'));
    }

    public function create()
    {
        $schools = School::orderBy('name')->get();
        $sessions = AcademicSession::orderByDesc('starts_at')->get();

        return view('crm.student-imports.create', compact('schools', 'sessions'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'import_class' => 'nullable|string|in:1,2,3,4,5,6,7,8,9,10,11,12,custom',
            'import_class_custom' => 'nullable|string|max:100',
            'import_section_name' => 'nullable|string|max:50',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $importClassName = null;
        if ($valid['import_class'] === 'custom' && trim((string) ($valid['import_class_custom'] ?? '')) !== '') {
            $importClassName = trim($valid['import_class_custom']);
        } elseif ($valid['import_class'] !== null && $valid['import_class'] !== '') {
            $importClassName = $valid['import_class'];
        }
        $importSectionName = isset($valid['import_section_name']) && trim($valid['import_section_name']) !== '' ? trim($valid['import_section_name']) : null;

        $file = $request->file('file');
        $import = StudentImport::create([
            'school_id' => $valid['school_id'],
            'academic_session_id' => $valid['academic_session_id'] ?? null,
            'import_class_name' => $importClassName,
            'import_section_name' => $importSectionName,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'mapping',
            'total_rows' => 0,
        ]);

        $path = $file->storeAs('student_imports', 'import_'.$import->id.'.'.$file->getClientOriginalExtension());
        $import->update(['file_path' => $path]);

        $fullPath = Storage::path($path);
        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $headerRow = $rows[0] ?? [];
        $import->update(['total_rows' => max(0, count($rows) - 1)]);

        return redirect()->route('student-imports.mapping', $import)
            ->with('headers', $headerRow);
    }

    public function mapping(StudentImport $studentImport)
    {
        $headers = session('headers');
        if (! $headers) {
            $path = Storage::path($studentImport->file_path);
            if (! $path || ! file_exists($path)) {
                return redirect()->route('student-imports.index')->with('error', __('File not found.'));
            }
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $headers = $rows[0] ?? [];
        }

        $targetFields = [
            '' => __('— Skip —'),
            'name' => __('Student name'),
            'father_name' => __('Father name'),
            'whatsapp_phone_primary' => __('WhatsApp primary'),
            'whatsapp_phone_secondary' => __('WhatsApp secondary'),
            'class_name' => __('Class (e.g. 6, 7)'),
            'section_name' => __('Section (e.g. A, B)'),
            'roll_number' => __('Roll number'),
            'admission_number' => __('Admission number'),
        ];

        return view('crm.student-imports.mapping', [
            'import' => $studentImport,
            'headers' => $headers,
            'targetFields' => $targetFields,
        ]);
    }

    public function saveMapping(Request $request, StudentImport $studentImport)
    {
        $request->validate([
            'duplicate_phone_policy' => 'required|in:skip,overwrite',
        ]);

        $studentImport->columnMappings()->delete();
        $mappings = $request->input('mappings', []);

        foreach ($mappings as $index => $targetField) {
            if ($targetField === '' || $targetField === null) {
                continue;
            }
            $columnName = $request->input('column_names.'.$index);
            StudentImportColumn::create([
                'student_import_id' => $studentImport->id,
                'column_index' => (int) $index,
                'column_name' => $columnName,
                'target_field' => $targetField,
            ]);
        }

        $studentImport->update([
            'status' => 'processing',
            'duplicate_phone_policy' => $request->input('duplicate_phone_policy', 'skip'),
        ]);

        return redirect()->route('student-imports.process', $studentImport);
    }

    public function process(StudentImport $studentImport)
    {
        $path = Storage::path($studentImport->file_path);
        if (! $path || ! file_exists($path)) {
            $studentImport->update(['status' => 'failed', 'error_message' => 'File not found.']);

            return redirect()->route('student-imports.index')->with('error', __('File not found.'));
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $mappings = $studentImport->columnMappings->keyBy('column_index');
        $schoolId = $studentImport->school_id;
        $sessionId = $studentImport->academic_session_id
            ?? AcademicSession::where('is_current', true)->value('id')
            ?? AcademicSession::orderByDesc('starts_at')->value('id');

        if (! $sessionId) {
            $studentImport->update(['status' => 'failed', 'error_message' => 'No academic session set. Create a session or select one for this import.']);

            return redirect()->route('student-imports.index')->with('error', __('No academic session. Create one or select it for the import.'));
        }

        $processed = 0;
        $skipped = 0;
        $overwritten = 0;
        $errors = [];
        $policy = $studentImport->duplicate_phone_policy ?? 'skip';

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $data = [];
            foreach ($mappings as $index => $map) {
                $value = $row[$index] ?? null;
                if ($value !== null && $value !== '') {
                    $data[$map->target_field] = trim((string) $value);
                }
            }

            if (empty($data['name'])) {
                $errors[] = "Row ".($i + 1).": missing name";
                continue;
            }

            $rawPrimary = $data['whatsapp_phone_primary'] ?? null;
            $rawSecondary = $data['whatsapp_phone_secondary'] ?? null;
            $primaryNormalized = Student::normalizeIndianPhone($rawPrimary);
            $secondaryNormalized = $rawSecondary ? Student::normalizeIndianPhone($rawSecondary) : null;

            if ($rawPrimary && $primaryNormalized === null) {
                $errors[] = "Row ".($i + 1).": invalid Indian phone (".$rawPrimary."). Use 10 digits or +91.";
                continue;
            }
            if ($rawSecondary && $secondaryNormalized === null) {
                $errors[] = "Row ".($i + 1).": invalid secondary phone.";
                continue;
            }

            $className = $studentImport->import_class_name ?? $data['class_name'] ?? 'Unknown';
            $sectionName = (string) ($studentImport->import_section_name ?? $data['section_name'] ?? '');

            $existingByPhone = $primaryNormalized ? Student::findByPhone($primaryNormalized) : null;

            if ($existingByPhone) {
                if ($policy === 'skip') {
                    $skipped++;
                    continue;
                }
                $student = $existingByPhone;
                $classSection = ClassSection::firstOrCreate(
                    [
                        'school_id' => $schoolId,
                        'academic_session_id' => $sessionId,
                        'class_name' => $className,
                        'section_name' => $sectionName,
                    ],
                    []
                );
                $payload = [
                    'class_section_id' => $classSection->id,
                    'name' => $data['name'],
                    'father_name' => $data['father_name'] ?? null,
                    'roll_number' => $data['roll_number'] ?? null,
                    'admission_number' => $data['admission_number'] ?? null,
                    'whatsapp_phone_primary' => $primaryNormalized,
                    'whatsapp_phone_secondary' => $secondaryNormalized,
                    'status' => 'active',
                ];
                $student->update($payload);
                $overwritten++;
                $processed++;
                continue;
            }

            $classSection = ClassSection::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_session_id' => $sessionId,
                    'class_name' => $className,
                    'section_name' => $sectionName,
                ],
                []
            );

            $payload = [
                'name' => $data['name'],
                'father_name' => $data['father_name'] ?? null,
                'roll_number' => $data['roll_number'] ?? null,
                'admission_number' => $data['admission_number'] ?? null,
                'whatsapp_phone_primary' => $primaryNormalized,
                'whatsapp_phone_secondary' => $secondaryNormalized,
                'status' => 'active',
            ];

            Student::create(array_merge($payload, ['class_section_id' => $classSection->id]));
            $processed++;
        }

        $studentImport->update([
            'status' => 'completed',
            'processed_rows' => $processed,
            'error_message' => empty($errors) ? null : implode("\n", array_slice($errors, 0, 20)),
        ]);

        $message = __('Import completed. :count processed.', ['count' => $processed]);
        if ($skipped > 0) {
            $message .= ' '.__(':skipped rows skipped (duplicate phone).', ['skipped' => $skipped]);
        }
        if ($overwritten > 0) {
            $message .= ' '.__(':overwritten existing records updated.', ['overwritten' => $overwritten]);
        }

        return redirect()->route('student-imports.index')
            ->with('success', $message);
    }
}
