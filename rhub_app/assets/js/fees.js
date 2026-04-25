// Fee Payment Portal JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initPaymentMethods();
    initPaymentForm();
    initQuickAmounts();
});

function initPaymentMethods() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove active class from all
            paymentMethods.forEach(m => m.classList.remove('active'));
            // Add active class to clicked
            this.classList.add('active');
            
            // Update hidden input
            const methodInput = document.getElementById('payment_method');
            if (methodInput) {
                methodInput.value = this.dataset.method;
            }
            
            // Update phone placeholder based on method
            const phoneInput = document.getElementById('phone_number');
            if (phoneInput) {
                if (this.dataset.method === 'mtn') {
                    phoneInput.placeholder = 'e.g., 670123456';
                } else {
                    phoneInput.placeholder = 'e.g., 690123456';
                }
            }
        });
    });
}

function initPaymentForm() {
    const paymentForm = document.getElementById('payment-form');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            processPayment();
        });
    }
}

function initQuickAmounts() {
    const quickAmountBtns = document.querySelectorAll('.quick-amount');
    
    quickAmountBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const amount = this.dataset.amount;
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.value = amount;
            }
            
            // Update active state
            quickAmountBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

function processPayment() {
    const form = document.getElementById('payment-form');
    const formData = new FormData(form);
    
    // Validate
    const amount = formData.get('amount');
    const paymentMethod = formData.get('payment_method');
    const phoneNumber = formData.get('phone_number');
    
    if (!amount || parseFloat(amount) <= 0) {
        showNotification('Please enter a valid amount', 'error');
        return;
    }
    
    if (!paymentMethod) {
        showNotification('Please select a payment method', 'error');
        return;
    }
    
    if (!phoneNumber || !validatePhone(phoneNumber)) {
        showNotification('Please enter a valid phone number (9 digits starting with 6)', 'error');
        return;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
    submitBtn.disabled = true;
    
    // Show payment modal
    showPaymentModal(paymentMethod, amount, phoneNumber);
    
    // Process payment
    fetch('api/process-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hidePaymentModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            showPaymentSuccess(data);
            
            // Update UI
            setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hidePaymentModal();
        showNotification('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function validatePhone(phone) {
    return /^6[0-9]{8}$/.test(phone);
}

function showPaymentModal(method, amount, phone) {
    const modal = document.getElementById('payment-modal');
    if (modal) {
        const methodName = method === 'mtn' ? 'MTN Mobile Money' : 'Orange Money';
        modal.querySelector('.payment-method-name').textContent = methodName;
        modal.querySelector('.payment-amount').textContent = formatCurrency(amount);
        modal.querySelector('.payment-phone').textContent = phone;
        modal.classList.add('active');
    }
}

function hidePaymentModal() {
    const modal = document.getElementById('payment-modal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function showPaymentSuccess(data) {
    const successModal = document.getElementById('success-modal');
    if (successModal) {
        successModal.querySelector('.transaction-ref').textContent = data.transaction_ref;
        successModal.querySelector('.amount-paid').textContent = formatCurrency(data.amount_paid);
        successModal.querySelector('.new-balance').textContent = formatCurrency(data.new_balance);
        successModal.classList.add('active');
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-CM', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0
    }).format(amount);
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
