document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".check_in_seats_form");

    if (!form) return;

    const selects = form.querySelectorAll("select");

    // Проверка при потере фокуса — убрать ошибку
    selects.forEach(select => {
        select.addEventListener("blur", () => {
            if (select.value !== "0") {
                select.classList.remove("error");
            }
        });
    });

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        let isValid = true;
        const chosenSeats = new Set();

        selects.forEach((select, index) => {
            const value = select.value;

            // Проверка, что выбрано место
            if (value === "0") {
                select.classList.add("error");
                notification(`Выберите место для пассажира №${index + 1}`, "error");
                isValid = false;
                return;
            }

            const seatId = value.split("-")[1];

            if (chosenSeats.has(seatId)) {
                select.classList.add("error");
                notification(`Место ${select.options[select.selectedIndex].text} уже выбрано другим пассажиром`, "error");
                isValid = false;
                return;
            }

            chosenSeats.add(seatId);
        });

        if (!isValid) {
            report("Пожалуйста, исправьте ошибки перед отправкой формы");
            return false;
        }
        form.submit();
    });
});