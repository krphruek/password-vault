/* resources/js/manage.js */
function switchManageTab(tab) {
    document.querySelectorAll('.manage-tab').forEach((t, i) => {
        t.classList.toggle('active', ['store','bu','rm','am','system'][i] === tab);
    });
    document.querySelectorAll('.manage-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panel-' + tab).style.display = 'block';
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 2200);
}

//ค้นหาในตาราง (client-side filter)
function filterManageTable(panelId, query) {
    const q = query.trim().toLowerCase();
    const rows = document.querySelectorAll(`#${panelId} .manage-table tbody tr`);
    rows.forEach(row => {
        if (row.querySelector('.manage-empty')) return;
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
}

// Popup helpers
function showPopup(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function hidePopup(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
function closePopup(e, id) {
    if (e.target === document.getElementById(id)) hidePopup(id);
}

//เพิ่มร้าน
async function addStore() {
    const ID = document.getElementById('newID').value.trim();
    const Name = document.getElementById('newName').value.trim();
    if (!ID || !Name) { showToast('กรอก ID และชื่อร้านด้วย', 'error'); return; }

    const btn = document.getElementById('btnAddStore');
    btn.disabled = true; btn.textContent = 'กำลังเพิ่ม...';
    try {
        const res = await fetch('/store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({
                ID, Name,
                BU: document.getElementById('newBU').value.trim(),
                RM: document.getElementById('newRM').value.trim(),
                am_user_id: document.getElementById('newAM').value,
                ช่องทางการขาย: document.getElementById('newChannel').value.trim(),
            })
        });
        const d = await res.json();
        if (d.success) {
            showToast('✓ เพิ่มร้านเรียบร้อย');
            setTimeout(() => reloadKeepTab(), 600);
        } else {
            showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
    btn.disabled = false; btn.textContent = 'เพิ่มร้าน';
}
// ล้างฟอร์ม
function clearStoreForm(){
    ['newID','newName','newChannel'].forEach(id => document.getElementById(id).value='');
    ['newBU','newRM','newAM'].forEach(id => document.getElementById(id).selectedIndex =0);
}
//เพิ่ม BU
function openAddBU(){
    document.getElementById('newBUName').value = '';
    showPopup('addBUOverlay');
}

async function saveAddBU() {
    const name = document.getElementById('newBUName').value.trim().toUpperCase();
    if (!name) { showToast('กรอกชื่อ BU', 'error'); return; }
    try {
        const res = await fetch('/manage/bu/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ name })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ เพิ่ม BU เรียบร้อย'); 
            setTimeout(() => reloadKeepTab(), 500); }
        else showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
    } catch(e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}
//BU
function openEditBU(name) {
    document.getElementById('editBUOld').value = name;
    document.getElementById('editBUNew').value = name;
    showPopup('editBUOverlay');
}

async function saveEditBU() {
    const oldName = document.getElementById('editBUOld').value;
    const newName = document.getElementById('editBUNew').value.trim();
    if (!newName) { showToast('กรอกชื่อ BU', 'error'); return; }

    try {
        const res = await fetch('/manage/bu/rename', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ old: oldName, new: newName })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ บันทึกเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}

async function deleteBU(name) {
    if (!confirm(`ลบ BU "${name}"? ร้านที่ใช้ BU นี้จะกลายเป็นค่าว่าง`)) return;
    try {
        const res = await fetch('/manage/bu/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ value: name })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ ลบเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}

//เพิ่ม RM
function openAddRM() {
    document.getElementById('newRMName').value = '';
    showPopup('addRMOverlay');
}

async function saveAddRM() {
    const name = document.getElementById('newRMName').value.trim();
    if (!name) { showToast('กรอกชื่อ RM', 'error'); return; }
    try {
        const res = await fetch('/manage/rm/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ name })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ เพิ่ม RM เรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
        else showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
    } catch(e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}
//RM
function openEditRM(name) {
    document.getElementById('editRMOld').value = name;
    document.getElementById('editRMNew').value = name;
    showPopup('editRMOverlay');
}

async function saveEditRM() {
    const oldName = document.getElementById('editRMOld').value;
    const newName = document.getElementById('editRMNew').value.trim();
    if (!newName) { showToast('กรอกชื่อ RM', 'error'); return; }

    try {
        const res = await fetch('/manage/rm/rename', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ old: oldName, new: newName })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ บันทึกเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}

async function deleteRM(name) {
    if (!confirm(`ลบ RM "${name}"? ร้านที่ใช้ RM นี้จะกลายเป็นค่าว่าง`)) return;
    try {
        const res = await fetch('/manage/rm/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ value: name })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ ลบเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}

//AM
function openAddAM() {
    document.getElementById('amOverlayTitle').textContent = 'เพิ่ม AM ใหม่';
    document.getElementById('amId').value = '';
    document.getElementById('amUsername').value = '';
    document.getElementById('amName').value = '';
    document.getElementById('amPin').value = '';

    document.getElementById('amUsernameGroup').style.display    = 'block';
    document.getElementById('amPinGroup').style.display    = 'block';

    document.getElementById('amUsernameEditGroup').style.display = 'none';
    document.getElementById('amPinEditGroup').style.display      = 'none';
    document.getElementById('amActiveGroup').style.display       = 'none';

    document.getElementById('btnSaveAM').textContent = 'เพิ่ม AM';
    document.getElementById('btnSaveAM').setAttribute('onclick', 'createAM()');
    showPopup('amOverlay');
}

function openEditAM(id, name, isActive) {
    document.getElementById('amOverlayTitle').textContent = 'แก้ไข AM';
    document.getElementById('amId').value = id;
    document.getElementById('amName').value = name;
    document.getElementById('amActive').checked = isActive;

    document.getElementById('amUsernameGroup').style.display    = 'none';
    document.getElementById('amUsernameEditGroup').style.display = 'block';
    document.getElementById('amPinGroup').style.display          = 'none';
    document.getElementById('amPinEditGroup').style.display      = 'block';
    document.getElementById('amActiveGroup').style.display       = 'flex';
    document.getElementById('amPinEdit').value = '';

    document.getElementById('btnSaveAM').textContent = 'บันทึก';
    document.getElementById('btnSaveAM').setAttribute('onclick', 'updateAM()');
    showPopup('amOverlay');
}

async function createAM() {
    const username = document.getElementById('amUsername').value.trim();
    const name = document.getElementById('amName').value.trim();
    const pin = document.getElementById('amPin').value.trim();
    if (!username || !name || !pin) { showToast('กรอกข้อมูลให้ครบ', 'error'); return; }

    const btn = document.getElementById('btnSaveAM');
    btn.disabled = true; btn.textContent = 'กำลังเพิ่ม...';
    try {
        const res = await fetch('/manage/am', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ username, name, pin })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ เพิ่ม AM เรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
        else showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
    btn.disabled = false; btn.textContent = 'เพิ่ม AM';
}

async function updateAM() {
    const id       = document.getElementById('amId').value;
    const name     = document.getElementById('amName').value.trim();
    const username = document.getElementById('amUsernameEdit').value.trim();
    const pin      = document.getElementById('amPinEdit').value.trim();
    const isActive = document.getElementById('amActive').checked;

    if (!name) { showToast('กรอกชื่อ AM', 'error'); return; }

    const payload = { name, is_active: isActive };
    if (username) payload.username = username;
    if (pin)      payload.pin      = pin;

    try {
        const res = await fetch(`/manage/am/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify(payload)
        });
        const d = await res.json();
        if (d.success) { showToast('✓ บันทึกเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
        else showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
    } catch(e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}

async function saveAM() {
    const id = document.getElementById('amId').value;
    if (id){
        await updateAM(); //มี id = แก้ไข
    } else {
        await createAM(); //ไม่มี id = เพิ่ม
    }
}

async function deleteAM(id, username) {
    if (!confirm(`ลบ AM "${username}"? ร้านที่มี AM นี้จะกลายเป็นไม่มี AM`)) return;
    try {
        const res = await fetch(`/manage/am/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const d = await res.json();
        if (d.success) { showToast('✓ ลบเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}

//Systems 
function openAddSystem() {
    document.getElementById('sysOverlayTitle').textContent = 'เพิ่มระบบใหม่';
    document.getElementById('sysId').value = '';
    document.getElementById('sysName').value = '';
    document.getElementById('sysColor').value = '#5b8af7';
    document.getElementById('btnSaveSys').textContent = 'เพิ่มระบบ';
    document.getElementById('btnSaveSys').setAttribute('onclick', 'createSystem()');
    showPopup('sysOverlay');
}

function openEditSystem(id, name, color) {
    document.getElementById('sysOverlayTitle').textContent = 'แก้ไขระบบ';
    document.getElementById('sysId').value = id;
    document.getElementById('sysName').value = name;
    document.getElementById('sysColor').value = color;
    document.getElementById('btnSaveSys').textContent = 'บันทึก';
    document.getElementById('btnSaveSys').setAttribute('onclick', 'updateSystem()');
    showPopup('sysOverlay');
}

async function createSystem() {
    const name = document.getElementById('sysName').value.trim();
    const color = document.getElementById('sysColor').value;
    if (!name) { showToast('กรอกชื่อระบบ', 'error'); return; }

    const btn = document.getElementById('btnSaveSys');
    btn.disabled = true; btn.textContent = 'กำลังเพิ่ม...';
    try {
        const res = await fetch('/manage/system', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ name, color })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ เพิ่มระบบเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
        else showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
    btn.disabled = false; btn.textContent = 'เพิ่มระบบ';
}

async function updateSystem() {
    const id = document.getElementById('sysId').value;
    const name = document.getElementById('sysName').value.trim();
    const color = document.getElementById('sysColor').value;
    if (!name) { showToast('กรอกชื่อระบบ', 'error'); return; }

    try {
        const res = await fetch(`/manage/system/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ name, color })
        });
        const d = await res.json();
        if (d.success) { showToast('✓ บันทึกเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
        else showToast(d.message || 'เกิดข้อผิดพลาด', 'error');
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}

async function deleteSystem(id, name) {
    if (!confirm(`ลบระบบ "${name}"?`)) return;
    try {
        const res = await fetch(`/manage/system/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const d = await res.json();
        if (d.success) { showToast('✓ ลบเรียบร้อย'); setTimeout(() => reloadKeepTab(), 500); }
        else showToast(d.message || 'ลบไม่ได้ เพราะมีข้อมูลใช้งานอยู่', 'error');
    } catch (e) { showToast('เกิดข้อผิดพลาด', 'error'); }
}
//เก็บ tab ที่อยู่ไว้ก่อน reload
function reloadKeepTab() {
    const activeTab = document.querySelector('.manage-tab.active')?.textContent.trim();
    const tabMap = {
        'เพิ่มร้าน': 'store',
        'BU': 'bu',
        'RM': 'rm',
        'AM': 'am',
        'ระบบ (Systems)': 'system',
    };
    const tab = tabMap[activeTab] || 'store';
    sessionStorage.setItem('activeManageTab', tab);
    location.reload();
}
// อ่าน tab จาก sessionStorage
document.addEventListener('DOMContentLoaded', () => {
    const savedTab = sessionStorage.getItem('activeManageTab');
    if (savedTab) {
        switchManageTab(savedTab);
        sessionStorage.removeItem('activeManageTab');
    }
});

//Expose to global scope (inline onclick ต้องใช้)
window.switchManageTab   = switchManageTab;
window.filterManageTable = filterManageTable;
window.hidePopup         = hidePopup;
window.closePopup        = closePopup;
window.addStore          = addStore;
window.clearStoreForm    = clearStoreForm;
window.openAddBU         = openAddBU;
window.saveAddBU         = saveAddBU;
window.openEditBU        = openEditBU;
window.saveEditBU        = saveEditBU;
window.deleteBU          = deleteBU;
window.openEditRM        = openEditRM;
window.openAddRM         = openAddRM;
window.saveAddRM         = saveAddRM;
window.saveEditRM        = saveEditRM;
window.deleteRM          = deleteRM;
window.openAddAM         = openAddAM;
window.openEditAM        = openEditAM;
window.createAM          = createAM;
window.updateAM          = updateAM;
window.saveAM            = saveAM;
window.deleteAM          = deleteAM;
window.openAddSystem     = openAddSystem;
window.openEditSystem    = openEditSystem;
window.createSystem      = createSystem;
window.updateSystem      = updateSystem;
window.deleteSystem      = deleteSystem;
window.reloadKeepTab     = reloadKeepTab;