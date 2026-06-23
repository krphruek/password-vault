@extends('layouts.app')
@section('title', 'Password Vault')
@section('page-title', 'Password Vault')
@section('page-sub', 'ระบบจัดการ Password Vault')

@section('styles')
@vite(['resources/css/dashboard.css'])
@endsection

@section('content')

{{-- JS globals ต้องอยู่ก่อน dashboard.js --}}
<script>
/* prettier-ignore */
const USER_ROLE = '{{ Auth::user()->role }}';
const CSRF_TOKEN = '{{ csrf_token() }}';
const INIT_TOTAL_PAGES = {{ $totalPages ?? 1 }};
const INIT_TOTAL = {{ $total ?? 0 }};
const BUS_DATA = @json($bus ?? []);
const RMS_DATA = @json($rms ?? []);
const AMS_DATA = @json($ams ?? []);
const SYSTEMS_DATA = @json($systems ?? []);
</script>

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card">
        <p class="stat-label">ร้านในความดูแล</p>
        <p class="stat-value">{{ $total ?? 0 }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">สาขา (BU)</p>
        <p class="stat-value">{{ $buCount }}</p>
    </div>
</div>

{{-- Toolbar --}}
<div class="toolbar">
    <div class="search-wrap">

        <input type="text" class="search-input" id="searchInput" autocomplete="off"
            placeholder="ค้นหาร้าน, รหัส, AM, RM...">
    </div>
    <select class="filter-select" id="filterBU">
        <option value="">ทุก BU</option>
        @foreach($bus as $bu)
        <option value="{{ $bu }}">{{ $bu }}</option>
        @endforeach
    </select>

    @if(Auth::user()->role ==='admin')
    <button class="btn-add" onclick="document.getElementById('importStoreInput').click()">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <line x1="12" y1="3" x2="12" y2="15"/>
        <polyline points="7 9 12 4 17 9"/>
    </svg>
    Import Excel
    <input type="file"
        id="importStoreInput"
        accept=".xlsx,.xls,.csv"
        style="display:none"
        onchange="importFile(this,'stores')">
    </button>

    <a href="{{ route('dashboard.export') }}" class="btn-export">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
        </svg>
        Export Excel
    </a>
    @endif
    @if(Auth::user()->role === 'staff')

    <button class="btn-add" onclick="document.getElementById('importInput').click()">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
            <polyline points="7 9 12 4 17 9"/>
        </svg>
        Import Excel
        <input type="file"
                id="importInput"
               accept=".xlsx,.xls,.csv"
               style="display:none"
               onchange="importFile(this,'credentials')">
    </button>

    <a href="{{ route('credentials.export') }}" class="btn-export">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Export Excel
    </a>

@endif
</div>
{{-- Table --}}
<div class="table-wrap" id="tableWrap">
    <div class="table-header">
        <div class="th">ชื่อร้าน / รหัส</div>
        <div class="th">BU</div>
        <div class="th">ช่องทาง</div>
        <div class="th">RM</div>
        <div class="th">AM</div>
    </div>
    <div id="tableBody"></div>
</div>
<div id="pagination"></div>

{{-- Main Modal --}}
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
    <div class="modal">
        <div class="modal-header">
            <div>
                <div class="modal-store-name" id="modalName"></div>
                <div class="modal-store-meta" id="modalMeta"></div>
            </div>
            <button class="modal-close" onclick="closeModalBtn()">✕</button>
        </div>
        <div class="modal-tabs" id="modalTabs" style="display:none">
            <button class="modal-tab active" onclick="switchTab('view')">ดู User/Password</button>
            <button class="modal-tab" onclick="switchTab('editCred')">แก้ไข User/Password</button>
            <button class="modal-tab" id="tabEditStore" onclick="switchTab('editStore')"
                style="display:none">แก้ไขร้าน</button>
        </div>
        <div class="modal-body">
            <div id="tabView"></div>
            <div id="tabEditCred" style="display:none"></div>
            <div id="tabEditStore2" style="display:none"></div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

@endsection

@section('scripts')
@vite(['resources/js/dashboard.js'])
@endsection