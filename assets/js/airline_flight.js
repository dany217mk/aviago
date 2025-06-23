document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form.form_add");
    const depTimeInput = form.querySelector("input[name='dep_time']");
    const arrTimeInput = form.querySelector("input[name='arr_time']");
    const seatInput = form.querySelector("input[name='charter_seats_number']");
    const airplaneSelect = form.querySelector("select[name='airplane_airline_id']");

    form.addEventListener("submit", function (e) {
        const depTime = new Date(depTimeInput.value);
        const arrTime = new Date(arrTimeInput.value);

        if (isNaN(depTime.getTime()) || isNaN(arrTime.getTime())) {
            notification("Проверьте дату и время вылета и прилета", "error");
            e.preventDefault();
            return false;
        }

        if (arrTime <= depTime) {
            notification("Время прилета должно быть позже времени вылета", "error");
            e.preventDefault();
            return false;
        }

        const selectedOption = airplaneSelect.options[airplaneSelect.selectedIndex];
        const maxCapacity = parseInt(selectedOption.dataset.capacity || "0");
        const requestedSeats = parseInt(seatInput.value);

        if (isNaN(requestedSeats) || requestedSeats < 0) {
            notification("Введите корректное количество мест", "error");
            e.preventDefault();
            return false;
        }

        if (requestedSeats > maxCapacity) {
            notification(`Максимальное количество мест для выбранного самолета: ${maxCapacity}`, "error");
            e.preventDefault();
            return false;
        }
    });
});
