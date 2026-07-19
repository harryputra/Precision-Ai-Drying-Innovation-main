@extends('layouts.app')
@section('title', 'Request Pengeringan')
@section('breadcrumb', 'Manajemen Batch / Request Pengeringan')

@section('content')

<div class="page-header-banner" style="padding:1.5rem 1.75rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">MANAJEMEN BATCH</div>
            <h2 style="font-size:1.5rem;font-weight:900;color:#fff;margin:0 0 0.375rem;">📋 Request Pengeringan</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">Permintaan pengeringan dari petani yang menunggu persetujuan.</p>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;">
            <a href="{{ route('web.batches.index') }}" class="btn-secondary btn-sm">← Kembali ke Batch</a>
        </div>
    </div>
</div>

<div style="padding:1.5rem;">

    {{-- Flash messages --}}
    @if(session('success'))
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #86efac;border-radius:12px;padding:12px 16px;margin-bottom:16px;color:#166534;font-size:.85rem;font-weight:600">
        ✅ {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:12px 16px;margin-bottom:16px;color:#dc2626;font-size:.85rem">
        ⚠️ {{ $errors->first() }}
    </div>
    @endif

    @if($requests->isEmpty())
    <div class="glass-card" style="padding:3rem;text-align:center;color:#64748b;">
        <div style="font-size:3rem;margin-bottom:1rem">✅</div>
        <div style="font-size:1rem;font-weight:600;margin-bottom:0.5rem">Tidak ada request yang menunggu</div>
        <div style="font-size:0.82rem">Semua permintaan pengeringan sudah diproses.</div>
    </div>
    @else

    <div style="display:flex;flex-direction:column;gap:1rem;">
        @foreach($requests as $req)
        <div class="glass-card" style="padding:1.25rem 1.5rem;" x-data="{ showApprove: false, showReject: false }">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">

                {{-- Info petani & gabah --}}
                <div style="flex:1;min-width:260px;">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#15803d,#22c55e);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0;">
                            {{ strtoupper(substr($req->petani_name ?? 'P', 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight:700;color:#0f172a;font-size:.95rem;">{{ $req->petani_name ?? 'Petani' }}</div>
                            <div style="font-size:.72rem;color:#64748b;">{{ $req->requested_at?->format('d M Y, H:i') ?? $req->created_at->format('d M Y, H:i') }} · {{ $req->requested_at?->diffForHumans() ?? $req->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:0.5rem;margin-bottom:0.75rem;">
                        <div style="background:rgba(249,115,22,0.08);border-radius:8px;padding:8px 10px;">
                            <div style="font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Varietas</div>
                            <div style="font-size:.88rem;font-weight:700;color:#0f172a;">{{ $req->rice_variety }}</div>
                        </div>
                        <div style="background:rgba(59,130,246,0.08);border-radius:8px;padding:8px 10px;">
                            <div style="font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Berat</div>
                            <div style="font-size:.88rem;font-weight:700;color:#0f172a;">{{ $req->initial_weight }} kg</div>
                        </div>
                        <div style="background:rgba(168,85,247,0.08);border-radius:8px;padding:8px 10px;">
                            <div style="font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Kadar Air</div>
                            <div style="font-size:.88rem;font-weight:700;color:#0f172a;">{{ $req->initial_moisture }}% → {{ $req->target_moisture }}%</div>
                        </div>
                    </div>

                    @if($req->request_notes)
                    <div style="background:#f8fafc;border-left:3px solid #cbd5e1;border-radius:0 8px 8px 0;padding:8px 12px;font-size:.8rem;color:#475569;">
                        💬 <em>{{ $req->request_notes }}</em>
                    </div>
                    @endif

                    <div style="margin-top:0.5rem;font-size:.7rem;color:#94a3b8;font-family:monospace;">
                        {{ $req->batch_code }}
                    </div>
                </div>

                {{-- Tombol aksi --}}
                <div style="display:flex;flex-direction:column;gap:0.5rem;min-width:160px;">
                    <button @click="showApprove = !showApprove; showReject = false"
                            style="background:linear-gradient(135deg,#15803d,#22c55e);color:#fff;border:none;border-radius:10px;padding:9px 16px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(21,128,61,.3)">
                        ✅ Setujui
                    </button>
                    <button @click="showReject = !showReject; showApprove = false"
                            style="background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;border-radius:10px;padding:9px 16px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 2px 8px rgba(220,38,38,.3)">
                        ❌ Tolak
                    </button>
                </div>
            </div>

            {{-- Form Approve --}}
            <div x-show="showApprove" x-transition style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e2e8f0;">
                <form method="POST" action="{{ route('web.batches.approve', $req) }}">
                    @csrf
                    <div style="margin-bottom:10px;">
                        <label style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Catatan untuk petani (opsional)</label>
                        <textarea name="operator_notes" rows="2"
                                  placeholder="Contoh: Mesin siap, gabah bisa langsung masuk..."
                                  style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:8px 12px;font-size:.82rem;font-family:inherit;outline:none;resize:vertical"
                                  onfocus="this.style.borderColor='#15803d'"
                                  onblur="this.style.borderColor='#e5e7eb'"></textarea>
                    </div>
                    <button type="submit"
                            style="background:linear-gradient(135deg,#14532d,#15803d);color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit">
                        ✅ Konfirmasi Setujui — Mulai Pengeringan
                    </button>
                </form>
            </div>

            {{-- Form Reject --}}
            <div x-show="showReject" x-transition style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e2e8f0;">
                <form method="POST" action="{{ route('web.batches.reject', $req) }}">
                    @csrf
                    <div style="margin-bottom:10px;">
                        <label style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Alasan penolakan <span style="color:#ef4444">*</span></label>
                        <textarea name="operator_notes" rows="2" required
                                  placeholder="Contoh: Mesin sedang maintenance, coba lagi besok..."
                                  style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:8px 12px;font-size:.82rem;font-family:inherit;outline:none;resize:vertical"
                                  onfocus="this.style.borderColor='#dc2626'"
                                  onblur="this.style.borderColor='#e5e7eb'"></textarea>
                    </div>
                    <button type="submit"
                            style="background:linear-gradient(135deg,#b91c1c,#dc2626);color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit">
                        ❌ Konfirmasi Tolak
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <div style="margin-top:1rem;">
        {{ $requests->links() }}
    </div>

    @endif
</div>
@endsection
