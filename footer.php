<?php
// footer.php - NO SPACES BEFORE THIS LINE
?>
        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Daffodil Software Limited, Dhaka, Bangladesh. All Rights Reserved.</p>
            <p class="text-muted">Restaurant POS System v2.0</p>
        </div>
    </div>
</div>

<!-- Access Denied Modal -->
<div class="modal fade modal-access-denied" id="accessDeniedModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="modal-icon">
                    <i class="fas fa-lock fa-4x text-danger mb-3"></i>
                </div>
                <h4 class="text-danger">Access Denied!</h4>
                <p class="mt-3">You do not have permission to access this page.</p>
                <p class="text-muted">Please contact the system administrator.</p>
                <button class="btn btn-danger mt-3" onclick="closeAccessDenied()">
                    <i class="fas fa-times-circle"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle Sidebar Collapse
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed');
    
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('sidebarCollapsed', 'true');
    } else {
        localStorage.setItem('sidebarCollapsed', 'false');
    }
}

// Toggle Section (Closable Menu Sections)
function toggleSection(element) {
    if (!element) return;
    element.classList.toggle('collapsed');
    const content = element.nextElementSibling;
    if (content) {
        content.classList.toggle('collapsed');
        // For smooth animation, set max-height
        if (!content.classList.contains('collapsed')) {
            content.style.maxHeight = content.scrollHeight + 'px';
        } else {
            content.style.maxHeight = '0';
        }
    }
    
    // Save section states to localStorage
    const sections = document.querySelectorAll('.nav-section');
    const states = {};
    sections.forEach((section, index) => {
        states['section_' + index] = section.classList.contains('collapsed');
    });
    localStorage.setItem('sectionStates', JSON.stringify(states));
}

// Load saved sidebar and section states
document.addEventListener('DOMContentLoaded', function() {
    // Load sidebar collapse state
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
    const sidebar = document.getElementById('sidebar');
    if (sidebarCollapsed === 'true' && sidebar) {
        sidebar.classList.add('collapsed');
    }
    
    // Load section collapse states
    const savedStates = localStorage.getItem('sectionStates');
    if (savedStates) {
        const states = JSON.parse(savedStates);
        const sections = document.querySelectorAll('.nav-section');
        sections.forEach((section, index) => {
            if (states['section_' + index]) {
                section.classList.add('collapsed');
                const content = section.nextElementSibling;
                if (content) {
                    content.classList.add('collapsed');
                    content.style.maxHeight = '0';
                }
            } else {
                const content = section.nextElementSibling;
                if (content) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            }
        });
    }
    
    // Initialize all section contents with max-height for transition
    const contents = document.querySelectorAll('.nav-section-content');
    contents.forEach(content => {
        if (!content.classList.contains('collapsed')) {
            content.style.maxHeight = content.scrollHeight + 'px';
        } else {
            content.style.maxHeight = '0';
        }
    });
});

// Update max-height when window resizes
window.addEventListener('resize', function() {
    const contents = document.querySelectorAll('.nav-section-content');
    contents.forEach(content => {
        if (!content.classList.contains('collapsed')) {
            content.style.maxHeight = content.scrollHeight + 'px';
        }
    });
});

// Access Denied Modal Functions
function showAccessDenied() {
    const modalEl = document.getElementById('accessDeniedModal');
    if (modalEl) {
        var myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    }
}

function closeAccessDenied() {
    const modalEl = document.getElementById('accessDeniedModal');
    if (modalEl) {
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
    window.location.href = 'index.php';
}

// Check if we need to show access denied for non-admin on settings pages
<?php
// This will run on settings pages for non-admin users
if (isset($access_denied) && $access_denied === true):
?>
showAccessDenied();
<?php endif; ?>
</script>
</body>
</html>