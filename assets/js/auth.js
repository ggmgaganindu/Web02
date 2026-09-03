// Interactive sliding authentication toggle

document.addEventListener('DOMContentLoaded', () => {
    const signUpButton = document.getElementById('signUp');
    const signInButton = document.getElementById('signIn');
    const container = document.getElementById('auth-container');

    if (signUpButton && signInButton && container) {
        signUpButton.addEventListener('click', () => {
            container.classList.add('active');
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove('active');
        });
    }
});
