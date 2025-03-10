MicroModal.init();

document.querySelectorAll('.open').forEach((modal)=>{
    MicroModal.show(modal.getAttribute('id'));
});
// support
document.addEventListener("DOMContentLoaded", function () {
    const supportBtn = document.querySelector(".support-btn");
    const ticketForm = document.querySelector(".support-create-tickets");
    const closeBtn = document.querySelector(".close-create-ticket");

    supportBtn.addEventListener("click", function () {
        ticketForm.classList.toggle("active");
    });

    closeBtn.addEventListener("click", function () {
        ticketForm.classList.remove("active");
    });

    // Закрытие при клике вне формы
    document.addEventListener("click", function (event) {
        if (!ticketForm.contains(event.target) && !supportBtn.contains(event.target)) {
            ticketForm.classList.remove("active");
        }
    });
});