const cursor = document.getElementById("cursor");
const ring = document.getElementById("cursor-ring");
let mx = 0,
    my = 0,
    rx = 0,
    ry = 0;

document.addEventListener("mousemove", (e) => {
    mx = e.clientX;
    my = e.clientY;
    cursor.style.transform = `translate(${mx - 5}px,${my - 5}px)`;
});
(function animRing() {
    rx += (mx - rx - 18) * 0.12;
    ry += (my - ry - 18) * 0.12;
    ring.style.transform = `translate(${rx}px,${ry}px)`;
    requestAnimationFrame(animRing);
})();

document.querySelectorAll("a, button, .product-card, .benefit").forEach((el) => {
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

window.addEventListener("scroll", () => {
    document.getElementById("navbar").classList.toggle("scrolled", window.scrollY > 30);
});

const obs = new IntersectionObserver(
    (entries) => {
        entries.forEach((e) => {
            if (e.isIntersecting) e.target.classList.add("visible");
        });
    },
    { threshold: 0.1, rootMargin: "0px 0px -40px 0px" },
);
document.querySelectorAll(".reveal, .reveal-left, .reveal-right").forEach((el) => obs.observe(el));

// ── HAMBURGER MENU ──
const hamburger = document.getElementById("hamburger");
const mobileMenu = document.getElementById("mobileMenu");

hamburger.addEventListener("click", () => {
    const isOpen = mobileMenu.classList.toggle("open");
    hamburger.classList.toggle("open", isOpen);
    hamburger.setAttribute("aria-expanded", isOpen);
});

mobileMenu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
        mobileMenu.classList.remove("open");
        hamburger.classList.remove("open");
        hamburger.setAttribute("aria-expanded", false);
    });
});
