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

<script>
// Toggle Sidebar Collapse
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    
    // Save state to localStorage
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('sidebarCollapsed', 'true');
    } else {
        localStorage.setItem('sidebarCollapsed', 'false');
    }
}

// Toggle Section (Closable Menu Sections)
function toggleSection(element) {
    element.classList.toggle('collapsed');
    const content = element.nextElementSibling;
    content.classList.toggle('collapsed');
    
    // Save section states to localStorage
    const sections = document.querySelectorAll('.nav-section');
    const states = {};
    sections.forEach((section, index) => {
        states['section_' + index] = section.classList.contains('collapsed');
    });
    localStorage.setItem('sectionStates', JSON.stringify(states));
}

// Load saved sidebar state
document.addEventListener('DOMContentLoaded', function() {
    // Load sidebar collapse state
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
    if (sidebarCollapsed === 'true') {
        document.getElementById('sidebar').classList.add('collapsed');
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
                if (content) content.classList.add('collapsed');
            }
        });
    }
    
    // Initialize all section contents with max-height for transition
    const contents = document.querySelectorAll('.nav-section-content');
    contents.forEach(content => {
        content.style.maxHeight = content.scrollHeight + 'px';
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>