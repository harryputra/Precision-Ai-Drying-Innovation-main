@extends('layouts.app')
@section('title', __('app.knowledge_base'))
@section('breadcrumb', __('app.nav_ai_system') . ' / ' . __('app.knowledge_base'))

@section('content')

{{-- Page header --}}
<div class="page-header-banner" style="margin-bottom:1.25rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="font-size:0.7rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-bottom:0.375rem;">AI System</div>
            <h2 style="font-size:1.5rem;font-weight:900;letter-spacing:-0.02em;color:#fff;margin:0 0 0.375rem;">{{ __('app.knowledge_base') }}</h2>
            <p style="font-size:0.82rem;color:rgba(255,255,255,0.95);margin:0;">{{ __('app.knowledge_desc') }}</p>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.4rem;">
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.6);background:rgba(255,255,255,0.1);border-radius:6px;padding:2px 10px;border:1px solid rgba(255,255,255,0.15);">{{ __('app.knowledge_base') }}</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">·</span>
                <span style="font-size:0.7rem;color:rgba(255,255,255,0.5);">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
        @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
        <button onclick="openModal('create')" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('app.add_knowledge') }}
        </button>
        @endif
    </div>
</div>

{{-- Filter --}}
<form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1.25rem;">
    <div style="flex:1;min-width:180px;">
        <label class="label-dark">{{ __('app.search') }}</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('app.search') }}" class="input-dark">
    </div>
    <div>
        <label class="label-dark">{{ __('app.knowledge_category') }}</label>
        <select name="category" class="input-dark" style="width:190px;">
            <option value="">{{ __('app.all_types') }}</option>
            @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$cat)) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn-primary btn-sm">{{ __('app.search_btn') }}</button>
    <a href="{{ route('web.knowledge.index') }}" class="btn-secondary btn-sm">{{ __('app.reset') }}</a>
</form>

@php
$categoryGradients = [
    'drying_rules'     => ['linear-gradient(135deg,#78350f,#d97706)',  '#fde68a', 'badge-orange', 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'],
    'rice_varieties'   => ['linear-gradient(135deg,#064e3b,#059669)',  '#a7f3d0', 'badge-green',  'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z'],
    'weather_patterns' => ['linear-gradient(135deg,#0c4a6e,#0891b2)',  '#a5f3fc', 'badge-blue',   'M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z'],
    'equipment_specs'  => ['linear-gradient(135deg,#4c1d95,#7c3aed)',  '#ddd6fe', 'badge-purple', 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z'],
    'troubleshooting'  => ['linear-gradient(135deg,#7f1d1d,#dc2626)',  '#fca5a5', 'badge-red',    'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'],
    'best_practices'   => ['linear-gradient(135deg,#1e3a8a,#1d4ed8)',  '#bfdbfe', 'badge-blue',   'M9 11l3 3L22 4'],
    'other'            => ['linear-gradient(135deg,#312e81,#6366f1)',  '#c7d2fe', 'badge-gray',   'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z'],
];
$categoryIcons = [
    'drying_rules'     => 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
    'rice_varieties'   => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
    'weather_patterns' => 'M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z',
    'equipment_specs'  => 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z',
    'troubleshooting'  => 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z',
    'best_practices'   => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11',
    'other'            => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z',
];
@endphp

@if($knowledgeBases->isEmpty())
<div class="glass-card" style="padding:3rem;text-align:center;">
    <div style="width:64px;height:64px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:20px;box-shadow:0 4px 16px rgba(59,130,246,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        </svg>
    </div>
    <p style="color:#0f172a;font-weight:500;margin:0 0 0.25rem;">{{ __('app.no_knowledge') }}</p>
    <p style="color:#0f172a;font-size:0.8rem;margin:0 0 1.25rem;">{{ __('app.knowledge_empty_hint') }}</p>
    @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
    <button onclick="openModal('create')" class="btn-primary btn-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ __('app.add_knowledge') }}
    </button>
    @endif
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;margin-bottom:1.5rem;">
    @foreach($knowledgeBases as $kb)
    @php
        [$catGrad, $catBorder, $catBadge, $catIconPath] = $categoryGradients[$kb->category] ?? $categoryGradients['other'];
        $catIcon = $categoryIcons[$kb->category] ?? $categoryIcons['other'];
    @endphp
    <div style="background:#fff;border-radius:18px;border:1.5px solid #e2e8f0;box-shadow:0 2px 8px rgba(0,0,0,0.05);overflow:hidden;transition:transform 0.2s,box-shadow 0.2s,border-color 0.2s;"
         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 28px rgba(0,0,0,0.1)';this.style.borderColor='{{ $catBorder }}'"
         onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)';this.style.borderColor='#e2e8f0'">

        {{-- Accent bar --}}
        <div style="height:3px;background:{{ $catGrad }};"></div>

        <div style="padding:1.1rem 1.25rem;">
            {{-- Header row --}}
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.875rem;">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <div style="width:44px;height:44px;border-radius:12px;background:{{ $catGrad }};display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $catIcon }}"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:0.875rem;font-weight:800;color:#0f172a;line-height:1.25;letter-spacing:-0.01em;max-width:160px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">{{ $kb->title }}</div>
                        <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;display:flex;align-items:center;gap:4px;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            {{ ucwords(str_replace('_',' ',$kb->category)) }}
                        </div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
                    @if($kb->use_for_ai)
                    <span class="badge badge-green">AI Active</span>
                    @else
                    <span class="badge badge-gray">Inactive</span>
                    @endif
                    <span class="badge badge-gray" style="font-size:0.6rem;">v{{ $kb->version }}</span>
                </div>
            </div>

            {{-- Content box --}}
            <div style="background:#f8fafc;border-radius:10px;border-left:3px solid {{ $catBorder }};padding:0.6rem 0.875rem;margin-bottom:0.875rem;">
                <p style="font-size:0.78rem;color:#374151;margin:0;line-height:1.6;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">{{ $kb->content }}</p>
            </div>

            {{-- Tags --}}
            @if($kb->tags)
            <div style="display:flex;flex-wrap:wrap;gap:0.35rem;margin-bottom:0.75rem;">
                @foreach(array_slice((array)$kb->tags,0,5) as $tag)
                <span style="font-size:0.6rem;color:#7c3aed;font-family:monospace;background:#f5f3ff;border:1px solid #ddd6fe;padding:2px 7px;border-radius:5px;font-weight:600;">#{{ $tag }}</span>
                @endforeach
            </div>
            @endif

            {{-- Footer --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:0.625rem;border-top:1px solid #f1f5f9;">
                <span style="font-size:0.68rem;color:#94a3b8;display:inline-flex;align-items:center;gap:3px;">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                    {{ $kb->updated_at->diffForHumans() }}
                    &nbsp;·&nbsp; ⭐ {{ number_format($kb->priority_weight,1) }}
                </span>
                @if(auth()->user()->isAdmin() || auth()->user()->isOperator())
                <button onclick="openModal('edit',{{ $kb->id }},{{ json_encode($kb->title) }},{{ json_encode($kb->content) }},{{ json_encode($kb->category) }},{{ $kb->use_for_ai ? 'true' : 'false' }},{{ $kb->is_active ? 'true' : 'false' }},{{ json_encode(implode(', ',(array)($kb->tags??[]))) }},{{ $kb->priority_weight }})"
                        style="font-size:0.72rem;font-weight:700;color:#fff;background:{{ $catGrad }};padding:4px 12px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(0,0,0,0.15);transition:opacity 0.15s;"
                        onmouseover="this.style.opacity='0.85'"
                        onmouseout="this.style.opacity='1'">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    {{ __('app.edit') }}
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
                </button>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="pagination-wrapper">{{ $knowledgeBases->links() }}</div>
@endif

{{-- ===== MODAL ===== --}}
<div id="kb-modal" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,0.2);width:100%;max-width:560px;max-height:90vh;overflow-y:auto;">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;">
            <h3 id="modal-title" style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">{{ __('app.add_knowledge') }}</h3>
            <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;color:#0f172a;padding:4px;border-radius:6px;display:flex;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="kb-form" method="POST" style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
            @csrf
            <span id="method-field"></span>

            <div>
                <label class="label-dark">{{ __('app.knowledge_title') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" id="f-title" name="title" required placeholder="{{ __('app.knowledge_title') }}" class="input-dark">
            </div>
            <div>
                <label class="label-dark">{{ __('app.knowledge_category') }} <span style="color:#ef4444;">*</span></label>
                <select id="f-category" name="category" required class="input-dark">
                    @foreach(['drying_rules','rice_varieties','weather_patterns','equipment_specs','troubleshooting','best_practices','other'] as $cat)
                    <option value="{{ $cat }}">{{ ucwords(str_replace('_',' ',$cat)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-dark">{{ __('app.knowledge_content') }} <span style="color:#ef4444;">*</span></label>
                <textarea id="f-content" name="content" required rows="6" placeholder="{{ __('app.knowledge_content') }}" class="input-dark" style="resize:vertical;"></textarea>
            </div>
            <div>
                <label class="label-dark">Tags <span style="color:#0f172a;font-weight:400;">({{ __('app.tags_hint') }})</span></label>
                <input type="text" id="f-tags" name="tags" placeholder="padi, pengeringan, suhu…" class="input-dark">
            </div>
            <div>
                <label class="label-dark">{{ __('app.priority_weight') }}</label>
                <input type="number" id="f-priority" name="priority_weight" value="1.0" min="0" max="10" step="0.1" class="input-dark" style="width:140px;">
            </div>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" id="f-useai" name="use_for_ai" value="1" checked style="width:16px;height:16px;accent-color:#1d4ed8;">
                    <span style="font-size:0.82rem;font-weight:600;color:#1e293b;">{{ __('app.use_for_ai') }}</span>
                </label>
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" id="f-active" name="is_active" value="1" checked style="width:16px;height:16px;accent-color:#1d4ed8;">
                    <span style="font-size:0.82rem;font-weight:600;color:#1e293b;">{{ __('app.active') }}</span>
                </label>
            </div>

            <div style="display:flex;gap:0.75rem;justify-content:flex-end;padding-top:0.75rem;border-top:1px solid #f1f5f9;">
                <button type="button" onclick="closeModal()" class="btn-secondary">{{ __('app.cancel') }}</button>
                <button type="submit" class="btn-primary" id="modal-submit-btn">{{ __('app.save') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const _lang = {
    addTitle:    @json(__('app.add_knowledge')),
    editTitle:   @json(__('app.kb_edit_title')),
    saveEntry:   @json(__('app.save')),
    updateEntry: @json(__('app.kb_update_entry')),
};
function openModal(mode, id, title, content, category, useForAi, isActive, tags, priority) {
    const modal = document.getElementById('kb-modal');
    const form  = document.getElementById('kb-form');
    const mf    = document.getElementById('method-field');

    if (mode === 'create') {
        document.getElementById('modal-title').textContent = _lang.addTitle;
        document.getElementById('modal-submit-btn').textContent = _lang.saveEntry;
        form.action = '{{ route("web.knowledge.store") }}';
        mf.innerHTML = '';
        document.getElementById('f-title').value    = '';
        document.getElementById('f-content').value  = '';
        document.getElementById('f-category').value = 'drying_rules';
        document.getElementById('f-tags').value     = '';
        document.getElementById('f-priority').value = '1.0';
        document.getElementById('f-useai').checked  = true;
        document.getElementById('f-active').checked = true;
    } else {
        document.getElementById('modal-title').textContent = _lang.editTitle;
        document.getElementById('modal-submit-btn').textContent = _lang.updateEntry;
        form.action = '/knowledge-base/' + id;
        mf.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('f-title').value    = title;
        document.getElementById('f-content').value  = content;
        document.getElementById('f-category').value = category;
        document.getElementById('f-tags').value     = tags;
        document.getElementById('f-priority').value = priority;
        document.getElementById('f-useai').checked  = useForAi;
        document.getElementById('f-active').checked = isActive;
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('kb-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('kb-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endpush
