document.addEventListener('DOMContentLoaded', () => {
    
    const messageContainer = document.getElementById('message-container');
    const loginForm = document.getElementById('login-form');

    const createAlert = (message, type) => {
        const existingAlert = messageContainer.querySelector('.auth-message');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alertDiv = document.createElement('div');
        alertDiv.className = `auth-message ${type}`;
        alertDiv.setAttribute('role', 'alert');
        
        const messageSpan = document.createElement('span');
        messageSpan.textContent = message;
        
        const closeButton = document.createElement('button');
        closeButton.className = 'close-btn';
        closeButton.type = 'button';
        closeButton.innerHTML = '&times;';

        alertDiv.appendChild(messageSpan);
        alertDiv.appendChild(closeButton);
        messageContainer.prepend(alertDiv);
    };

    messageContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('close-btn')) {
            e.target.parentElement.remove();
        }
    });

    const validateEmail = (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    };

    const markInputError = (input, isError) => {
        if (isError) {
            input.classList.add('input-error');
        } else {
            input.classList.remove('input-error');
        }
    };

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            const email = document.getElementById('email');
            const senha = document.getElementById('senha');
            let isValid = true;
            
            markInputError(email, false);
            markInputError(senha, false);

            if (email.value.trim() === '' || senha.value.trim() === '') {
                isValid = false;
                createAlert('Por favor, preencha o e-mail e a senha.', 'error');
                if (email.value.trim() === '') markInputError(email, true);
                if (senha.value.trim() === '') markInputError(senha, true);
            } else if (!validateEmail(email.value)) {
                isValid = false;
                createAlert('Formato de e-mail inválido.', 'error');
                markInputError(email, true);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});