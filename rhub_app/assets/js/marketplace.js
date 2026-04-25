// Marketplace JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initCategoryFilter();
    initSearchForm();
    initItemCards();
    initSellForm();
    initContactSeller();
    initMarkSold();
    initDeleteItem();
});

function initCategoryFilter() {
    const categoryBtns = document.querySelectorAll('.category-btn');
    
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.dataset.category;
            filterItems(category);
        });
    });
}

function initSearchForm() {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            searchItems(query);
        });
    }
    
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                searchItems(this.value.trim());
            }, 300);
        });
    }
}

function filterItems(category) {
    const items = document.querySelectorAll('.item-card');
    
    items.forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = '';
            item.classList.add('fade-in');
        } else {
            item.style.display = 'none';
        }
    });
}

function searchItems(query) {
    const items = document.querySelectorAll('.item-card');
    
    items.forEach(item => {
        const title = item.querySelector('.item-title').textContent.toLowerCase();
        const description = item.querySelector('.item-description')?.textContent.toLowerCase() || '';
        
        if (title.includes(query.toLowerCase()) || description.includes(query.toLowerCase()) || query === '') {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function initItemCards() {
    const itemCards = document.querySelectorAll('.item-card');
    
    itemCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.item-actions') && !e.target.closest('button')) {
                const itemId = this.dataset.id;
                window.location.href = `item-detail.php?id=${itemId}`;
            }
        });
    });
}

function initSellForm() {
    const sellForm = document.getElementById('sell-item-form');
    
    if (sellForm) {
        // Image preview
        const imageInput = document.getElementById('item-image');
        const imagePreview = document.getElementById('image-preview');
        
        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                        imagePreview.classList.add('has-image');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        sellForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitItem();
        });
    }
}

function submitItem() {
    const form = document.getElementById('sell-item-form');
    const formData = new FormData(form);
    formData.append('action', 'add_item');
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner"></span> Listing...';
    submitBtn.disabled = true;
    
    fetch('api/marketplace.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                window.location.href = 'my-items.php';
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function initContactSeller() {
    const contactBtns = document.querySelectorAll('.contact-seller-btn');
    
    contactBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const itemId = this.dataset.itemId;
            showContactModal(itemId);
        });
    });
}

function showContactModal(itemId) {
    const modal = document.getElementById('contact-modal');
    if (modal) {
        modal.dataset.itemId = itemId;
        modal.classList.add('active');
        
        const form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                sendBuyRequest(itemId, this);
            });
        }
    }
}

function sendBuyRequest(itemId, form) {
    const message = form.querySelector('textarea').value;
    const formData = new FormData();
    formData.append('action', 'request_to_buy');
    formData.append('item_id', itemId);
    formData.append('message', message);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Sending...';
    
    fetch('api/marketplace.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal();
            setTimeout(() => {
                window.location.href = `messages.php?conversation=${data.conversation_id}`;
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Send Message';
    });
}

function initMarkSold() {
    const markSoldBtns = document.querySelectorAll('.mark-sold-btn');
    
    markSoldBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const itemId = this.dataset.itemId;
            
            if (confirm('Mark this item as sold? It will be removed from the marketplace.')) {
                markItemSold(itemId, this);
            }
        });
    });
}

function markItemSold(itemId, btn) {
    const formData = new FormData();
    formData.append('action', 'mark_sold');
    formData.append('item_id', itemId);
    
    btn.disabled = true;
    
    fetch('api/marketplace.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            const card = btn.closest('.item-card');
            if (card) {
                card.classList.add('fade-out');
                setTimeout(() => card.remove(), 300);
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

function initDeleteItem() {
    const deleteBtns = document.querySelectorAll('.delete-item-btn');
    
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const itemId = this.dataset.itemId;
            
            if (confirm('Are you sure you want to delete this item?')) {
                deleteItem(itemId, this);
            }
        });
    });
}

function deleteItem(itemId, btn) {
    const formData = new FormData();
    formData.append('action', 'delete_item');
    formData.append('item_id', itemId);
    
    btn.disabled = true;
    
    fetch('api/marketplace.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            const card = btn.closest('.item-card');
            if (card) {
                card.classList.add('fade-out');
                setTimeout(() => card.remove(), 300);
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

function closeModal() {
    const modals = document.querySelectorAll('.modal.active');
    modals.forEach(modal => modal.classList.remove('active'));
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

// Close modals on backdrop click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
