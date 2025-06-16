const countrySelect = document.getElementById('countrySelect');

fetch('https://restcountries.com/v3.1/all?fields=name,cca2')
  .then(res => res.json())
  .then(countries => {
    // Сортируем по имени
    countries.sort((a, b) => a.name.common.localeCompare(b.name.common));

    countrySelect.innerHTML = '<option selected value="RU">Россия</option>';

    countries.forEach(country => {
      const option = document.createElement('option');
      option.value = country.cca2; // код страны (например, RU, US)
      option.textContent = country.name.common; // имя страны
      countrySelect.appendChild(option);
    });
  })
  .catch(err => {
    console.error('Ошибка загрузки стран:', err);
    countrySelect.innerHTML = '<option selected value="RU">Россия</option><option disabled>Ошибка загрузки</option>';
  });


  const create_form = document.getElementById('create_form');

  const nameInp = document.getElementById('name');
  const icao = document.getElementById('icao');
  const iata = document.getElementById('iata');

  

  create_form.onsubmit = function(){
    if (!(nameInp.value.length > 0 && nameInp.value.length < 20)){
        report('Название авиакомпании не соответствует формату. <br> Должно быть менее 20 символов');
        return false;
    }
    if (!(icao.value.length == 4 || icao.value.length == 3)){
        report('ИКАО код авиакомпании не соответствует формату. <br> Формат: XXXX');
        return false;
    }
    if (!(iata.value.length == 3 || iata.value.length == 2)){
        report('ИАТА код авиакомпании не соответствует формату. <br> Формат: XXX');
        return false;
    }
    if (!(countrySelect.value != 'none')){
        report('Выберите страну');
        return false;
    }
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