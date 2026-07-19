@extends('layouts.app')
@section('title', 'API Settings')

@section('content')
<div style="max-width:820px;">
    <h1 style="font-size:1.25rem;font-weight:800;color:#1e293b;margin:0 0 0.25rem;display:flex;align-items:center;gap:0.5rem;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2" style="flex-shrink:0;">
            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
        </svg>
        API Settings
    </h1>
    <p style="font-size:0.82rem;color:#64748b;margin:0 0 1.5rem;">
        Kelola API keys untuk layanan AI dan cuaca. Keys disimpan <b>terenkripsi</b> di database.
    </p>

    @if(session('success'))
    <div style="margin-bottom:1rem;padding:0.7rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #16a34a;border-radius:10px;color:#166534;font-size:0.82rem;display:flex;align-items:center;gap:0.5rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="margin-bottom:1rem;padding:0.7rem 1rem;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:10px;color:#b91c1c;font-size:0.82rem;display:flex;align-items:center;gap:0.5rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('admin.api-settings.update') }}" id="apiSettingsForm">
        @csrf

        {{-- ═══ GEMINI ═══ --}}
        <div class="api-card" id="card-gemini">
            <div class="api-card-header">
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <div class="api-icon" style="background:linear-gradient(135deg,#1a73e8,#4285f4);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:0.95rem;color:#1e293b;">Google Gemini</div>
                        <div style="font-size:0.7rem;color:#64748b;">AI utama untuk analisis & chat</div>
                    </div>
                </div>
                <span class="status-badge {{ $hasGeminiKey ? 'status-ok' : 'status-none' }}">
                    {{ $hasGeminiKey ? '✓ Terkonfigurasi' : '✗ Belum diatur' }}
                </span>
            </div>

            <div class="api-card-body">
                <div class="field-group">
                    <label class="field-label">API Key</label>
                    <div style="position:relative;">
                        <input type="password" name="gemini_api_key" id="gemini_api_key"
                               value="{{ $geminiKey }}"
                               placeholder="AIzaSy... (dari Google AI Studio)"
                               class="field-input" autocomplete="off">
                        <button type="button" class="toggle-pass" onclick="togglePassword('gemini_api_key', this)" title="Tampilkan/sembunyikan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Model</label>
                    <select name="gemini_model" id="gemini_model" class="field-input" style="cursor:pointer;">
                        @php
                            $currentModel = $geminiModel;
                            $models = ['gemini-2.0-flash','gemini-2.5-flash','gemini-1.5-pro','gemini-1.5-flash'];
                        @endphp
                        @foreach($models as $m)
                            <option value="{{ $m }}" {{ $currentModel === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                        @if(!in_array($currentModel, $models))
                            <option value="{{ $currentModel }}" selected>{{ $currentModel }}</option>
                        @endif
                    </select>
                </div>

                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <button type="button" class="btn-test" onclick="testApi('gemini')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5,3 19,12 5,21 5,3"/></svg>
                        Test Koneksi
                    </button>
                    <div id="result-gemini" class="test-result"></div>
                </div>
            </div>
        </div>

        {{-- ═══ GROQ ═══ --}}
        <div class="api-card" id="card-groq">
            <div class="api-card-header">
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <div class="api-icon" style="background:linear-gradient(135deg,#f97316,#fb923c);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:0.95rem;color:#1e293b;">Groq</div>
                        <div style="font-size:0.7rem;color:#64748b;">Fallback AI saat Gemini rate-limited</div>
                    </div>
                </div>
                <span class="status-badge {{ $hasGroqKey ? 'status-ok' : 'status-none' }}">
                    {{ $hasGroqKey ? '✓ Terkonfigurasi' : '✗ Opsional' }}
                </span>
            </div>

            <div class="api-card-body">
                <div class="field-group">
                    <label class="field-label">API Key</label>
                    <div style="position:relative;">
                        <input type="password" name="groq_api_key" id="groq_api_key"
                               value="{{ $groqKey }}"
                               placeholder="gsk_... (dari console.groq.com)"
                               class="field-input" autocomplete="off">
                        <button type="button" class="toggle-pass" onclick="togglePassword('groq_api_key', this)" title="Tampilkan/sembunyikan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <button type="button" class="btn-test" onclick="testApi('groq')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5,3 19,12 5,21 5,3"/></svg>
                        Test Koneksi
                    </button>
                    <div id="result-groq" class="test-result"></div>
                </div>
            </div>
        </div>

        {{-- ═══ OPENWEATHER ═══ --}}
        <div class="api-card" id="card-openweather">
            <div class="api-card-header">
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <div class="api-icon" style="background:linear-gradient(135deg,#0ea5e9,#38bdf8);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:0.95rem;color:#1e293b;">OpenWeather</div>
                        <div style="font-size:0.7rem;color:#64748b;">Data cuaca & forecast untuk AI</div>
                    </div>
                </div>
                <span class="status-badge {{ $hasOpenweatherKey ? 'status-ok' : 'status-none' }}">
                    {{ $hasOpenweatherKey ? '✓ Terkonfigurasi' : '✗ Belum diatur' }}
                </span>
            </div>

            <div class="api-card-body">
                <div class="field-group">
                    <label class="field-label">API Key</label>
                    <div style="position:relative;">
                        <input type="password" name="openweather_api_key" id="openweather_api_key"
                               value="{{ $openweatherKey }}"
                               placeholder="(dari openweathermap.org/api)"
                               class="field-input" autocomplete="off">
                        <button type="button" class="toggle-pass" onclick="togglePassword('openweather_api_key', this)" title="Tampilkan/sembunyikan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    <button type="button" class="btn-test" onclick="testApi('openweather')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5,3 19,12 5,21 5,3"/></svg>
                        Test Koneksi
                    </button>
                    <div id="result-openweather" class="test-result"></div>
                </div>
            </div>
        </div>

        {{-- ═══ SAVE BUTTON ═══ --}}
        <div style="margin-top:1.25rem;display:flex;align-items:center;gap:0.75rem;">
            <button type="submit" class="btn-save">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/>
                </svg>
                Simpan Semua
            </button>
            <span style="font-size:0.72rem;color:#94a3b8;">Field kosong atau masked (•••) tidak akan diperbarui.</span>
        </div>
    </form>

    <p style="font-size:0.72rem;color:#94a3b8;line-height:1.6;margin-top:1.5rem;">
        <b>Catatan keamanan:</b> API keys dienkripsi dengan AES-256-CBC (Laravel Encryption) sebelum disimpan ke database.
        Nilai asli hanya bisa didekripsi oleh aplikasi ini (APP_KEY). Jika APP_KEY berubah, keys perlu diatur ulang.
        Field yang sudah tersimpan ditampilkan dengan mask (•••) — isi ulang hanya jika ingin mengganti.
    </p>
</div>

<style>
    .api-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        margin-bottom: 1rem;
        overflow: hidden;
        transition: box-shadow 0.2s, border-color 0.2s;
    }
    .api-card:hover {
        box-shadow: 0 4px 16px rgba(22,101,52,0.08);
        border-color: #bbf7d0;
    }
    .api-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .api-card-body {
        padding: 1rem 1.25rem;
    }
    .api-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }
    .status-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.7rem;
        border-radius: 999px;
    }
    .status-ok {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .status-none {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .field-group {
        margin-bottom: 0.75rem;
    }
    .field-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.3rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .field-input {
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        padding: 0.55rem 0.75rem;
        font-size: 0.82rem;
        color: #1e293b;
        background: #fafbfc;
        transition: border-color 0.15s, box-shadow 0.15s;
        box-sizing: border-box;
        font-family: 'SFMono-Regular', 'Consolas', monospace;
    }
    .field-input:focus {
        outline: none;
        border-color: #1d4ed8;
        box-shadow: 0 0 0 3px rgba(29,78,216,0.08);
        background: #fff;
    }
    .field-input::placeholder {
        color: #94a3b8;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .toggle-pass {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        padding: 4px;
        border-radius: 6px;
        display: flex;
        transition: color 0.15s;
    }
    .toggle-pass:hover { color: #475569; }

    .btn-test {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 0.78rem;
        padding: 0.5rem 0.9rem;
        border-radius: 9px;
        border: 1px solid #bfdbfe;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-test:hover {
        background: #dbeafe;
        box-shadow: 0 2px 8px rgba(29,78,216,0.12);
    }
    .btn-test:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .btn-test.loading {
        pointer-events: none;
    }
    .btn-test.loading svg {
        animation: spin 0.7s linear infinite;
    }

    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: linear-gradient(135deg, #166534, #16a34a);
        color: #fff;
        font-weight: 700;
        font-size: 0.85rem;
        padding: 0.65rem 1.5rem;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 12px rgba(22,101,52,0.25);
        transition: all 0.2s;
    }
    .btn-save:hover {
        box-shadow: 0 4px 20px rgba(22,101,52,0.35);
        transform: translateY(-1px);
    }

    .test-result {
        font-size: 0.78rem;
        font-weight: 600;
        transition: opacity 0.2s;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        flex-wrap: wrap;
    }
    .test-result.test-success { color: #166534; }
    .test-result.test-error   { color: #b91c1c; }
    .test-result .test-detail {
        font-weight: 400;
        font-size: 0.72rem;
        color: #64748b;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    /* Dark mode support */
    .dark .api-card {
        background: #1e293b;
        border-color: #334155;
    }
    .dark .api-card:hover {
        border-color: #475569;
        box-shadow: 0 4px 16px rgba(0,0,0,0.3);
    }
    .dark .api-card-header {
        border-bottom-color: #334155;
    }
    .dark .api-card-header div[style*="font-weight:700"] { color: #e2e8f0 !important; }
    .dark .field-input {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }
    .dark .field-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        background: #1e293b;
    }
    .dark .field-label { color: #94a3b8; }
    .dark .status-ok {
        background: rgba(22,101,52,0.2);
        border-color: rgba(22,163,74,0.3);
    }
    .dark .status-none {
        background: rgba(146,64,14,0.2);
        border-color: rgba(253,230,138,0.3);
    }
</style>

@push('scripts')
<script>
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
}

function testApi(service) {
    const btn       = event.target.closest('.btn-test');
    const resultDiv = document.getElementById('result-' + service);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Build payload
    let url, body = {};

    switch (service) {
        case 'gemini':
            url = '{{ route("admin.api-settings.test-gemini") }}';
            body.api_key = document.getElementById('gemini_api_key').value;
            body.model   = document.getElementById('gemini_model').value;
            break;
        case 'groq':
            url = '{{ route("admin.api-settings.test-groq") }}';
            body.api_key = document.getElementById('groq_api_key').value;
            break;
        case 'openweather':
            url = '{{ route("admin.api-settings.test-openweather") }}';
            body.api_key = document.getElementById('openweather_api_key').value;
            break;
    }

    // Loading state
    btn.disabled = true;
    btn.classList.add('loading');
    const origHTML = btn.innerHTML;
    btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Testing...';
    resultDiv.className = 'test-result';
    resultDiv.innerHTML = '';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            resultDiv.className = 'test-result test-success';
            let detail = '';
            if (data.response_time) detail += data.response_time;
            if (data.reply) detail += ' — "' + data.reply.substring(0, 50) + '"';
            resultDiv.innerHTML = '✓ ' + data.message
                + (detail ? ' <span class="test-detail">(' + detail + ')</span>' : '');
        } else {
            resultDiv.className = 'test-result test-error';
            resultDiv.innerHTML = '✗ ' + data.message;
        }
    })
    .catch(err => {
        resultDiv.className = 'test-result test-error';
        resultDiv.innerHTML = '✗ Network error: ' + err.message;
    })
    .finally(() => {
        btn.disabled = false;
        btn.classList.remove('loading');
        btn.innerHTML = origHTML;
    });
}
</script>
@endpush
@endsection
