const new_password = document.getElementById('new_password');
const rep_password = document.getElementById('rep_password');
const newPasswordError = document.getElementById('error-new-password');
const repPasswordError = document.getElementById('error-rep-password');

const passwordCheck = /^[a-zA-Z0-9_\-\$]{5,20}$/;


document.getElementById('form').onsubmit = function () {
  let valid = true;
  if (!passwordCheck.test(new_password.value)) {
    newPasswordError.innerHTML = "Пароль не соответсвует формату";
    report("Формат пароля: от 5 до 20 символов, буквы, цифры, $, _, -.", 6000);
    valid = false;
  }
  if (new_password.value != rep_password.value) {
    repPasswordError.innerHTML = "Пароли не совпадают";
    valid = false;
  }
  if (!valid) {
    notification("Неверно заполнены поля!", 'error');
    return false;
  }
};