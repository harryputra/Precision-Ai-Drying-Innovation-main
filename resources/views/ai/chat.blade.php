@extends('layouts.app')
@section('title', __('app.ai_chat'))
@section('breadcrumb', __('app.nav_ai_system') . ' / ' . __('app.nav_ai_chat'))

@section('content')
<div x-data="aiChat()" x-init="init()" class="chat-layout" :class="showSessions ? 'show-sessions' : ''">

    {{-- Session List --}}
    <div class="glass-card chat-sessions">
        <div style="padding:0.875rem 1rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:0.8rem;font-weight:600;color:#0f172a;">{{ __('app.conversations') }}</span>
            <button @click="newSession()" class="btn-primary btn-sm">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('app.new_chat') }}
            </button>
        </div>
        <div style="flex:1;overflow-y:auto;padding:0.5rem;">
            @forelse($sessions as $session)
            <a href="{{ route('web.ai.chat', ['session_id' => $session->session_id]) }}"
               style="display:block;padding:0.625rem 0.75rem;border-radius:8px;text-decoration:none;margin-bottom:2px;
                      background:{{ request('session_id') === $session->session_id ? 'rgba(249,115,22,0.15)' : 'transparent' }};
                      border:1px solid {{ request('session_id') === $session->session_id ? 'rgba(249,115,22,0.2)' : 'transparent' }};"
               onmouseover="this.style.background='rgba(255,255,255,0.05)'"
               onmouseout="this.style.background='{{ request('session_id') === $session->session_id ? 'rgba(249,115,22,0.15)' : 'transparent' }}'">
                <div style="font-size:0.75rem;color:#0f172a;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ substr($session->session_id, 0, 16) }}…
                </div>
                <div style="font-size:0.65rem;color:#0f172a;margin-top:2px;">
                    {{ $session->message_count }} messages · {{ \Carbon\Carbon::parse($session->last_message_at)->diffForHumans() }}
                </div>
            </a>
            @empty
            <p style="font-size:0.75rem;color:#0f172a;padding:0.75rem;text-align:center;">{{ __('app.no_data') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Chat Window --}}
    <div class="glass-card chat-window">

        {{-- Context Bar --}}
        <div style="padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" style="flex-shrink:0;"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
            <span style="font-size:0.75rem;color:#1e293b;">AI Assistant — Padi PRECISION</span>
            <div style="margin-left:auto;display:flex;gap:0.5rem;align-items:center;">
                {{-- Toggle riwayat percakapan — hanya tampil di HP --}}
                <button type="button" @click="showSessions = !showSessions"
                        class="btn-secondary btn-sm chat-sessions-toggle" style="font-size:0.72rem;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/>
                    </svg>
                    <span x-text="showSessions ? '{{ __('app.conversations') }} ✕' : '{{ __('app.conversations') }}'"></span>
                </button>
                <select x-model="deviceId" class="input-dark" style="width:150px;max-width:42vw;padding:0.25rem 0.5rem;font-size:0.75rem;">
                    <option value="">{{ __('app.select_device') }}</option>
                    @foreach($devices as $d)
                    <option value="{{ $d->id }}">{{ $d->device_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Messages --}}
        <div id="chatMessages" style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.875rem;">

            @if($messages->isEmpty())
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:2rem;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,rgba(249,115,22,0.2),rgba(251,191,36,0.1));border:1px solid rgba(249,115,22,0.2);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2">
                        <rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/>
                    </svg>
                </div>
                <h3 class="gradient-text" style="font-size:1rem;font-weight:700;margin:0 0 0.5rem;">{{ __('app.ai_empty_title') }}</h3>
                <p style="color:#1e293b;font-size:0.8rem;max-width:300px;line-height:1.5;">
                    {{ __('app.ai_empty_desc') }}
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;justify-content:center;margin-top:1rem;">
                    @foreach(['What is the current moisture level?','Should I open the roof now?','Explain the drying process','Optimize drying for IR64'] as $suggestion)
                    <button @click="messageInput = '{{ $suggestion }}'" class="btn-secondary btn-sm" style="font-size:0.72rem;">{{ $suggestion }}</button>
                    @endforeach
                </div>
            </div>
            @else
            @foreach($messages as $msg)
            @if($msg->role === 'user')
            <div style="display:flex;justify-content:flex-end;gap:0.625rem;">
                <div style="max-width:75%;">
                    <div class="chat-bubble-user">{{ $msg->message }}</div>
                    <div style="font-size:0.65rem;color:#0f172a;text-align:right;margin-top:4px;">
                        {{ $msg->created_at->format('H:i') }}
                    </div>
                </div>
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#f97316,#fbbf24);display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;color:#0f172a;flex-shrink:0;margin-top:2px;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
            @elseif($msg->role === 'assistant')
            <div style="display:flex;gap:0.625rem;">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,rgba(168,85,247,0.3),rgba(59,130,246,0.2));border:1px solid rgba(168,85,247,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                </div>
                <div style="max-width:75%;">
                    <div class="chat-bubble-ai">{!! renderChatMessage($msg->message) !!}</div>
                    <div style="font-size:0.65rem;color:#0f172a;margin-top:4px;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        <span>{{ $msg->created_at->format('H:i') }}</span>
                        @if($msg->ai_model)<span style="font-family:monospace;">{{ $msg->ai_model }}</span>@endif
                        @if($msg->tokens_used)<span>{{ $msg->tokens_used }} tokens</span>@endif
                        <button onclick="bacakanTeks(this, {{ json_encode($msg->message) }})"
                                title="Bacakan pesan ini"
                                style="background:rgba(168,85,247,0.12);color:#a855f7;border:1px solid rgba(168,85,247,0.25);border-radius:999px;padding:2px 9px;font-size:0.65rem;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:3px;">
                            <span class="tts-icon">🔊</span> Bacakan
                        </button>
                    </div>
                </div>
            </div>
            @endif
            @endforeach
            @endif

            {{-- Typing indicator --}}
            <div x-show="isTyping" style="display:flex;gap:0.625rem;align-items:center;">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,rgba(168,85,247,0.3),rgba(59,130,246,0.2));border:1px solid rgba(168,85,247,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                </div>
                <div class="glass-card" style="padding:0.625rem 0.875rem;border-radius:16px 16px 16px 4px;display:flex;gap:4px;align-items:center;">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div style="padding:0.875rem 1rem;border-top:1px solid rgba(255,255,255,0.06);">
            <div style="display:flex;gap:0.625rem;align-items:flex-end;">
                <textarea x-model="messageInput"
                          @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                          placeholder="{{ __('app.ask_placeholder') }}"
                          class="input-dark"
                          style="resize:none;min-height:44px;max-height:120px;line-height:1.5;"
                          rows="1"
                          @input="$el.style.height='auto';$el.style.height=$el.scrollHeight+'px'"></textarea>
                <button @click="sendMessage()" :disabled="!messageInput.trim() || isTyping"
                        class="btn-primary" style="padding:0.625rem 1rem;flex-shrink:0;"
                        :style="(!messageInput.trim() || isTyping) ? 'opacity:0.5;cursor:not-allowed' : ''">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9 22,2"/>
                    </svg>
                </button>
            </div>
            <p style="font-size:0.65rem;color:#0f172a;margin:0.375rem 0 0;">{{ __('app.powered_by') }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function aiChat() {
    return {
        messageInput: '',
        isTyping: false,
        showSessions: false,   // panel riwayat (mobile) — desktop selalu tampil via CSS
        sessionId: '{{ $currentSessionId ?? "" }}',
        deviceId: '',

        init() {
            this.scrollToBottom();
            // Listen for AI replies via Echo
            if (window.Echo && this.sessionId) {
                window.Echo.private(`ai-chat.${this.sessionId}`)
                    .listen('AiReplyReceived', (e) => {
                        this.isTyping = false;
                        this.appendMessage('assistant', e.message, e.ai_model);
                        this.scrollToBottom();
                    });
            }
        },

        async sendMessage() {
            const msg = this.messageInput.trim();
            if (!msg || this.isTyping) return;

            this.messageInput = '';
            this.appendMessage('user', msg);
            this.isTyping = true;
            this.scrollToBottom();

            try {
                const res = await fetch('{{ route("web.ai.chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message:    msg,
                        session_id: this.sessionId || null,
                        device_id:  this.deviceId  || null,
                    }),
                });

                const data = await res.json();

                this.isTyping = false;

                if (data.status) {
                    this.sessionId = data.session_id;
                    this.appendMessage('assistant', data.reply, data.model);
                } else {
                    this.appendMessage('assistant', '⚠️ ' + (data.message || @json(__('app.error_generic'))));
                }

                this.scrollToBottom();

            } catch(e) {
                this.isTyping = false;
                this.appendMessage('assistant', '⚠️ ' + @json(__('app.error_connection')));
                this.scrollToBottom();
            }
        },

        appendMessage(role, text, model = null) {
            const container = document.getElementById('chatMessages');
            const time = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
            let html = '';

            if (role === 'user') {
                html = `<div style="display:flex;justify-content:flex-end;gap:0.625rem;">
                    <div style="max-width:75%;">
                        <div class="chat-bubble-user">${this.escapeHtml(text)}</div>
                        <div style="font-size:0.65rem;color:#0f172a;text-align:right;margin-top:4px;">${time}</div>
                    </div>
                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#f97316,#fbbf24);display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;color:#0f172a;flex-shrink:0;margin-top:2px;">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                </div>`;
            } else {
                const rendered = this.renderMessage(text);
                const escapedForAttr = text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, '\\n');
                html = `<div style="display:flex;gap:0.625rem;">
                    <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,rgba(168,85,247,0.3),rgba(59,130,246,0.2));border:1px solid rgba(168,85,247,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                    </div>
                    <div style="max-width:75%;">
                        <div class="chat-bubble-ai">${rendered}</div>
                        <div style="font-size:0.65rem;color:#0f172a;margin-top:4px;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                            <span>${time}</span>
                            ${model ? '<span style="font-family:monospace;">'+model+'</span>' : ''}
                            <button onclick="bacakanTeks(this, '${escapedForAttr}')"
                                    title="Bacakan pesan ini"
                                    style="background:rgba(168,85,247,0.12);color:#a855f7;border:1px solid rgba(168,85,247,0.25);border-radius:999px;padding:2px 9px;font-size:0.65rem;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:3px;">
                                <span class="tts-icon">🔊</span> Bacakan
                            </button>
                        </div>
                    </div>
                </div>`;
            }

            // Remove typing indicator, append message, re-add typing if needed
            const typingEl = container.querySelector('[x-show="isTyping"]');
            const div = document.createElement('div');
            div.innerHTML = html;
            if (typingEl) container.insertBefore(div.firstElementChild, typingEl);
            else container.appendChild(div.firstElementChild);
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('chatMessages');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        newSession() {
            window.location.href = '{{ route("web.ai.chat") }}';
        },

        escapeHtml(text) {
            return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        },

        // Render: YouTube embed + linkify + escape + newlines
        renderMessage(text) {
            // 1. Escape
            let s = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

            // 2. YouTube embed
            s = s.replace(
                /https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w\-]{11})[^\s]*/gi,
                (match, id) => {
                    const embedUrl = `https://www.youtube-nocookie.com/embed/${id}?rel=0`;
                    const watchUrl = `https://www.youtube.com/watch?v=${id}`;
                    return `<div style="margin:8px 0;border-radius:12px;overflow:hidden;max-width:100%">` +
                        `<iframe width="100%" height="215" src="${embedUrl}" ` +
                        `frameborder="0" ` +
                        `allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ` +
                        `allowfullscreen referrerpolicy="strict-origin-when-cross-origin" ` +
                        `style="display:block;border-radius:12px 12px 0 0;border:0" ` +
                        `onerror="this.style.display='none'"></iframe>` +
                        `<a href="${watchUrl}" target="_blank" rel="noopener noreferrer" ` +
                        `style="display:block;background:#f1f5f9;padding:8px 12px;font-size:0.75rem;color:#a855f7;` +
                        `text-decoration:none;border-radius:0 0 12px 12px;border-top:1px solid #e2e8f0;word-break:break-all">` +
                        `▶ Buka di YouTube: ${watchUrl}</a></div>`;
                }
            );

            // 3. Linkify sisa URL
            s = s.replace(
                /https?:\/\/[^\s<>"]+/gi,
                url => `<a href="${url}" target="_blank" rel="noopener noreferrer" style="color:#a855f7;text-decoration:underline;word-break:break-all">${url}</a>`
            );

            // 4. Newline → <br>
            return s.replace(/\n/g, '<br>');
        },
    };
}

// ── TTS (global, dipakai di blade loop maupun appendMessage) ─────────────────
let _activeTtsBtn = null;

function bacakanTeks(btn, teks) {
    if (!('speechSynthesis' in window)) {
        alert('Browser kamu tidak mendukung fitur suara. Gunakan Chrome atau Edge.');
        return;
    }

    // Toggle stop jika sedang berbicara
    if (_activeTtsBtn && speechSynthesis.speaking) {
        speechSynthesis.cancel();
        _resetTtsBtn(_activeTtsBtn);
        _activeTtsBtn = null;
        if (btn === _activeTtsBtn) return;
    }

    const bersih = teks
        .replace(/https?:\/\/\S+/g, '')
        .replace(/[*_`#]/g, '')
        .trim();

    const utt = new SpeechSynthesisUtterance(bersih);
    utt.lang  = 'id-ID';
    utt.rate  = 0.88;
    utt.pitch = 1;

    const voices = speechSynthesis.getVoices();
    const voice  = voices.find(v => v.lang === 'id-ID')
                || voices.find(v => v.lang.startsWith('id'))
                || voices.find(v => v.name.toLowerCase().includes('indonesian'))
                || voices.find(v => v.name.toLowerCase().includes('indonesia'))
                || null;
    if (voice) utt.voice = voice;

    _activeTtsBtn = btn;
    const icon = btn.querySelector('.tts-icon');
    if (icon) icon.textContent = '⏹';
    btn.title = 'Klik untuk stop';

    utt.onend   = () => { _resetTtsBtn(btn); _activeTtsBtn = null; };
    utt.onerror = () => { _resetTtsBtn(btn); _activeTtsBtn = null; };

    speechSynthesis.speak(utt);
}

function _resetTtsBtn(btn) {
    const icon = btn.querySelector('.tts-icon');
    if (icon) icon.textContent = '🔊';
    btn.title = 'Bacakan pesan ini';
}

speechSynthesis.onvoiceschanged = () => speechSynthesis.getVoices();
</script>
@endpush
