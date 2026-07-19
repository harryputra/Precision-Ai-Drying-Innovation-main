@extends('layouts.app')
@section('title', __('app.edit_device'))
@section('breadcrumb', __('app.devices') . ' / ' . __('app.edit'))

@section('content')

<div class="page-header-banner" style="margin-bottom:1rem;">
    <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
        <div>
            <h2 style="font-size:1.1rem;font-weight:800;color:#fff;margin:0;">{{ __('app.edit_device') }}</h2>
            <p style="font-size:0.75rem;color:rgba(255,255,255,0.75);margin:0;">{{ $device->device_name }} · {{ $device->serial_number }}</p>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <a href="{{ route('web.devices.show', $device) }}" class="btn-secondary btn-sm">{{ __('app.back') }}</a>
            <a href="{{ route('web.devices.index') }}" class="btn-secondary btn-sm">{{ __('app.device_list') }}</a>
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="card-body">
        <form method="POST" action="{{ route('web.devices.update', $device) }}">
            @csrf @method('PATCH')

            @if ($errors->any())
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:0.875rem 1rem;margin-bottom:1rem;">
                <ul style="margin:0;padding-left:1.25rem;color:#dc2626;font-size:0.8rem;">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr;gap:1rem;" class="form-grid">

                <div>
                    <label class="label-dark">{{ __('app.device_name') }} <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="device_name" value="{{ old('device_name', $device->device_name) }}"
                           class="input-dark @error('device_name') border-red-400 @enderror"
                           placeholder="Solar Dryer Unit 1" required>
                    @error('device_name') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.serial_number') }} <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $device->serial_number) }}"
                           class="input-dark @error('serial_number') border-red-400 @enderror"
                           placeholder="SD-001-2024" required>
                    @error('serial_number') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.firmware_version') }}</label>
                    <input type="text" name="firmware_version" value="{{ old('firmware_version', $device->firmware_version) }}"
                           class="input-dark @error('firmware_version') border-red-400 @enderror"
                           placeholder="v1.2.3">
                    @error('firmware_version') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.ip_address') }}</label>
                    <input type="text" name="ip_address" value="{{ old('ip_address', $device->ip_address) }}"
                           class="input-dark @error('ip_address') border-red-400 @enderror"
                           placeholder="192.168.1.10">
                    @error('ip_address') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.location') }}</label>
                    <input type="text" name="location" value="{{ old('location', $device->location) }}"
                           class="input-dark @error('location') border-red-400 @enderror"
                           placeholder="{{ __('app.location') }}">
                    @error('location') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label-dark">{{ __('app.status') }} <span style="color:#dc2626;">*</span></label>
                    <select name="status" class="input-dark @error('status') border-red-400 @enderror" required>
                        <option value="offline"      {{ old('status', $device->status) === 'offline'      ? 'selected' : '' }}>{{ __('app.offline') }}</option>
                        <option value="online"       {{ old('status', $device->status) === 'online'       ? 'selected' : '' }}>{{ __('app.online') }}</option>
                        <option value="maintenance"  {{ old('status', $device->status) === 'maintenance'  ? 'selected' : '' }}>{{ __('app.maintenance') }}</option>
                    </select>
                    @error('status') <p style="font-size:0.75rem;color:#dc2626;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

            </div>

            <div style="display:flex;gap:0.75rem;margin-top:1.5rem;flex-wrap:wrap;align-items:center;">
                <button type="submit" class="btn-primary">{{ __('app.save_changes') }}</button>
                <a href="{{ route('web.devices.show', $device) }}" class="btn-secondary">{{ __('app.cancel') }}</a>

                <div style="margin:0;">
                    <button type="button"
                            onclick="if(confirm('{{ addslashes(__('app.confirm_delete_device')) }}')) document.getElementById('delete-device-form').submit()"
                            class="btn-danger btn-sm" style="background:#dc2626;color:#fff;border:none;padding:0.375rem 0.875rem;border-radius:8px;font-size:0.8rem;font-weight:600;cursor:pointer;">
                        {{ __('app.delete_device') }}
                    </button>
                </div>
            </div>

        </form>

        {{-- Form delete di luar form edit — nested form tidak valid HTML --}}
        <form id="delete-device-form"
              method="POST"
              action="{{ route('web.devices.destroy', $device) }}">
            @csrf @method('DELETE')
        </form>
    </div>
</div>

@endsection

@push('scripts')
<style>
@media (min-width: 640px) {
    .form-grid { grid-template-columns: 1fr 1fr !important; }
}
</style>
@endpush
