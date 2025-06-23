document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('crew_form');

  if (!form) return;

  form.addEventListener('submit', (e) => {
    const workerSelect = form.querySelector('select[name="worker_id"]');
    const flightRoleInput = form.querySelector('input[name="flight_role"]');

    if (!workerSelect.value) {
      e.preventDefault();
      notification('Пожалуйста, выберите сотрудника.', 'error');
      return false;
    }

    if (flightRoleInput.value.length > 255) {
      e.preventDefault();
      notification('Описание роли не должно превышать 255 символов.', 'error');
      return false;
    }

    return true;
  });
});
