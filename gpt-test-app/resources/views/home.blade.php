<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تطبيق اختبار نظام المدرسة - OperationGpt</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; text-align: center; padding: 50px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: inline-block; max-width: 500px; width: 100%; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .btn { display: block; background: #3498db; color: white; padding: 12px; margin: 10px 0; border-radius: 5px; text-decoration: none; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #2980b9; }
        .btn-admin { background: #e74c3c; }
        .btn-admin:hover { background: #c0392b; }
        .btn-teacher { background: #2ecc71; }
        .btn-teacher:hover { background: #27ae60; }
        .btn-logout { background: #95a5a6; }
        .btn-logout:hover { background: #7f8c8d; }
        .user-info { background: #e8f4fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-right: 5px solid #3498db; }
    </style>
</head>
<body>

    <div class="card">
        <h1>🏫 نظام المدرسة التجريبي 🧪</h1>
        <p>مرحباً بك في نظام محاكاة المعلمين والطلاب لاختبار حزمة <strong>OperationGpt</strong>.</p>
        <hr>

        @if(auth()->check())
            <div class="user-info">
                <p>👤 الحساب الحالي: <strong>{{ auth()->user()->name }}</strong></p>
                <p>🔑 الدور (Role): <span style="text-transform: uppercase; font-weight: bold; color: #e74c3c;">{{ auth()->user()->role }}</span></p>
            </div>
            
            <a href="/operation-gpt" class="btn">➡️ الدخول لواجهة ذكاء الحزمة (Chat Dashboard)</a>
            
            <form action="{{ route('logout') }}" method="POST" style="margin-top: 15px;">
                @csrf
                <button type="submit" class="btn btn-logout" style="width: 100%; border: none; cursor: pointer;">تسجيل الخروج</button>
            </form>
        @else
            <p>الرجاء الدخول بأحد الأدوار المتاحة لاختبار حدود الحماية والصلاحيات:</p>
            
            <a href="{{ route('login.as', 'admin') }}" class="btn btn-admin">👑 الدخول كـ مدير المدرسة (Admin)</a>
            <a href="{{ route('login.as', 'teacher') }}" class="btn btn-teacher">👨‍🏫 الدخول كـ معلم (Teacher)</a>
            <a href="{{ route('login.as', 'student') }}" class="btn">🎓 الدخول كـ طالب (Student)</a>
        @endif
    </div>

</body>
</html>
