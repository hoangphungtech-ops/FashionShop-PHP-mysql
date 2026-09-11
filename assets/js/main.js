(() => {
    "use strict";

    const header = document.querySelector("[data-site-header]");
    const menuToggle = header?.querySelector("[data-menu-toggle]");
    const navigation = header?.querySelector("[data-site-nav]");

    const setMenuState = (isOpen, returnFocus = false) => {
        if (!header || !menuToggle) {
            return;
        }

        header.classList.toggle("is-menu-open", isOpen);
        document.body.classList.toggle("has-open-menu", isOpen);
        menuToggle.setAttribute("aria-expanded", String(isOpen));
        menuToggle.setAttribute("aria-label", isOpen ? "Đóng menu" : "Mở menu");

        if (returnFocus) {
            menuToggle.focus();
        }
    };

    if (header && menuToggle && navigation) {
        menuToggle.addEventListener("click", () => {
            setMenuState(!header.classList.contains("is-menu-open"));
        });

        navigation.addEventListener("click", (event) => {
            if (event.target.closest("a")) {
                setMenuState(false);
            }
        });

        document.addEventListener("click", (event) => {
            if (!header.contains(event.target)) {
                setMenuState(false);
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && header.classList.contains("is-menu-open")) {
                setMenuState(false, true);
            }
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth > 960) {
                setMenuState(false);
            }
        });
    }

    const updateHeader = () => {
        header?.classList.toggle("is-scrolled", window.scrollY > 12);
    };

    const backToTop = document.querySelector("[data-back-to-top]");
    const updateBackToTop = () => {
        backToTop?.classList.toggle("is-visible", window.scrollY > 560);
    };

    updateHeader();
    updateBackToTop();

    window.addEventListener("scroll", () => {
        updateHeader();
        updateBackToTop();
    }, { passive: true });

    backToTop?.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    document.querySelectorAll("[data-gallery-thumb]").forEach((thumbnail) => {
        thumbnail.addEventListener("click", () => {
            const gallery = thumbnail.closest("[data-product-gallery]");
            const mainImage = gallery?.querySelector("[data-gallery-main]");
            const nextSource = thumbnail.getAttribute("data-gallery-src");
            const nextAlt = thumbnail.querySelector("img")?.getAttribute("alt");

            if (!mainImage || !nextSource) {
                return;
            }

            mainImage.src = nextSource;
            if (nextAlt) {
                mainImage.alt = nextAlt;
            }

            gallery.querySelectorAll("[data-gallery-thumb]").forEach((item) => {
                item.classList.toggle("is-active", item === thumbnail);
                item.setAttribute("aria-pressed", String(item === thumbnail));
            });
        });
    });

    document.querySelectorAll("[data-quantity-control]").forEach((control) => {
        const input = control.querySelector("[data-quantity-input]");
        const decreaseButton = control.querySelector("[data-quantity-decrease]");
        const increaseButton = control.querySelector("[data-quantity-increase]");

        if (!input || !decreaseButton || !increaseButton) {
            return;
        }

        if (input.disabled) {
            decreaseButton.disabled = true;
            increaseButton.disabled = true;
            return;
        }

        const minimum = Math.max(1, Number.parseInt(input.min || "1", 10));
        const parsedMaximum = Number.parseInt(input.max || String(minimum), 10);
        const maximum = Number.isFinite(parsedMaximum)
            ? Math.max(minimum, parsedMaximum)
            : minimum;

        const updateQuantity = (nextValue) => {
            const parsedValue = Number.parseInt(String(nextValue), 10);
            const quantity = Number.isFinite(parsedValue)
                ? Math.min(maximum, Math.max(minimum, parsedValue))
                : minimum;

            input.value = String(quantity);
            decreaseButton.disabled = quantity <= minimum;
            increaseButton.disabled = quantity >= maximum;
        };

        decreaseButton.addEventListener("click", () => {
            updateQuantity(Number.parseInt(input.value, 10) - 1);
        });

        increaseButton.addEventListener("click", () => {
            updateQuantity(Number.parseInt(input.value, 10) + 1);
        });

        input.addEventListener("change", () => updateQuantity(input.value));
        input.addEventListener("blur", () => updateQuantity(input.value));
        updateQuantity(input.value);
    });
})();
