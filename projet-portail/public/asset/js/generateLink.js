document.addEventListener('DOMContentLoaded', () => {
    const generateBtn = document.querySelector('button[onclick="generateLink()"]');
    if (!generateBtn) return;

    generateBtn.addEventListener('click', () => {
        const customerNameInput = document.getElementById('customer_name');
        const linkInput = document.getElementById('generated_link');
        const hiddenCharsInput = document.getElementById('four_chars');
        const container = document.getElementById('generatedLinkContainer');

        const customerName = customerNameInput.value.trim().toLowerCase().replace(/\s+/g, '-');
        const random = Math.random().toString(36).substring(2, 6).toUpperCase();
        const baseUrl = window.location.origin + '/link/';
        const fullUrl = baseUrl + customerName + '-' + random;

        linkInput.value = fullUrl;
        hiddenCharsInput.value = random;
        container.style.display = 'block';
    });
});