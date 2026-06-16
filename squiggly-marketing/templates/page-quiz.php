<?php /* Template Name: Quiz */ ?>
<?php get_header(); ?>

<section class="quiz-section">
    <div class="container">
        <div class="section-title">
            <h1>Let's See If We're a Good Fit</h1>
            <p>This takes 2 minutes. Be honest - we'll only move forward if this makes sense for both of us.</p>
        </div>
        
        <div class="quiz-container">
            <div class="quiz-progress">
                <div class="quiz-progress-bar" style="width: 20%;"></div>
            </div>
            
            <div class="quiz-question">
                <h3>What is your monthly marketing budget?</h3>
                <div class="quiz-options">
                    <div class="quiz-option" data-value="under200">Under $200</div>
                    <div class="quiz-option" data-value="200-500">$200-500</div>
                    <div class="quiz-option" data-value="500-1000">$500-1000</div>
                    <div class="quiz-option" data-value="1000+">$1000+</div>
                </div>
            </div>
            
            <div class="quiz-nav">
                <div></div>
                <button class="btn btn-primary" id="quiz-next" disabled>Next</button>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var currentQuestion = 0;
    var answers = {};
    var questions = [
        {
            question: 'What is your monthly marketing budget?',
            options: [
                { value: 'under200', label: 'Under $200' },
                { value: '200-500', label: '$200-500' },
                { value: '500-1000', label: '$500-1000' },
                { value: '1000+', label: '$1000+' }
            ]
        },
        {
            question: 'What type of business do you have?',
            options: [
                { value: 'restaurant', label: 'Restaurant / Food service' },
                { value: 'retail', label: 'Retail store' },
                { value: 'salon', label: 'Salon / Spa / Beauty' },
                { value: 'professional', label: 'Professional services (lawyer, dentist, realtor)' },
                { value: 'home', label: 'Home services (plumber, HVAC, landscaping)' },
                { value: 'other', label: 'Other' }
            ]
        },
        {
            question: 'Do you have someone who can answer the phone when new customers call?',
            options: [
                { value: 'yes', label: 'Yes - we answer our own phones' },
                { value: 'sometimes', label: 'Sometimes' },
                { value: 'no', label: 'No - we need help with that too' }
            ]
        },
        {
            question: "What's your goal?",
            options: [
                { value: 'walkin', label: 'Get more walk-in customers' },
                { value: 'leads', label: 'Generate more leads/calls' },
                { value: 'presence', label: 'Build my online presence' },
                { value: 'dontknow', label: "I don't know - that's why I'm asking" }
            ]
        },
        {
            question: 'How soon do you need results?',
            options: [
                { value: 'yesterday', label: 'Yesterday (emergency)' },
                { value: '30days', label: 'Within 30 days' },
                { value: '90days', label: 'Within 90 days' },
                { value: 'long', label: "I'm playing the long game" }
            ]
        }
    ];
    
    function renderQuestion(index) {
        var q = questions[index];
        var container = document.querySelector('.quiz-container');
        var progressBar = document.querySelector('.quiz-progress-bar');
        
        var html = '<div class="quiz-progress"><div class="quiz-progress-bar" style="width: ' + ((index + 1) / questions.length * 100) + '%"></div></div>';
        html += '<div class="quiz-question">';
        html += '<h3>' + q.question + '</h3>';
        html += '<div class="quiz-options">';
        
        q.options.forEach(function(opt) {
            html += '<div class="quiz-option" data-value="' + opt.value + '">' + opt.label + '</div>';
        });
        
        html += '</div></div>';
        html += '<div class="quiz-nav">';
        
        if (index > 0) {
            html += '<button class="btn btn-outline" id="quiz-prev">Back</button>';
        } else {
            html += '<div></div>';
        }
        
        html += '<button class="btn btn-primary" id="quiz-next" disabled>Next</button>';
        html += '</div>';
        
        container.innerHTML = html;
        
        // Option click handlers
        container.querySelectorAll('.quiz-option').forEach(function(opt) {
            opt.addEventListener('click', function() {
                container.querySelectorAll('.quiz-option').forEach(function(o) {
                    o.classList.remove('selected');
                });
                this.classList.add('selected');
                answers[questions[index].question] = this.dataset.value;
                container.querySelector('#quiz-next').disabled = false;
            });
        });
        
        // Navigation handlers
        container.querySelector('#quiz-next').addEventListener('click', function() {
            if (index < questions.length - 1) {
                currentQuestion++;
                renderQuestion(currentQuestion);
            } else {
                showResults();
            }
        });
        
        if (index > 0) {
            container.querySelector('#quiz-prev').addEventListener('click', function() {
                currentQuestion--;
                renderQuestion(currentQuestion);
            });
        }
    }
    
    function showResults() {
        var container = document.querySelector('.quiz-container');
        var budget = answers['What is your monthly marketing budget?'];
        
        var html = '<div class="quiz-progress"><div class="quiz-progress-bar" style="width: 100%"></div></div>';
        html += '<div class="quiz-results">';
        
        if (budget === 'under200' || budget === '200-500') {
            html += '<h3>Thanks for Reaching Out!</h3>';
            html += '<p>For your budget, we\'d recommend our Starter tier. But let\'s still talk - no pressure.</p>';
        } else {
            html += '<h3>Looks Like We\'re a Great Match!</h3>';
            html += '<p>Based on your answers, our Growth or Dominance tier seems right. Let\'s talk.</p>';
        }
        
        html += '<a href="/contact" class="btn btn-primary">Schedule Your Free Strategy Call</a>';
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    // Initialize
    renderQuestion(0);
});
</script>

<?php get_footer(); ?>