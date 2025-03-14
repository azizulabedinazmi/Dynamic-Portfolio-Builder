// Add Skill/Entry functionality
document.addEventListener('click', (e) => {
    // Add Skill
    if (e.target && e.target.id === 'add-skill') {
        const container = document.getElementById('skills-container');
        const template = container.querySelector('.skill-entry');
        const clone = template.cloneNode(true);
        clone.querySelectorAll('input').forEach(input => input.value = '');
        container.appendChild(clone);
    }
    
    // Add Academic Entry
    if (e.target && e.target.id === 'add-academic') {
        const container = document.getElementById('academic-container');
        const template = container.querySelector('.academic-entry');
        const clone = template.cloneNode(true);
        clone.querySelectorAll('input').forEach(input => input.value = '');
        container.appendChild(clone);
    }
    
    // Add Work Entry
    if (e.target && e.target.id === 'add-work') {
        const container = document.getElementById('work-container');
        const template = container.querySelector('.work-entry');
        const clone = template.cloneNode(true);
        clone.querySelectorAll('input').forEach(input => input.value = '');
        container.appendChild(clone);
    }
    
    // Add Project Entry
    if (e.target && e.target.id === 'add-project') {
        const container = document.getElementById('project-container');
        const template = container.querySelector('.project-entry');
        const clone = template.cloneNode(true);
        clone.querySelectorAll('input, textarea').forEach(field => field.value = '');
        container.appendChild(clone);
    }
    
    // Remove Entry
    if (e.target && e.target.classList.contains('remove-btn')) {
        const entry = e.target.closest('.skill-entry, .academic-entry, .work-entry, .project-entry');
        if (entry && entry.parentElement.children.length > 1) {
            entry.remove();
        }
    }
});

// Font Selection Handler
document.addEventListener('change', (e) => {
    if (e.target && e.target.classList.contains('font-selector')) {
        const section = e.target.dataset.section;
        const font = e.target.value;
        
        // Apply font to all elements in section
        const sectionElement = document.querySelector(`[data-section="${section}"]`);
        if (sectionElement) {
            sectionElement.querySelectorAll('input, textarea, select, label').forEach(el => {
                el.style.fontFamily = font;
            });
            
            // Store selected font for new entries
            sectionElement.dataset.currentFont = font;
        }
    }
});