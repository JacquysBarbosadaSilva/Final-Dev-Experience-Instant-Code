function showSuccessAlert(title, message, timer = 3000) {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'success',
        timer: timer,
        timerProgressBar: true,
        showConfirmButton: false,
        background: 'var(--cor-fundo-card)',
        color: 'var(--cor-texto)',
        customClass: {
            popup: 'animate-fadeIn'
        }
    });
}

function showErrorAlert(title, message) {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'error',
        confirmButtonText: 'Entendi',
        background: 'var(--cor-fundo-card)',
        color: 'var(--cor-texto)',
        customClass: {
            popup: 'animate-fadeIn'
        }
    });
}

function showWarningAlert(title, message, timer = 4000) {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        timer: timer,
        timerProgressBar: true,
        showConfirmButton: false,
        background: 'var(--cor-fundo-card)',
        color: 'var(--cor-texto)',
        customClass: {
            popup: 'animate-fadeIn'
        }
    });
}

function showInfoAlert(title, message, timer = 3500) {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'info',
        timer: timer,
        timerProgressBar: true,
        showConfirmButton: false,
        background: 'var(--cor-fundo-card)',
        color: 'var(--cor-texto)',
        customClass: {
            popup: 'animate-fadeIn'
        }
    });
}

function showConfirmAlert(title, message, confirmText, cancelText) {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        background: 'var(--cor-fundo-card)',
        color: 'var(--cor-texto)',
        confirmButtonColor: 'var(--cor-primaria)',
        cancelButtonColor: 'var(--cor-borda)',
        customClass: {
            popup: 'animate-fadeIn'
        }
    });
}