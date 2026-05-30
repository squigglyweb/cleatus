/**
 * TLN Summer Camps Renderer
 * Pulls from camp-database.json and renders camp listings
 * 
 * Usage: Include this script and add <div id="tln-camps-output"></div> where you want the list
 */

(function() {
    // Configuration
    const DATA_URL = '/wp-content/uploads/tln/camp-database.json';
    const CATEGORY_ORDER = ['Sports', 'STEM', 'Arts', 'Specialty', 'Day Camp', 'Academic', 'Other'];
    
    let campData = null;

    // Fetch and render
    async function init() {
        try {
            const response = await fetch(DATA_URL);
            campData = await response.json();
            render();
        } catch(e) {
            console.error('TLN Camps: Could not load camp data', e);
            document.getElementById('tln-camps-output').innerHTML = '<p>Loading camps...</p>';
        }
    }

    function render() {
        const container = document.getElementById('tln-camps-output');
        if (!container || !campData) return;

        let html = '';
        
        // Render by category
        CATEGORY_ORDER.forEach(cat => {
            const camps = campData.by_category[cat];
            if (!camps || camps.length === 0) return;
            
            html += `<h2>${cat} Camps</h2>`;
            html += '<div class="tln-camps-grid">';
            
            camps.forEach(camp => {
                const name = camp.camp_name;
                const desc = camp.notes || getDescription(camp);
                const pricing = camp.pricing && camp.pricing !== 'TBD' ? camp.pricing : '';
                const ages = camp.ages && camp.ages !== 'TBD' ? camp.ages : '';
                const website = camp.website || '#';
                
                html += `
                <div class="tln-camp-card">
                    ${camp.logo_image ? `<img src="${camp.logo_image}" alt="${name} logo">` : ''}
                    <h3>${name}</h3>
                    <p class="tln-camp-meta">${ages ? `<span>Ages: ${ages}</span>` : ''} ${pricing ? `<span>${pricing}</span>` : ''}</p>
                    <p>${desc}</p>
                    <a href="${website}" class="tln-btn" target="_blank" rel="noopener">Register</a>
                </div>
                `;
            });
            
            html += '</div>';
        });

        container.innerHTML = html;
    }

    function getDescription(camp) {
        // Generate description based on category
        const descs = {
            'Sports': 'Active camps featuring athletics and physical activities.',
            'STEM': 'Science, technology, engineering, and math programs.',
            'Arts': 'Creative camps including art, music, dance, and theatre.',
            'Specialty': 'Unique camp experiences for specialized interests.',
            'Day Camp': 'Traditional day camp programs.',
            'Academic': 'Academic enrichment and educational camps.'
        };
        return descs[camp.category] || 'Summer camp program.';
    }

    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();