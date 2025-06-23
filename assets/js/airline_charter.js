document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("charter_decision_form");
    const commentField = document.getElementById("comment");

    form.addEventListener("submit", function (e) {
        const comment = commentField.value.trim();

        if (comment.length > 500) {
            e.preventDefault();
            notification("Комментарий не должен превышать 500 символов", "error");
            return false;
        }
    });
});