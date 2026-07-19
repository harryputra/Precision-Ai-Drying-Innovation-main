@extends('layouts.app')
@section('title', __('app.edit_batch'))
@section('breadcrumb', __('app.batch_title_label') . ' / ' . __('app.edit_batch'))

@section('content')

<div class="page-header-banner" style="margin-bottom:1rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
        <div>
            <h2 style="font-size:1.1rem;font-weight:800;color:#fff;margin:0;">{{ __('app.edit_batch') }}: {{ $dryingBatch->batch_code }}</h2>
            <p style="font-size:0.75rem;color:rgba(255,255,255,0.75);margin:0;">{{ __('app.edit_batch_desc') }}</p>
        </div>
        <a href="{{ route('web.batches.show', $dryingBatch) }}" class="btn-secondary btn-sm">{{ __('app.back') }}</a>
    </div>
</div>

<div class="glass-card">
    <div class="card-body">
        <form method="POST" action="{{ route('web.batches.update', $dryingBatch) }}">
            @csrf @method('PATCH')
            <div style="display:grid;grid-template-columns:1fr;gap:1rem;" class="form-grid">

                <div>
                    <label class="label-dark">{{ __('app.device') }} <span style="color:#dc2626;">*</span></label>
                    <select name="device_id" class="input-dark" required>
                        <option value="">{{ __('app.select_device_ph') }}</option>
                        @foreach($devices as $d)
                        <option value="{{ $d->id }}" {{ old('device_id', $dryingBatch->device_id) == $d->id ? 'selected' : '' }}>{{ $d->device_name }}</option>
                        @endforeach
                    </select>
                    @error('device_id') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.batch_code') }} <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="batch_code" value="{{ old('batch_code', $dryingBatch->batch_code) }}" class="input-dark" required>
                    @error('batch_code') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.rice_type') }} <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="rice_type" value="{{ old('rice_type', $dryingBatch->rice_type) }}" class="input-dark" required>
                </div>

                <div>
                    <label class="label-dark">{{ __('app.rice_variety') }}</label>
                    <input type="text" name="rice_variety" value="{{ old('rice_variety', $dryingBatch->rice_variety) }}" class="input-dark">
                </div>

                <div>
                    <label class="label-dark">{{ __('app.initial_weight') }}</label>
                    <input type="number" step="0.01" name="initial_weight" value="{{ old('initial_weight', $dryingBatch->initial_weight) }}" class="input-dark" required>
                </div>

                <div>
                    <label class="label-dark">{{ __('app.current_weight') }}</label>
                    <input type="number" step="0.01" name="current_weight" value="{{ old('current_weight', $dryingBatch->current_weight) }}" class="input-dark">
                </div>

                <div>
                    <label class="label-dark">{{ __('app.initial_moisture') }}</label>
                    <input type="number" step="0.1" name="initial_moisture" value="{{ old('initial_moisture', $dryingBatch->initial_moisture) }}" class="input-dark" required>
                </div>

                <div>
                    <label class="label-dark">{{ __('app.current_moisture_form') }}</label>
                    <input type="number" step="0.1" name="current_moisture" value="{{ old('current_moisture', $dryingBatch->current_moisture) }}" class="input-dark">
                </div>

                <div>
                    <label class="label-dark">{{ __('app.target_moisture') }}</label>
                    <input type="number" step="0.1" name="target_moisture" value="{{ old('target_moisture', $dryingBatch->target_moisture) }}" class="input-dark" required>
                </div>

                <div>
                    <label class="label-dark">{{ __('app.drying_method') }}</label>
                    <select name="drying_method" class="input-dark" required>
                        @foreach(['Solar Dryer', 'Natural Sun', 'Hybrid', 'Mechanical'] as $m)
                        <option value="{{ $m }}" {{ old('drying_method', $dryingBatch->drying_method) === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label-dark">{{ __('app.operator_name') }}</label>
                    <input type="text" name="operator_name" value="{{ old('operator_name', $dryingBatch->operator_name) }}" class="input-dark">
                </div>

                <div>
                    <label class="label-dark">{{ __('app.status') }}</label>
                    <select name="status" class="input-dark" required>
                        @foreach([
                            'waiting'   => __('app.waiting'),
                            'drying'    => __('app.running'),
                            'paused'    => __('app.paused'),
                            'completed' => __('app.completed'),
                            'failed'    => __('app.failed'),
                        ] as $val => $label)
                        <option value="{{ $val }}" {{ old('status', $dryingBatch->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label-dark">{{ __('app.start_time') }}</label>
                    <input type="datetime-local" name="start_time"
                           value="{{ old('start_time', $dryingBatch->start_time?->format('Y-m-d\TH:i')) }}"
                           class="input-dark">
                </div>

                <div>
                    <label class="label-dark">{{ __('app.end_time') }}</label>
                    <input type="datetime-local" name="end_time"
                           value="{{ old('end_time', $dryingBatch->end_time?->format('Y-m-d\TH:i')) }}"
                           class="input-dark">
                </div>

            </div>

            <div style="margin-top:1.5rem;display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
                <button type="submit" class="btn-primary">{{ __('app.save_changes') }}</button>
                <a href="{{ route('web.batches.show', $dryingBatch) }}" class="btn-secondary">{{ __('app.cancel') }}</a>

                <div style="margin-left:auto;">
                    <button type="button" class="btn-danger btn-sm"
                            onclick="if(confirm('{{ addslashes(__('app.confirm_delete_batch', ['code' => $dryingBatch->batch_code])) }}')) document.getElementById('delete-batch-form').submit()">
                        {{ __('app.delete_batch') }}
                    </button>
                </div>
            </div>
        </form>

        {{-- Form delete di luar form edit — nested form tidak valid HTML --}}
        <form id="delete-batch-form"
              method="POST"
              action="{{ route('web.batches.destroy', $dryingBatch) }}"
              onsubmit="return confirm('{{ __('app.confirm_delete_batch', ['code' => $dryingBatch->batch_code]) }}')">
            @csrf @method('DELETE')
        </form>
    </div>
</div>

@endsection

@push('scripts')
<style>
@media (min-width: 768px) { .form-grid { grid-template-columns: 1fr 1fr !important; } }
</style>
@endpush
