document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("plane_form_add");
  const registrationInput = document.getElementById("registration");

  const pattern = /^[A-Za-z0-9-]+$/;

  // При потере фокуса убираем ошибку, если поле валидно
  registrationInput.addEventListener("blur", () => {
    if (pattern.test(registrationInput.value.trim())) {
      registrationInput.classList.remove("error");
    }
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const value = registrationInput.value.trim();

    if (!value) {
      registrationInput.classList.add("error");
      notification("Поле регистрации не может быть пустым", "error");
      report("Ошибка в форме");
      return false;
    }

    if (!pattern.test(value)) {
      registrationInput.classList.add("error");
      notification("Регистрационный номер может содержать только латинские буквы, цифры и тире", "error");
      report("Ошибка в форме");
      return false;
    }

    // Все ок — отправляем форму
    form.submit();
  });
});
