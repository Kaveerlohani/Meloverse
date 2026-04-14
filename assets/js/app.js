(function () {
    const body = document.body;
    const base = (body.dataset.base || "").replace(/\/$/, "");

    function apiUrl(path) {
        return base + path;
    }

    function initMobileNav() {
        const toggle = document.querySelector(".mv-menu-toggle");
        const bar = document.querySelector(".mv-topbar");
        if (!toggle || !bar) return;
        toggle.addEventListener("click", () => {
            const open = bar.classList.toggle("nav-open");
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        });
    }

    function initGuestModal() {
        const logged = body.dataset.loggedIn === "1";
        const mobile = body.dataset.mobile === "1";
        const modal = document.getElementById("mv-guest-modal");
        if (!modal || logged || mobile) return;
        const t = setTimeout(() => {
            modal.hidden = false;
        }, 60000);
        modal.querySelectorAll("[data-close-modal]").forEach((el) => {
            el.addEventListener("click", () => {
                modal.hidden = true;
                clearTimeout(t);
            });
        });
    }

    async function postJson(url, data) {
        const res = await fetch(apiUrl(url), {
            method: "POST",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            credentials: "same-origin",
            body: JSON.stringify(data),
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.ok === false) {
            const err = json.error || res.statusText || "Request failed";
            throw new Error(err);
        }
        return json;
    }

    function initActions() {
        document.addEventListener("click", async (e) => {
            const t = e.target;
            if (!(t instanceof HTMLElement)) return;

            const likeBtn = t.closest("[data-like]");
            if (likeBtn) {
                e.preventDefault();
                const postId = likeBtn.getAttribute("data-post");
                if (!postId) return;
                try {
                    const data = await postJson("/api/like.php", { post_id: Number(postId) });
                    likeBtn.classList.toggle("is-on", !!data.liked);
                    likeBtn.innerHTML = (data.liked ? "♥" : "♡") + ' <span class="mv-like-count">' + (data.likes_count ?? 0) + "</span>";
                } catch (err) {
                    if (String(err.message).includes("Authentication")) {
                        window.location.href = "login.php";
                    } else {
                        alert(err.message);
                    }
                }
                return;
            }

            const bmBtn = t.closest("[data-bookmark]");
            if (bmBtn) {
                e.preventDefault();
                const postId = bmBtn.getAttribute("data-post");
                if (!postId) return;
                try {
                    const data = await postJson("/api/bookmark.php", { post_id: Number(postId) });
                    bmBtn.classList.toggle("is-on", !!data.bookmarked);
                    bmBtn.textContent = (data.bookmarked ? "★ " : "☆ ") + "Save";
                } catch (err) {
                    if (String(err.message).includes("Authentication")) {
                        window.location.href = "login.php";
                    } else {
                        alert(err.message);
                    }
                }
                return;
            }

            const folBtn = t.closest("[data-follow]");
            if (folBtn) {
                e.preventDefault();
                const uid = folBtn.getAttribute("data-user");
                if (!uid) return;
                try {
                    const data = await postJson("/api/follow.php", { user_id: Number(uid) });
                    folBtn.textContent = data.following ? "Following" : "Follow";
                    folBtn.classList.toggle("mv-btn--secondary", !!data.following);
                } catch (err) {
                    alert(err.message);
                }
                return;
            }

            const shareBtn = t.closest("[data-share]");
            if (shareBtn) {
                e.preventDefault();
                const url = shareBtn.getAttribute("data-url") || window.location.href;
                try {
                    if (navigator.share) {
                        await navigator.share({ title: "MELOVERSE", url });
                    } else if (navigator.clipboard) {
                        await navigator.clipboard.writeText(url);
                        alert("Link copied to clipboard.");
                    } else {
                        prompt("Copy link:", url);
                    }
                } catch (_) {
                    /* cancelled */
                }
            }
        });
    }

    function formatTime(sec) {
        sec = Math.max(0, Math.floor(sec || 0));
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return m + ":" + String(s).padStart(2, "0");
    }

    const played = new Set();

    function initPlayers() {
        document.querySelectorAll("[data-mv-player]").forEach((root) => {
            const audio = root.querySelector("audio");
            const btn = root.querySelector(".mv-play-toggle");
            const bar = root.querySelector(".mv-player__bar");
            const fill = root.querySelector(".mv-player__fill");
            const curEl = root.querySelector(".mv-cur");
            const durEl = root.querySelector(".mv-dur");
            const vol = root.querySelector(".mv-vol");
            if (!audio || !btn || !bar || !fill || !curEl || !durEl) return;

            const postArticle = root.closest("[data-post-id]");
            const postId = postArticle ? postArticle.getAttribute("data-post-id") : null;

            function setDur() {
                if (audio.duration && isFinite(audio.duration)) {
                    durEl.textContent = formatTime(audio.duration);
                }
            }

            audio.addEventListener("loadedmetadata", setDur);

            audio.addEventListener("timeupdate", () => {
                if (!audio.duration || !isFinite(audio.duration)) return;
                const p = (audio.currentTime / audio.duration) * 100;
                fill.style.width = p + "%";
                curEl.textContent = formatTime(audio.currentTime);
            });

            audio.addEventListener("play", () => {
                btn.textContent = "❚❚";
                btn.setAttribute("aria-label", "Pause");
                if (postId && !played.has(postId)) {
                    played.add(postId);
                    fetch(apiUrl("/api/play.php"), {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        credentials: "same-origin",
                        body: JSON.stringify({ post_id: Number(postId) }),
                    }).catch(() => {});
                }
            });

            audio.addEventListener("pause", () => {
                btn.textContent = "▶";
                btn.setAttribute("aria-label", "Play");
            });

            btn.addEventListener("click", () => {
                document.querySelectorAll("[data-mv-player] audio").forEach((a) => {
                    if (a !== audio && !a.paused) a.pause();
                });
                if (audio.paused) {
                    audio.play().catch(() => {});
                } else {
                    audio.pause();
                }
            });

            bar.addEventListener("click", (ev) => {
                if (!audio.duration || !isFinite(audio.duration)) return;
                const rect = bar.getBoundingClientRect();
                const x = ev.clientX - rect.left;
                const ratio = Math.min(1, Math.max(0, x / rect.width));
                audio.currentTime = ratio * audio.duration;
            });

            if (vol) {
                vol.addEventListener("input", () => {
                    audio.volume = Number(vol.value);
                });
            }
        });
    }

    function initCommentForm() {
        const form = document.getElementById("mv-comment-form");
        const parentInput = document.getElementById("mv-parent-id");
        const hint = document.getElementById("mv-reply-hint");
        const cancel = document.getElementById("mv-cancel-reply");
        if (!form || !parentInput) return;

        document.addEventListener("click", (e) => {
            const btn = e.target instanceof Element ? e.target.closest("[data-reply-to]") : null;
            if (!btn) return;
            const id = btn.getAttribute("data-reply-to");
            parentInput.value = id || "";
            if (hint) hint.hidden = !id;
        });

        cancel?.addEventListener("click", () => {
            parentInput.value = "";
            if (hint) hint.hidden = true;
        });

        form.addEventListener("submit", async (ev) => {
            ev.preventDefault();
            const postId = Number(form.getAttribute("data-post"));
            const bodyText = /** @type {HTMLTextAreaElement} */ (form.querySelector("textarea[name=body]")).value.trim();
            const parentId = parentInput.value ? Number(parentInput.value) : 0;
            if (!bodyText) return;
            try {
                await postJson("/api/comment.php", {
                    post_id: postId,
                    body: bodyText,
                    parent_id: parentId > 0 ? parentId : undefined,
                });
                window.location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    }

    function initUploadDropzone() {
        const dz = document.getElementById("mv-dropzone");
        const input = document.getElementById("mv-audio-input");
        if (!dz || !input) return;
        dz.addEventListener("dragover", (e) => {
            e.preventDefault();
            dz.classList.add("is-drag");
        });
        dz.addEventListener("dragleave", () => dz.classList.remove("is-drag"));
        dz.addEventListener("drop", (e) => {
            e.preventDefault();
            dz.classList.remove("is-drag");
            const f = e.dataTransfer?.files?.[0];
            if (f && input) {
                input.files = e.dataTransfer.files;
            }
        });
    }

    initMobileNav();
    initGuestModal();
    initActions();
    initPlayers();
    initCommentForm();
    initUploadDropzone();
})();
