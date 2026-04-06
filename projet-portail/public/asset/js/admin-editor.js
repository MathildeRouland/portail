import ClassicEditor from 'ckeditor5';

document.addEventListener('DOMContentLoaded', () => {
    const editors = document.querySelectorAll('.js-ckeditor');

    editors.forEach((el) => {
        ClassicEditor.create(el).catch(error => {
            console.error(error);
        });
    });
});