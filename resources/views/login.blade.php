<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/UIlogin.css'])
    @vite(['resources/js/Login.js'])
    <!-- นำเข้าไฟล์ CSS และ JS ที่กำหนดเองสำหรับการออกแบบ UI ของแชท -->
</head>

<body>
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="page">
        <div class="card">
            <div class="logo-block">
                <div class="logo-img-wrap">
                    <img src="{{ asset('images/com7logo.png') }}" alt="COM7 HR Logo" class="logo-img">
                </div>
                <!-- เส้นแบ่งระหว่างโลโก้และส่วนเนื้อหา -->
                <div class="divider"></div>

                <h1>เข้าสู่ระบบ</h1>
                <p class="sub">ใส่ชื่อผู้ใช้และรหัสผ่านเพื่อดำเนินการต่อ</p>
                <!-- // แสดงข้อความผิดพลาดจากเซิร์ฟเวอร์ (ถ้ามี) และข้อความผิดพลาดจาก JavaScript -->
                @if ($errors->any())
                <div class="error-msg show">
                    {{ $errors->first() }}
                </div>
                @endif
                <!-- // แสดงข้อความผิดพลาดจาก JavaScript (ถ้ามี) โดยใช้ไอดี js-error เพื่อให้ JavaScript สามารถอัปเดตข้อความได้ -->
                <div class="error-msg" id="js-error"></div>
                <!-- // ฟอร์มเข้าสู่ระบบที่ส่งข้อมูลไปยังเส้นทาง /login ด้วยวิธี POST และมีการป้องกัน CSRF ด้วย @csrf -->
                <form method="POST" action="/login" id="loginForm">
                    @csrf
                    <!-- ฟิลด์ชื่อผู้ใช้ -->
                    <div class="field">
                        <div class="input-wrap">
                            <span class="input-placeholder">User ID</span>
                            <input type="text" name="username" inputmode="numeric" pattern="[0-9]*" id="username"
                                minlength="2" maxlength="7" autocomplete="off" value="{{ old('username') }}">
                        </div>
                    </div>
                    <!-- ฟิลด์รหัสผ่าน -->
                    <div class="field">
                        <div class="input-wrap">
                            <span class="input-placeholder">Password</span>
                            <input type="password" name="password" inputmode="numeric" pattern="[0-9]*"
                                autocomplete="off" id="password" maxlength="6">
                        </div>
                    </div>
                    <!-- ปุ่มส่งฟอร์ม -->
                    <button type="submit" class="btn" id="submitBtn">
                        เข้าสู่ระบบ →
                    </button>
                </form>
                <div class="footer-note">
                    <div class="dot"></div>
                    ระบบพร้อมใช้งาน · Powered Com7
                </div>
            </div>
        </div>
</body>

</html>