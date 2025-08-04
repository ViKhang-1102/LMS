document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;
    const themeToggle = document.querySelector('.theme-toggle');
    const savedTheme = localStorage.getItem('theme') || 'light';

    html.setAttribute('data-theme', savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }

    const noDueDateCheckbox = document.getElementById('no_due_date');
    const dueDateInput = document.getElementById('due_date');

    if (noDueDateCheckbox && dueDateInput) {
        noDueDateCheckbox.addEventListener('change', function () {
            if (this.checked) {
                dueDateInput.disabled = true;
                dueDateInput.removeAttribute('required');
                dueDateInput.value = '';
            } else {
                dueDateInput.disabled = false;
                dueDateInput.setAttribute('required', 'required');
            }
        });

        if (noDueDateCheckbox.checked) {
            dueDateInput.disabled = true;
            dueDateInput.removeAttribute('required');
        }
    }
});

function loadChartData(chartId, chartType, chartConfig) {
    fetch(`/api/chart_data.php?type=${chartType}`)
        .then(response => {
            if (!response.ok) throw new Error('Failed to fetch chart data');
            return response.json();
        })
        .then(result => {
            if (result.success) {
                const ctx = document.getElementById(chartId).getContext('2d');
                new Chart(ctx, {
                    type: chartConfig.type,
                    data: result.data,
                    options: chartConfig.options
                });
            } else {
                console.error('Error loading chart data:', result.error);
                const container = document.getElementById(chartId).parentElement;
                container.innerHTML += '<div class="alert alert-danger">Failed to load chart data: ' + result.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            const container = document.getElementById(chartId).parentElement;
            container.innerHTML += '<div class="alert alert-danger">Error loading chart data.</div>';
        });
}

if (document.getElementById('submissionChart')) {
    loadChartData('submissionChart', 'submission_status', {
        type: 'bar',
        options: { scales: { y: { beginAtZero: true } } }
    });
}

if (document.getElementById('paymentChart')) {
    loadChartData('paymentChart', 'payment_status', {
        type: 'pie',
        options: {}
    });
}

/*
if (document.getElementById('gradeChart')) {
    loadChartData('gradeChart', 'average_grades', {
        type: 'bar',
        options: { scales: { y: { beginAtZero: true, max: 100 } } }
    });
}
*/

if (document.getElementById('purchaseChart')) {
    loadChartData('purchaseChart', 'top_purchases', {
        type: 'bar',
        options: { scales: { y: { beginAtZero: true } } }
    });
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
            const feedback = input.nextElementSibling || document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = 'This field is required.';
            input.after(feedback);
        } else {
            input.classList.remove('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.remove();
            }
        }
    });

    return isValid;
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form.matches('form')) {
        if (!validateForm(form.id)) {
            event.preventDefault();
            return false;
        }
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('button[type="submit"][formaction*="/controllers/"]');
    if (button && button.form && button.form.querySelector('input[name="action"][value*="delete"]')) {
        if (!confirm('Are you sure you want to delete this item?')) {
            event.preventDefault();
        }
    }
});

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(anchor.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});


