<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OperationGpt\Services\OpenAIService;

/**
 * OperationGPT Package - Student Role Permission Tests
 *
 * يختبر هذا الملف منطق الصلاحيات في الـ package بدون الحاجة لـ OpenAI حقيقي.
 * يتم عمل Mock للـ OpenAIService وإرجاع SQL مباشرة لاختبار الـ Controller.
 */
class StudentPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $teacher;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the school migration manually
        $this->artisan('migrate');

        // Create users with roles (use DB::table to bypass $fillable for role column)
        $adminId = DB::table('users')->insertGetId([
            'name'       => 'مستشار المدرسة (Admin)',
            'email'      => 'admin@school.com',
            'password'   => bcrypt('password'),
            'role'       => 'super_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->admin = User::find($adminId);

        $teacherId = DB::table('users')->insertGetId([
            'name'       => 'أ. أحمد (معلم)',
            'email'      => 'teacher@school.com',
            'password'   => bcrypt('password'),
            'role'       => 'teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->teacher = User::find($teacherId);

        $studentId = DB::table('users')->insertGetId([
            'name'       => 'خالد (طالب بالصف 11)',
            'email'      => 'student@school.com',
            'password'   => bcrypt('password'),
            'role'       => 'student',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->student = User::find($studentId);

        // Create student record
        DB::table('students')->insert([
            'user_id'    => $this->student->id,
            'grade'      => 11,
            'gpa'        => 3.85,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create teacher record
        DB::table('teachers')->insert([
            'user_id'    => $this->teacher->id,
            'subject'    => 'Math',
            'salary'     => 3500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // HELPER: Mock OpenAI to return a specific SQL
    // ─────────────────────────────────────────────────────────
    private function mockAI(string $sql): void
    {
        $mock = $this->createMock(OpenAIService::class);
        $mock->method('sendMessage')
             ->willReturn(json_encode(['sql_query' => $sql]));

        $this->app->instance(OpenAIService::class, $mock);
    }

    // ─────────────────────────────────────────────────────────
    // STUDENT TESTS - Allowed (SELECT)
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function student_can_select_own_data_from_students_table(): void
    {
        $studentId = $this->student->id;
        $this->mockAI("SELECT * FROM students WHERE user_id = {$studentId}");

        $response = $this->actingAs($this->student)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'أرني بياناتي'
                         ]);

        echo "\n[TEST 1] Student SELECT own data\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Type:   " . ($response->json('type') ?? 'N/A') . "\n";
        echo "  Reply:  " . strip_tags($response->json('reply') ?? '') . "\n";

        $response->assertStatus(200)
                 ->assertJson(['type' => 'report']);

        echo "  ✅ PASSED - Student can SELECT own data\n";
    }

    /** @test */
    public function student_can_select_from_users_table(): void
    {
        $studentId = $this->student->id;
        $this->mockAI("SELECT id, name, email FROM users WHERE id = {$studentId}");

        $response = $this->actingAs($this->student)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'ما هو اسمي؟'
                         ]);

        echo "\n[TEST 2] Student SELECT from users\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Type:   " . ($response->json('type') ?? 'N/A') . "\n";

        $response->assertStatus(200)
                 ->assertJson(['type' => 'report']);

        echo "  ✅ PASSED - Student can SELECT from users\n";
    }

    // ─────────────────────────────────────────────────────────
    // STUDENT TESTS - Forbidden (UPDATE / DELETE / INSERT)
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function student_cannot_update_own_gpa(): void
    {
        $studentId = $this->student->id;
        $this->mockAI("UPDATE students SET gpa = 4.0 WHERE user_id = {$studentId}");

        $response = $this->actingAs($this->student)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'عدّل معدلي إلى 4.0'
                         ]);

        echo "\n[TEST 3] Student tries UPDATE gpa (FORBIDDEN)\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Type:   " . ($response->json('type') ?? 'N/A') . "\n";
        echo "  Reply:  " . ($response->json('reply') ?? '') . "\n";

        $response->assertStatus(403)
                 ->assertJson([
                     'type'    => 'error',
                     'message' => 'Role-based permission denied.',
                 ]);

        echo "  ✅ PASSED - Student UPDATE correctly BLOCKED\n";
    }

    /** @test */
    public function student_cannot_delete_records(): void
    {
        $this->mockAI("DELETE FROM students WHERE user_id = 1");

        $response = $this->actingAs($this->student)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'احذف سجلي'
                         ]);

        echo "\n[TEST 4] Student tries DELETE (FORBIDDEN)\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Reply:  " . ($response->json('reply') ?? '') . "\n";

        $response->assertStatus(403)
                 ->assertJson(['type' => 'error']);

        echo "  ✅ PASSED - Student DELETE correctly BLOCKED\n";
    }

    /** @test */
    public function student_cannot_insert_records(): void
    {
        $this->mockAI("INSERT INTO students (user_id, grade, gpa) VALUES (99, 11, 4.0)");

        $response = $this->actingAs($this->student)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'أضف طالباً جديداً'
                         ]);

        echo "\n[TEST 5] Student tries INSERT (FORBIDDEN)\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Reply:  " . ($response->json('reply') ?? '') . "\n";

        $response->assertStatus(403)
                 ->assertJson(['type' => 'error']);

        echo "  ✅ PASSED - Student INSERT correctly BLOCKED\n";
    }

    /** @test */
    public function student_cannot_drop_tables(): void
    {
        $this->mockAI("DROP TABLE students");

        $response = $this->actingAs($this->student)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'احذف جدول الطلاب'
                         ]);

        echo "\n[TEST 6] Student tries DROP TABLE (FORBIDDEN)\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Reply:  " . ($response->json('reply') ?? '') . "\n";

        $response->assertStatus(403)
                 ->assertJson(['type' => 'error']);

        echo "  ✅ PASSED - Student DROP correctly BLOCKED\n";
    }

    // ─────────────────────────────────────────────────────────
    // TEACHER TESTS - Bonus
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function teacher_can_update_student_gpa(): void
    {
        $this->mockAI("UPDATE students SET gpa = 3.9 WHERE user_id = {$this->student->id}");

        $response = $this->actingAs($this->teacher)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'عدّل معدل الطالب خالد إلى 3.9'
                         ]);

        echo "\n[TEST 7] Teacher UPDATE student gpa (ALLOWED)\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Type:   " . ($response->json('type') ?? 'N/A') . "\n";

        $response->assertStatus(200);

        echo "  ✅ PASSED - Teacher can UPDATE students\n";
    }

    /** @test */
    public function teacher_cannot_delete_records(): void
    {
        $this->mockAI("DELETE FROM students WHERE id = 1");

        $response = $this->actingAs($this->teacher)
                         ->postJson('/operation-gpt/chat', [
                             'message' => 'احذف الطالب'
                         ]);

        echo "\n[TEST 8] Teacher tries DELETE (FORBIDDEN - only SELECT+UPDATE allowed)\n";
        echo "  Status: " . $response->status() . "\n";
        echo "  Reply:  " . ($response->json('reply') ?? '') . "\n";

        $response->assertStatus(403)
                 ->assertJson(['type' => 'error']);

        echo "  ✅ PASSED - Teacher DELETE correctly BLOCKED\n";
    }

    // ─────────────────────────────────────────────────────────
    // UNAUTHENTICATED TEST
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_gets_401(): void
    {
        $response = $this->postJson('/operation-gpt/chat', [
            'message' => 'أرني البيانات'
        ]);

        echo "\n[TEST 9] Unauthenticated request\n";
        echo "  Status: " . $response->status() . "\n";

        $response->assertStatus(401);

        echo "  ✅ PASSED - Unauthenticated request correctly returns 401\n";
    }
}
