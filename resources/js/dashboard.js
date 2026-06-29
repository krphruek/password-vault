// ── Constants ─────────────────────────────────────────────
const SYS_COLORS = {};
const ALL_SYS = [];
(SYSTEMS_DATA ||[]).forEach(s => {
    SYS_COLORS[s.name] = s.color;
    ALL_SYS.push(s.name);
});
const PAGE_SIZE = 10;

let allStores = [],
    currentPage = 1,
    totalPages   = typeof INIT_TOTAL_PAGES !== 'undefined' ? INIT_TOTAL_PAGES : 1,
    total        = typeof INIT_TOTAL !== 'undefined' ? INIT_TOTAL : 0,
    currentStore = null;

//Server-side search & filter
let searchTimeout = null;

// dashboard ใช้ AJAX fetch เป็น source of truth
allStores = [];

// Render
function render(stores) {
    if (stores !== undefined) allStores = stores;
    renderPage();
}

function renderPage() {
    const body = document.getElementById("tableBody");

    if (!allStores.length) {
        body.innerHTML =
            '<div class="no-result">ไม่พบร้านที่ตรงกับเงื่อนไข</div>';
        renderPagination();
        return;
    }

    const groups = {};
    allStores.forEach((s) => {
        const bu = s.bu || "ไม่ระบุ";
        if (!groups[bu]) groups[bu] = [];
        groups[bu].push(s);
    });

    let html = "";
    Object.entries(groups).forEach(([bu, list]) => {
        html += `<div class="group-header">${esc(bu)}<span class="group-badge">${list.length}</span></div>`;
        list.forEach((s) => {
            const idx = allStores.indexOf(s);
            const pips = ALL_SYS.map(
                (sys) =>
                    `<div class="cred-pip ${(s.credentials || {})[sys] ? "" : "empty"}" title="${sys}"></div>`,
            ).join("");
            html += `<div class="store-row" onclick="openModal(${idx})">
                <div><div class="cell-name">${esc(s.name)}</div><div class="cell-id">${esc(s.id)}</div></div>
                <div class="cell-bu">${esc(s.bu || "")}</div>
                <div class="cell-bu">${esc(s.channel || "")}</div>
                <div class="cell-rm">${esc(s.rm || "-")}</div>
                <div class="cell-am">${esc(s.am || "-")}</div>
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div class="cell-cred">${pips}</div>
                    <div><polyline points="9 18 15 12 9 6"/></svg></div>
                </div>
            </div>`;
        });
    });

    body.innerHTML = html;
    renderPagination();
}

// สร้างปุ่ม pagination โดยแสดงปุ่มก่อนหน้าและถัดไป พร้อมกับเลขหน้าปัจจุบัน และซ่อนปุ่มที่ไม่สามารถใช้งานได้ เช่น ถ้าอยู่หน้าแรกให้ซ่อนปุ่มก่อนหน้า และถ้าอยู่หน้าสุดท้ายให้ซ่อนปุ่มถัดไป นอกจากนี้ยังมีการจัดการแสดงเลขหน้าที่เหมาะสมเมื่อมีจำนวนหน้ามาก
function renderPagination() {
    const pg = document.getElementById("pagination");
    if (!pg || totalPages <= 1) {
        if (pg) pg.innerHTML = "";
        return;
    }

    let html = `<button class="page-btn ${currentPage === 1 ? "disabled" : ""}" onclick="goPage(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""}>
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    </button>`;

    for (let i = 1; i <= totalPages; i++) {
        if (
            totalPages > 7 &&
            i > 2 &&
            i < totalPages - 1 &&
            Math.abs(i - currentPage) > 1
        ) {
            if (i === 3 || i === totalPages - 2)
                html += `<span style="color:var(--muted);padding:0 4px">...</span>`;
            continue;
        }
        html += `<button class="page-btn ${i === currentPage ? "active" : ""}" onclick="goPage(${i})">${i}</button>`;
    }

    html += `<button class="page-btn ${currentPage === totalPages ? "disabled" : ""}" onclick="goPage(${currentPage + 1})" ${currentPage === totalPages ? "disabled" : ""}>
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
    </button>`;

    pg.innerHTML = html;
}

const credCache = {};
// การเปิด modal แสดงรายละเอียดร้าน และจัดการข้อมูลภายใน modal ตามสิทธิ์ของผู้ใช้ (view/edit credentials/edit store)
async function openModal(idx) {
    currentStore = allStores[idx];
    if (!currentStore) return;
    const s = currentStore;
    
    document.getElementById("modalName").textContent = s.name;
    let meta = "";
    if (s.id) meta += `<span class="meta-tag">ID: ${esc(s.id)}</span>`;
    if (s.bu) meta += `<span class="meta-tag">BU: ${esc(s.bu)}</span>`;
    if (s.rm) meta += `<span class="meta-tag">RM: ${esc(s.rm)}</span>`;
    if (s.am) meta += `<span class="meta-tag">AM: ${esc(s.am)}</span>`;
    if (s.channel) meta += `<span class="meta-tag">${esc(s.channel)}</span>`;
    document.getElementById("modalMeta").innerHTML = meta;

    const tabs = document.getElementById("modalTabs");
    const tabStore = document.getElementById("tabEditStore");

    if (USER_ROLE === "staff" || USER_ROLE === "admin") {
        tabs.style.display = "flex";
        tabStore.style.display = USER_ROLE === "admin" ? "block" : "none";
    } else {
        tabs.style.display = "none";
    }
    document.getElementById("tabView").innerHTML = '<div class="no-cred">กำลังโหลด...</div>';
    document.getElementById("modalOverlay").classList.add("open");
    document.body.style.overflow = "hidden";
// fetch เฉพาะถ้ายังไม่มีใน cache
    if (!credCache[s.id]) {
        try {
            const res = await fetch(`/store/${s.id}/credentials`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            credCache[s.id] = await res.json();
        } catch(e) {
            credCache[s.id] = {};
        }
    }
    currentStore.credentials = credCache[s.id];

    document.getElementById("tabView").innerHTML = buildViewTable(currentStore);
    if (USER_ROLE === "staff")
        document.getElementById("tabEditCred").innerHTML = buildEditCredForm(currentStore);
    if (USER_ROLE === "admin")
        document.getElementById("tabEditStore2").innerHTML = buildEditStoreForm(currentStore);

    if (USER_ROLE === "admin")
        document.querySelectorAll("#modalTabs .modal-tab")[1].style.display = "none";

    switchTab("view");
}
// ปิด modal เมื่อคลิกที่ overlay หรือกดปุ่มปิด
function switchTab(tab) {
    document.querySelectorAll(".modal-tab").forEach((t, i) => {
        t.classList.toggle(
            "active",
            ["view", "editCred", "editStore"][i] === tab,
        );
    });
    document.getElementById("tabView").style.display =
        tab === "view" ? "block" : "none";
    document.getElementById("tabEditCred").style.display =
        tab === "editCred" ? "block" : "none";
    document.getElementById("tabEditStore2").style.display =
        tab === "editStore" ? "block" : "none";
}
// ปิด modal เมื่อคลิกที่ overlay หรือกดปุ่มปิด
function closeModal(e) {
    if (e.target === document.getElementById("modalOverlay")) closeModalBtn();
}
function closeModalBtn() {
    document.getElementById("modalOverlay").classList.remove("open");
    document.body.style.overflow = "";
}
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModalBtn();
});

// หากร้านไม่มี credentials ให้แสดงข้อความแจ้งเตือน และถ้ามีให้แสดงในรูปแบบตาราง พร้อมปุ่มคัดลอกและแสดงรหัสผ่านได้
function buildViewTable(store) {
    const creds = store.credentials || {},
        systems = Object.keys(creds).filter(sys => {
            const users = Array.isArray(creds[sys])? creds[sys] : [creds[sys]];
            return users.some(u => u.username);
        });

    if (!systems.length)
        return '<div class="no-cred">ยังไม่มี User/Password สำหรับร้านนี้</div>';
    
     // สร้าง vertical nav
    const navItems = systems.map((sys, i) => {
        const color = SYS_COLORS[sys] || '#888';
        const users = Array.isArray(creds[sys]) ? creds[sys] : [creds[sys]];
        return `<button class="sys-nav-btn ${i===0?'active':''}" 
            onclick="switchSysTab('${escAttr(sys)}')" 
            id="sysnav-${escAttr(sys)}"
            style="--sys-color:${color}">
            <div style="display:flex;align-items:center;gap:6px">
                <span class="sys-dot" style="background:${color}"></span>
                <span class="sys-nav-label">${esc(sys)}</span>
            </div>
            <span class="sys-nav-badge">${users.length}</span>
        </button>`;
    }).join('');

     // สร้าง content panels
    const panels = systems.map((sys, i) => {
        const color = SYS_COLORS[sys] || '#888';
        const users = Array.isArray(creds[sys]) ? creds[sys] : [creds[sys]];
        const rows = users.map((c, idx) => {
            const pwId = `pw-${store.id}-${sys.replace(/\W/g,'_')}-${idx}`;
            return `<div class="cred-card-row">
                <span class="cred-username">${esc(c.username||'-')}</span>
                <span class="cred-pw-mask" id="${pwId}" data-val="${escAttr(c.password||'')}">• • • • •</span>
                <button class="icon-btn" onclick="togglePw(this,'${pwId}',event)">
                    <svg width="12" height="12" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                     <circle cx="12" cy="12" r="3"/>
                     </svg>
                </button>
                <button class="icon-btn" onclick="copyTxt(this,'${escJs(c.username||'')}',event,'${escJs(c.password||'')}')">
                    <svg width="12" height="12" fill="none" stroke="currentColor" 
                    stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" 
                    height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                </button>
            </div>`;
        }).join('');

        return `<div class="sys-panel ${i===0?'':'hidden'}" id="syspanel-${escAttr(sys)}">
            <div class="sys-panel-header">
                <span class="sys-dot" style="background:${color}"></span>
                <span style="font-size:13px;font-weight:600;color:var(--text)">${esc(sys)}</span>
                <span style="font-size:11px;color:var(--muted)">${users.length} บัญชี</span>
            </div>
            <div class="cred-card-list">${rows}</div>
        </div>`;
    }).join('');

    return `<div class="vertical-nav-layout">
        <div class="sys-nav">${navItems}</div>
        <div class="sys-content">${panels}</div>
    </div>`;
     
}

function switchSysTab(sys) {
    const color = SYS_COLORS[sys] || '#888';
    
    document.querySelectorAll('.sys-nav-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.querySelector('.sys-nav-label').style.color = 'var(--muted)';
        btn.querySelector('.sys-nav-badge').style.color = 'var(--muted)';
    });

    document.querySelectorAll('.sys-panel').forEach(p => p.classList.add('hidden'));

    const btn = document.getElementById('sysnav-' + sys);
    const panel = document.getElementById('syspanel-' + sys);

    if (btn) {
        btn.classList.add('active');
        btn.style.background = `${color}20`;
        btn.querySelector('.sys-nav-label').style.color = color;
        btn.querySelector('.sys-nav-badge').style.background = `${color}30`;
        btn.querySelector('.sys-nav-badge').style.color = color;
    }
    if (panel) panel.classList.remove('hidden');
}

// สร้างฟอร์มแก้ไข credentials สำหรับ user/password
function buildEditCredForm(store) {
    const creds = store.credentials || {};
     // จัดกลุ่ม system -> array
    const grouped = {};
    Object.entries(creds).forEach(([sys, val]) => {
        // รองรับทั้ง array (หลายuser) และ object (userเดียว) เพื่อความยืดหยุ่น
        grouped[sys] = Array.isArray(val) ? val : [val];
    });

    const blocks = ALL_SYS.map((sys) => {
        const color = SYS_COLORS[sys] || "#888";
        const sysKey = sys.replace( /[^a-zA-Z0-9]/g, "_");
        const existing = grouped[sys] || [{ username: "", password: "" }]; // ถ้าไม่มีข้อมูลเดิมให้แสดงฟิลด์ว่าง 1 ชุด

        const inputRows = existing.map(c => `
            <div class="cred-user-row">
                <input type="text" class="form-input" autocomplete="off" placeholder="Username" value="${escAttr(c.username || '')}" data-sys="${escAttr(sys)}" data-field="username">
                <input type="text" class="form-input" autocomplete="off" placeholder="Password" value="${escAttr(c.password || '')}" data-sys="${escAttr(sys)}" data-field="password">
                <button type="button" class="btn-remove-row" onclick="removeCredRow(this)" title="ลบ">✕</button>
            </div>
        `).join("");
        return `<div class="cred-edit-block">
            <div class="sys-label"><div class="sys-dot" style="background:${color}"></div>${esc(sys)}
            <button type="button" class="btn-add-row" onclick="addCredRow('${escAttr(sys)}','${sysKey}')"> + เพิ่ม </button>
            </div>
             <div id="credrows_${sysKey}"> ${inputRows} </div>
            </div>`;
    }).join("");
    return `<div class="edit-panel">
         <div style="margin-bottom:16px;font-size:13px;color:var(--muted)"> แต่ละระบบสามารถมีได้หลาย Username </div>
         ${blocks}
        <button class="btn-save" onclick="saveCreds('${escAttr(store.id)}')">บันทึก</button>
    </div>`;
}

function addCredRow(sys, sysKey) {
    const container = document.getElementById(`credrows_${sysKey}`);
    const div = document.createElement("div");
    div.className = "cred-user-row";

    div.innerHTML = `
        <input type="text" class="form-input" autocomplete="off" placeholder="Username" data-sys="${escAttr(sys)}" data-field="username">
        <input type="text" class="form-input" autocomplete="off" placeholder="Password" data-sys="${escAttr(sys)}" data-field="password">
        <button type="button" class="btn-remove-row" onclick="removeCredRow(this)" title="ลบ">✕</button>
    `;
    container.appendChild(div);
}

function removeCredRow(btn) {
    const row = btn.closest('[id^="credrows_"]').querySelectorAll(".cred-user-row");

    if (row.length <= 1) {
        showToast("ต้องมีอย่างน้อย 1 รายการ", "error");
        return;
    }
    btn.closest(".cred-user-row").remove();
}

// Edit Store tab (admin)
function buildEditStoreForm(store) {
    const buOptions  = BUS_DATA.map(b  => `<option value="${esc(b)}"  ${store.bu===b?'selected':''}>${esc(b)}</option>`).join('');
    const rmOptions  = RMS_DATA.map(r  => `<option value="${esc(r)}"  ${store.rm===r?'selected':''}>${esc(r)}</option>`).join('');
    const amOptions = AMS_DATA.map(a =>`<option value="${esc(a.id)}"${store.am_user_id == a.id ? 'selected' : ''}>${esc(a.name)}</option>`).join('');
    return `<div class="edit-panel">
        <div class="form-group"><label class="form-label">ชื่อร้าน</label>
            <input type="text" autocomplete="off" class="form-input" id="editName" value="${escAttr(store.name||'')}"></div>

        <div class="form-group"><label class="form-label">BU</label>
            <select class="form-input" id="editBU">
                <option value="">-- เลือก BU --</option>${buOptions}
            </select></div>

        <div class="form-group"><label class="form-label">RM</label>
            <select class="form-input" id="editRM">
                <option value="">-- เลือก RM --</option>${rmOptions}
            </select></div>
            
        <div class="form-group"><label class="form-label">AM</label>
            <select class="form-input" id="editAM">
                <option value="">-- เลือก AM --</option>${amOptions}
            </select></div>
            
        <div class="form-group"><label class="form-label">ช่องทางการขาย</label>
            <input type="text" autocomplete="off" class="form-input" id="editChannel" value="${escAttr(store.channel || "")}"></div>
        <div style="display:flex;gap:8px;align-items:center">
            <button class="btn-save" onclick="saveStore('${escAttr(store.id)}')">บันทึกข้อมูลร้าน</button>
            <button class="btn-del-store" onclick="deleteStore('${escAttr(store.id)}')">ลบร้านนี้</button>
        </div>
    </div>`;
}

// ชุดคำสั่งสำหรับบันทึก credentials โดยจะรวบรวมข้อมูลจากฟอร์มแก้ไข credentials แล้วส่งไปยังเซิร์ฟเวอร์ผ่าน API และอัปเดตข้อมูลในหน้าแสดงผลหากการบันทึกสำเร็จ
async function saveCreds(storeId) {
    const allRows = document.querySelectorAll('#tabEditCred .cred-user-row');
    const credentials = [];
    allRows.forEach(row => {
        const sys  = row.querySelector('[data-field="username"]').dataset.sys;
        const user = row.querySelector('[data-field="username"]').value.trim();
        const pass = row.querySelector('[data-field="password"]').value.trim();
        if(user && pass) credentials.push({ system:sys, username:user, password:pass });
    });
    if (!credentials.length) {
        showToast("กรอก Username และ Password อย่างน้อย 1 ระบบ", "error");
        return;
    }
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = "กำลังบันทึก...";
    try {
        const res = await fetch(`/credentials/${storeId}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify({ credentials }),
        });
        const data = await res.json();
        // ล้าง cache เมื่อเปิดดูร้านเดิม
        if (data.success) {
            delete credCache[storeId];
            showToast("✓ บันทึก Credentials เรียบร้อย");
            credentials.forEach((c) => {
                if (!currentStore.credentials) currentStore.credentials = {};
                currentStore.credentials[c.system] = {
                    username: c.username,
                    password: c.password,
                };
            });
            document.getElementById("tabView").innerHTML =
                buildViewTable(currentStore);
        }
    } catch (e) {
        showToast("เกิดข้อผิดพลาด", "error");
    }
    btn.disabled = false;
    btn.textContent = "บันทึกสำเร็จ";
}

async function importFile(input, type) {
    const file = input.files[0];
    if (!file) return;

    const url     = type === 'credentials' ? '/credentials/import' : '/stores/import';
    const formData = new FormData();
    formData.append('file', file);

    showToast('กำลัง import...');
    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData,
        });
        const data = await res.json();

        if (data.success) {
            let msg = `✓ Import สำเร็จ ${data.imported} รายการ`;
            if (data.errors.length) msg += ` (ข้าม ${data.errors.length} แถว)`;
            showToast(msg);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('เกิดข้อผิดพลาด', 'error');
        }
    } catch(e) {
        showToast('เกิดข้อผิดพลาด', 'error');
    }
    input.value = ''; // reset input
}

// ── Save store
async function saveStore(storeId) {
    const data = {
        Name: document.getElementById("editName").value,
        BU: document.getElementById("editBU").value,
        RM: document.getElementById("editRM").value,
        am_user_id: document.getElementById("editAM").value,
        ช่องทางการขาย: document.getElementById("editChannel").value,
    };
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = "กำลังบันทึก...";
    try {
        const res = await fetch(`/store/${storeId}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            body: JSON.stringify(data),
        });
        const d = await res.json();
        if (d.success) {
            showToast("✓ บันทึกข้อมูลร้านเรียบร้อย");
            currentStore.name = data.Name;
            currentStore.bu = data.BU;
            currentStore.rm = data.RM;
            currentStore.am = data.AM;
            currentStore.am = document.getElementById("editAM")
            .selectedOptions[0]?.text || "-";
            currentStore.channel = data["ช่องทางการขาย"];
            document.getElementById("modalName").textContent = data.Name;
            renderPage();
        }
    } catch (e) {
        showToast("เกิดข้อผิดพลาด", "error");
    }
    btn.disabled = false;
    btn.textContent = "บันทึกข้อมูลร้าน";
}

// Delete store
async function deleteStore(storeId) {
    if (!confirm(`ลบร้าน ${storeId} ใช่มั้ย? ข้อมูล credentials จะหายด้วย`))
        return;
    try {
        const res = await fetch(`/store/${storeId}`, {
            method: "DELETE",
            headers: { "X-CSRF-TOKEN": CSRF_TOKEN },
        });
        const d = await res.json();
        if (d.success) {
            allStores = allStores.filter((s) => s.id !== storeId);
            closeModalBtn();
            render(allStores);
            showToast("✓ ลบร้านเรียบร้อย");
        }
    } catch (e) {
        showToast("เกิดข้อผิดพลาด", "error");
    }
}

//Toggle PW / Copy / Toast 
function togglePw(btn, elId, e) {
    e.stopPropagation();
    const el = document.getElementById(elId);
    if (!el) return;
    const s = el.dataset.showing === "1";
    el.textContent = s ? "•••••" : el.dataset.val;
    el.dataset.showing = s ? "0" : "1";
    el.classList.toggle("val-mask", s);
    btn.classList.toggle("active", !s);
}
function copyTxt(btn, val, e, val2 = null) {
    e.stopPropagation();
    // ถ้ามี val2 = copy ทั้ง username และ password
    const text = val2 ? `Username: ${val}\nPassword: ${val2}` : val;
    navigator.clipboard.writeText(text).then(() => {
        btn.classList.add("copied");
        const o = btn.innerHTML;
        btn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`;
        setTimeout(() => {
            btn.classList.remove("copied");
            btn.innerHTML = o;
        }, 1600);
        showToast(val2 ? "✓ คัดลอกแล้ว" : "✓ คัดลอกแล้ว");
    });
}

function showToast(msg, type = "success") {
    const t = document.getElementById("toast");
    t.textContent = msg;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove("show"), 800);
}

document.getElementById("searchInput").addEventListener("input", function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchStores(1), 400); // debounce 400ms
});
//เพิ่ม debounce ให้ BU filter
document.getElementById("filterBU").addEventListener("change", function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(()=> fetchStores(1), 300);
});

function showSkeleton() {
    const rows = Array(10).fill(0).map(() => `
        <div class="skeleton-row">
            <div class="skeleton skeleton-cell"></div>
            <div class="skeleton skeleton-cell"></div>
            <div class="skeleton skeleton-cell"></div>
            <div class="skeleton skeleton-cell"></div>
            <div class="skeleton skeleton-cell"></div>
        </div>`).join('');
    document.getElementById('tableBody').innerHTML = rows;
}

// ฟังก์ชันหลักในการดึงข้อมูลร้านจากเซิร์ฟเวอร์ โดยส่งพารามิเตอร์ page, search, bu และอัปเดตตัวแปรทั้งหมดที่เกี่ยวข้องกับการแสดงผล จากนั้นเรียก render() และ renderPagination() เพื่ออัปเดตตารางและปุ่ม pagination
let currentFetch = null ; 
async function fetchStores(page) {
    if (currentFetch) currentFetch.abort();
    const controller = new AbortController();
    currentFetch = controller;

    showSkeleton();
    const search = document.getElementById("searchInput").value.trim();
    const bu = document.getElementById("filterBU").value;

    try {
        const params = new URLSearchParams({ page, search, bu });
        const res = await fetch(`/dashboard?${params}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            signal: controller.signal //ผูก abort signal
        });
        const data = await res.json();
    
        allStores = [];
        data.data.forEach((s) => allStores.push(s)); // flat array แล้ว JS group เอง

        totalPages  = data.pagination.last_page;
        currentPage = data.pagination.current_page;
        total       = data.pagination.total;

        render(allStores);
        renderPagination();
    } catch (e) {
        if (e.name === 'AbortError') return; // ถูก cancel ไม่ต้อง error
        console.error(e);
    }
}
// ฟังก์ชันสำหรับจัดการการคลิกปุ่ม pagination โดยตรวจสอบว่าหน้าที่ต้องการไปนั้นอยู่ในช่วงที่ถูกต้องหรือไม่
async function goPage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    await fetchStores(page);
    window.scrollTo({ top: 0, behavior: "smooth" });
}

// Helpers
function esc(s) {
    return String(s || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}
function escJs(s) {
    return String(s || "")
        .replace(/\\/g, "\\\\")
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"');
}
function escAttr(s) {
    return String(s || "").replace(/"/g, "&quot;");
}

// Init
showSkeleton();
fetchStores(1);

// Expose functions to global scope (required for inline onclick)
window.openModal = openModal;
window.closeModal = closeModal;
window.closeModalBtn = closeModalBtn;
window.switchTab = switchTab;
window.goPage = goPage;
window.togglePw = togglePw;
window.copyTxt = copyTxt;
window.saveCreds = saveCreds;
window.saveStore = saveStore;
window.deleteStore = deleteStore;
window.addCredRow = addCredRow;
window.removeCredRow = removeCredRow;
window.importFile = importFile;
window.switchSysTab = switchSysTab;
window.showSkeleton = showSkeleton;