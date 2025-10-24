document.addEventListener('DOMContentLoaded', () => {
    const cadastroForm = document.getElementById('cadastro-form');
    if (!cadastroForm) return;

    const nome = document.getElementById('nome');
    const email = document.getElementById('email');
    const senha = document.getElementById('senha');
    const submitBtn = cadastroForm.querySelector('.auth-button');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');

    // Elementos de erro
    const nomeError = document.getElementById('nome-error');
    const emailError = document.getElementById('email-error');
    const senhaError = document.getElementById('senha-error');

    const validateEmail = (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    };

    const showError = (element, errorElement, message) => {
        element.classList.add('input-error');
        errorElement.textContent = message;
        errorElement.classList.add('show');
    };

    const hideError = (element, errorElement) => {
        element.classList.remove('input-error');
        errorElement.classList.remove('show');
    };

    const validateField = (element, errorElement, validationFn, errorMessage) => {
        if (!validationFn(element.value)) {
            showError(element, errorElement, errorMessage);
            return false;
        } else {
            hideError(element, errorElement);
            return true;
        }
    };

    // Validação em tempo real
    nome.addEventListener('input', () => {
        if (nome.value.trim() !== '') {
            hideError(nome, nomeError);
        }
    });

    email.addEventListener('input', () => {
        if (email.value.trim() !== '' && validateEmail(email.value)) {
            hideError(email, emailError);
        }
    });

    senha.addEventListener('input', () => {
        if (senha.value.trim() !== '' && senha.value.length >= 8) {
            hideError(senha, senhaError);
        }
    });

    // Validação ao sair do campo (blur)
    nome.addEventListener('blur', () => {
        if (nome.value.trim() === '') {
            showError(nome, nomeError, 'Por favor, preencha seu nome completo');
        }
    });

    email.addEventListener('blur', () => {
        if (email.value.trim() === '') {
            showError(email, emailError, 'Por favor, preencha seu e-mail');
        } else if (!validateEmail(email.value)) {
            showError(email, emailError, 'Por favor, insira um e-mail válido');
        }
    });

    senha.addEventListener('blur', () => {
        if (senha.value.trim() === '') {
            showError(senha, senhaError, 'Por favor, crie uma senha');
        } else if (senha.value.length < 8) {
            showError(senha, senhaError, 'A senha deve ter no mínimo 8 caracteres');
        }
    });

    // Validação no submit
    cadastroForm.addEventListener('submit', (e) => {
        let isValid = true;

        // Reset errors
        hideError(nome, nomeError);
        hideError(email, emailError);
        hideError(senha, senhaError);

        // Validação do nome
        if (nome.value.trim() === '') {
            showError(nome, nomeError, 'Por favor, preencha seu nome completo');
            isValid = false;
        }

        // Validação do email
        if (email.value.trim() === '') {
            showError(email, emailError, 'Por favor, preencha seu e-mail');
            isValid = false;
        } else if (!validateEmail(email.value)) {
            showError(email, emailError, 'Por favor, insira um e-mail válido');
            isValid = false;
        }

        // Validação da senha
        if (senha.value.trim() === '') {
            showError(senha, senhaError, 'Por favor, crie uma senha');
            isValid = false;
        } else if (senha.value.length < 8) {
            showError(senha, senhaError, 'A senha deve ter no mínimo 8 caracteres');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            
            // Scroll para o primeiro erro
            const firstError = cadastroForm.querySelector('.input-error');
            if (firstError) {
                firstError.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                firstError.focus();
            }
            return;
        }

        // Mostrar loading
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline-block';
        submitBtn.disabled = true;
    });
});