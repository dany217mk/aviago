document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".book_flight_form");

    const namePattern = /^([а-яА-ЯЁёa-zA-Z\-]{1,30})$/u;
    const passportPattern = /^\d{4}\s?\d{6}$/;
    const emailCheck = /^[a-z0-9_.-]+@([a-z0-9-]+\.)+[a-z]{2,6}$/i;

    const passportInputs = document.querySelectorAll(".book_passport");

    passportInputs.forEach(input => {
        input.addEventListener("input", (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.slice(0, 10);
            if (value.length > 4) value = value.slice(0, 4) + ' ' + value.slice(4);
            e.target.value = value;
        });
    });

    const allInputs = form.querySelectorAll("input[type='text'], input[type='email']");
    allInputs.forEach(input => {
        input.addEventListener("blur", () => {
            if (validateInput(input)) {
                input.classList.remove("error");
            }
        });
    });

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        let isValid = true;

        const names = form.querySelectorAll(".book_name");
        const surnames = form.querySelectorAll(".book_surname");
        const patronymics = form.querySelectorAll(".book_patronymic");
        const passports = form.querySelectorAll(".book_passport");
        const email = form.querySelector(".book_email");

        for (let i = 0; i < names.length; i++) {
            const name = names[i];
            const surname = surnames[i];
            const patronymic = patronymics[i];
            const passport = passports[i];

            if (!namePattern.test(name.value.trim())) {
                name.classList.add("error");
                notification(`Имя пассажира №${i + 1} введено некорректно`, "error");
                isValid = false;
                break;
            }

            if (!namePattern.test(surname.value.trim())) {
                surname.classList.add("error");
                notification(`Фамилия пассажира №${i + 1} введена некорректно`, "error");
                isValid = false;
                break;
            }

            if (patronymic.value.trim() !== "" && !namePattern.test(patronymic.value.trim())) {
                patronymic.classList.add("error");
                notification(`Отчество пассажира №${i + 1} введено некорректно`, "error");
                isValid = false;
                break;
            }

            if (!passportPattern.test(passport.value.trim())) {
                passport.classList.add("error");
                notification(`Паспорт пассажира №${i + 1} введён некорректно`, "error");
                isValid = false;
                break;
            }
        }

        // Проверка email
        if (isValid && email && !emailCheck.test(email.value.trim())) {
            email.classList.add("error");
            notification("Email введён некорректно", "error");
            isValid = false;
        }

        if (!isValid) {
            report("Пожалуйста, исправьте ошибки перед отправкой формы");
            return false;
        }

        form.submit();
    });

    function validateInput(input) {
        const value = input.value.trim();

        if (input.classList.contains("book_name") || input.classList.contains("book_surname")) {
            return namePattern.test(value);
        }

        if (input.classList.contains("book_patronymic")) {
            return value === "" || namePattern.test(value);
        }

        if (input.classList.contains("book_passport")) {
            return passportPattern.test(value);
        }

        if (input.classList.contains("book_email")) {
            return emailCheck.test(value);
        }

        return true;
    }
});
