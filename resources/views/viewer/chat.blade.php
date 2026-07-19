@extends('layouts.viewer')
@section('title', 'Tanya AI')
@section('content')

<div style="font-weight:800;font-size:1.1rem;color:#1f2937;margin-bottom:4px">💬 Tanya Kondisi Gabah</div>
<p style="font-size:.82rem;color:#6b7280;margin-bottom:16px">
    Tanya apa saja soal pengeringan. Contoh: "Kapan selesai?", "Aman tidak ditinggal?"
</p>

{{-- Chat box --}}
<div id="chat-box" style="background:#fff;border-radius:20px;padding:16px;min-height:320px;max-height:55vh;overflow-y:auto;margin-bottom:14px;box-shadow:0 2px 14px rgba(0,0,0,.07);border:1px solid rgba(0,0,0,.04)">

    @if($history->isEmpty())
        <div style="text-align:center;padding:48px 20px;color:#9ca3af">
            <div style="font-size:3rem;margin-bottom:10px">🌾</div>
            <p style="margin:0;font-size:.88rem;line-height:1.6">Halo! Saya asisten PADI PRECISION.<br>Tanya apa saja soal gabah kamu.</p>
        </div>
    @endif

    @foreach($history as $msg)
        @if($msg->role === 'user')
            <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
                <div style="background:linear-gradient(135deg,#15803d,#22c55e);color:#fff;border-radius:18px 18px 4px 18px;padding:11px 16px;max-width:82%;font-size:.88rem;line-height:1.55;box-shadow:0 2px 8px rgba(21,128,61,.25)">
                    {{ $msg->message }}
                </div>
            </div>
        @else
            <div style="display:flex;justify-content:flex-start;margin-bottom:12px;gap:9px;align-items:flex-end">
                <div style="width:30px;height:30px;background:linear-gradient(135deg,#14532d,#22c55e);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.9rem;box-shadow:0 2px 6px rgba(0,0,0,.1)">🤖</div>
                <div style="max-width:82%;">
                    <div class="ai-bubble-viewer" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);color:#1f2937;border-radius:18px 18px 18px 4px;padding:11px 16px;font-size:.88rem;line-height:1.55;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #e2e8f0">
                        {!! renderChatMessage($msg->message) !!}
                    </div>
                    {{-- Tombol TTS --}}
                    <button onclick="bacakanTeks(this, {{ json_encode($msg->message) }})"
                            title="Bacakan pesan ini"
                            style="margin-top:5px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#166534;border:1px solid #86efac;border-radius:999px;padding:4px 11px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:4px;transition:all .15s"
                            onmouseover="this.style.background='linear-gradient(135deg,#dcfce7,#bbf7d0)'"
                            onmouseout="this.style.background='linear-gradient(135deg,#f0fdf4,#dcfce7)'">
                        <span class="tts-icon">🔊</span> Bacakan
                    </button>
                </div>
            </div>
        @endif
    @endforeach
</div>

{{-- Konteks batch aktif --}}
@if($activeBatch)
<div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:12px;padding:10px 14px;margin-bottom:12px;font-size:.78rem;color:#166534;border:1px solid #bbf7d0">
    🌾 Gabah aktif: <strong>{{ $activeBatch->rice_variety ?? $activeBatch->batch_code }}</strong>
    &middot; Kadar air {{ $activeBatch->current_moisture ?? $activeBatch->initial_moisture }}%
    → target {{ $activeBatch->target_moisture }}%
</div>
@endif

{{-- Form --}}
<form method="POST" action="{{ route('viewer.chat.send') }}">
    @csrf
    <input type="hidden" name="session_id" value="{{ $sessionId }}">
    <div style="display:flex;gap:8px">
        <input type="text" name="message"
               placeholder="Ketik pertanyaan kamu..."
               autocomplete="off" required
               style="flex:1;border:2px solid #e5e7eb;border-radius:14px;padding:12px 16px;font-size:.9rem;font-family:inherit;outline:none;transition:border .15s,box-shadow .15s;background:#fff"
               onfocus="this.style.borderColor='#15803d';this.style.boxShadow='0 0 0 3px rgba(21,128,61,.1)'"
               onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <button type="submit"
                style="background:linear-gradient(135deg,#14532d,#15803d);color:#fff;border:none;border-radius:14px;padding:12px 22px;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit;box-shadow:0 4px 12px rgba(21,128,61,.3);transition:transform .1s"
                onmousedown="this.style.transform='scale(.97)'"
                onmouseup="this.style.transform='scale(1)'">
            Kirim ↑
        </button>
    </div>
    @error('message')<div style="color:#ef4444;font-size:.78rem;margin-top:4px">{{ $message }}</div>@enderror
</form>

{{-- Pertanyaan cepat --}}
<div style="margin-top:14px">
    <div style="font-size:.74rem;color:#9ca3af;margin-bottom:7px;font-weight:500">Pertanyaan cepat:</div>
    <div style="display:flex;flex-wrap:wrap;gap:7px">
        @foreach(['Kapan gabah selesai?','Apakah aman ditinggal?','Kenapa mesin berhenti?','Cuaca hari ini aman?','Berapa lama lagi?'] as $q)
            <button type="button"
                    onclick="document.querySelector('input[name=message]').value='{{ $q }}';document.querySelector('input[name=message]').focus()"
                    style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#166534;border:1px solid #86efac;border-radius:999px;padding:6px 13px;font-size:.76rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s"
                    onmouseover="this.style.background='linear-gradient(135deg,#dcfce7,#bbf7d0)'"
                    onmouseout="this.style.background='linear-gradient(135deg,#f0fdf4,#dcfce7)'">
                {{ $q }}
            </button>
        @endforeach
    </div>
</div>

<script>
    const box = document.getElementById('chat-box');
    box.scrollTop = box.scrollHeight;

    // ── TTS ──────────────────────────────────────────────────────────
    let activeTts = null;

    function bacakanTeks(btn, teks) {
        if (!('speechSynthesis' in window)) {
            alert('Browser kamu tidak mendukung fitur suara. Coba pakai Chrome atau Edge.');
            return;
        }

        // Kalau sedang bicara, stop
        if (activeTts && speechSynthesis.speaking) {
            speechSynthesis.cancel();
            activeTts = null;
            resetTtsBtn(btn);
            return;
        }

        // Bersihkan teks dari URL dan karakter khusus sebelum dibacakan
        const bersih = teks
            .replace(/https?:\/\/\S+/g, '')
            .replace(/[*_`#]/g, '')
            .trim();

        const utterance = new SpeechSynthesisUtterance(bersih);
        utterance.lang = 'id-ID';
        utterance.rate = 0.88;
        utterance.pitch = 1;

        // Pilih suara Bahasa Indonesia, fallback bertingkat
        const voices = speechSynthesis.getVoices();
        const voice = voices.find(v => v.lang === 'id-ID')
                   || voices.find(v => v.lang.startsWith('id'))
                   || voices.find(v => v.name.toLowerCase().includes('indonesian'))
                   || voices.find(v => v.name.toLowerCase().includes('indonesia'))
                   || null; // biarkan browser pilih default
        if (voice) utterance.voice = voice;

        btn.querySelector('.tts-icon').textContent = '⏹';
        btn.innerHTML = btn.innerHTML.replace('Bacakan', 'Stop');
        activeTts = btn;

        utterance.onend = () => { activeTts = null; resetTtsBtn(btn); };
        utterance.onerror = () => { activeTts = null; resetTtsBtn(btn); };

        speechSynthesis.speak(utterance);
    }

    function resetTtsBtn(btn) {
        btn.querySelector('.tts-icon').textContent = '🔊';
        btn.innerHTML = btn.innerHTML.replace('Stop', 'Bacakan');
    }

    // Pastikan daftar suara sudah dimuat (Chrome lazy-load voices)
    speechSynthesis.onvoiceschanged = () => speechSynthesis.getVoices();
</script>

@endsection
