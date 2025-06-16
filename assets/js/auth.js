// Элементы формы
const slidePage = document.querySelector(".slide-page");
const nextBtnFirst = document.querySelector(".firstNext");
const prevBtnSec = document.querySelector(".prev-1");
const nextBtnSec = document.querySelector(".next-1");
const prevBtnThird = document.querySelector(".prev-2");
const nextBtnThird = document.querySelector(".next-2");
const prevBtnFourth = document.querySelector(".prev-3");
const submitBtn = document.querySelector(".submit");

const progressText = document.querySelectorAll(".step p");
const progressCheck = document.querySelectorAll(".step .check");
const bullet = document.querySelectorAll(".step .bullet");

const passportInput = document.getElementById('passport');
const birthdateInput = document.getElementById('birthdate');
const gender = document.getElementById('gender');
const form_reg = document.getElementById('form-reg');
const email_reg = document.getElementById('email-reg');
const password_reg = document.getElementById('password-reg');
const cpassword = document.getElementById('cpassword');
const nameInput = document.getElementById('name');
const surname = document.getElementById('surname');
const patronymic = document.getElementById('patronymic');

// Регулярные выражения
const emailCheck = /^[a-z0-9_.-]+@([a-z0-9-]+\.)+[a-z]{2,6}$/i;
const passwordCheck = /^[a-zA-Z0-9_\-\$]{5,20}$/;
const name_surname_patronymic_check = /^([а-яА-ЯЁёa-zA-Z\-]+)$/u;
const passport_check = /^\d{4}\s?\d{6}$/;
const datePattern = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/;

let current = 1;

// Навигация между страницами формы
function activateStep() {
  bullet[current - 1].classList.add("active");
  progressCheck[current - 1].classList.add("active");
  progressText[current - 1].classList.add("active");
}

function deactivateStep() {
  bullet[current - 2].classList.remove("active");
  progressCheck[current - 2].classList.remove("active");
  progressText[current - 2].classList.remove("active");
}

nextBtnFirst.addEventListener("click", (e) => {
  e.preventDefault();
  slidePage.style.marginLeft = "-25%";
  activateStep();
  current++;
  document.getElementById('name-surname').innerHTML = `${nameInput.value} ${patronymic.value},`;
});

nextBtnSec.addEventListener("click", (e) => {
  e.preventDefault();
  slidePage.style.marginLeft = "-50%";
  activateStep();
  current++;
});

nextBtnThird.addEventListener("click", (e) => {
  e.preventDefault();
  slidePage.style.marginLeft = "-75%";
  activateStep();
  current++;
});

submitBtn.addEventListener("click", () => {
  activateStep();
  current++;
});

prevBtnSec.addEventListener("click", (e) => {
  e.preventDefault();
  slidePage.style.marginLeft = "0%";
  deactivateStep();
  current--;
});

prevBtnThird.addEventListener("click", (e) => {
  e.preventDefault();
  slidePage.style.marginLeft = "-25%";
  deactivateStep();
  current--;
});

prevBtnFourth.addEventListener("click", (e) => {
  e.preventDefault();
  slidePage.style.marginLeft = "-50%";
  deactivateStep();
  current--;
});

// Модальные окна авторизации и регистрации
const toggleAuthModal = (id) => {
  document.getElementById('layer_bg').classList.toggle('active');
  document.getElementById(id).classList.toggle('active');
  document.getElementById('close-area').classList.add('active');
};

document.getElementById('auth-button').onclick = () => toggleAuthModal('auth');
document.getElementById('close-auth').onclick = () => toggleAuthModal('auth');
document.getElementById('reg-button').onclick = () => toggleAuthModal('join');
document.getElementById('close-reg').onclick = () => toggleAuthModal('join');

document.getElementById('close-area').onclick = () => {
  document.getElementById('layer_bg').classList.remove('active');
  document.getElementById('auth').classList.remove('active');
  document.getElementById('join').classList.remove('active');
  document.getElementById('close-area').classList.remove('active');
};

// Проверка ФИО
function checkNameBlockReg() {
  if (name_surname_patronymic_check.test(nameInput.value) && nameInput.value.length > 1 && nameInput.value.length <= 30 &&
      name_surname_patronymic_check.test(surname.value) && surname.value.length > 0 && surname.value.length <= 30 &&
      (name_surname_patronymic_check.test(patronymic.value) && patronymic.value.length > 2 && patronymic.value.length <= 30 || patronymic.value === "")) {
    nextBtnFirst.disabled = false;
  } else {
    nextBtnFirst.disabled = true;
  }
}

[nameInput, surname, patronymic].forEach(field => {
  field.oninput = checkNameBlockReg;
  field.onfocus = () => field.classList.remove("errorInput");
});

nameInput.onblur = () => {
  if (!name_surname_patronymic_check.test(nameInput.value) || nameInput.value.length <= 1 || nameInput.value.length > 30) {
    nameInput.classList.add("errorInput");
    notification('Имя должно быть больше 1 символа и менее 30. Запрещается использовать спецсимволы, кроме "-".', 'warning', 3000);
  }
};

surname.onblur = () => {
  if (!name_surname_patronymic_check.test(surname.value) || surname.value.length === 0 || surname.value.length > 30) {
    surname.classList.add("errorInput");
    notification('Фамилия должна быть более 1 символа и менее 30. Запрещается использовать спецсимволы, кроме "-".', 'warning', 3000);
  }
};

patronymic.onblur = () => {
  if (!(name_surname_patronymic_check.test(patronymic.value) && patronymic.value.length > 2 && patronymic.value.length <= 30 || patronymic.value === "")) {
    patronymic.classList.add("errorInput");
    notification('Отчество должно быть больше 2 символов и менее 30. Запрещается использовать спецсимволы, кроме "-".', 'warning', 3000);
  }
};

// Проверка паспорта
passportInput.addEventListener('input', (e) => {
  let value = e.target.value.replace(/\D/g, '');
  if (value.length > 10) value = value.slice(0, 10);
  if (value.length > 4) value = value.slice(0, 4) + ' ' + value.slice(4);
  e.target.value = value;
  checkPasportBlockReg();
});

passportInput.onfocus = () => passportInput.classList.remove("errorInput");

passportInput.onblur = () => {
  if (!passport_check.test(passportInput.value) || passportInput.value.length !== 11) {
    passportInput.classList.add("errorInput");
    notification('Номер паспорта должен быть в формате "XXXX XXXXXX".', 'warning', 3000);
  }
};

// Проверка даты рождения
function isLeapYear(year) {
  return (year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0));
}

function isValidDate(day, month, year) {
  const daysInMonth = {
    '01': 31, '02': isLeapYear(year) ? 29 : 28, '03': 31, '04': 30, '05': 31, '06': 30,
    '07': 31, '08': 31, '09': 30, '10': 31, '11': 30, '12': 31
  };
  const monthString = month < 10 ? `0${month}` : `${month}`;
  return day >= 1 && day <= daysInMonth[monthString] && year >= 1924 && year <= new Date().getFullYear() - 1;
}

birthdateInput.onfocus = () => birthdateInput.classList.remove("errorInput");

birthdateInput.onblur = () => {
  const value = birthdateInput.value.trim();  
  const [year, month, day] = value.split('-').map(Number);

  if (!(datePattern.test(value) && isValidDate(day, month, year))) {
    birthdateInput.classList.add("errorInput");
    notification('Некорректная дата. Проверьте день и месяц!', 'warning', 3000);
  }
};


birthdateInput.addEventListener('input', (e) => {
  checkPasportBlockReg();
});

// Проверка пола
gender.onfocus = () => gender.classList.remove("errorInput");
gender.onblur = () => {
  if (gender.value === '-') gender.classList.add("errorInput");
};
gender.onchange = checkPasportBlockReg;

// Блокировка кнопок при неправильных данных
function checkPasportBlockReg() {
  const [year, month, day] = birthdateInput.value.split('-').map(Number);
  nextBtnThird.disabled = !(passport_check.test(passportInput.value) &&
    datePattern.test(birthdateInput.value) && isValidDate(day, month, year) &&
    gender.value !== '-');
}

function checkContactBlockReg() {
  nextBtnSec.disabled = !(emailCheck.test(email_reg.value) &&
    passwordCheck.test(password_reg.value) &&
    password_reg.value === cpassword.value);
}

[email_reg, password_reg, cpassword].forEach(input => {
  input.oninput = checkContactBlockReg;
  input.onfocus = () => input.classList.remove("errorInput");
});

email_reg.onblur = () => {
  if (!emailCheck.test(email_reg.value)) {
    email_reg.classList.add("errorInput");
  }
};

password_reg.onblur = () => {
  if (!passwordCheck.test(password_reg.value)) {
    password_reg.classList.add("errorInput");
    report("Формат пароля: от 5 до 20 символов, буквы, цифры, $, _, -.", 6000);
  }
  if (password_reg.value !== cpassword.value) {
    cpassword.classList.add("errorInput");
  }
};

cpassword.onblur = () => {
  if (password_reg.value !== cpassword.value) {
    cpassword.classList.add("errorInput");
  }
};

// Блокировка перехода TAB/ENTER при заполнении формы регистрации
form_reg.addEventListener('keydown', (e) => {
  if (['Tab', 'Enter'].includes(e.key)) {
    e.preventDefault();
  }
});

// Авторизация
const email = document.getElementById('email');
const password = document.getElementById('password');
const emailError = document.getElementById('error-email');
const passwordError = document.getElementById('error-password');

email.oninput = () => {
  if (emailCheck.test(email.value)) {
    emailError.innerHTML = "";
  }
};

password.oninput = () => {
  if (passwordCheck.test(password.value)) {
    passwordError.innerHTML = "";
  }
};

document.getElementById('form').onsubmit = function () {
  let valid = true;
  if (!emailCheck.test(email.value)) {
    emailError.innerHTML = "Неверный email";
    valid = false;
  }
  if (!passwordCheck.test(password.value)) {
    passwordError.innerHTML = "Пароль не соответствует формату";
    valid = false;
  }
  if (!valid) {
    notification("Неверно заполнены поля!", 'error');
    return false;
  }
};