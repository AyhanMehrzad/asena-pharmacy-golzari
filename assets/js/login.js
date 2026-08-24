function toggleMode(mode) {
    const btnLogin = document.getElementById('btn-login');
    const btnSignup = document.getElementById('btn-signup');
    const signupFields = document.getElementById('signup-fields');
    const loginExtras = document.getElementById('login-extras');
    const formTitle = document.getElementById('form-title');
    const formSubtitle = document.getElementById('form-subtitle');
    const submitText = document.getElementById('submit-text');
    const container = document.getElementById('auth-container');
    const formMode = document.getElementById('form-mode');
    
    formMode.value = mode;

    // Apply quick animation
    container.style.opacity = '0';
    container.style.transform = 'translateY(10px)';

    setTimeout(() => {
        if (mode === 'signup') {
            btnLogin.classList.remove('bg-white', 'shadow-sm', 'text-primary');
            btnLogin.classList.add('text-on-surface-variant');
            btnSignup.classList.add('bg-white', 'shadow-sm', 'text-primary');
            btnSignup.classList.remove('text-on-surface-variant');
            
            signupFields.classList.remove('hidden');
            loginExtras.classList.add('hidden');
            
            formTitle.innerText = 'عضویت در خانواده ما';
            formSubtitle.innerText = 'برای استفاده از تمامی امکانات، حساب کاربری خود را بسازید.';
            submitText.innerText = 'ایجاد حساب کاربری';
        } else {
            btnSignup.classList.remove('bg-white', 'shadow-sm', 'text-primary');
            btnSignup.classList.add('text-on-surface-variant');
            btnLogin.classList.add('bg-white', 'shadow-sm', 'text-primary');
            btnLogin.classList.remove('text-on-surface-variant');
            
            signupFields.classList.add('hidden');
            loginExtras.classList.remove('hidden');
            
            formTitle.innerText = 'خوش آمدید';
            formSubtitle.innerText = 'لطفاً برای ورود به پنل کاربری اطلاعات خود را وارد کنید.';
            submitText.innerText = 'ورود به حساب';
        }
        
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
    }, 200);
}
