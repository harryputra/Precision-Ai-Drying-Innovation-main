@extends('layouts.viewer')
@section('title', 'Ajukan Pengeringan')

@section('content')

<div style="font-weight:800;font-size:1.1rem;color:#1f2937;margin-bottom:4px">🌾 Ajukan Pengeringan Gabah</div>
<p style="font-size:.82rem;color:#6b7280;margin-bottom:20px">
    Isi data gabah kamu. Operator akan memeriksa dan menyetujui sebelum mesin mulai bekerja.
</p>

{{-- Alert sukses --}}
@if(session('success'))
<div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #86efac;border-radius:14px;padding:14px 18px;margin-bottom:18px;color:#166534;font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:10px">
    ✅ {{ session('success') }}
</div>
@endif

{{-- Request pending yang masih menunggu --}}
@if($pendingRequest)
<div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1px solid #fcd34d;border-radius:16px;padding:18px 20px;margin-bottom:20px">
    <div style="font-weight:700;color:#92400e;font-size:.9rem;margin-bottom:8px">⏳ Ada Permintaan yang Sedang Menunggu</div>
    <div style="font-size:.83rem;color:#78350f;line-height:1.7">
        <div>Kode: <strong>{{ $pendingRequest->batch_code }}</strong></div>
        <div>Varietas: <strong>{{ $pendingRequest->rice_variety }}</strong></div>
        <div>Berat: <strong>{{ $pendingRequest->initial_weight }} kg</strong></div>
        <div>Diajukan: <strong>{{ $pendingRequest->requested_at?->diffForHumans() ?? '-' }}</strong></div>
        @if($pendingRequest->request_notes)
        <div>Catatan: {{ $pendingRequest->request_notes }}</div>
        @endif
    </div>
    <div style="margin-top:10px;font-size:.78rem;color:#92400e;background:rgba(255,255,255,.5);border-radius:8px;padding:8px 12px">
        ℹ️ Kamu sudah memiliki permintaan yang menunggu persetujuan operator. Tunggu sebentar ya!
    </div>
</div>
@else

{{-- Form --}}
<div style="background:#fff;border-radius:20px;padding:24px;box-shadow:0 2px 14px rgba(0,0,0,.07);border:1px solid rgba(0,0,0,.04)">

    <form method="POST" action="{{ route('viewer.request.store') }}">
        @csrf

        {{-- Varietas Padi --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:.83rem;font-weight:700;color:#374151;margin-bottom:6px">
                🌱 Varietas Padi <span style="color:#ef4444">*</span>
            </label>
            <select name="rice_variety" required
                    style="width:100%;border:2px solid #e5e7eb;border-radius:12px;padding:11px 14px;font-size:.9rem;font-family:inherit;background:#fff;outline:none;appearance:none;cursor:pointer"
                    onfocus="this.style.borderColor='#15803d';this.style.boxShadow='0 0 0 3px rgba(21,128,61,.1)'"
                    onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                <option value="">-- Pilih varietas --</option>
                <option value="IR64" {{ old('rice_variety') == 'IR64' ? 'selected' : '' }}>IR64</option>
                <option value="Ciherang" {{ old('rice_variety') == 'Ciherang' ? 'selected' : '' }}>Ciherang</option>
                <option value="Memberamo" {{ old('rice_variety') == 'Memberamo' ? 'selected' : '' }}>Memberamo</option>
                <option value="Mekongga" {{ old('rice_variety') == 'Mekongga' ? 'selected' : '' }}>Mekongga</option>
                <option value="Inpari 32" {{ old('rice_variety') == 'Inpari 32' ? 'selected' : '' }}>Inpari 32</option>
                <option value="Inpari 42" {{ old('rice_variety') == 'Inpari 42' ? 'selected' : '' }}>Inpari 42</option>
                <option value="Rojolele" {{ old('rice_variety') == 'Rojolele' ? 'selected' : '' }}>Rojolele</option>
                <option value="Pandan Wangi" {{ old('rice_variety') == 'Pandan Wangi' ? 'selected' : '' }}>Pandan Wangi</option>
                <option value="Lainnya" {{ old('rice_variety') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
            </select>
            @error('rice_variety')<div style="color:#ef4444;font-size:.75rem;margin-top:4px">{{ $message }}</div>@enderror
        </div>

        {{-- Berat Gabah --}}
        <div style="margin-bottom:18px">
            <label style="display:block;font-size:.83rem;font-weight:700;color:#374151;margin-bottom:6px">
                ⚖️ Berat Gabah (kg) <span style="color:#ef4444">*</span>
            </label>
            <input type="number" name="initial_weight" step="0.1" min="1" max="10000"
                   value="{{ old('initial_weight') }}" required
                   placeholder="Contoh: 500"
                   style="width:100%;border:2px solid #e5e7eb;border-radius:12px;padding:11px 14px;font-size:.9rem;font-family:inherit;outline:none"
                   onfocus="this.style.borderColor='#15803d';this.style.boxShadow='0 0 0 3px rgba(21,128,61,.1)'"
                   onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
            @error('initial_weight')<div style="color:#ef4444;font-size:.75rem;margin-top:4px">{{ $message }}</div>@enderror
        </div>

        {{-- Kadar Air Awal + Target berdampingan --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px">
            <div>
                <label style="display:block;font-size:.83rem;font-weight:700;color:#374151;margin-bottom:6px">
                    💧 Kadar Air Sekarang (%) <span style="color:#ef4444">*</span>
                </label>
                <input type="number" name="initial_moisture" step="0.1" min="10" max="35"
                       value="{{ old('initial_moisture', 24) }}" required
                       style="width:100%;border:2px solid #e5e7eb;border-radius:12px;padding:11px 14px;font-size:.9rem;font-family:inherit;outline:none"
                       onfocus="this.style.borderColor='#15803d';this.style.boxShadow='0 0 0 3px rgba(21,128,61,.1)'"
                       onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                <div style="font-size:.72rem;color:#9ca3af;margin-top:3px">Biasanya 22–25% saat panen</div>
                @error('initial_moisture')<div style="color:#ef4444;font-size:.75rem;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            <div>
                <label style="display:block;font-size:.83rem;font-weight:700;color:#374151;margin-bottom:6px">
                    🎯 Target Kadar Air (%) <span style="color:#ef4444">*</span>
                </label>
                <input type="number" name="target_moisture" step="0.1" min="10" max="20"
                       value="{{ old('target_moisture', 14) }}" required
                       style="width:100%;border:2px solid #e5e7eb;border-radius:12px;padding:11px 14px;font-size:.9rem;font-family:inherit;outline:none"
                       onfocus="this.style.borderColor='#15803d';this.style.boxShadow='0 0 0 3px rgba(21,128,61,.1)'"
                       onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                <div style="font-size:.72rem;color:#9ca3af;margin-top:3px">Standar GKG ≤ 14%</div>
                @error('target_moisture')<div style="color:#ef4444;font-size:.75rem;margin-top:4px">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Catatan tambahan --}}
        <div style="margin-bottom:22px">
            <label style="display:block;font-size:.83rem;font-weight:700;color:#374151;margin-bottom:6px">
                📝 Catatan untuk Operator (opsional)
            </label>
            <textarea name="request_notes" rows="3" maxlength="500"
                      placeholder="Contoh: Gabah basah karena hujan kemarin, tolong cek kondisinya..."
                      style="width:100%;border:2px solid #e5e7eb;border-radius:12px;padding:11px 14px;font-size:.88rem;font-family:inherit;outline:none;resize:vertical"
                      onfocus="this.style.borderColor='#15803d';this.style.boxShadow='0 0 0 3px rgba(21,128,61,.1)'"
                      onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">{{ old('request_notes') }}</textarea>
            @error('request_notes')<div style="color:#ef4444;font-size:.75rem;margin-top:4px">{{ $message }}</div>@enderror
        </div>

        {{-- Info proses --}}
        <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:12px;padding:12px 16px;margin-bottom:20px;font-size:.78rem;color:#166534;line-height:1.7">
            <div style="font-weight:700;margin-bottom:4px">ℹ️ Proses setelah mengajukan:</div>
            <div>1. Operator akan mendapat notifikasi permintaanmu</div>
            <div>2. Operator memeriksa dan menyiapkan mesin</div>
            <div>3. Setelah disetujui, mesin langsung mulai bekerja</div>
            <div>4. Kamu dapat notifikasi saat gabah selesai dikeringkan</div>
        </div>

        {{-- Tombol submit --}}
        <button type="submit"
                style="width:100%;background:linear-gradient(135deg,#14532d,#15803d);color:#fff;border:none;border-radius:14px;padding:14px;font-size:.95rem;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:0 4px 14px rgba(21,128,61,.35);transition:transform .1s"
                onmousedown="this.style.transform='scale(.98)'"
                onmouseup="this.style.transform='scale(1)'">
            📋 Ajukan Permintaan Pengeringan
        </button>
    </form>
</div>

@endif

{{-- Kembali ke dashboard --}}
<div style="margin-top:16px;text-align:center">
    <a href="{{ route('viewer.dashboard') }}"
       style="color:#6b7280;font-size:.82rem;text-decoration:none;display:inline-flex;align-items:center;gap:5px">
        ← Kembali ke Dashboard
    </a>
</div>

@endsection
