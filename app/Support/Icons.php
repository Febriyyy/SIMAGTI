<?php

namespace App\Support;

/**
 * Peta ikon aplikasi.
 *
 * Ikon utama: Google Material Symbols (Rounded), dimuat dari Google Fonts.
 * Hanya ikon yang ada di peta ini yang diunduh (parameter icon_names), jadi berkasnya kecil.
 *
 * Cadangan: jika font tidak bisa dimuat (misalnya tanpa internet), dipakai Heroicons
 * (paket blade-ui-kit/blade-heroicons, jika terpasang) atau ikon garis sederhana bawaan.
 */
class Icons
{
    /** nama di aplikasi => nama Material Symbol */
    public const MATERIAL = [
        'home' => 'space_dashboard',
        'tasks' => 'task_alt',
        'activity' => 'monitoring',
        'logout' => 'logout',
        'link' => 'link',
        'code' => 'code',
        'video' => 'videocam',
        'plus' => 'add',
        'calendar' => 'calendar_month',
        'clock' => 'schedule',
        'users' => 'group',
        'student' => 'school',
        'building' => 'apartment',
        'verify' => 'verified',
        'chat' => 'chat',
        'back' => 'chevron_left',
        'expand' => 'expand_more',
        'paperclip' => 'attach_file',
        'arrow' => 'arrow_forward',
        'arrow-up-right' => 'arrow_outward',
        'check' => 'check_circle',
        'award' => 'workspace_premium',
        'chart' => 'bar_chart',
        'alert' => 'warning',
        'upload' => 'upload',
        'download' => 'download',
        'delete' => 'delete',
        'send' => 'send',
        'file' => 'draft',
        'image' => 'image',
        'pdf' => 'picture_as_pdf',
        'sheet' => 'table_chart',
        'slide' => 'slideshow',
        'archive' => 'folder_zip',
        'placement' => 'work',
        'approval' => 'fact_check',
        'history' => 'history',
        'board' => 'view_kanban',
        'list' => 'view_list',
        'syllabus' => 'menu_book',
        'project' => 'folder',
        'flag' => 'flag',
        'edit' => 'edit',
        'filter' => 'filter_list',
        'search' => 'search',
        'excel' => 'table_view',
        'print' => 'print',
        'person' => 'person',
        'mail' => 'mail',
        'more' => 'more_horiz',
        'drag' => 'drag_indicator',
        'timer' => 'timer',
        'school' => 'school',
        'bell' => 'notifications',
        'swap' => 'swap_horiz',
        'star' => 'star',
        'grade' => 'grading',
    ];

    /** nama di aplikasi => nama Heroicons (outline), cadangan pertama */
    public const HEROICONS = [
        'home' => 'home', 'tasks' => 'clipboard-document-check', 'activity' => 'presentation-chart-line',
        'logout' => 'arrow-right-start-on-rectangle', 'link' => 'link', 'code' => 'code-bracket', 'video' => 'video-camera',
        'plus' => 'plus', 'calendar' => 'calendar-days', 'clock' => 'clock', 'users' => 'user-group', 'student' => 'academic-cap',
        'building' => 'building-office-2', 'verify' => 'check-badge', 'chat' => 'chat-bubble-left-ellipsis', 'back' => 'chevron-left', 'expand' => 'chevron-down',
        'paperclip' => 'paper-clip', 'arrow' => 'arrow-right', 'arrow-up-right' => 'arrow-up-right', 'check' => 'check-circle',
        'award' => 'trophy', 'chart' => 'chart-bar', 'alert' => 'exclamation-triangle', 'upload' => 'arrow-up-tray',
        'download' => 'arrow-down-tray', 'delete' => 'trash', 'send' => 'paper-airplane', 'file' => 'document',
        'image' => 'photo', 'pdf' => 'document-text', 'sheet' => 'table-cells', 'slide' => 'presentation-chart-bar',
        'archive' => 'archive-box', 'placement' => 'briefcase', 'approval' => 'clipboard-document-list', 'history' => 'clock',
        'board' => 'view-columns', 'list' => 'list-bullet', 'syllabus' => 'book-open', 'project' => 'folder', 'flag' => 'flag',
        'edit' => 'pencil-square', 'filter' => 'funnel', 'search' => 'magnifying-glass', 'excel' => 'table-cells', 'print' => 'printer',
        'person' => 'user', 'mail' => 'envelope', 'more' => 'ellipsis-horizontal', 'drag' => 'bars-3', 'timer' => 'clock', 'school' => 'academic-cap',
        'bell' => 'bell', 'swap' => 'arrows-right-left', 'star' => 'star', 'grade' => 'clipboard-document-check',
    ];

    /** Cadangan terakhir: garis sederhana (viewBox 24). */
    public const PATHS = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'tasks' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'activity' => '<path d="M4 19h16M7 15v-4M12 15V7M17 15v-6"/>',
        'logout' => '<path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4"/><path d="M15 8l4 4-4 4M19 12H9"/>',
        'link' => '<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>',
        'code' => '<path d="M8 8l-4 4 4 4M16 8l4 4-4 4M13.5 5l-3 14"/>',
        'video' => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'history' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20c.6-3.4 3-5.5 6-5.5s5.4 2.1 6 5.5M16 5a3.5 3.5 0 010 7"/>',
        'student' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
        'building' => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/>',
        'verify' => '<path d="M12 3l2.5 2 3.2-.3.6 3.1 2.7 1.7-1.3 2.9 1.3 2.9-2.7 1.7-.6 3.1-3.2-.3L12 21l-2.5-2-3.2.3-.6-3.1L3 14.5l1.3-2.9L3 8.7l2.7-1.7.6-3.1 3.2.3z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/>',
        'back' => '<path d="M15 5l-7 7 7 7"/>',
        'expand' => '<path d="M6 9l6 6 6-6"/>',
        'paperclip' => '<path d="M20 11.5l-8 8a5 5 0 01-7-7l8-8a3.4 3.4 0 014.8 4.8l-8 8a1.7 1.7 0 01-2.4-2.4l7.4-7.4"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17L17 7M9 7h8v8"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
        'award' => '<path d="M8 4h8v5a4 4 0 01-8 0z"/><path d="M8 6H5v1a3 3 0 003 3M16 6h3v1a3 3 0 01-3 3M12 13v4M9 20h6"/>',
        'chart' => '<path d="M4 19h16M7 15v-4M12 15V7M17 15v-6"/>',
        'alert' => '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17v.5"/>',
        'upload' => '<path d="M12 16V4M7 9l5-5 5 5M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"/>',
        'download' => '<path d="M12 4v12M7 11l5 5 5-5M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"/>',
        'delete' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'send' => '<path d="M4 12l16-8-6 16-2.5-6.5z"/><path d="M11.5 13.5L20 4"/>',
        'file' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/>',
        'pdf' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 13h6M9 17h4"/>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="M21 16l-5-5-8 8"/>',
        'sheet' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 10h16M4 15h16M10 4v16"/>',
        'slide' => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M12 16v4M8 20h8"/>',
        'archive' => '<rect x="3" y="4" width="18" height="5" rx="1"/><path d="M5 9v11h14V9M10 13h4"/>',
        'placement' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V4h6v3M3 13h18"/>',
        'approval' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 13l2 2 4-4"/>',
        'board' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M15 4v10"/>',
        'list' => '<path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/>',
        'syllabus' => '<path d="M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2z"/><path d="M4 19V5M9 7h6"/>',
        'project' => '<path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
        'flag' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M14 6l4 4"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="M20 20l-4.5-4.5"/>',
        'excel' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 10h16M10 4v16"/>',
        'print' => '<path d="M7 9V4h10v5M7 17H5a1 1 0 01-1-1v-5a2 2 0 012-2h12a2 2 0 012 2v5a1 1 0 01-1 1h-2"/><rect x="7" y="14" width="10" height="6"/>',
        'person' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'more' => '<path d="M6 12h.01M12 12h.01M18 12h.01"/>',
        'drag' => '<path d="M9 6h.01M15 6h.01M9 12h.01M15 12h.01M9 18h.01M15 18h.01"/>',
        'timer' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M10 2h4"/>',
        'school' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
        'bell' => '<path d="M6 16V11a6 6 0 0112 0v5l2 2H4z"/><path d="M10 20a2 2 0 004 0"/>',
        'swap' => '<path d="M4 8h14M14 4l4 4-4 4M20 16H6M10 12l-4 4 4 4"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
        'grade' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 13l2 2 4-4"/>',
    ];

    public static function material(string $name): ?string
    {
        return self::MATERIAL[$name] ?? null;
    }

    /** Alamat Google Fonts yang hanya memuat ikon yang dipakai. */
    public static function fontUrl(): string
    {
        $names = array_values(array_unique(self::MATERIAL));
        sort($names);

        return 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200'
            .'&icon_names='.implode(',', $names).'&display=block';
    }
}
