const slides = [...document.querySelectorAll(".slide")],
  dots = [...document.querySelectorAll(".slider-dots button")];
let current = 0,
  timer;
function showSlide(index) {
  current = (index + slides.length) % slides.length;
  slides.forEach((slide, i) =>
    slide.classList.toggle("active", i === current),
  );
  dots.forEach((dot, i) => dot.classList.toggle("active", i === current));
}
function startSlider() {
  clearInterval(timer);
  timer = setInterval(() => showSlide(current + 1), 5000);
}
dots.forEach((dot, i) =>
  dot.addEventListener("click", () => {
    showSlide(i);
    startSlider();
  }),
);
startSlider();
const toggle = document.querySelector(".menu-toggle"),
  nav = document.querySelector(".nav"),
  backdrop = document.querySelector(".nav-backdrop");
function setMenu(open) {
  nav.classList.toggle("open", open);
  backdrop.classList.toggle("open", open);
  document.body.classList.toggle("menu-open", open);
  toggle.setAttribute("aria-expanded", open);
}
toggle.addEventListener("click", () =>
  setMenu(!nav.classList.contains("open")),
);
backdrop.addEventListener("click", () => setMenu(false));
const drawerClose = document.querySelector(".nav-drawer-close");
if (drawerClose) {
  drawerClose.addEventListener("click", () => setMenu(false));
}
nav
  .querySelectorAll("a")
  .forEach((link) =>
    link.addEventListener("click", () => setMenu(false)),
  );
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") setMenu(false);
});
document.querySelectorAll(".faq-trigger").forEach((trigger) => {
  trigger.addEventListener("click", () => {
    const expanded = trigger.getAttribute("aria-expanded") === "true";
    const content = document.getElementById(
      trigger.getAttribute("aria-controls"),
    );
    trigger.setAttribute("aria-expanded", String(!expanded));
    if (!content) return;
    if (expanded) {
      content.style.maxHeight = content.scrollHeight + "px";
      requestAnimationFrame(() => {
        content.style.maxHeight = "0px";
      });
      content.classList.remove("is-open");
    } else {
      content.classList.add("is-open");
      content.style.maxHeight = content.scrollHeight + "px";
    }
  });
});
