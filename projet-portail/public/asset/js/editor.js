document.addEventListener('DOMContentLoaded', () => {
    tinymce.init({
        selector: '.js-editor',
        plugins: 'lists link code table',
        toolbar: 'bold italic underline | bullist numlist | link | code',
        height: 300
    });
});