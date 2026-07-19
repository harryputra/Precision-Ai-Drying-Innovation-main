<?php

if (!function_exists('renderChatMessage')) {
    /**
     * Render chat message: escape HTML, embed YouTube, linkify URLs, newlines → <br>.
     */
    function renderChatMessage(string $text): string
    {
        // 1. Escape HTML terlebih dahulu
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 2. Deteksi YouTube URL dan ganti dengan embed iframe
        //    Pola: youtube.com/watch?v=ID atau youtu.be/ID
        $safe = preg_replace_callback(
            '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w\-]{11})(?:[^\s]*)?/i',
            function ($m) {
                $id       = $m[1];
                $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0';
                $watchUrl = 'https://www.youtube.com/watch?v=' . $id;
                return '<div style="margin:8px 0;border-radius:12px;overflow:hidden;max-width:100%">'
                    . '<iframe width="100%" height="215" '
                    . 'src="' . $embedUrl . '" '
                    . 'frameborder="0" '
                    . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
                    . 'allowfullscreen '
                    . 'referrerpolicy="strict-origin-when-cross-origin" '
                    . 'style="display:block;border-radius:12px 12px 0 0;border:0" '
                    . 'onerror="this.style.display=\'none\'"></iframe>'
                    . '<a href="' . $watchUrl . '" target="_blank" rel="noopener noreferrer" '
                    . 'style="display:block;background:#f1f5f9;padding:8px 12px;font-size:0.75rem;color:#a855f7;'
                    . 'text-decoration:none;border-radius:0 0 12px 12px;border-top:1px solid #e2e8f0;'
                    . 'word-break:break-all">▶ Buka di YouTube: ' . $watchUrl . '</a>'
                    . '</div>';
            },
            $safe
        );

        // 3. Linkify URL non-YouTube yang tersisa
        $safe = preg_replace(
            '/https?:\/\/[^\s<>"]+/i',
            '<a href="$0" target="_blank" rel="noopener noreferrer" '
                . 'style="color:#15803d;text-decoration:underline;word-break:break-all">$0</a>',
            $safe
        );

        // 4. Newline → <br>
        $safe = nl2br($safe);

        return $safe;
    }
}
