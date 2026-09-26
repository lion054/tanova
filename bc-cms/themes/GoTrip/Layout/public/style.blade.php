<style>
/* Public service page: one design for hotels, tours, spaces, cars, boats and events. Strict black, white and gold. */
:root { --pv-ink:#0d0d10; --pv-mute:#6b6b6b; --pv-line:#e8e8e8; --pv-soft:#f6f6f6; --pv-gold:#E0A23B; --pv-r:14px; }
body.pv-body { background:#fff !important; color:var(--pv-ink) !important; font-family:'Inter',system-ui,-apple-system,sans-serif !important; font-size:15px; line-height:1.6; margin:0; }
.pv *, .pv *::before, .pv *::after { box-sizing:border-box; }
.pv a { color:inherit; }
.pv-wrap { max-width:1180px; margin:0 auto; padding:0 24px; }
.pv-i { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }
.pv-i-sm { width:12px; height:12px; }
.pv svg { fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }

/* Top bar */
.pv-top { position:sticky; top:0; z-index:900; background:rgba(255,255,255,.94); backdrop-filter:blur(8px); border-bottom:1px solid var(--pv-line); }
.pv-top-in { display:flex; align-items:center; gap:10px; height:60px; }
.pv-brand img { display:block; height:26px !important; width:auto !important; }
.pv-grow { flex:1; }
.pv-chip { display:inline-flex; align-items:center; gap:7px; height:36px; padding:0 14px; border:1px solid var(--pv-line); border-radius:999px; background:#fff; color:var(--pv-ink) !important; font:600 13px/1 'Inter',sans-serif; text-decoration:none !important; cursor:pointer; transition:border-color .15s; }
.pv-chip:hover { border-color:var(--pv-ink); }
.pv-chip-dark { background:var(--pv-ink); color:#fff !important; border-color:var(--pv-ink); }
.pv-dd { position:relative; }
.pv-menu { display:none; position:absolute; right:0; top:calc(100% + 6px); min-width:170px; background:#fff; border:1px solid var(--pv-line); border-radius:12px; padding:6px; box-shadow:0 12px 32px rgba(0,0,0,.1); }
.pv-dd.open .pv-menu { display:block; }
.pv-menu a { display:block; padding:9px 12px; border-radius:8px; font-size:13px; text-decoration:none !important; }
.pv-menu a:hover, .pv-menu a.is-on { background:var(--pv-soft); font-weight:600; }
.pv-preview { background:#fff8e6; border-bottom:1px solid #f0d9a8; padding:10px 0; font-size:13px; }
.pv-preview a { font-weight:600; margin-left:10px; text-decoration:underline; }

/* Title */
.pv-head { padding:28px 0 18px; }
.pv-crumb { display:flex; gap:10px; align-items:center; font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:var(--pv-mute); font-weight:600; }
.pv-crumb span + span::before { content:'·'; margin-right:10px; }
.pv-kind { color:var(--pv-ink); }
.pv-head-row { display:flex; justify-content:space-between; gap:20px; align-items:flex-end; margin-top:8px; }
.pv-title { font-family:'DM Serif Display',Georgia,serif !important; font-weight:400 !important; font-size:44px; line-height:1.08; letter-spacing:-.02em; margin:0; color:var(--pv-ink) !important; }
.pv-sub { display:flex; flex-wrap:wrap; gap:6px 18px; align-items:center; margin-top:12px; color:var(--pv-mute); font-size:14px; }
.pv-addr { display:inline-flex; gap:6px; align-items:center; }
.pv-stars { display:inline-flex; gap:2px; color:var(--pv-gold); }
.pv-stars svg { width:15px; height:15px; fill:currentColor; stroke:none; }
.pv-score { text-decoration:none; color:var(--pv-ink) !important; }
.pv-score b { background:var(--pv-ink); color:#fff; border-radius:7px; padding:2px 7px; font-size:13px; }
.pv-btn { display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border:1px solid var(--pv-line); background:#fff; border-radius:999px; font:600 13px/1 'Inter',sans-serif; cursor:pointer; color:var(--pv-ink); }
.pv-btn:hover { border-color:var(--pv-ink); }

/* Photos */
.pv-gallery { position:relative; display:grid; gap:8px; border-radius:var(--pv-r); overflow:hidden; height:min(520px,52vw); grid-template-columns:2fr 1fr 1fr; grid-template-rows:1fr 1fr; }
.pv-ph { position:relative; display:block; overflow:hidden; background:var(--pv-soft); }
.pv-ph img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .5s; }
.pv-ph:hover img { transform:scale(1.03); }
.pv-ph0 { grid-row:1 / 3; }
.pv-gallery.n0 { height:190px; grid-template-columns:1fr; grid-template-rows:1fr; }
.pv-gallery.n1 { grid-template-columns:1fr; grid-template-rows:1fr; } .pv-gallery.n1 .pv-ph0 { grid-row:auto; }
.pv-gallery.n2 { grid-template-columns:1fr 1fr; grid-template-rows:1fr; } .pv-gallery.n2 .pv-ph0 { grid-row:auto; }
.pv-gallery.n3 { grid-template-columns:2fr 1fr; } .pv-gallery.n3 .pv-ph0 { grid-row:1 / 3; }
.pv-gallery.n4 { grid-template-columns:2fr 1fr 1fr; } .pv-gallery.n4 .pv-ph3 { grid-column:2 / 4; }
.pv-more { position:absolute; inset:0; background:rgba(13,13,16,.55); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; }
.pv-allph { position:absolute; right:16px; bottom:16px; height:38px; padding:0 16px; border:0; border-radius:999px; background:#fff; font:600 13px/1 'Inter',sans-serif; cursor:pointer; box-shadow:0 4px 14px rgba(0,0,0,.18); }
.pv-noimg { grid-column:1 / -1 !important; grid-row:1 / -1 !important; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; color:#a8a8a8; background:linear-gradient(135deg,#f3f3f3,#e9e9e9); }
.pv-noimg svg { width:52px; height:52px; stroke-width:1.2; }

/* Facts */
.pv-facts { list-style:none; display:flex; flex-wrap:wrap; gap:14px 34px; margin:22px 0 0; padding:18px 0; border-top:1px solid var(--pv-line); border-bottom:1px solid var(--pv-line); }
.pv-facts li { display:flex; gap:12px; align-items:center; }
.pv-fi { width:38px; height:38px; border-radius:11px; background:var(--pv-soft); display:flex; align-items:center; justify-content:center; }
.pv-fi svg { width:18px; height:18px; }
.pv-facts small { display:block; font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--pv-mute); line-height:1.2; }
.pv-facts b { font-weight:600; font-size:14px; }

/* Section nav */
.pv-nav { position:sticky; top:60px; z-index:800; display:flex; gap:4px; overflow-x:auto; padding:10px 0; background:#fff; border-bottom:1px solid var(--pv-line); margin-bottom:8px; scrollbar-width:none; }
.pv-nav::-webkit-scrollbar { display:none; }
.pv-nav a { white-space:nowrap; padding:8px 14px; border-radius:999px; font-size:13px; font-weight:600; color:var(--pv-mute) !important; text-decoration:none !important; }
.pv-nav a:hover { color:var(--pv-ink) !important; }
.pv-nav a.is-on { background:var(--pv-ink); color:#fff !important; }

/* Layout */
.pv-grid { display:grid; grid-template-columns:minmax(0,1fr) 380px; gap:56px; align-items:start; }
.pv-sec { padding:34px 0; border-bottom:1px solid var(--pv-line); scroll-margin-top:130px; }
.pv-sec:last-child { border-bottom:0; }
.pv-sec h2, .pv-related h2 { font-family:'DM Serif Display',Georgia,serif !important; font-weight:400 !important; font-size:28px; letter-spacing:-.01em; margin:0 0 16px; color:var(--pv-ink) !important; }
.pv-sec h3, .pv-h3 { font-size:15px; font-weight:700; margin:0 0 10px; color:var(--pv-ink) !important; }
.pv-lead { font-size:17px; color:#333; margin:0 0 14px; }
.pv-prose { color:#333; font-size:15.5px; line-height:1.75; }
.pv-prose p { margin:0 0 12px; } .pv-prose ul, .pv-prose ol { padding-left:20px; margin:0 0 12px; } .pv-prose img { max-width:100%; height:auto; border-radius:10px; }
.pv-clamp { max-height:250px; overflow:hidden; position:relative; }
.pv-clamp.is-short { max-height:none; }
.pv-clamp:not(.is-short):not(.is-open)::after { content:''; position:absolute; left:0; right:0; bottom:0; height:70px; background:linear-gradient(transparent,#fff); }
.pv-clamp.is-open { max-height:none; }
.pv-link { background:none; border:0; padding:0; margin-top:8px; font-weight:600; text-decoration:underline; cursor:pointer; color:var(--pv-ink); }
.pv-muted { color:var(--pv-mute); }

.pv-two { display:grid; grid-template-columns:1fr 1fr; gap:26px; }
.pv-ticks { list-style:none; margin:0; padding:0; display:grid; gap:10px; }
.pv-ticks li { display:flex; gap:10px; align-items:flex-start; }
.pv-ticks svg { width:18px; height:18px; margin-top:3px; color:var(--pv-gold); stroke-width:2.2; }
.pv-ticks.is-no svg { color:#b9b9b9; }

.pv-tl { list-style:none; margin:0; padding:0; border-left:2px solid var(--pv-line); margin-left:15px; }
.pv-tl li { position:relative; padding:0 0 18px 30px; }
.pv-tl button { all:unset; cursor:pointer; display:flex; gap:12px; align-items:flex-start; width:100%; }
.pv-tl button b { display:block; font-size:16px; } .pv-tl button small { display:block; color:var(--pv-mute); }
.pv-dot { position:absolute; left:-17px; top:0; width:32px; height:32px; border-radius:50%; background:var(--pv-ink); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; }
.pv-tl .pv-chev { margin-left:auto; margin-top:5px; transition:transform .2s; }
.pv-tl li.is-open .pv-chev { transform:rotate(180deg); }
.pv-tl-body { display:none; margin-top:10px; color:#333; }
.pv-tl li.is-open .pv-tl-body { display:block; }

.pv-amen { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:22px 30px; }
.pv-amen ul { list-style:none; margin:0; padding:0; display:grid; gap:8px; }
.pv-amen li { display:flex; gap:10px; align-items:center; font-size:14px; }
.pv-amen li svg { width:16px; height:16px; color:var(--pv-gold); stroke-width:2.2; } .pv-amen li img { width:18px; height:18px; object-fit:contain; } .pv-amen li i { width:18px; text-align:center; color:var(--pv-mute); }
.pv-specs { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:10px 30px; margin:0; }
.pv-specs div { display:flex; justify-content:space-between; gap:14px; border-bottom:1px solid var(--pv-line); padding:9px 0; } .pv-specs dt { color:var(--pv-mute); font-weight:500; } .pv-specs dd { margin:0; font-weight:600; text-align:right; }

.pv-acc details { border-bottom:1px solid var(--pv-line); }
.pv-acc summary { list-style:none; cursor:pointer; padding:16px 0; display:flex; justify-content:space-between; gap:16px; align-items:center; font-weight:600; }
.pv-acc summary::-webkit-details-marker { display:none; }
.pv-acc details[open] .pv-chev { transform:rotate(180deg); }
.pv-acc details > div { padding:0 0 16px; color:#333; }
.pv-chev { transition:transform .2s; }

.pv-map { height:360px; border-radius:var(--pv-r); overflow:hidden; background:var(--pv-soft); }
.pv-addr-line { color:var(--pv-mute); margin:-6px 0 14px; }
.pv-nearby { list-style:none; padding:0; margin:0; display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:8px 26px; }
.pv-nearby li { display:flex; justify-content:space-between; gap:10px; border-bottom:1px solid var(--pv-line); padding:8px 0; } .pv-nearby span { color:var(--pv-mute); }

.pv-rate { display:flex; align-items:center; gap:14px; margin-bottom:20px; } .pv-rate-n { width:54px; height:54px; border-radius:14px; background:var(--pv-ink); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700; }
.pv-reviews { display:grid; gap:22px; }
.pv-reviews article { border:1px solid var(--pv-line); border-radius:var(--pv-r); padding:18px; }
.pv-rv-h { display:flex; gap:12px; align-items:center; } .pv-rv-h small { display:block; color:var(--pv-mute); } .pv-rv-s { margin-left:auto; }
.pv-av, .pv-by-i { width:40px; height:40px; border-radius:50%; background:var(--pv-soft); display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0; }
.pv-reviews h4 { font-size:15px; margin:12px 0 4px; } .pv-reviews p { margin:0; color:#333; }
.pv-review-form { margin-top:30px; display:grid; gap:12px; }
.pv-review-form input[type=text], .pv-review-form textarea { width:100%; border:1px solid var(--pv-line); border-radius:12px; padding:12px 14px; font:inherit; }
.pv-review-form input:focus, .pv-review-form textarea:focus { outline:none; border-color:var(--pv-ink); }
.pv-rate-in { display:flex; flex-wrap:wrap; gap:14px 28px; } .pv-rate-in .item span { font-size:13px; font-weight:600; display:block; }
.pv-rate-in .rates i { color:var(--pv-gold); cursor:pointer; font-size:16px; }

/* Booking card */
.pv-side { position:sticky; top:130px; }
.pv-book { border:1px solid var(--pv-line); border-radius:18px; padding:22px; background:#fff; box-shadow:0 18px 46px rgba(13,13,16,.09); margin-top:34px; }
.pv-book-price { display:flex; align-items:baseline; gap:8px; margin-bottom:14px; } .pv-book-price b { font-size:28px; font-family:'DM Serif Display',Georgia,serif; font-weight:400; } .pv-book-price small { color:var(--pv-mute); } .pv-book-price s { color:#b0b0b0; }
.pv-book-note { color:var(--pv-mute); font-size:12.5px; margin:10px 0 0; text-align:center; }
.pv-cta { display:flex; align-items:center; justify-content:center; height:50px; padding:0 22px; border-radius:12px; background:var(--pv-ink); color:#fff !important; font:600 15px/1 'Inter',sans-serif; text-decoration:none !important; border:0; cursor:pointer; width:100%; transition:background .15s, transform .1s; }
.pv-cta:hover { background:#26262b; } .pv-cta:active { transform:translateY(1px); }
.pv-by { display:flex; gap:12px; align-items:center; margin-top:18px; padding-top:16px; border-top:1px solid var(--pv-line); } .pv-by small { display:block; color:var(--pv-mute); font-size:12px; line-height:1.2; }

/* The platform's booking widget, restyled inside the card (its scripts and endpoints are unchanged) */
.pv-book .bc_single_book_wrap { display:block !important; margin:0 !important; }
.pv-book .w-360, .pv-book .lg\:w-full { width:100% !important; }
.pv-book .bc_single_book { padding:0 !important; border:0 !important; box-shadow:none !important; background:transparent !important; border-radius:0 !important; }
.pv-book .owner-info { display:none !important; }
.pv-book .text-red-1 { color:#b0b0b0 !important; } .pv-book .text-blue-1, .pv-book .bg-blue-1 { color:inherit; }
.pv-book .bg-blue-1.rounded-4.flex-center { background:var(--pv-ink) !important; }
.pv-book input, .pv-book select, .pv-book textarea { border:1px solid var(--pv-line) !important; border-radius:10px !important; font-family:inherit !important; background:#fff !important; }
.pv-book .form-group, .pv-book .form-content { border:1px solid var(--pv-line) !important; border-radius:12px !important; }
.pv-book .btn-primary, .pv-book .btn-book-ajax, .pv-book button[type=submit], .pv-book .btn.btn-primary, .pv-book .btn-search { background:var(--pv-ink) !important; border:0 !important; color:#fff !important; border-radius:12px !important; height:50px; font-weight:600 !important; }
.pv-book .nav-enquiry { display:flex; gap:0; margin:14px 0; border-bottom:1px solid var(--pv-line); } .pv-book .enquiry-item { flex:1; text-align:center; padding:9px 0; cursor:pointer; color:var(--pv-mute); font-weight:600; font-size:13px; } .pv-book .enquiry-item.active { color:var(--pv-ink); border-bottom:2px solid var(--pv-ink); }
.pv-book .total-price, .pv-book .price-total { font-weight:700; }
.pv-book .loading { opacity:.6; }

/* Rooms (hotel): the platform's availability form and room list */
.pv-rooms .bravo_form, .pv-rooms .form-search-rooms, .pv-rooms .g-form { border:1px solid var(--pv-line) !important; border-radius:var(--pv-r) !important; box-shadow:none !important; background:#fff !important; }
.pv-rooms .btn-primary, .pv-rooms .btn-search, .pv-rooms button[type=submit], .pv-rooms .button.-dark-1, .pv-rooms .btn-book-ajax { background:var(--pv-ink) !important; border:0 !important; border-radius:12px !important; color:#fff !important; }
.pv-rooms input, .pv-rooms select { border-radius:10px !important; font-family:inherit !important; }
.pv-rooms .item, .pv-rooms .room-item, .pv-rooms .hotel-room-item { border:1px solid var(--pv-line) !important; border-radius:var(--pv-r) !important; box-shadow:none !important; background:#fff !important; margin-bottom:14px; }
.pv-rooms h3.text-22 { display:none; }
.pv-rooms .text-blue-1 { color:var(--pv-ink) !important; }
.pv-rooms .text-red-1 { color:#b0b0b0 !important; }
.pv-rooms .alert, .pv-rooms .alert-warning { border-radius:12px; border:1px solid #f0d9a8; background:#fffaf0; color:#333; }

/* Related */
.pv-related { padding:38px 0 30px; border-top:1px solid var(--pv-line); margin-top:10px; }
.pv-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:22px; }
.pv-card { display:flex; flex-direction:column; gap:3px; text-decoration:none !important; }
.pv-card-img { display:block; aspect-ratio:4/3; border-radius:var(--pv-r); overflow:hidden; background:linear-gradient(135deg,#f3f3f3,#e9e9e9); margin-bottom:8px; }
.pv-card-img img { width:100%; height:100%; object-fit:cover; transition:transform .5s; } .pv-card:hover .pv-card-img img { transform:scale(1.04); }
.pv-card b { font-size:15px; } .pv-card small { color:var(--pv-mute); } .pv-card-p { font-size:13px; color:var(--pv-mute); } .pv-card-p b { color:var(--pv-ink); }

.pv-foot { border-top:1px solid var(--pv-line); margin-top:30px; padding:34px 0 90px; background:#fff; }
.pv-foot-in { display:flex; justify-content:space-between; gap:20px; flex-wrap:wrap; align-items:flex-end; }
.pv-foot img { height:22px !important; width:auto !important; }
.pv-foot p { color:var(--pv-mute); font-size:13px; margin:8px 0 0; } .pv-foot-r { color:var(--pv-mute); font-size:12.5px; }

/* Lightbox */
.pv-lb { position:fixed; inset:0; z-index:20000; background:rgba(13,13,16,.94); display:flex; align-items:center; justify-content:center; }
.pv-lb[hidden] { display:none; }
.pv-lb img { max-width:min(92vw,1300px); max-height:86vh; border-radius:8px; }
.pv-lb button { position:absolute; background:rgba(255,255,255,.12); color:#fff; border:0; width:46px; height:46px; border-radius:50%; font-size:26px; cursor:pointer; }
.pv-lb-x { top:20px; right:20px; } .pv-lb-p { left:20px; top:50%; } .pv-lb-n { right:20px; top:50%; }
.pv-lb-c { position:absolute; bottom:20px; color:#fff; font-size:13px; }

/* Phones */
.pv-bar { display:none; }
@media (max-width: 991px) {
    .pv-grid { grid-template-columns:1fr; gap:0; }
    .pv-side { position:static; } .pv-book { margin-top:10px; margin-bottom:20px; }
    .pv-title { font-size:32px; }
    .pv-head-row { flex-direction:column; align-items:flex-start; }
    .pv-gallery { height:300px; grid-template-columns:1fr 1fr; grid-template-rows:1fr; } .pv-gallery .pv-ph:nth-child(n+3) { display:none; } .pv-ph0 { grid-row:auto; }
    .pv-gallery.n1 { grid-template-columns:1fr; }
    .pv-two { grid-template-columns:1fr; }
    .pv-wrap { padding:0 16px; }
    .pv-bar { display:flex; position:fixed; left:0; right:0; bottom:0; z-index:950; background:#fff; border-top:1px solid var(--pv-line); padding:10px 16px; align-items:center; justify-content:space-between; gap:14px; }
    .pv-bar small { display:block; color:var(--pv-mute); font-size:11px; line-height:1; } .pv-bar b { font-size:20px; font-family:'DM Serif Display',Georgia,serif; font-weight:400; }
    .pv-bar .pv-cta { width:auto; min-width:160px; height:46px; }
    .pv-sec { padding:26px 0; } .pv-sec h2 { font-size:24px; }
}
@media (max-width: 575px) { .pv-gallery { grid-template-columns:1fr; } .pv-gallery .pv-ph:nth-child(n+2) { display:none; } }
@media (prefers-reduced-motion: reduce) { .pv *, .pv *::before { transition:none !important; } }
</style>
