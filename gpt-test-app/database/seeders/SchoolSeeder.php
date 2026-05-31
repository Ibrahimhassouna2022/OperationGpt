<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->insertGetId([
            'name' => 'مستشار المدرسة (Admin)',
            'email' => 'admin@school.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $teacherId = DB::table('users')->insertGetId([
            'name' => 'أ. أحمد (معلم رياضيات)',
            'email' => 'teacher@school.com',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $studentId = DB::table('users')->insertGetId([
            'name' => 'خالد (طالب بالصف 11)',
            'email' => 'student@school.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('teachers')->insert([
            'user_id' => $teacherId,
            'subject' => 'Math',
            'salary' => 3500.00,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('students')->insert([
            'user_id' => $studentId,
            'grade' => 11,
            'gpa' => 3.85,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
