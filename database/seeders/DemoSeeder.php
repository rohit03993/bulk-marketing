<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AisensyTemplate;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        $school1 = School::firstOrCreate(
            ['name' => 'Demo Primary School'],
            [
                'short_name' => 'DPS',
                'address' => '123 Education Lane',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'contact_person' => 'Principal Sharma',
                'contact_phone' => '9876543210',
                'contact_email' => 'principal@demoschool.in',
            ]
        );

        $school2 = School::firstOrCreate(
            ['name' => 'Green Valley High'],
            [
                'short_name' => 'GVH',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'contact_phone' => '9123456789',
            ]
        );

        $session1 = AcademicSession::firstOrCreate(
            ['name' => '2024-25'],
            [
                'starts_at' => now()->parse('2024-04-01'),
                'ends_at' => now()->parse('2025-03-31'),
                'is_current' => true,
            ]
        );

        $session2 = AcademicSession::firstOrCreate(
            ['name' => '2025-26'],
            [
                'starts_at' => now()->parse('2025-04-01'),
                'ends_at' => now()->parse('2026-03-31'),
                'is_current' => false,
            ]
        );

        AcademicSession::whereNotIn('id', [$session1->id])->update(['is_current' => false]);
        $session1->update(['is_current' => true]);

        $classes = [];
        foreach ([['6', 'A'], ['6', 'B'], ['7', 'A'], ['8', 'A']] as $i => [$class, $sec]) {
            $classes[] = ClassSection::firstOrCreate(
                [
                    'school_id' => $school1->id,
                    'academic_session_id' => $session1->id,
                    'class_name' => $class,
                    'section_name' => $sec,
                ],
                []
            );
        }
        $class6a = $classes[0];
        $class6b = $classes[1];
        $class7a = $classes[2];

        $studentData = [
            ['Rahul Kumar', 'Suresh Kumar', '9876123450', '101', '6', 'A'],
            ['Priya Sharma', 'Rajesh Sharma', '9876123451', '102', '6', 'A'],
            ['Amit Singh', 'Vikram Singh', '9876123452', '103', '6', 'A'],
            ['Neha Patel', 'Manoj Patel', '9876123453', '104', '6', 'A'],
            ['Vikram Reddy', 'Krishna Reddy', '9876123454', '105', '6', 'A'],
            ['Anita Desai', 'Sunil Desai', '9876123455', '106', '6', 'B'],
            ['Rohit Nair', 'Kerala Nair', '9876123456', '107', '6', 'B'],
            ['Kavita Iyer', 'Ramesh Iyer', '9876123457', '108', '6', 'B'],
            ['Arjun Mehta', 'Deepak Mehta', '9876123458', '201', '7', 'A'],
            ['Sneha Joshi', 'Pradeep Joshi', '9876123459', '202', '7', 'A'],
        ];

        foreach ($studentData as [$name, $father, $phone, $roll, $classNum, $sec]) {
            $cs = ClassSection::where('school_id', $school1->id)
                ->where('academic_session_id', $session1->id)
                ->where('class_name', $classNum)
                ->where('section_name', $sec)
                ->first();
            if (! $cs) {
                continue;
            }
            Student::firstOrCreate(
                [
                    'class_section_id' => $cs->id,
                    'roll_number' => $roll,
                ],
                [
                    'name' => $name,
                    'father_name' => $father,
                    'whatsapp_phone_primary' => $phone,
                    'status' => 'active',
                ]
            );
        }

        $tpl1 = AisensyTemplate::firstOrCreate(
            ['name' => 'FEE_REMINDER_DEMO'],
            [
                'description' => 'Demo fee reminder (2 params: name, class)',
                'param_count' => 2,
                'param_mappings' => ['student.name', 'class.full_name'],
            ]
        );

        $tpl2 = AisensyTemplate::firstOrCreate(
            ['name' => 'WELCOME_PARENT_DEMO'],
            [
                'description' => 'Demo welcome (3 params)',
                'param_count' => 3,
                'param_mappings' => ['student.name', 'student.father_name', 'school.name'],
            ]
        );

        $campaign = Campaign::firstOrCreate(
            [
                'name' => 'Demo Fee Reminder April',
                'school_id' => $school1->id,
            ],
            [
                'academic_session_id' => $session1->id,
                'aisensy_template_id' => $tpl1->id,
                'status' => 'queued',
                'total_recipients' => 0,
                'sent_count' => 0,
                'failed_count' => 0,
                'created_by' => $admin->id,
            ]
        );

        if ($campaign->recipients()->count() === 0) {
            $students = Student::whereIn('class_section_id', [$class6a->id, $class6b->id])
                ->where('status', 'active')
                ->whereNotNull('whatsapp_phone_primary')
                ->get();
            $count = 0;
            foreach ($students as $s) {
                CampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'student_id' => $s->id,
                    'phone' => $s->whatsapp_phone_primary,
                    'status' => 'pending',
                ]);
                $count++;
            }
            $campaign->update(['total_recipients' => $count]);
        }
    }
}
