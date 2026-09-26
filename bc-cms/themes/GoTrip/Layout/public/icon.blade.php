@switch($n ?? '')
    @case('clock')<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>@break
    @case('users')<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c.6-3.6 3-5 6-5s5.4 1.4 6 5M16 5a3 3 0 010 6M18 15c2 .6 3 2.2 3.4 5"/></svg>@break
    @case('bed')<svg viewBox="0 0 24 24"><path d="M3 18V7M3 14h18v4M21 14v-3a3 3 0 00-3-3h-7v6"/></svg>@break
    @case('bath')<svg viewBox="0 0 24 24"><path d="M4 12h16v3a4 4 0 01-4 4H8a4 4 0 01-4-4v-3zM7 12V6a2 2 0 014 0"/></svg>@break
    @case('size')<svg viewBox="0 0 24 24"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>@break
    @case('gear')<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/></svg>@break
    @case('door')<svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6zM14 12h.01"/></svg>@break
    @case('bag')<svg viewBox="0 0 24 24"><rect x="4" y="8" width="16" height="12" rx="2"/><path d="M9 8V6a3 3 0 016 0v2"/></svg>@break
    @case('tag')<svg viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1"/></svg>@break
    @default<svg viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"/></svg>
@endswitch
