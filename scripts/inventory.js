function openEditModal(code, name, description, price, quantity) {
    document.getElementById('original_code').value = code;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_quantity').value = quantity;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function openDeleteModal(code) {
    const confirmDeleteLink = document.getElementById('confirmDelete');
    confirmDeleteLink.href = `inventory.php?delete=${encodeURIComponent(code)}`;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Search functionality
function searchProducts() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(card => {
        const code = card.querySelector('strong').textContent.toLowerCase();
        const name = card.querySelector('h3').textContent.toLowerCase();
        
        if (code.includes(searchInput) || name.includes(searchInput)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Sort functionality
function sortProducts() {
    const sortValue = document.getElementById('sortSelect').value;
    const grid = document.getElementById('productsGrid');
    const cards = Array.from(grid.querySelectorAll('.card'));
    
    cards.sort((a, b) => {
        if (sortValue === 'default') {
            // Default sorting (by code descending, as in the original PHP query)
            const codeA = a.querySelector('strong').textContent.trim().replace('Code:', '').trim();
            const codeB = b.querySelector('strong').textContent.trim().replace('Code:', '').trim();
            return codeB.localeCompare(codeA);
        } else if (sortValue === 'name-asc') {
            const nameA = a.querySelector('h3').textContent.trim();
            const nameB = b.querySelector('h3').textContent.trim();
            return nameA.localeCompare(nameB);
        } else if (sortValue === 'name-desc') {
            const nameA = a.querySelector('h3').textContent.trim();
            const nameB = b.querySelector('h3').textContent.trim();
            return nameB.localeCompare(nameA);
        } else if (sortValue === 'price-asc') {
            const priceA = parseFloat(a.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
            const priceB = parseFloat(b.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
            return priceA - priceB;
        } else if (sortValue === 'price-desc') {
            const priceA = parseFloat(a.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
            const priceB = parseFloat(b.querySelector('.price').textContent.replace('₱', '').replace(',', ''));
            return priceB - priceA;
        } else if (sortValue === 'quantity-asc') {
            const qtyA = parseInt(a.querySelector('p:nth-of-type(2)').textContent.replace('Stock:', '').trim());
            const qtyB = parseInt(b.querySelector('p:nth-of-type(2)').textContent.replace('Stock:', '').trim());
            return qtyA - qtyB;
        } else if (sortValue === 'quantity-desc') {
            const qtyA = parseInt(a.querySelector('p:nth-of-type(2)').textContent.replace('Stock:', '').trim());
            const qtyB = parseInt(b.querySelector('p:nth-of-type(2)').textContent.replace('Stock:', '').trim());
            return qtyB - qtyA;
        }
        return 0;
    });
    
    // Remove all cards and re-append in sorted order
    cards.forEach(card => grid.removeChild(card));
    cards.forEach(card => grid.appendChild(card));
}

// Add event listener for search input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchProducts();
            }
        });
    }
});