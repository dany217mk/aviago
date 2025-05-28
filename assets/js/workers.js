const worker_form = document.getElementById('worker_form_add');


const nameInp = document.getElementById('name');
const surname = document.getElementById('surname');
const patronymic = document.getElementById('patronymic');
const hired_at = document.getElementById('hired_at');
const role = document.getElementById('role');
const email = document.getElementById('email');
const password = document.getElementById('password');

const emailCheck = /^[a-z0-9_.-]+@([a-z0-9-]+\.)+[a-z]{2,6}$/i;
const passwordCheck = /^[a-zA-Z0-9_\-\$]{5,20}$/;
const name_surname_patronymic_check = /^([а-яА-ЯЁёa-zA-Z\-]+)$/u;
const datePattern = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/;

worker_form.onsubmit = function(){
    if (!name_surname_patronymic_check.test(nameInp.value) || nameInp.value.length <= 1 || nameInp.value.length > 30){
        report('Имя должно быть больше 1 символа и менее 30. Запрещается использовать спецсимволы, кроме "-".');
        return false;
    }
    if (!name_surname_patronymic_check.test(surname.value) || surname.value.length <= 1 || surname.value.length > 30){
        report('Фамилия должна быть более 1 символа и менее 30. Запрещается использовать спецсимволы, кроме "-".');
        return false;
    }
    if (!(name_surname_patronymic_check.test(patronymic.value) && patronymic.value.length > 2 && patronymic.value.length <= 30 || patronymic.value === "")){
        report('Отчество должно быть больше 2 символов и менее 30. Запрещается использовать спецсимволы, кроме "-".');
        return false;
    }
    const hired_at_value = hired_at.value.trim();
    const [year, month, day] = hired_at_value.split('-').map(Number);
    
    if (!(datePattern.test(hired_at_value) && isValidDate(day, month, year))) {
        report('Некорректная дата. Проверьте день и месяц!');
        return false;
    }
    if (!passwordCheck.test(password.value)) {
        report("Формат пароля: от 5 до 20 символов, буквы, цифры, $, _, -.");
        return false;
    }
    if (!emailCheck.test(email.value)) {
        report("Неверный формат email");
        return false;
    }
}

function isLeapYear(year) {
  return (year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0));
}

function isValidDate(day, month, year) {
  const daysInMonth = {
    '01': 31, '02': isLeapYear(year) ? 29 : 28, '03': 31, '04': 30, '05': 31, '06': 30,
    '07': 31, '08': 31, '09': 30, '10': 31, '11': 30, '12': 31
  };
  const monthString = month < 10 ? `0${month}` : `${month}`;
  return day >= 1 && day <= daysInMonth[monthString] && year >= 1924 && year <= new Date().getFullYear();
}




  function report(str, time=4000){
    let blockMas = document.getElementsByClassName('report');
    if (blockMas.length > 0){
      return;
    }
    let div = document.createElement('div');
    div.classList.add('report');
    div.id = "report";
    let title = document.createElement("span");
    title.innerHTML = str;
    div.append(title);
    document.body.append(div);
    div.classList.add('active');
    setTimeout(function() {
      div.classList.add('darker');
    }, time);
    setTimeout(function() {
      div.className = '';
    div.remove();
    }, time+200);
}