const inputDeparture = document.getElementById('select_from');
const inputArrival = document.getElementById('select_to');
const flightDate = document.getElementById('flight-date');
const passengersCount = document.getElementById('passengers-count');


const errorDeparture = document.getElementById('errorDeparture');
const errorArrival = document.getElementById('errorArrival');
const errorFlightDate = document.getElementById('errorFlightDate');
const errorPassengersCount = document.getElementById('errorPassengersCount');






const datePattern = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/;




function isLeapYear(year) {
  return (year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0));
}

function isValidDate(day, month, year) {
  const daysInMonth = {
    '01': 31, '02': isLeapYear(year) ? 29 : 28, '03': 31, '04': 30, '05': 31, '06': 30,
    '07': 31, '08': 31, '09': 30, '10': 31, '11': 30, '12': 31
  };
  const monthString = month < 10 ? `0${month}` : `${month}`;
  return day >= 1 && day <= daysInMonth[monthString];
}

function isFutureDate(day, month, year) {
    const inputDate = new Date(year, month - 1, day);
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Обнуляем время для точного сравнения дат
    
    return inputDate > today;
}


document.getElementById("charter_form").onsubmit = function(){
    let checkInputs = true;
    if (inputDeparture.value == 0){
        errorDeparture.classList.add('active');
        checkInputs = false;
    } 
    if (inputArrival.value == 0){
        errorArrival.classList.add('active');
        checkInputs = false;
    } 
    if (passengersCount.value <= 0 || passengersCount.value > 853){
        errorPassengersCount.classList.add('active');
        checkInputs = false;
    } 

    const valueFlightDate = flightDate.value.trim();  
    const [year, month, day] = valueFlightDate.split('-').map(Number);

    if (!(datePattern.test(valueFlightDate) && isValidDate(day, month, year) && isFutureDate(day, month, year))) {
        errorFlightDate.classList.add("active");
        checkInputs = false;
    }
    
    if (!reg){
        const inputFio = document.getElementById('contact-fio');
        const advancedNamePattern = /^[А-Яа-яЁё]+(?:-[А-Яа-яЁё]+)?(?: [А-Яа-яЁё]+){1,2}$/;
        const errorContactFio = document.getElementById('errorContactFio');

        const inputEmail = document.getElementById('email');
        const emailCheck = /^[a-z0-9_.-]+@([a-z0-9-]+\.)+[a-z]{2,6}$/i;
        const errorEmail = document.getElementById('errorEmail');

        const inputOrg = document.getElementById('org');
        const errorOrg = document.getElementById('errorOrg');

        const inputAddInfo = document.getElementById('additional-info');
        const errorAddInfo = document.getElementById('errorAddInfo');

       

        
        if (!advancedNamePattern.test(inputFio.value)){
            errorContactFio.classList.add('active');
            checkInputs = false;    
         }   
         
         if (!emailCheck.test(inputEmail.value)){
            errorEmail.classList.add('active');
            checkInputs = false;    
         } 
         
         if (!(inputOrg.value.length < 20)){
            errorOrg.classList.add('active');
            checkInputs = false; 
         }

         if (!(inputAddInfo.value.length <= 500)){
            errorAddInfo.classList.add('active');
            checkInputs = false; 
         }
    }
    
    if (!checkInputs){
        notification("Неверно заполнены поля!", 'warning');
        return false;
    }
}


inputDeparture.onchange = function(){
    if (inputDeparture.value != 0){
        errorDeparture.classList.remove('active');
    } 
}


inputArrival.onchange = function(){
    if (inputArrival.value != 0){
        errorArrival.classList.remove('active');
    } 
}

passengersCount.oninput = function(){
    if (passengersCount.value > 0 && passengersCount.value <= 853){
        errorPassengersCount.classList.remove('active');
    }
}

flightDate.onchange = function(){
    const valueFlightDate = flightDate.value.trim();  
    const [year, month, day] = valueFlightDate.split('-').map(Number);
    if ((datePattern.test(valueFlightDate) && isValidDate(day, month, year) && isFutureDate(day, month, year))) {
        errorFlightDate.classList.remove("active");
    }
}

document.getElementById("agree").onchange = function(){
    if (this.checked){
        document.getElementById('submit-btn-charter').disabled = false;
    }else{
        document.getElementById('submit-btn-charter').disabled = true;
    }
}


if (!reg){
    const inputFio = document.getElementById('contact-fio');
    const advancedNamePattern = /^[А-Яа-яЁё]+(?:-[А-Яа-яЁё]+)?(?: [А-Яа-яЁё]+){1,2}$/;
    const errorContactFio = document.getElementById('errorContactFio');

    const inputEmail = document.getElementById('email');
    const emailCheck = /^[a-z0-9_.-]+@([a-z0-9-]+\.)+[a-z]{2,6}$/i;
    const errorEmail = document.getElementById('errorEmail');

    const inputOrg = document.getElementById('org');
    const errorOrg = document.getElementById('errorOrg');

    const inputAddInfo = document.getElementById('additional-info');
    const errorAddInfo = document.getElementById('errorAddInfo');

    inputFio.oninput = function(){
        if (advancedNamePattern.test(inputFio.value)){
                errorContactFio.classList.remove('active');  
        }   
    }

    inputEmail.oninput = function(){
        if (emailCheck.test(inputEmail.value)){
            errorEmail.classList.remove('active');   
         } 
    }

    inputOrg.oninput = function(){
        if ((inputOrg.value.length < 20)){
            errorOrg.classList.remove('active');
        }   
    }

    inputAddInfo.oninput = function(){
        if ((inputAddInfo.value.length <= 500)){
            errorAddInfo.classList.remove('active');
        }   
    }
}