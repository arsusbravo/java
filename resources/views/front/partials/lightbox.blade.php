{{--
    Photo lightbox. Include once per page, then mark photo links with data-lightbox="group":

        <a href="{{ image_url($path) }}" data-lightbox="hotel" data-caption="Pool">...</a>

    Links keep working without JavaScript (they open the photo). Arrow keys, swipe,
    Esc and the thumbnails move between the photos of the same group.
--}}
@once
<dialog id="lightbox" aria-label="Photo viewer" class="m-0 h-full max-h-none w-full max-w-none bg-black/95 p-0 backdrop:bg-black/80">
    <div class="flex h-full w-full flex-col text-white" data-lightbox-backdrop>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <p class="text-sm text-white/80" aria-live="polite"><span data-lightbox-counter></span><span data-lightbox-caption class="ml-3"></span></p>
            <button type="button" data-lightbox-close class="rounded-full p-2 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-white" aria-label="Close">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="relative flex min-h-0 flex-1 items-center justify-center px-2 sm:px-16" data-lightbox-stage>
            <button type="button" data-lightbox-prev class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/40 p-3 hover:bg-black/70 focus-visible:outline-2 focus-visible:outline-white sm:left-4" aria-label="Previous photo">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <img data-lightbox-image src="" alt="" class="max-h-full max-w-full select-none rounded-lg object-contain shadow-2xl transition-opacity duration-200">
            <button type="button" data-lightbox-next class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/40 p-3 hover:bg-black/70 focus-visible:outline-2 focus-visible:outline-white sm:right-4" aria-label="Next photo">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>

        <div class="flex justify-center gap-2 overflow-x-auto px-4 py-3" data-lightbox-thumbs></div>
    </div>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('lightbox');
    if (!dialog || typeof dialog.showModal !== 'function') return; // old browsers: links just open the photo

    const image = dialog.querySelector('[data-lightbox-image]');
    const counter = dialog.querySelector('[data-lightbox-counter]');
    const caption = dialog.querySelector('[data-lightbox-caption]');
    const thumbs = dialog.querySelector('[data-lightbox-thumbs]');
    const prev = dialog.querySelector('[data-lightbox-prev]');
    const next = dialog.querySelector('[data-lightbox-next]');
    let photos = [];
    let index = 0;
    let opener = null;

    const show = (i) => {
        index = (i + photos.length) % photos.length;
        const photo = photos[index];
        image.style.opacity = '0';
        image.onload = () => { image.style.opacity = '1'; };
        image.src = photo.src;
        image.alt = photo.alt;
        counter.textContent = `${index + 1} / ${photos.length}`;
        caption.textContent = photo.caption;
        thumbs.querySelectorAll('button').forEach((thumb, t) => {
            thumb.setAttribute('aria-current', t === index ? 'true' : 'false');
            thumb.classList.toggle('ring-2', t === index);
            thumb.classList.toggle('opacity-50', t !== index);
            if (t === index) thumb.scrollIntoView({ block: 'nearest', inline: 'center' });
        });
        // Load the neighbours ahead, so arrows feel instant
        [index + 1, index - 1].forEach((n) => { new Image().src = photos[(n + photos.length) % photos.length].src; });
    };

    const open = (link) => {
        const group = link.dataset.lightbox;
        const links = [...document.querySelectorAll(`[data-lightbox="${CSS.escape(group)}"]`)];
        // The same photo may be linked twice (big tile and grid): show it once
        const seen = new Set();
        photos = links.filter((l) => !seen.has(l.href) && seen.add(l.href)).map((l) => ({
            src: l.href,
            alt: l.querySelector('img')?.alt || l.dataset.caption || '',
            caption: l.dataset.caption || '',
        }));
        const single = photos.length < 2;
        prev.hidden = next.hidden = thumbs.hidden = single;
        thumbs.replaceChildren(...photos.map((photo, t) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'h-14 w-20 shrink-0 overflow-hidden rounded ring-white transition-opacity hover:opacity-100';
            button.setAttribute('aria-label', `Photo ${t + 1}`);
            const thumb = document.createElement('img');
            thumb.src = photo.src;
            thumb.alt = '';
            thumb.loading = 'lazy';
            thumb.className = 'h-full w-full object-cover';
            button.append(thumb);
            button.addEventListener('click', () => show(t));
            return button;
        }));
        opener = link;
        dialog.showModal();
        document.documentElement.style.overflow = 'hidden';
        show(photos.findIndex((photo) => photo.src === link.href));
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-lightbox]');
        if (!link || event.metaKey || event.ctrlKey || event.shiftKey) return; // new-tab clicks still work
        event.preventDefault();
        open(link);
    });

    prev.addEventListener('click', () => show(index - 1));
    next.addEventListener('click', () => show(index + 1));
    dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
    // A click on the dark area around the photo closes it
    dialog.querySelector('[data-lightbox-stage]').addEventListener('click', (event) => {
        if (event.target === event.currentTarget) dialog.close();
    });
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') show(index - 1);
        if (event.key === 'ArrowRight') show(index + 1);
    });
    dialog.addEventListener('close', () => {
        document.documentElement.style.overflow = '';
        image.removeAttribute('src');
        opener?.focus();
    });

    // Swipe left / right on phones
    let touchX = null;
    dialog.addEventListener('touchstart', (event) => { touchX = event.touches[0].clientX; }, { passive: true });
    dialog.addEventListener('touchend', (event) => {
        if (touchX === null || photos.length < 2) return;
        const moved = event.changedTouches[0].clientX - touchX;
        if (Math.abs(moved) > 50) show(index + (moved < 0 ? 1 : -1));
        touchX = null;
    });
})();
</script>
@endonce
