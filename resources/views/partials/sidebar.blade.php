<div class="sidebar">
    <div class="sidebar-logo">
        <img src="{{ asset('images/com7logo.png') }}" alt="Logo" class="sidebar-logo-img">
    </div>
    <nav class="sidebar-nav">
        <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <i class="bi bi-key-fill"></i><span>Password Vault</span>
        </a>
        
        @if(Auth::user()->role === 'admin')
        <a href="{{ route('manage.index') }}" class="nav-item {{ request()->is('manage') ? 'active' : '' }}">
            <i class="bi bi-gear-fill"></i><span>จัดการระบบ</span>
        </a>
        @endif

    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div>
                <p class="user-name">{{ Auth::user()->name }}</p>
                <span class="role-badge role-{{ Auth::user()->role }}">{{ Auth::user()->role }}</span>
            </div>

        </div>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="logout-btn">ออก</button>
        </form>
    </div>
</div>