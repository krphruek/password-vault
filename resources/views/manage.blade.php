@extends('layouts.app')
@section('title', 'จัดการระบบ')
@section('page-title', 'จัดการระบบ')
@section('page-sub', 'เพิ่มร้าน / จัดการ BU, RM, AM, Systems')

@section('styles')
@vite(['resources/css/dashboard.css'])
@vite(['resources/css/manage.css'])
@endsection

@section('content')

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
</script>

<a href="{{ route('dashboard') }}" class="manage-back">
    <svg width="14" height="14" fill="none" stroke="currentColor" 
    stroke-width="2" viewBox="0 0 24 24">
    <polyline points="15 18 9 12 15 6"/></svg>
    กลับหน้า Dashboard
</a>

<div class="manage-tabs">
    <button class="manage-tab active" onclick="switchManageTab('store')">เพิ่มร้าน</button>
    <button class="manage-tab" onclick="switchManageTab('bu')">BU</button>
    <button class="manage-tab" onclick="switchManageTab('rm')">RM</button>
    <button class="manage-tab" onclick="switchManageTab('am')">AM</button>
    <button class="manage-tab" onclick="switchManageTab('system')">ระบบ (Systems)</button>
</div>

{{-- Tab: เพิ่มร้าน --}}
<div class="manage-panel" id="panel-store">
    <div class="store-form-grid">

        <div class="store-form-section">
            <p class="store-form-section-title">ข้อมูลหลัก</p>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">รหัสร้าน (ID) <span class="form-required">*</span></label>
                    <input type="text" class="form-input" id="newID" placeholder="เช่น 1234" autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label">ชื่อร้าน <span class="form-required">*</span></label>
                    <input type="text" class="form-input" id="newName" placeholder="ชื่อร้าน" autocomplete="off">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">BU</label>
                    <select class="form-input" id="newBU">
                        <option value="">เลือก BU</option>
                        @foreach($bus as $b)
                        <option value="{{ $b['name'] }}">{{ $b['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">RM</label>
                    <select class="form-input" id="newRM">
                        <option value="">เลือก RM</option>
                        @foreach($rms as $rm)
                        <option value="{{ $rm['name'] }}">{{ $rm['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">AM</label>
                    <select class="form-input" id="newAM">
                        <option value="">เลือก AM</option>
                        @foreach($ams as $am)
                        <option value="{{ $am->id }}">{{ $am->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">ช่องทางการขาย</label>
                    <input type="text" class="form-input" id="newChannel" placeholder="เช่น AAR" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="store-form-footer">
            <button type="button" class="btn-ghost" onclick="clearStoreForm()">ล้างข้อมูล</button>
            <button type="button" class="btn-save" id="btnAddStore" onclick="addStore()">เพิ่มร้าน</button>
        </div>

    </div>
</div>
{{-- Tab: BU --}}
<div class="manage-panel" id="panel-bu" style="display:none">
    <div class="manage-table-header">
        <div class="manage-search-wrap" style="margin-bottom:0">
            <input type="text" class="manage-search-input" placeholder="ค้นหา BU..." oninput="filterManageTable('panel-bu', this.value)">
        </div>
        <button class="btn-add" onclick="openAddBU()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            เพิ่ม BU
        </button>
    </div>
    {{--popup เพิ่ม BU --}}
    <div class="modal-overlay" id="addBUOverlay" onclick="closePopup(event,'addBUOverlay')">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <div class="modal-store-name">เพิ่ม BU ใหม่</div>
            <button class="modal-close" onclick="hidePopup('addBUOverlay')">✕</button>
        </div>
        <div class="modal-body"><div class="edit-panel">
            <div class="form-group"><label class="form-label">ชื่อ BU</label>
                <input type="text" class="form-input" id="newBUName" autocomplete="off"></div>
            <button class="btn-save" onclick="saveAddBU()">เพิ่ม BU</button>
        </div></div>
    </div>
</div>
    <table class="manage-table">
        <thead>
            <tr><th>ชื่อ BU</th><th style="width:100px">จำนวนร้าน</th><th style="width:70px"></th></tr>
        </thead>
        <tbody>
            @forelse($bus as $b)
            <tr>
                <td>{{ $b['name'] }}</td>
                <td class="manage-muted">{{ $b['count'] }} ร้าน</td>
                <td class="manage-actions">
                    <button class="manage-icon-btn" title="แก้ไข" onclick="openEditBU('{{ $b['name'] }}')">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" 
                        viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    </button>
                    <button class="manage-icon-btn danger" title="ลบ" onclick="deleteBU('{{ $b['name'] }}')">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" 
                        viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                    </svg>
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" class="manage-empty">ยังไม่มี BU ในระบบ</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{--Tab: RM--}}
<div class="manage-panel" id="panel-rm" style="display:none">
    <div class="manage-table-header">
        <div class="manage-search-wrap" style="margin-bottom:0">
            <input type="text" class="manage-search-input" placeholder="ค้นหา RM..." oninput="filterManageTable('panel-rm', this.value)">
        </div>
        <button class="btn-add" onclick="openAddRM()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            เพิ่ม RM
        </button>
    </div>
    <div class="modal-overlay" id="addRMOverlay" onclick="closePopup(event,'addRMOverlay')">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <div class="modal-store-name">เพิ่ม RM ใหม่</div>
            <button class="modal-close" onclick="hidePopup('addRMOverlay')">✕</button>
        </div>
        <div class="modal-body"><div class="edit-panel">
            <div class="form-group"><label class="form-label">ชื่อ RM</label>
                <input type="text" class="form-input" id="newRMName" autocomplete="off"></div>
            <button class="btn-save" onclick="saveAddRM()">เพิ่ม RM</button>
        </div></div>
    </div>
</div>
    <table class="manage-table">
        <thead>
            <tr><th>ชื่อ RM</th><th style="width:100px">จำนวนร้าน</th><th style="width:70px"></th></tr>
        </thead>
        <tbody>
            @forelse($rms as $rm)
            <tr>
                <td>{{ $rm['name'] }}</td>
                <td class="manage-muted">{{ $rm['count'] }} ร้าน</td>
                <td class="manage-actions">
                    <button class="manage-icon-btn" title="แก้ไข" onclick="openEditRM('{{ $rm['name'] }}')">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" 
                        viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    </button>
                    <button class="manage-icon-btn danger" title="ลบ" onclick="deleteRM('{{ $rm['name'] }}')">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" 
                        viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                    </svg>
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" class="manage-empty">ยังไม่มี RM ในระบบ</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{--Tab: AM--}}
<div class="manage-panel" id="panel-am" style="display:none">
    <div class="manage-table-header">
        <div class="manage-search-wrap" style="margin-bottom:0">
            <input type="text" class="manage-search-input" placeholder="ค้นหา AM..." oninput="filterManageTable('panel-am', this.value)">
        </div>
        <button class="btn-add" onclick="openAddAM()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            เพิ่ม AM
        </button>
    </div>
    <table class="manage-table">
        <thead>
            <tr><th style="width:90px">รหัส</th><th>ชื่อ</th><th style="width:90px">สถานะ</th><th style="width:70px"></th></tr>
        </thead>
        <tbody>
            @forelse($ams as $am)
            <tr>
                <td class="manage-mono">{{ $am->username }}</td>
                <td>{{ $am->name }}</td>
                <td>
                    <span class="manage-status-badge {{ $am->is_active ? 'manage-status-active' : 'manage-status-inactive' }}">
                        {{ $am->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}
                    </span>
                </td>
                <td class="manage-actions">
                    <button class="manage-icon-btn" title="แก้ไข"
                            data-id="{{ $am->id }}"
                            data-name="{{ $am->name }}"
                            data-active="{{ $am->is_active ? 'true' : 'false' }}"
                            onclick="openEditAM(this.dataset.id, this.dataset.name, this.dataset.active === 'true')">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="manage-icon-btn danger" title="ลบ"
                            data-id="{{ $am->id }}"
                            data-username="{{ $am->username }}"
                            onclick="deleteAM(this.dataset.id, this.dataset.username)">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="manage-empty">ยังไม่มี AM ในระบบ</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{--Tab: Systems--}}
<div class="manage-panel" id="panel-system" style="display:none">
    <div class="manage-table-header">
        <span></span>
        <button class="btn-add" onclick="openAddSystem()">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
            เพิ่มระบบ
        </button>
    </div>
    <table class="manage-table">
        <thead>
            <tr><th style="width:50px"></th><th>ชื่อระบบ</th><th style="width:70px"></th></tr>
        </thead>
        <tbody>
            @forelse($systems as $sys)
            <tr>
                <td><span class="manage-color-dot" style="background:{{ $sys->color }}"></span></td>
                <td>{{ $sys->name }}</td>
                <td class="manage-actions">
                    <button class="manage-icon-btn" title="แก้ไข" onclick='openEditSystem({{ $sys->id }}, {{ json_encode($sys->name) }}, {{ json_encode($sys->color) }})'>
                        <svg width="15" height="15" fill="none" stroke="currentColor" 
                        stroke-width="2" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="manage-icon-btn danger" title="ลบ" onclick="deleteSystem({{ $sys->id }}, {{ json_encode($sys->name) }})">
                        <svg width="15" height="15" fill="none" stroke="currentColor"
                         stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/>
                         <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/>
                         <path d="M14 11v6"/></svg>
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" class="manage-empty">ยังไม่มีระบบในระบบ</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{--Popup: แก้ไข BU--}}
<div class="modal-overlay" id="editBUOverlay" onclick="closePopup(event,'editBUOverlay')">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <div class="modal-store-name">แก้ไขชื่อ BU</div>
            <button class="modal-close" onclick="hidePopup('editBUOverlay')">✕</button>
        </div>
        <div class="modal-body"><div class="edit-panel">
            <input type="hidden" id="editBUOld">
            <div class="form-group"><label class="form-label">ชื่อ BU</label>
                <input type="text" class="form-input" id="editBUNew" autocomplete="off"></div>
            <button class="btn-save" onclick="saveEditBU()">บันทึก</button>
        </div></div>
    </div>
</div>

{{-- Popup: แก้ไข RM --}}
<div class="modal-overlay" id="editRMOverlay" onclick="closePopup(event,'editRMOverlay')">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <div class="modal-store-name">แก้ไขชื่อ RM</div>
            <button class="modal-close" onclick="hidePopup('editRMOverlay')">✕</button>
        </div>
        <div class="modal-body"><div class="edit-panel">
            <input type="hidden" id="editRMOld">
            <div class="form-group"><label class="form-label">ชื่อ RM</label>
                <input type="text" class="form-input" id="editRMNew" autocomplete="off"></div>
            <button class="btn-save" onclick="saveEditRM()">บันทึก</button>
        </div></div>
    </div>
</div>

{{--  Popup: เพิ่ม / แก้ไข AM  --}}
<div class="modal-overlay" id="amOverlay" onclick="closePopup(event,'amOverlay')">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <div class="modal-store-name" id="amOverlayTitle">เพิ่ม AM ใหม่</div>
            <button class="modal-close" onclick="hidePopup('amOverlay')">✕</button>
        </div>
        <div class="modal-body"><div class="edit-panel">
            <input type="hidden" id="amId">
            <div class="form-group" id="amUsernameGroup"><label class="form-label">รหัสพนักงาน (Username) *</label>
                <input type="text" class="form-input" id="amUsername" placeholder="เช่น 9031" autocomplete="off"></div>
            <div class="form-group"><label class="form-label">ชื่อ AM *</label>
                <input type="text" class="form-input" id="amName" autocomplete="off"></div>
            <div class="form-group" id="amPinGroup"><label class="form-label">PIN เริ่มต้น *</label>
                <input type="text" class="form-input" id="amPin" placeholder="เช่น 123456" autocomplete="off"></div>
            <label class="manage-toggle" id="amActiveGroup" style="display:none;margin-bottom:14px">
                <input type="checkbox" id="amActive"><span>ใช้งาน</span>
            </label>
            <button class="btn-save" id="btnSaveAM" onclick="saveAM()">เพิ่ม AM</button>
        </div></div>
    </div>
</div>

{{--Popup: เพิ่ม / แก้ไข ระบบ --}}
<div class="modal-overlay" id="sysOverlay" onclick="closePopup(event,'sysOverlay')">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <div class="modal-store-name" id="sysOverlayTitle">เพิ่มระบบใหม่</div>
            <button class="modal-close" onclick="hidePopup('sysOverlay')">✕</button>
        </div>
        <div class="modal-body"><div class="edit-panel">
            <input type="hidden" id="sysId">
            <div class="form-group"><label class="form-label">ชื่อระบบ *</label>
                <input type="text" class="form-input" id="sysName" placeholder="เช่น ShopeePay" autocomplete="off"></div>
            <div class="form-group"><label class="form-label">สี</label>
                <input type="color" class="form-input manage-color-input" id="sysColor" value="#5b8af7"></div>
            <button class="btn-save" id="btnSaveSys" onclick="saveSystem()">เพิ่มระบบ</button>
        </div></div>
    </div>
</div>

<div class="toast" id="toast"></div>

@endsection

@section('scripts')
@vite(['resources/js/manage.js'])
@endsection