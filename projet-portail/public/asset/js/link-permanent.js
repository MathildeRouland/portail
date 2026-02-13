(function(){
    // Toggle start/end date fields when 'permanent' checkbox is checked.
    function findFormGroup(el) {
        if (!el) return null;
        return el.closest('.form-group');
    }

    function initToggle(container) {
        const permCheckbox = container.querySelector('[name$="[permanent]"]');
        const startInput = container.querySelector('[name$="[startDate]"]');
        const endInput = container.querySelector('[name$="[endDate]"]');

        function toggleDateFields() {
            const disabled = permCheckbox && permCheckbox.checked;
            if (startInput) {
                startInput.disabled = disabled;
                const group = findFormGroup(startInput);
                if (group) group.classList.toggle('disabled-field', disabled);
            }
            if (endInput) {
                endInput.disabled = disabled;
                const group = findFormGroup(endInput);
                if (group) group.classList.toggle('disabled-field', disabled);
            }
        }

        if (permCheckbox) {
            permCheckbox.addEventListener('change', toggleDateFields);
            // init
            toggleDateFields();
        }
    }

    document.addEventListener('DOMContentLoaded', function(){
        // Initialize for the whole document; if forms are inside modals or loaded dynamically
        // you can call initToggle(element) on a specific container.
        initToggle(document);
    });
})();
