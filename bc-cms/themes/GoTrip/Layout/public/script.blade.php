<script>
function pvDd(b) { var d = b.closest('.pv-dd'); d.classList.toggle('open'); }
document.addEventListener('click', function (e) { document.querySelectorAll('.pv-dd.open').forEach(function (d) { if (!d.contains(e.target)) d.classList.remove('open'); }); });
function pvBroken(img) { var p = img.closest('.pv-ph, .pv-card-img'); img.style.display = 'none'; if (p && p.classList.contains('pv-ph') && !p.querySelector('img:not([style*="none"])')) p.classList.add('pv-noimg'); }
function pvShare(btn) {
    var u = btn.dataset.url || location.href, t = btn.dataset.title || document.title;
    if (navigator.share) { navigator.share({ title: t, url: u }).catch(function () {}); return; }
    (navigator.clipboard ? navigator.clipboard.writeText(u) : Promise.reject()).then(function () { var s = btn.querySelector('span'), o = s.textContent; s.textContent = @json(__('Link copied')); setTimeout(function () { s.textContent = o; }, 1800); }).catch(function () { prompt(t, u); });
}
var pvI = 0;
function pvLightbox(i) { if (!window.pvPhotos || !window.pvPhotos.length) return true; pvI = i; document.getElementById('pvLb').hidden = false; document.body.style.overflow = 'hidden'; pvShow(); return false; }
function pvShow() { var n = window.pvPhotos.length; pvI = (pvI + n) % n; document.getElementById('pvLbImg').src = window.pvPhotos[pvI]; document.getElementById('pvLbC').textContent = (pvI + 1) + ' / ' + n; }
function pvStep(d) { pvI += d; pvShow(); }
function pvClose() { document.getElementById('pvLb').hidden = true; document.body.style.overflow = ''; }
document.addEventListener('keydown', function (e) { var lb = document.getElementById('pvLb'); if (!lb || lb.hidden) return; if (e.key === 'Escape') pvClose(); if (e.key === 'ArrowLeft') pvStep(-1); if (e.key === 'ArrowRight') pvStep(1); });
function pvExpand() { document.getElementById('pvProse').classList.add('is-open'); document.getElementById('pvMore').style.display = 'none'; }
(function () {
    var prose = document.getElementById('pvProse');
    if (prose) { if (prose.scrollHeight <= 262) { prose.classList.add('is-short'); } else { document.getElementById('pvMore').style.display = ''; } }
    // Section nav: highlight where you are.
    var links = [].slice.call(document.querySelectorAll('#pvNav a'));
    var secs = links.map(function (a) { return document.getElementById(a.dataset.s); }).filter(Boolean);
    function spy() { var y = window.scrollY + 170, cur = null; secs.forEach(function (s) { if (s.offsetTop <= y) cur = s.id; }); links.forEach(function (a) { a.classList.toggle('is-on', a.dataset.s === cur); }); }
    window.addEventListener('scroll', spy, { passive: true }); spy();
    links.forEach(function (a) { a.addEventListener('click', function (e) { var t = document.getElementById(a.dataset.s); if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); } }); });
})();
</script>
