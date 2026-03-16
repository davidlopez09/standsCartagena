// ── CURSOR ──
const cursor = document.getElementById("cursor");
const ring = document.getElementById("cursor-ring");
let mx = 0,
    my = 0,
    rx = 0,
    ry = 0;
document.addEventListener("mousemove", (e) => {
    mx = e.clientX;
    my = e.clientY;
    cursor.style.transform = `translate(${mx - 6}px,${my - 6}px)`;
});
(function animRing() {
    rx += (mx - rx - 18) * 0.12;
    ry += (my - ry - 18) * 0.12;
    ring.style.transform = `translate(${rx}px,${ry}px)`;
    requestAnimationFrame(animRing);
})();
document.querySelectorAll("a,button,.product-card,.benefit-item").forEach((el) => {
    el.addEventListener("mouseenter", () => {
        ring.style.width = "54px";
        ring.style.height = "54px";
        ring.style.borderColor = "rgba(240,90,26,0.8)";
    });
    el.addEventListener("mouseleave", () => {
        ring.style.width = "36px";
        ring.style.height = "36px";
        ring.style.borderColor = "rgba(240,90,26,0.6)";
    });
});

// ── NAV SCROLL ──
window.addEventListener("scroll", () => {
    document.getElementById("navbar").classList.toggle("scrolled", window.scrollY > 30);
});

// ── TABS ──
function initSlider() {
    const activeBtn = document.querySelector(".tab-btn.active");
    const slider = document.getElementById("tabSlider");
    if (activeBtn && slider) {
        slider.style.transition = "none";
        slider.style.width = activeBtn.offsetWidth + "px";
        slider.style.transform = `translateX(${activeBtn.offsetLeft - 6}px)`;
        // Re-enable transition after a tick
        requestAnimationFrame(() => {
            slider.style.transition = "";
        });
    }
}

function switchTab(city, btn) {
    document.querySelectorAll(".tab-panel").forEach((p) => p.classList.remove("active"));
    document.querySelectorAll(".tab-btn").forEach((b) => b.classList.remove("active"));
    document.getElementById("tab-" + city).classList.add("active");
    btn.classList.add("active");
    const slider = document.getElementById("tabSlider");
    slider.style.width = btn.offsetWidth + "px";
    slider.style.transform = `translateX(${btn.offsetLeft - 6}px)`;
}

// Init on DOMContentLoaded (faster than load)
document.addEventListener("DOMContentLoaded", initSlider);
window.addEventListener("load", initSlider);
window.addEventListener("resize", initSlider);

// ── SCROLL REVEAL ──
const revealObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
            }
        });
    },
    {
        threshold: 0.12,
        rootMargin: "0px 0px -40px 0px",
    },
);
document.querySelectorAll(".reveal, .reveal-left, .reveal-right").forEach((el) => revealObserver.observe(el));

// ── COUNTER ANIMATION ──
function animateCounter(el, target, duration = 1600) {
    let start = 0;
    const step = (ts) => {
        if (!start) start = ts;
        const progress = Math.min((ts - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(ease * target);
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = target;
    };
    requestAnimationFrame(step);
}
const counterObserver = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const nums = entry.target.querySelectorAll(".num");
                nums.forEach((n) => {
                    const val = parseInt(n.textContent.replace(/[^0-9]/g, ""));
                    if (val) {
                        const span = n.querySelector("span");
                        const suffix = span ? span.outerHTML : "";
                        animateCounter(
                            {
                                textContent: "",
                            },
                            val,
                            1600,
                        );
                        n.innerHTML = `<span>0</span>${suffix}`;
                    }
                });

                counterObserver.unobserve(entry.target);
            }
        });
    },
    {
        threshold: 0.3,
    },
);
document.querySelectorAll(".why-stat").forEach((el) => counterObserver.observe(el));

// ── DROPDOWN CIUDADES — agregar al final de script.js ──
(function () {
    const trigger = document.getElementById("dropdownTrigger");
    const menu = document.getElementById("dropdownMenu");
    if (!trigger || !menu) return;

    // Abrir/cerrar con clic
    trigger.addEventListener("click", function (e) {
        e.stopPropagation();
        const isOpen = trigger.getAttribute("aria-expanded") === "true";
        trigger.setAttribute("aria-expanded", String(!isOpen));
        menu.classList.toggle("open", !isOpen);
    });

    // Cerrar al hacer clic fuera
    document.addEventListener("click", function () {
        trigger.setAttribute("aria-expanded", "false");
        menu.classList.remove("open");
    });

    // Evitar que el clic dentro del menú lo cierre
    menu.addEventListener("click", function (e) {
        e.stopPropagation();
    });

    // Cerrar con Escape
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            trigger.setAttribute("aria-expanded", "false");
            menu.classList.remove("open");
            trigger.focus();
        }
    });
})();
