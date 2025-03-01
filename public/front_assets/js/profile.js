document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('app_update_profile').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal (assuming you're using Bootstrap)
                const modal = bootstrap.Modal.getInstance(document.querySelector('.modal'));
                if (modal) {
                    modal.hide();
                }
                
                // Refresh page to show updated data
                location.reload();
            } else {
                // Show errors in the modal
                Object.keys(data.errors).forEach(field => {
                    const errorElement = document.getElementById(`${field}-error`);
                    if (errorElement) {
                        errorElement.textContent = data.errors[field];
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
});