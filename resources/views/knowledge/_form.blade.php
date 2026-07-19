<div>
    <label class="label-dark">{{ __('app.knowledge_title') }} <span style="color:#ef4444;">*</span></label>
    <input type="text" name="title" value="{{ old('title') }}" required placeholder="{{ __('app.knowledge_title') }}" class="input-dark">
</div>
<div>
    <label class="label-dark">{{ __('app.knowledge_category') }} <span style="color:#ef4444;">*</span></label>
    <select name="category" required class="input-dark">
        @foreach(['drying_rules','rice_varieties','weather_patterns','equipment_specs','troubleshooting','best_practices','other'] as $cat)
        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$cat)) }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="label-dark">{{ __('app.knowledge_content') }} <span style="color:#ef4444;">*</span></label>
    <textarea name="content" required rows="6" placeholder="{{ __('app.knowledge_content') }}" class="input-dark" style="resize:vertical;">{{ old('content') }}</textarea>
</div>
<div>
    <label class="label-dark">Tags <span style="color:#0f172a;font-weight:400;">({{ __('app.tags_hint') }})</span></label>
    <input type="text" name="tags" value="{{ old('tags') }}" placeholder="padi, pengeringan, suhu…" class="input-dark">
</div>
<div>
    <label class="label-dark">{{ __('app.priority_weight') }}</label>
    <input type="number" name="priority_weight" value="{{ old('priority_weight', 1.0) }}" min="0" max="10" step="0.1" class="input-dark" style="width:140px;">
</div>
<div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" name="use_for_ai" value="1" checked style="width:16px;height:16px;accent-color:#1d4ed8;">
        <span style="font-size:0.8rem;font-weight:600;color:#1e293b;">{{ __('app.use_for_ai') }}</span>
    </label>
    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" name="is_active" value="1" checked style="width:16px;height:16px;accent-color:#1d4ed8;">
        <span style="font-size:0.8rem;font-weight:600;color:#1e293b;">{{ __('app.active') }}</span>
    </label>
</div>
