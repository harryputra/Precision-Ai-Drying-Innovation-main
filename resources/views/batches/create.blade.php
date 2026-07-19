@extends('layouts.app')
@section('title', __('app.add_batch_title'))
@section('breadcrumb', __('app.batch_title_label') . ' / ' . __('app.add_batch'))

@section('content')

<div class="page-header-banner" style="margin-bottom:1rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
        <div>
            <h2 style="font-size:1.1rem;font-weight:800;color:#fff;margin:0;">{{ __('app.add_batch_new') }}</h2>
            <p style="font-size:0.75rem;color:rgba(255,255,255,0.75);margin:0;">{{ __('app.add_batch_desc') }}</p>
        </div>
        <a href="{{ route('web.batches.index') }}" class="btn-secondary btn-sm">{{ __('app.back') }}</a>
    </div>
</div>

<div class="glass-card">
    <div class="card-body">
        <form method="POST" action="{{ route('web.batches.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr;gap:1rem;" class="form-grid">

                <div>
                    <label class="label-dark">{{ __('app.device') }} <span style="color:#dc2626;">*</span></label>
                    <select name="device_id" class="input-dark" required>
                        <option value="">{{ __('app.select_device_ph') }}</option>
                        @foreach($devices as $d)
                        <option value="{{ $d->id }}" {{ old('device_id') == $d->id ? 'selected' : '' }}>{{ $d->device_name }}</option>
                        @endforeach
                    </select>
                    @error('device_id') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.batch_code') }} <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="batch_code" value="{{ old('batch_code') }}" class="input-dark" placeholder="e.g. BATCH-2026-001" required>
                    @error('batch_code') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.rice_type') }} <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="rice_type" value="{{ old('rice_type') }}" class="input-dark" placeholder="e.g. Padi IR64" required>
                    @error('rice_type') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.rice_variety') }}</label>
                    <input type="text" name="rice_variety" value="{{ old('rice_variety') }}" class="input-dark" placeholder="{{ __('app.optional_label') }}">
                </div>

                <div>
                    <label class="label-dark">{{ __('app.initial_weight') }} <span style="color:#dc2626;">*</span></label>
                    <input type="number" step="0.01" name="initial_weight" value="{{ old('initial_weight') }}" class="input-dark" placeholder="100.00" required>
                    @error('initial_weight') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.initial_moisture') }} <span style="color:#dc2626;">*</span></label>
                    <input type="number" step="0.1" name="initial_moisture" value="{{ old('initial_moisture') }}" class="input-dark" placeholder="25.0" required>
                    @error('initial_moisture') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.target_moisture') }} <span style="color:#dc2626;">*</span></label>
                    <input type="number" step="0.1" name="target_moisture" value="{{ old('target_moisture', 14) }}" class="input-dark" placeholder="14.0" required>
                    @error('target_moisture') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.drying_method') }} <span style="color:#dc2626;">*</span></label>
                    <select name="drying_method" class="input-dark" required>
                        <option value="">{{ __('app.select_method_ph') }}</option>
                        @foreach(['Solar Dryer', 'Natural Sun', 'Hybrid', 'Mechanical'] as $m)
                        <option value="{{ $m }}" {{ old('drying_method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    @error('drying_method') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.operator_name') }}</label>
                    <input type="text" name="operator_name" value="{{ old('operator_name') }}" class="input-dark" placeholder="{{ __('app.optional_label') }}">
                </div>

                <div>
                    <label class="label-dark">{{ __('app.status') }} <span style="color:#dc2626;">*</span></label>
                    <select name="status" class="input-dark" required>
                        @foreach([
                            'waiting'   => __('app.waiting'),
                            'drying'    => __('app.running'),
                            'paused'    => __('app.paused'),
                            'completed' => __('app.completed'),
                            'failed'    => __('app.failed'),
                        ] as $val => $label)
                        <option value="{{ $val }}" {{ old('status', 'waiting') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label-dark">{{ __('app.start_time') }}</label>
                    <input type="datetime-local" name="start_time" value="{{ old('start_time') }}" class="input-dark">
                </div>

            </div>

            <div style="margin-top:1.5rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn-primary">{{ __('app.save_batch') }}</button>
                <a href="{{ route('web.batches.index') }}" class="btn-secondary">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<style>
@media (min-width: 768px) { .form-grid { grid-template-columns: 1fr 1fr !important; } }
</style>
@endpush
