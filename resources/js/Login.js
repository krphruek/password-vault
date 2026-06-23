const usernameEl = document.getElementById("username");
const passwordEl = document.getElementById("password");
const userCount = document.getElementById("user-count");
const passCount = document.getElementById("pass-count");
const jsError = document.getElementById("js-error");
const form = document.getElementById("loginForm");

/**
 * จัดการอินพุต: เฉพาะตัวเลข และอัปเดตตัวนับ
 */
function handleNumericInput(input, counter, minLimit, maxLimit) {
    // 1. ลบอักขระที่ไม่ใช่ตัวเลขออก
    input.value = input.value.replace(/[^0-9]/g, "");

    // 2. จำกัดความยาวสูงสุดไม่ให้เกิน maxLimit
    if (input.value.length > maxLimit) {
        input.value = input.value.slice(0, maxLimit);
    }
    if (!counter) return;
    
    const len = input.value.length;
    // แสดงตัวนับ เช่น "5 / 5-6"
    counter.textContent = `${len} / ${minLimit}-${maxLimit}`;

    // ใส่สีเตือน (คลาส .warn) เมื่อความยาวอยู่ในช่วงที่กำหนด (5 หรือ 6)
    // หรือจะเลือกให้เตือนเมื่อถึง maxLimit (6) ก็ได้ครับ
    counter.classList.toggle("warn", len >= minLimit);
}

// ตั้งค่า: Username (1-7 หลัก), Password (6 หลักเป๊ะๆ)
usernameEl.addEventListener("input", () =>
    handleNumericInput(usernameEl, userCount, 1, 7),
);
passwordEl.addEventListener("input", () =>
    handleNumericInput(passwordEl, passCount, 6, 6),
);

// ตรวจสอบก่อนส่งฟอร์ม
form.addEventListener("submit", function (e) {
    const u = usernameEl.value.trim();
    const p = passwordEl.value.trim();
    let errorMessage = "";

    if (!u || !p) {
        errorMessage = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    }
    // ตรวจสอบ Username: ต้องไม่น้อยกว่า 1 และไม่เกิน 10
    else if (u.length < 1 || u.length > 7) {
        errorMessage = "กรุณาใส่ ID ให้ถูกต้อง";
    }
    // ตรวจสอบ Password: ต้องเป็น 6 หลักเท่านั้น
    else if (p.length !== 6) {
        errorMessage = "กรุณาใส่รหัสผ่านให้ถูกต้อง";
    }

    if (errorMessage) {
        e.preventDefault();
        jsError.textContent = errorMessage;
        jsError.classList.add("show");
        return;
    }

    // ผ่านเงื่อนไข -> แสดง Loading
    const btn = document.getElementById("submitBtn");
    btn.textContent = "กำลังเข้าสู่ระบบ...";
    btn.style.opacity = ".7";
    btn.disabled = true;
});
