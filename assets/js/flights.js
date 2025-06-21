document.getElementById('flights_form').onsubmit = function(){
    if (document.getElementById('passengers_counts').value == "0"){
        document.getElementById('passengers_counts').classList.add('empty');
        notification("Выберите количество пассажиров", "warning");
        return false;
    }
}
document.getElementById('passengers_counts').onchange = function(){
    if (document.getElementById('passengers_counts').value != "0"){
        document.getElementById('passengers_counts').classList.remove('empty');
    }
}