function toggleSVG(checkbox) {
    var svgHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>';
    var container = checkbox.parentNode;
    
    if (checkbox.checked) {
        if (!container.querySelector('svg')) {
            container.insertAdjacentHTML('beforeend', svgHTML);
        }
    } else {
        var svg = container.querySelector('svg');
        if (svg) {
            container.removeChild(svg);
        }
    }
}

function toggleNarrower(button) {
    var container = button.nextElementSibling;
    if (container.style.display === "none") {
        container.style.display = "block";
        button.classList.remove("collapsed");
        button.setAttribute("aria-expanded", "true");
    } else {
        container.style.display = "none";
        button.classList.add("collapsed");
        button.setAttribute("aria-expanded", "false");
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('.nav-tab');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var sectionId = this.getAttribute('href').substring(1);
            
            // Hide all sections
            document.querySelectorAll('.amb-dido-section-content').forEach(function(section) {
                section.style.display = 'none';
            });
            
            // Show the selected section
            document.getElementById(sectionId + '-content').style.display = 'block';
            
            // Update active tab
            tabs.forEach(function(t) {
                t.classList.remove('nav-tab-active');
            });
            this.classList.add('nav-tab-active');
        });
    });
});



/* 
document.addEventListener('DOMContentLoaded', function() {
    const inputField = document.getElementById('components-form-token-input-amb');

    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const keyword = inputField.value.trim();
            inputField.value = '';

            if (keyword) {
                const formData = new FormData();
                formData.append('action', 'add_amb_keyword');
                formData.append('keyword', keyword);
                formData.append('post_id', '<?php echo $post->ID; ?>');
                formData.append('_wpnonce', '<?php echo wp_create_nonce(basename(__FILE__)); ?>');

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log(data.message);
                    } else {
                        console.error('Fehler:', data);
                    }
                })
                .catch(error => console.error('Fehler:', error));
            }
        }
    });
});


jQuery(document).ready(function($) {
    // Rebind AJAX events or other interactions
    $('#tagsdiv-ambkeywords .tagadd').unbind().click(function() {
        // Custom AJAX handler if necessary
    });

    // Or reinitialize existing WordPress scripts
    try {
        if (typeof tagBox !== 'undefined') {
            tagBox.init();
        }
    } catch (e) {
        console.error('Error reinitializing tagBox:', e);
    }
});
*/